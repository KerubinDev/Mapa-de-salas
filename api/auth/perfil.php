<?php
require_once __DIR__ . '/../../config.php';

// Log detalhado
error_log("=== DEBUG PERFIL DETALHADO ===");
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
error_log("Authorization header: " . $authHeader);

// Extrai o token
if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    error_log("Token não encontrado no header");
    responderErro('Token não fornecido', 401);
}

$token = $matches[1];
error_log("Token extraído: " . $token);

// Lê o banco de dados
$db = json_decode(file_get_contents(DB_FILE), true);
$usuarios = $db['usuarios'] ?? [];

error_log("Total de usuários no banco: " . count($usuarios));
error_log("Procurando token: " . $token);

// Debug de todos os usuários
foreach ($usuarios as $index => $u) {
    error_log("Usuário $index: " . json_encode([
        'id' => $u['id'],
        'token' => $u['token'] ?? 'sem token',
        'email' => $u['email']
    ]));
}

// Busca o usuário
$usuario = null;
foreach ($usuarios as $u) {
    if (($u['token'] ?? '') === $token) {
        $usuario = $u;
        break;
    }
}

if (!$usuario) {
    error_log("Usuário não encontrado para o token: $token");
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