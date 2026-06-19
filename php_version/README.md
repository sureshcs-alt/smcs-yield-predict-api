# PHP Version — Hostinger Compatible

This folder contains the **pure PHP** version of the SMCS crop yield predictor.
No Python, no external API, no Render. Runs directly on Hostinger shared hosting.

## Files

| File | Purpose |
|---|---|
| `api_predict.php` | Main prediction endpoint |
| `test_predict.php` | Self-test (delete after use) |

## Upload Instructions

1. Upload `api_predict.php` to `/api/api_predict.php` on Hostinger
2. Upload `test_predict.php` to `/api/test_predict.php` on Hostinger
3. Visit: `https://smcs.sknau.ac.in/api/test_predict.php`
4. All 5 tests should show ✅ PASS
5. **Delete** `test_predict.php` after verification

## Update config.php

Comment out or remove `ML_API_URL` — it's no longer needed.
The prediction now happens inside `api_predict.php` directly.

## Supported Crops
- Soybean, Groundnut, Mustard, Rapeseed & Mustard
- Sesamum, Total Oilseed, Sunflower, Linseed, Castor, Safflower

## Supported Geographies
- India, Rajasthan, Gujarat, Madhya Pradesh, Maharashtra, Karnataka
