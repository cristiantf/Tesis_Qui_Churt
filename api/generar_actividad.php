<?php
/**
 * Endpoint para generar sugerencias de actividades con Gemini API
 */

require_once '../includes/auth.php'; // Includes db, app config, session
require_once '../config/app.php';

// Check if user is logged in and is a teacher
if (!isLoggedIn() || getCurrentUser()['rol'] !== 'docente') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Get POST body
$data = json_decode(file_get_contents('php://input'), true);

$tema = $data['tema'] ?? '';
$metodologia = $data['metodologia'] ?? '';
$grado = $data['grado'] ?? 'alumnos';

if (empty($tema) || empty($metodologia)) {
    http_response_code(400);
    echo json_encode(['error' => 'Tema y metodología son obligatorios']);
    exit;
}

// Generate the prompt
$prompt = "Eres un experto en pedagogía y metodologías activas. Crea una actividad escolar para $grado sobre el tema '$tema' aplicando la metodología '$metodologia'. Devuelve la respuesta ESTRICTAMENTE en formato JSON válido con las siguientes claves: 'titulo' (un título corto y atractivo para la actividad), 'descripcion' (un resumen general de la actividad y su objetivo), e 'instrucciones' (los pasos detallados que deben seguir los estudiantes). No devuelvas nada más que el JSON puro, sin comillas invertidas ni bloques de código.";

// Call Gemini API
$apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';

if (empty($apiKey)) {
    http_response_code(500);
    echo json_encode(['error' => 'API Key de Gemini no configurada']);
    exit;
}

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key=' . $apiKey;

$requestData = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ],
    'generationConfig' => [
        'responseMimeType' => 'application/json',
        'temperature' => 0.7
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
// We may need this for local development (xampp)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 || $response === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión con la IA', 'details' => $error, 'code' => $httpCode, 'response' => $response]);
    exit;
}

$result = json_decode($response, true);

if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    $aiResponseText = $result['candidates'][0]['content']['parts'][0]['text'];
    
    // Parse the JSON that Gemini returns
    $aiData = json_decode($aiResponseText, true);
    
    if (json_last_error() === JSON_ERROR_NONE && isset($aiData['titulo']) && isset($aiData['descripcion']) && isset($aiData['instrucciones'])) {
        echo json_encode($aiData);
    } else {
        echo json_encode(['titulo' => 'Sugerencia generada', 'descripcion' => $aiResponseText]);
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Respuesta inesperada de la IA']);
}
