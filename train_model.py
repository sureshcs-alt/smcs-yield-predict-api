"""
train_model.py
Train and save the best crop yield prediction model.
Run this locally: python train_model.py
Requires: data/oilseeds_yield.csv
"""
import pandas as pd
import numpy as np
import joblib
import os
from sklearn.ensemble import RandomForestRegressor, ExtraTreesRegressor, GradientBoostingRegressor
from sklearn.linear_model import LinearRegression
from sklearn.model_selection import train_test_split, cross_val_score
from sklearn.metrics import mean_squared_error, mean_absolute_error, r2_score
from sklearn.preprocessing import LabelEncoder
from sklearn.pipeline import Pipeline
from sklearn.compose import ColumnTransformer
from sklearn.preprocessing import StandardScaler, OneHotEncoder

# ── 1. Load data ────────────────────────────────────────────────────────────────
DATA_PATH = "data/oilseeds_yield.csv"

if not os.path.exists(DATA_PATH):
    print(f"[ERROR] Data file not found: {DATA_PATH}")
    print("Create data/oilseeds_yield.csv with columns:")
    print("  geography, crop, year, area_mha, production_million_tonne, yield_kg_ha")
    exit(1)

df = pd.read_csv(DATA_PATH)
print(f"[OK] Loaded {len(df)} rows from {DATA_PATH}")
print(df.head())

# ── 2. Features & target ────────────────────────────────────────────────────────
FEATURES_NUM  = ["year", "area_mha", "production_million_tonne"]
FEATURES_CAT  = ["geography", "crop"]
TARGET        = "yield_kg_ha"

df = df.dropna(subset=FEATURES_NUM + FEATURES_CAT + [TARGET])
X  = df[FEATURES_NUM + FEATURES_CAT]
y  = df[TARGET].astype(float)

X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)
print(f"[OK] Train: {len(X_train)} | Test: {len(X_test)}")

# ── 3. Preprocessing pipeline ───────────────────────────────────────────────────
preprocessor = ColumnTransformer([
    ("num",  StandardScaler(),                               FEATURES_NUM),
    ("cat",  OneHotEncoder(handle_unknown="ignore"),         FEATURES_CAT),
])

# ── 4. Models to compare ────────────────────────────────────────────────────────
MODELS = {
    "Linear Regression":      LinearRegression(),
    "Random Forest":          RandomForestRegressor(n_estimators=200, random_state=42),
    "Extra Trees":            ExtraTreesRegressor(n_estimators=200, random_state=42),
    "Gradient Boosting":      GradientBoostingRegressor(n_estimators=200, random_state=42),
}

results = {}
for name, mdl in MODELS.items():
    pipe = Pipeline([("prep", preprocessor), ("model", mdl)])
    pipe.fit(X_train, y_train)
    preds   = pipe.predict(X_test)
    rmse    = np.sqrt(mean_squared_error(y_test, preds))
    mae     = mean_absolute_error(y_test, preds)
    r2      = r2_score(y_test, preds)
    results[name] = {"pipe": pipe, "rmse": rmse, "mae": mae, "r2": r2}
    print(f"  {name:25s} → RMSE: {rmse:.1f}  MAE: {mae:.1f}  R2: {r2:.4f}")

# ── 5. Save best model ──────────────────────────────────────────────────────────
best_name = max(results, key=lambda k: results[k]["r2"])
best_pipe  = results[best_name]["pipe"]

joblib.dump(best_pipe, "best_model.pkl")
print(f"\n[SAVED] best_model.pkl → {best_name}")
print(f"        RMSE: {results[best_name]['rmse']:.2f}")
print(f"        MAE:  {results[best_name]['mae']:.2f}")
print(f"        R2:   {results[best_name]['r2']:.4f}")
