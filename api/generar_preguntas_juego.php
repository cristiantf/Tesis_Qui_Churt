<?php
/**
 * Endpoint para generar preguntas de juegos con Gemini API
 */

require_once '../includes/auth.php';
require_once '../config/app.php';

// Verificación de autenticación y rol
if (!isLoggedIn() || getCurrentUser()['rol'] !== 'docente') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

requireValidCsrfHeader();
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'El cuerpo de la solicitud no es válido']);
    exit;
}

$asignatura = $data['asignatura'] ?? '';
$tema = $data['tema'] ?? '';
$cantidad = intval($data['cantidad'] ?? 5);

if (empty($asignatura) || empty($tema)) {
    http_response_code(400);
    echo json_encode(['error' => 'Asignatura y tema son obligatorios']);
    exit;
}

$prompt = "Eres un profesor experto. Crea $cantidad preguntas de opción múltiple sobre el tema '$tema' para la asignatura '$asignatura'. Devuelve la respuesta ESTRICTAMENTE en formato JSON válido que sea un arreglo (array) de objetos. Cada objeto debe tener las siguientes claves exactas: 'pregunta' (el texto de la pregunta), 'opcion_a' (texto opción A), 'opcion_b' (texto opción B), 'opcion_c' (texto opción C), 'opcion_d' (texto opción D), y 'respuesta_correcta' (debe ser 'A', 'B', 'C' o 'D' indicando cuál es la correcta). No devuelvas texto adicional, ni bloques de código markdown, SOLO el JSON válido.";

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
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 || $response === false) {
    error_log('Gemini API error: HTTP ' . $httpCode . ' - ' . $error);
    http_response_code(500);
    echo json_encode(['error' => 'No fue posible generar las preguntas. Inténtalo más tarde.']);
    exit;
}

$result = json_decode($response, true);

if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    $aiResponseText = $result['candidates'][0]['content']['parts'][0]['text'];
    $aiData = json_decode($aiResponseText, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($aiData)) {
        echo json_encode($aiData);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'La IA no devolvió un JSON válido con el formato esperado.', 'raw' => $aiResponseText]);
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Respuesta inesperada de la IA']);
}
