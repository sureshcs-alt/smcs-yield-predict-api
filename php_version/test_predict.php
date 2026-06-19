<?php
/**
 * test_predict.php
 * Quick self-test for api_predict.php
 * Upload alongside api_predict.php, run once, then DELETE.
 * URL: https://smcs.sknau.ac.in/api/test_predict.php
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>SMCS ML Predict — Self Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .card { background: white; border-radius: 8px; padding: 20px; margin: 10px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .pass { color: #16a34a; font-weight: bold; }
        .fail { color: #dc2626; font-weight: bold; }
        pre { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 13px; }
        h2 { color: #1e3a5f; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #f0f4f8; }
    </style>
</head>
<body>
<h2>🌾 SMCS Crop Yield Prediction — Self Test</h2>
<p><b>Server:</b> <?= $_SERVER['SERVER_NAME'] ?> &nbsp;|&nbsp; <b>PHP:</b> <?= PHP_VERSION ?> &nbsp;|&nbsp; <b>Time:</b> <?= date('d M Y H:i:s') ?></p>

<?php
$tests = [
    [
        'name'   => 'India — Soybean 2024',
        'input'  => ['geography'=>'India',  'crop'=>'Soybean',   'year'=>2024, 'area_mha'=>12.5,  'production_million_tonne'=>13.8],
        'expect' => 1000
    ],
    [
        'name'   => 'Rajasthan — Mustard 2023',
        'input'  => ['geography'=>'Rajasthan', 'crop'=>'Mustard', 'year'=>2023, 'area_mha'=>3.2,  'production_million_tonne'=>3.8],
        'expect' => 800
    ],
    [
        'name'   => 'India — Groundnut 2022',
        'input'  => ['geography'=>'India',  'crop'=>'Groundnut', 'year'=>2022, 'area_mha'=>4.8,  'production_million_tonne'=>6.2],
        'expect' => 900
    ],
    [
        'name'   => 'India — Sesamum 2023',
        'input'  => ['geography'=>'India',  'crop'=>'Sesamum',   'year'=>2023, 'area_mha'=>1.2,  'production_million_tonne'=>0.8],
        'expect' => 300
    ],
    [
        'name'   => 'India — Total Oilseed 2025',
        'input'  => ['geography'=>'India',  'crop'=>'Total Oilseed', 'year'=>2025, 'area_mha'=>30.0, 'production_million_tonne'=>38.5],
        'expect' => 900
    ],
];

$api_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
         . str_replace('test_predict.php', 'api_predict.php', $_SERVER['PHP_SELF']);

echo "<div class='card'><b>API endpoint being tested:</b> <code>$api_url</code></div>";

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
    $ok = !$curlError && $httpCode === 200 && ($result['success'] ?? false)
          && ($result['predicted_yield_kg_ha'] ?? 0) >= $test['expect'];

    if ($ok) $passed++; else $failed++;

    echo "<div class='card'>";
    echo "<b>Test " . ($i+1) . ":</b> {$test['name']} &nbsp; ";
    echo $ok ? "<span class='pass'>✅ PASS</span>" : "<span class='fail'>❌ FAIL</span>";
    echo "<br><br>";
    if ($curlError) echo "<b style='color:red'>cURL Error:</b> $curlError<br>";
    else {
        echo "<table><tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>HTTP Code</td><td>$httpCode</td></tr>";
        if ($result) {
            echo "<tr><td>Predicted Yield (kg/ha)</td><td>" . ($result['predicted_yield_kg_ha'] ?? 'N/A') . "</td></tr>";
            echo "<tr><td>Expected Production (MT)</td><td>" . ($result['expected_production_million_tonne'] ?? 'N/A') . "</td></tr>";
            echo "<tr><td>Model</td><td>" . ($result['model_name'] ?? 'N/A') . "</td></tr>";
        }
        echo "</table>";
        echo "<details><summary>Full JSON response</summary><pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre></details>";
    }
    echo "</div>";
}

echo "<div class='card' style='background: " . ($failed === 0 ? '#f0fdf4' : '#fef2f2') . "'>";
echo "<h3>" . ($failed === 0 ? '✅ All tests passed!' : "⚠️ $failed test(s) failed") . "</h3>";
echo "<b>Passed:</b> $passed &nbsp;|&nbsp; <b>Failed:</b> $failed<br><br>";
echo "<b style='color:red'>⚠️ DELETE this test file after verification!</b>";
echo "</div>";
?>
</body>
</html>
