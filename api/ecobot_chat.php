<?php
header('Content-Type: application/json');

// --- CONFIG ---
$OPENAI_API_KEY = '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'; // <-- Replace with your OpenAI API key
$OPENAI_API_URL = 'https://api.openai.com/v1/chat/completions';

// --- Get user message ---
$input = json_decode(file_get_contents('php://input'), true);
$user_message = isset($input['message']) ? trim($input['message']) : '';
if (!$user_message) {
    echo json_encode(['reply' => 'No message received.']);
    exit;
}

// --- Prepare OpenAI API request ---
$data = [
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'system', 'content' => 'You are EcoBot, a helpful assistant for waste management and environmental awareness.'],
        ['role' => 'user', 'content' => $user_message]
    ],
    'max_tokens' => 300,
    'temperature' => 0.7
];

$ch = curl_init($OPENAI_API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $OPENAI_API_KEY
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo json_encode(['reply' => 'Error connecting to AI service.']);
    exit;
}

$result = json_decode($response, true);
if (isset($result['choices'][0]['message']['content'])) {
    $reply = trim($result['choices'][0]['message']['content']);
    echo json_encode(['reply' => $reply]);
} else {
    // Show the actual error for debugging
    $errorMsg = isset($result['error']['message']) ? $result['error']['message'] : json_encode($result);
    echo json_encode(['reply' => 'OpenAI error: ' . $errorMsg]);
} 