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
    
    echo json_encode($usuario);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['erro' => $e->getMessage()]);
} 