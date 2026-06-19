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

// ── Crop baseline yields (kg/ha) ─────────────────────────────────────────────
// Based on historical India/Rajasthan oilseed data
$crop_baselines = [
    'soybean'                => 1050,
    'groundnut'              => 1320,
    'mustard'                => 1180,
    'rapeseed'               => 1180,
    'rapeseed and mustard'   => 1180,
    'rapeseed & mustard'     => 1180,
    'sesamum'                =>  450,
    'sesame'                 =>  450,
    'total oilseed'          => 1100,
    'total oilseeds'         => 1100,
    'oilseed'                => 1100,
    'oilseeds'               => 1100,
    'sunflower'              => 1050,
    'linseed'                =>  700,
    'castor'                 => 1450,
    'safflower'              =>  650,
];

// ── Geography adjustment factors ─────────────────────────────────────────────
$geo_factor = [
    'india'       => 1.00,
    'rajasthan'   => 0.92,
    'gujarat'     => 1.08,
    'madhya pradesh' => 1.05,
    'maharashtra' => 0.98,
    'karnataka'   => 0.95,
];

// ── Prediction logic ─────────────────────────────────────────────────────────
function predict_yield($crop, $geography, $year, $area_mha, $prod_mt, $baselines, $geo_factors) {

    // Get baseline
    $base = $baselines[$crop] ?? 1000;

    // Get geo factor (partial match if exact not found)
    $geo = 1.0;
    foreach ($geo_factors as $key => $factor) {
        if (strpos($geography, $key) !== false || strpos($key, $geography) !== false) {
            $geo = $factor;
            break;
        }
    }

    // Compute actual yield from input data
    if ($area_mha > 0 && $prod_mt > 0) {
        // yield kg/ha = (production MT * 1,000,000 kg) / (area Mha * 1,000,000 ha)
        $actual_yield = ($prod_mt * 1000000) / ($area_mha * 1000000);
        // Blend: 60% from actual data + 40% historical baseline
        $predicted = 0.60 * $actual_yield + 0.40 * ($base * $geo);
    } else {
        $predicted = $base * $geo;
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

// Crop display name
$crop_display = ucwords($data['crop'] ?? $crop);
$geo_display  = ucwords($data['geography'] ?? $geography);

echo json_encode([
    'success'                          => true,
    'predicted_yield_kg_ha'            => $predicted_yield,
    'expected_production_million_tonne'=> $expected_prod,
    'model_name'                       => 'PHP Baseline Predictor',
    'geography'                        => $geo_display,
    'crop'                             => $crop_display,
    'year'                             => $year,
    'area_mha'                         => $area_mha,
    'note'                             => 'Prediction based on historical crop yield baselines + input data blend'
]);
