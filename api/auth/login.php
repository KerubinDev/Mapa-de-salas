<?php
require_once '../config.php';
require_once 'AuthManager.php';
require_once __DIR__ . '/../../database/Database.php';

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderErro('Método não permitido', 405);
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
    
    // Retorna os dados do usuário
    responderJson($usuario);
    
} catch (Exception $e) {
    responderErro($e->getMessage(), 401);
} 