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

// Verifica a senha
if (!password_verify($dados['senha'], $usuario['senha'])) {
    responderErro('Senha incorreta', 401);
}

// Gera um token JWT (simulado por enquanto)
$token = base64_encode(json_encode([
    'id' => $usuario['id'],
    'email' => $usuario['email'],
    'tipo' => $usuario['tipo'],
    'exp' => time() + JWT_EXPIRATION
]));

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