<?php
// includes/ai_helper.php - DEBUG VERSION
function callGeminiAI($filePath) {
   $apiKey = "AIzaSyC1YoueHqDeIQf8DS2AY5QeorgUlKEWPS4";   // your current key

    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mime = ($ext === 'pdf') ? 'application/pdf' :
            ($ext === 'xlsx' ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'application/octet-stream');

    $base64 = base64_encode(file_get_contents($filePath));

    $prompt = "Tu es expert en documents internes d'Algérie Télécom. 
    Extrait TOUTES les informations importantes en JSON propre (sans texte supplémentaire).
    Champs obligatoires: title, version, date, object, contract_number, companies (array), amounts, responsible_divisions, key_dates, summary, tables (if any).";

    $data = [
        "contents" => [[
            "parts" => [
                ["text" => $prompt],
                [
                    "inline_data" => [
                        "mime_type" => $mime,
                        "data" => $base64
                    ]
                ]
            ]
        ]],
        "generationConfig" => ["response_mime_type" => "application/json"]
    ];

    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=$apiKey");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   // ← added for XAMPP

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // === DEBUG OUTPUT ===
    if ($curlError) {
        return ["error" => "CURL Error: " . $curlError];
    }
    if ($httpCode !== 200) {
        return ["error" => "HTTP " . $httpCode, "raw_response" => $response];
    }

    $result = json_decode($response, true);
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        return json_decode($result['candidates'][0]['content']['parts'][0]['text'], true);
    }

    return [
        "error" => "AI error - no valid JSON returned",
        "http_code" => $httpCode,
        "raw_response" => $response
    ];
}
?>