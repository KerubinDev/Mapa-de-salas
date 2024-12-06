<?php
require_once __DIR__ . '/../../config.php';

// Log de debug
error_log("=== DEBUG PERFIL ===");
error_log("Headers: " . json_encode(getallheaders()));
error_log("Método: " . $_SERVER['REQUEST_METHOD']);

// Verifica o token
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

error_log("Header de autorização: " . $authHeader);

if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    error_log("Token não encontrado no header");
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