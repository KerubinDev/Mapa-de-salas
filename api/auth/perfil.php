<?php
require_once __DIR__ . '/../../config.php';

// Log detalhado de todos os headers
error_log("=== DEBUG PERFIL DETALHADO ===");
$headers = getallheaders();
foreach ($headers as $name => $value) {
    error_log("$name: $value");
}

// Log específico do Authorization
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
error_log("Authorization header bruto: " . $authHeader);

// Verifica CORS e preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit(0);
}

// Verifica o token
if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    error_log("Token não encontrado no header. Headers completos: " . json_encode($headers));
    responderErro('Token não fornecido', 401);
}

$token = $matches[1];
error_log("Token extraído: " . $token);

// Lê o banco de dados
$db = json_decode(file_get_contents(DB_FILE), true);
$usuarios = $db['usuarios'] ?? [];

// Busca o usuário pelo token
$usuario = null;
foreach ($usuarios as $u) {
    if (($u['token'] ?? '') === $token) {
        $usuario = $u;
        break;
    }
}

if (!$usuario) {
    error_log("Usuário não encontrado para o token");
    responderErro('Token inválido', 401);
}

error_log("Usuário encontrado: " . json_encode($usuario));

// Remove dados sensíveis
unset($usuario['senha']);
unset($usuario['token']);

// Responde com os dados do usuário
responderJson([
    'sucesso' => true,
    'dados' => $usuario
]); 