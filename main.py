from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional
import joblib
import numpy as np
import os

app = FastAPI(
    title="SMCS Crop Yield Prediction API",
    description="ML-based crop yield prediction for SKNAU Jobner - SMCS Dashboard",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Load model at startup
model = None
model_name = "Rule-Based Fallback"

@app.on_event("startup")
def load_model():
    global model, model_name
    try:
        model = joblib.load("best_model.pkl")
        model_name = "Random Forest"
        print("[OK] Model loaded: best_model.pkl")
    except Exception as e:
        print(f"[WARN] Model file not found, using fallback predictor: {e}")
        model = None

class PredictRequest(BaseModel):
    geography: str          # e.g. "India", "Rajasthan"
    crop: str               # e.g. "Soybean", "Mustard", "Groundnut"
    year: int               # e.g. 2023
    area_mha: float         # Area in million hectares
    production_million_tonne: float  # Production in million tonnes

class PredictResponse(BaseModel):
    success: bool
    predicted_yield_kg_ha: float
    expected_production_million_tonne: float
    model_name: str
    geography: str
    crop: str
    year: int

# Crop-specific baseline yield (kg/ha) based on historical India/Rajasthan data
CROP_BASELINES = {
    "soybean":          1050,
    "groundnut":        1320,
    "mustard":          1180,
    "rapeseed":         1180,
    "rapeseed and mustard": 1180,
    "sesamum":           450,
    "sesame":            450,
    "total oilseed":    1100,
    "oilseed":          1100,
    "sunflower":        1050,
    "linseed":           700,
}

GEO_FACTOR = {
    "rajasthan": 0.92,
    "india":     1.00,
}

def fallback_predict(req: PredictRequest) -> float:
    """Rule-based prediction using historical crop yield baselines."""
    crop_key = req.crop.lower().strip()
    geo_key  = req.geography.lower().strip()

    base = CROP_BASELINES.get(crop_key, 1000)
    geo  = GEO_FACTOR.get(geo_key, 1.0)

    # Derive actual yield from provided data if non-zero area
    if req.area_mha > 0:
        actual_yield = (req.production_million_tonne * 1_000_000) / (req.area_mha * 1_000_000)
        # Blend: 60% actual + 40% historical baseline
        predicted = 0.6 * actual_yield + 0.4 * (base * geo)
    else:
        predicted = base * geo

    # Year trend: small +0.5% improvement per year after 2015
    year_boost = max(0, req.year - 2015) * 0.005
    predicted = predicted * (1 + year_boost)

    return round(predicted, 2)

@app.get("/")
def root():
    return {
        "status": "ok",
        "service": "SMCS Crop Yield Prediction API",
        "university": "SKNAU Jobner",
        "version": "1.0.0",
        "docs": "/docs"
    }

@app.get("/health")
def health():
    return {
        "status": "healthy",
        "model_loaded": model is not None,
        "model_name": model_name
    }

@app.post("/predict", response_model=PredictResponse)
def predict(req: PredictRequest):
    try:
        if model is not None:
            # Use trained ML model
            features = np.array([[req.year, req.area_mha, req.production_million_tonne]])
            predicted_yield = float(model.predict(features)[0])
            used_model = model_name
        else:
            # Use rule-based fallback
            predicted_yield = fallback_predict(req)
            used_model = "Baseline Predictor"

        predicted_yield = max(100, round(predicted_yield, 2))
        expected_prod   = round((req.area_mha * predicted_yield) / 1000, 4)

        return PredictResponse(
            success=True,
            predicted_yield_kg_ha=predicted_yield,
            expected_production_million_tonne=expected_prod,
            model_name=used_model,
            geography=req.geography,
            crop=req.crop,
            year=req.year
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Prediction failed: {str(e)}")
