<?php
require_once 'AuthManager.php';

// Tratamento específico para OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Content-Type: application/json');
    exit(0);
}

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

try {
    // Obtém os dados do corpo da requisição
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($dados['email']) || !isset($dados['senha'])) {
        throw new Exception('Email e senha são obrigatórios');
    }
    
    // Realiza o login
    $auth = AuthManager::getInstance();
    $usuario = $auth->login($dados['email'], $dados['senha']);
    
    // Gera um token único
    $token = bin2hex(random_bytes(32));
    $usuario['token'] = $token;

    // Salva o token no banco de dados
    $dados['usuarios'][$indice] = $usuario;
    salvarDados($dados);

    // Remove informações sensíveis antes de enviar
    unset($usuario['senha']);

    // Retorna os dados do usuário com o token
    responderJson($usuario);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['erro' => $e->getMessage()]);
} 