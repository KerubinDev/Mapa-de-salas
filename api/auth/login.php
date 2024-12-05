<?php
require_once __DIR__ . '/../../config.php';

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderErro('Método não permitido', 405);
}

// Obtém os dados do corpo da requisição
$dados = json_decode(file_get_contents('php://input'), true);

// Valida os dados recebidos
if (!isset($dados['email']) || !isset($dados['senha'])) {
    responderErro('Email e senha são obrigatórios', 400);
}

// Lê o banco de dados
if (!file_exists(DB_FILE)) {
    responderErro('Erro ao acessar banco de dados', 500);
}

$db = json_decode(file_get_contents(DB_FILE), true);
$usuarios = $db['usuarios'] ?? [];

// Busca o usuário pelo email
$usuario = null;
foreach ($usuarios as $u) {
    if ($u['email'] === $dados['email']) {
        $usuario = $u;
        break;
    }
}

// Verifica se o usuário existe
if (!$usuario) {
    responderErro('Usuário não encontrado', 401);
}

// Gera o hash da senha fornecida usando SHA-256
$senhaHash = hash('sha256', $dados['senha']);

// Verifica se o hash da senha corresponde
if ($senhaHash !== $usuario['senha']) {
    // Log para debug
    if (isset($dados['_debug']) && $dados['_debug']) {
        error_log("Debug - Comparação de senhas:");
        error_log("Hash recebido: " . $senhaHash);
        error_log("Hash armazenado: " . $usuario['senha']);
    }
    responderErro('Senha incorreta', 401);
}

// Gera um novo token
$token = bin2hex(random_bytes(32));

// Atualiza o token do usuário no banco
foreach ($usuarios as &$u) {
    if ($u['id'] === $usuario['id']) {
        $u['token'] = $token;
        $u['ultimoLogin'] = date('Y-m-d H:i:s');
        break;
    }
}

// Salva as alterações no banco
file_put_contents(DB_FILE, json_encode($db, JSON_PRETTY_PRINT));

// Remove dados sensíveis antes de retornar
unset($usuario['senha']);

// Retorna os dados do usuário e o token
responderJson([
    'sucesso' => true,
    'dados' => [
        'token' => $token,
        'usuario' => $usuario
    ]
]); 