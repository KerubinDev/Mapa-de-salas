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
    $auth = AuthManager::getInstance();
    $auth->logout();
    echo json_encode(['mensagem' => 'Logout realizado com sucesso']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['erro' => $e->getMessage()]);
} 