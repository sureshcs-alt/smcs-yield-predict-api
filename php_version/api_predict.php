<?php
/**
 * api_predict.php
 * SMCS Crop Yield Prediction — Pure PHP (No External API)
 * Runs directly on Hostinger shared hosting.
 * Upload to: /api/api_predict.php on smcs.sknau.ac.in
 *
 * SKNAU Jobner — Soil & Crop Management System
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST request required']);
    exit;
}

// ── Read input ────────────────────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) $data = $_POST;

$geography  = strtolower(trim($data['geography']  ?? ''));
$crop       = strtolower(trim($data['crop']       ?? ''));
$year       = intval($data['year']                ?? date('Y'));
$area_mha   = floatval($data['area_mha']          ?? 0);
$prod_mt    = floatval($data['production_million_tonne'] ?? 0);

if (!$geography || !$crop || !$year) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: geography, crop, year'
    ]);
    exit;
}

// ── Historical average yields (kg/ha) ─────────────────────────────────────────
// Source: DACFW India oilseed statistics (2015–2024 average)
$crop_baselines = [
    'soybean'                => 1048,
    'groundnut'              => 1318,
    'mustard'                =>  1182,
    'rapeseed'               =>  1182,
    'rapeseed and mustard'   =>  1182,
    'rapeseed & mustard'     =>  1182,
    'sesamum'                =>   447,
    'sesame'                 =>   447,
    'total oilseed'          =>  1098,
    'total oilseeds'         =>  1098,
    'oilseed'                =>  1098,
    'oilseeds'               =>  1098,
    'sunflower'              =>   862,
    'linseed'                =>   704,
    'castor'                 =>  1452,
    'safflower'              =>   650,
];

// ── Geography adjustment factors ─────────────────────────────────────────────
$geo_factor = [
    'india'              => 1.00,
    'rajasthan'          => 0.92,
    'gujarat'            => 1.08,
    'madhya pradesh'     => 1.05,
    'maharashtra'        => 0.98,
    'karnataka'          => 0.95,
    'andhra pradesh'     => 1.02,
    'telangana'          => 1.02,
    'uttar pradesh'      => 0.97,
    'haryana'            => 1.10,
    'punjab'             => 1.12,
];

// ── Prediction function ───────────────────────────────────────────────────────
function predict_yield($crop, $geography, $year, $area_mha, $prod_mt, $baselines, $geo_factors) {

    // Get historical baseline for this crop
    $base = $baselines[$crop] ?? 1000;

    // Get geography factor
    $geo = 1.0;
    foreach ($geo_factors as $key => $factor) {
        if ($key === $geography || strpos($geography, $key) !== false) {
            $geo = $factor;
            break;
        }
    }

    $historical_yield = $base * $geo;

    if ($area_mha > 0 && $prod_mt > 0) {
        // Actual yield from provided data (kg/ha)
        // prod_mt (million tonnes) → kg: * 1,000,000,000
        // area_mha (million ha)   → ha: * 1,000,000
        $actual_yield = ($prod_mt * 1000) / $area_mha;  // MT/Mha = t/ha * 1000 = kg/ha

        // If actual_yield looks reasonable (within 10x of baseline), blend it
        if ($actual_yield > 50 && $actual_yield < ($historical_yield * 5)) {
            // 40% actual data + 60% historical baseline (more weight to baseline for stability)
            $predicted = 0.40 * $actual_yield + 0.60 * $historical_yield;
        } else {
            // Outlier data — trust historical baseline fully
            $predicted = $historical_yield;
        }
    } else {
        $predicted = $historical_yield;
    }

    // Year trend: +0.5% per year improvement after 2015
    $year_boost = max(0, ($year - 2015)) * 0.005;
    $predicted  = $predicted * (1 + $year_boost);

    return max(100, round($predicted, 2));
}

$predicted_yield = predict_yield(
    $crop, $geography, $year,
    $area_mha, $prod_mt,
    $crop_baselines, $geo_factor
);

// Expected production from predicted yield
$expected_prod = 0;
if ($area_mha > 0) {
    $expected_prod = round(($area_mha * $predicted_yield) / 1000, 4);
}

$crop_display = ucwords($data['crop'] ?? $crop);
$geo_display  = ucwords($data['geography'] ?? $geography);

echo json_encode([
    'success'                           => true,
    'predicted_yield_kg_ha'             => $predicted_yield,
    'expected_production_million_tonne' => $expected_prod,
    'model_name'                        => 'PHP Baseline Predictor v2',
    'geography'                         => $geo_display,
    'crop'                              => $crop_display,
    'year'                              => $year,
    'area_mha'                          => $area_mha,
    'note'                              => 'Blend of input data yield + historical baseline (DACFW averages)'
]);
