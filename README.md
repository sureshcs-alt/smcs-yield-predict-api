# SMCS Crop Yield Prediction API

**SKNAU Jobner — Soil & Crop Management System (SMCS)**  
FastAPI-based ML service for oilseed crop yield prediction (India & Rajasthan).

---

## Live API

After Render deployment, your API base URL will be:
```
https://smcs-yield-predict-api.onrender.com
```

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/` | Service info |
| GET | `/health` | Health check |
| POST | `/predict` | Yield prediction |
| GET | `/docs` | Swagger UI |

---

## Prediction Request

```bash
curl -X POST https://smcs-yield-predict-api.onrender.com/predict \
  -H "Content-Type: application/json" \
  -d '{
    "geography": "India",
    "crop": "Soybean",
    "year": 2024,
    "area_mha": 12.5,
    "production_million_tonne": 13.8
  }'
```

### Response
```json
{
  "success": true,
  "predicted_yield_kg_ha": 1108.5,
  "expected_production_million_tonne": 13.86,
  "model_name": "Baseline Predictor",
  "geography": "India",
  "crop": "Soybean",
  "year": 2024
}
```

---

## Deploy on Render

1. Fork or clone this repo to your GitHub
2. Go to [render.com](https://render.com) → New Web Service
3. Connect this GitHub repo
4. Use these settings:
   - **Build Command:** `pip install -r requirements.txt`
   - **Start Command:** `uvicorn main:app --host 0.0.0.0 --port $PORT`
   - **Python Version:** 3.11
5. Click Deploy
6. Copy the live URL
7. Set in SMCS `config.php`:
   ```php
   define('ML_API_URL', 'https://smcs-yield-predict-api.onrender.com/predict');
   ```

---

## Train with Your Data (Optional)

```bash
# 1. Place your CSV in data/
# Required columns: geography, crop, year, area_mha, production_million_tonne, yield_kg_ha

# 2. Install dependencies
pip install -r requirements.txt

# 3. Train & save model
python train_model.py

# 4. Commit best_model.pkl to GitHub → Render auto-redeploys
```

---

## Supported Crops

- Soybean
- Groundnut
- Mustard / Rapeseed & Mustard
- Sesamum / Sesame
- Total Oilseed
- Sunflower
- Linseed

## Supported Geographies

- India
- Rajasthan

---

*Developed for SMCS Dashboard — SKNAU Jobner, Rajasthan*
