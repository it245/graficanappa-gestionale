<?php
/**
 * Test Visirun / Verizon Connect Reveal API
 * Uso: php test_visirun.php
 */

$username = 'anappa@graficanappa.com';
$password = 'Edom.1717';
$appId    = 'fleetmatics-p-eu-dvJ6pMwjaO1VxkNCuHJkKvyvEFTZam23xNXRdBdA';
$baseUrl  = 'https://fim.api.eu.fleetmatics.com';

// 1. Ottieni token
echo "=== STEP 1: Token ===\n";
$ch = curl_init("$baseUrl/token");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . base64_encode("$username:$password"),
        'Accept: text/plain',
        'Content-Type: text/plain',
    ],
]);
$token = curl_exec($ch);
if (curl_errno($ch)) echo "cURL Error: " . curl_error($ch) . "\n";
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP: $httpCode\n";
echo "Token: $token\n\n";

if ($httpCode !== 200 || empty($token)) {
    die("Errore ottenendo il token!\n");
}

// 2. Lista veicoli
echo "=== STEP 2: Veicoli (cmd/v1) ===\n";
$ch = curl_init("$baseUrl/cmd/v1/vehicles");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Atmosphere atmosphere_app_id=$appId, Bearer $token",
        'Accept: application/json',
        'Content-Type: application/json',
    ],
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP: $httpCode\n";
echo substr($resp, 0, 2000) . "\n\n";

// 3. Se cmd non va, prova rad
echo "=== STEP 3: Veicoli (rad/v1) ===\n";
$ch = curl_init("$baseUrl/rad/v1/vehicles");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Atmosphere atmosphere_app_id=$appId, Bearer $token",
        'Accept: application/json',
        'Content-Type: application/json',
    ],
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP: $httpCode\n";
echo substr($resp, 0, 2000) . "\n\n";

// 4. Prova con header diverso (Authorization-Token)
echo "=== STEP 4: Veicoli (Authorization-Token header) ===\n";
$ch = curl_init("$baseUrl/cmd/v1/vehicles");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization-Token: $token",
        "App-Id: $appId",
        'Accept: application/json',
    ],
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP: $httpCode\n";
echo substr($resp, 0, 2000) . "\n";
