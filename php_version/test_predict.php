<?php
/**
 * test_predict.php — SMCS Yield API Self-Test
 * Upload to /api/test_predict.php, run once, then DELETE.
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>SMCS ML Predict — Self Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .card { background: white; border-radius: 8px; padding: 20px; margin: 10px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .pass { color: #16a34a; font-weight: bold; font-size: 18px; }
        .fail { color: #dc2626; font-weight: bold; font-size: 18px; }
        pre  { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 12px; }
        h2   { color: #1e3a5f; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #f0f4f8; }
    </style>
</head>
<body>
<h2>🌾 SMCS Crop Yield Prediction — Self Test v2</h2>
<p><b>Server:</b> <?= $_SERVER['SERVER_NAME'] ?> &nbsp;|&nbsp; <b>PHP:</b> <?= PHP_VERSION ?> &nbsp;|&nbsp; <b>Time:</b> <?= date('d M Y H:i:s') ?></p>

<?php
// Tests with realistic expected ranges (min_yield, max_yield)
$tests = [
    [
        'name'    => 'India — Soybean 2024',
        'input'   => ['geography'=>'India',     'crop'=>'Soybean',         'year'=>2024, 'area_mha'=>12.5,  'production_million_tonne'=>13.8],
        'min'     => 900,   'max' => 1300,
        'note'    => 'India soybean avg ~1000-1100 kg/ha'
    ],
    [
        'name'    => 'Rajasthan — Mustard 2023',
        'input'   => ['geography'=>'Rajasthan', 'crop'=>'Mustard',         'year'=>2023, 'area_mha'=>3.2,   'production_million_tonne'=>3.8],
        'min'     => 900,   'max' => 1300,
        'note'    => 'Rajasthan mustard avg ~1100 kg/ha'
    ],
    [
        'name'    => 'India — Groundnut 2022',
        'input'   => ['geography'=>'India',     'crop'=>'Groundnut',       'year'=>2022, 'area_mha'=>4.8,   'production_million_tonne'=>6.2],
        'min'     => 1100,  'max' => 1600,
        'note'    => 'India groundnut avg ~1300 kg/ha'
    ],
    [
        'name'    => 'India — Sesamum 2023',
        'input'   => ['geography'=>'India',     'crop'=>'Sesamum',         'year'=>2023, 'area_mha'=>1.2,   'production_million_tonne'=>0.8],
        'min'     => 300,   'max' => 700,
        'note'    => 'India sesamum avg ~450 kg/ha'
    ],
    [
        'name'    => 'India — Total Oilseed 2025',
        'input'   => ['geography'=>'India',     'crop'=>'Total Oilseed',   'year'=>2025, 'area_mha'=>30.0,  'production_million_tonne'=>38.5],
        'min'     => 1000,  'max' => 1400,
        'note'    => 'India total oilseed avg ~1100 kg/ha'
    ],
    [
        'name'    => 'India — Rapeseed & Mustard 2024',
        'input'   => ['geography'=>'India',     'crop'=>'Rapeseed and Mustard', 'year'=>2024, 'area_mha'=>9.0, 'production_million_tonne'=>12.0],
        'min'     => 1100,  'max' => 1500,
        'note'    => 'India R&M avg ~1200 kg/ha'
    ],
];

$api_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
         . str_replace('test_predict.php', 'api_predict.php', $_SERVER['PHP_SELF']);

echo "<div class='card'><b>API endpoint:</b> <code>$api_url</code></div>";

$passed = 0; $failed = 0;

foreach ($tests as $i => $test) {
    $ch = curl_init($api_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($test['input']),
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    $yield  = $result['predicted_yield_kg_ha'] ?? 0;
    $ok = !$curlError && $httpCode === 200
          && ($result['success'] ?? false)
          && $yield >= $test['min']
          && $yield <= $test['max'];

    if ($ok) $passed++; else $failed++;

    echo "<div class='card'>";
    echo "<b>Test " . ($i+1) . ":</b> {$test['name']} &nbsp; ";
    echo $ok ? "<span class='pass'>✅ PASS</span>" : "<span class='fail'>❌ FAIL</span>";
    echo " <small style='color:#666'>({$test['note']})</small><br><br>";

    if ($curlError) {
        echo "<b style='color:red'>cURL Error:</b> $curlError";
    } else {
        echo "<table><tr><th>Field</th><th>Value</th><th>Expected Range</th></tr>";
        echo "<tr><td>HTTP Code</td><td>$httpCode</td><td>200</td></tr>";
        $ycolor = ($yield >= $test['min'] && $yield <= $test['max']) ? '#16a34a' : '#dc2626';
        echo "<tr><td>Predicted Yield (kg/ha)</td><td style='color:$ycolor'><b>$yield</b></td><td>{$test['min']} – {$test['max']}</td></tr>";
        echo "<tr><td>Expected Production (MT)</td><td>" . ($result['expected_production_million_tonne'] ?? 'N/A') . "</td><td>—</td></tr>";
        echo "<tr><td>Model</td><td>" . ($result['model_name'] ?? 'N/A') . "</td><td>—</td></tr>";
        echo "</table>";
        echo "<details><summary>Full JSON</summary><pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre></details>";
    }
    echo "</div>";
}

$bg = $failed === 0 ? '#f0fdf4' : ($passed > 0 ? '#fffbeb' : '#fef2f2');
$icon = $failed === 0 ? '✅' : ($passed > 0 ? '⚠️' : '❌');
echo "<div class='card' style='background:$bg'>";
echo "<h3>$icon Results: Passed $passed / " . count($tests) . "</h3>";
if ($failed === 0) {
    echo "<p style='color:#16a34a'><b>All tests passed! API is working correctly.</b></p>";
    echo "<p>Now update <code>config.php</code> — no ML_API_URL needed anymore.</p>";
} else {
    echo "<p>$failed test(s) outside expected range. Check Full JSON above for details.</p>";
}
echo "<br><b style='color:red'>⚠️ DELETE this test file after verification!</b>";
echo "</div>";
?>
</body>
</html>
