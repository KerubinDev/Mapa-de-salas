<?php
require_once '../config.php';
require_once 'AuthManager.php';
require_once __DIR__ . '/../../database/Database.php';

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderErro('Método não permitido', 405);
}

try {
    // Obtém o token do header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
        
        // Realiza o logout
        $auth = AuthManager::getInstance();
        $auth->logout($token);
    }
    
    responderJson(['mensagem' => 'Logout realizado com sucesso']);
    
} catch (Exception $e) {
    responderErro($e->getMessage(), 400);
} 