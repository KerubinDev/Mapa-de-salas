<?php
require_once __DIR__ . '/../../config.php';

// Log de debug
error_log("=== DEBUG LOGIN ===");

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderErro('Método não permitido', 405);
}

// Obtém os dados do corpo da requisição
$dados = json_decode(file_get_contents('php://input'), true);
error_log("Dados recebidos: " . json_encode($dados));

// Valida os dados recebidos
if (!isset($dados['email']) || !isset($dados['senha'])) {
    responderErro('Email e senha são obrigatórios', 400);
}

// Lê o banco de dados
$db = json_decode(file_get_contents(DB_FILE), true);
$usuarios = $db['usuarios'] ?? [];

// Procura o usuário
$usuario = null;
foreach ($usuarios as &$u) {
    if ($u['email'] === $dados['email']) {
        $usuario = &$u;
        break;
    }
}

if (!$usuario) {
    responderErro('Usuário não encontrado', 401);
}

// Verifica a senha
if ($usuario['senha'] !== $dados['senha']) {
    responderErro('Senha incorreta', 401);
}

// Gera um novo token
$token = bin2hex(random_bytes(32));
error_log("Novo token gerado: " . $token);

// Atualiza o token do usuário
$usuario['token'] = $token;
$usuario['ultimoLogin'] = date('Y-m-d H:i:s');

// Salva as alterações no banco
file_put_contents(DB_FILE, json_encode($db, JSON_PRETTY_PRINT));
error_log("Banco atualizado com novo token");

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