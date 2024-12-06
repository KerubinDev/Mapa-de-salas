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
error_log("Banco antes da atualização: " . json_encode($db));

// Procura o usuário
$usuarioIndex = null;
foreach ($db['usuarios'] as $index => $u) {
    if ($u['email'] === $dados['email']) {
        $usuarioIndex = $index;
        break;
    }
}

if ($usuarioIndex === null) {
    responderErro('Usuário não encontrado', 401);
}

$usuario = &$db['usuarios'][$usuarioIndex];

// Verifica a senha
error_log("Senha recebida (hash): " . $dados['senha']);
error_log("Senha no banco: " . $usuario['senha']);

if ($usuario['senha'] !== $dados['senha']) {
    error_log("Senha incorreta para o usuário: " . $usuario['email']);
    responderErro('Senha incorreta', 401);
}

// Gera um novo token
$token = bin2hex(random_bytes(32));
error_log("Novo token gerado: " . $token);

// Atualiza o token do usuário
$usuario['token'] = $token;
$usuario['ultimoLogin'] = date('Y-m-d H:i:s');
$usuario['dataAtualizacao'] = date('Y-m-d H:i:s');

error_log("Usuário após atualização: " . json_encode($usuario));
error_log("Banco após atualização: " . json_encode($db));

// Salva as alterações no banco
if (file_put_contents(DB_FILE, json_encode($db, JSON_PRETTY_PRINT)) === false) {
    error_log("Erro ao salvar no banco de dados");
    responderErro('Erro interno do servidor', 500);
}

error_log("Banco atualizado com novo token");

// Remove dados sensíveis antes de retornar
$respostaUsuario = $usuario;
unset($respostaUsuario['senha']);

// Retorna os dados do usuário e o token
responderJson([
    'sucesso' => true,
    'dados' => [
        'token' => $token,
        'usuario' => $respostaUsuario
    ]
]);