<?php
require_once 'AuthManager.php';

// Tratamento específico para OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0);
}

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderErro('Método não permitido', 405);
}

try {
    // Obtém os dados do corpo da requisição
    $dados = json_decode(file_get_contents('php://input'), true);
    
    // Validação dos campos
    if (empty($dados['nome'])) {
        throw new Exception('Nome é obrigatório');
    }
    
    if (empty($dados['email'])) {
        throw new Exception('Email é obrigatório');
    }
    
    if (empty($dados['senha'])) {
        throw new Exception('Senha é obrigatória');
    }
    
    if (strlen($dados['senha']) < 6) {
        throw new Exception('A senha deve ter no mínimo 6 caracteres');
    }
    
    // Registra o usuário
    $auth = AuthManager::getInstance();
    $usuario = $auth->registrarUsuario($dados);
    
    responderJson($usuario, 201);
} catch (Exception $e) {
    responderErro($e->getMessage());
} 