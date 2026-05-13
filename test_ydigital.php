<?php
$apiKey = 'ysens_p8wQanOTBDFwo2wyf72t2LRROIrkzzRp0_Kem25U5lg';

$ch = curl_init('https://ysens.it/api/v1/sensors/');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['X-API-Key: ' . $apiKey],
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP $code\n";
echo "Response:\n";
$data = json_decode($res, true);
if ($data) {
    echo "Count: " . ($data['count'] ?? 0) . "\n\n";
    foreach ($data['sensors'] ?? [] as $s) {
        echo "device_id: {$s['device_id']}\n";
        echo "name (sensor_name): {$s['name']}\n";
        echo "type: {$s['type']}\n";
        echo "device_name: " . ($s['device_name'] ?? '-') . "\n";
        echo "description: " . ($s['description'] ?? '-') . "\n";
        echo "has_actuators: " . ($s['has_actuators'] ? 'YES' : 'NO') . "\n";
        echo str_repeat('-', 40) . "\n";
    }
} else {
    echo $res . "\n";
}
