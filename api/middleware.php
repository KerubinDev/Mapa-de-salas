<?php
require_once 'config.php';
require_once 'auth/AuthManager.php';

/**
 * Verifica se o usuário está autenticado
 */
function verificarAutenticacao() {
    // Verifica se o token foi enviado no header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        throw new Exception('Token não fornecido', 401);
    }

    $token = $matches[1];
    $auth = AuthManager::getInstance();
    
    try {
        return $auth->verificarToken($token);
    } catch (Exception $e) {
        throw new Exception('Token inválido', 401);
    }
}

/**
 * Verifica se é uma requisição CORS preflight
 */
function tratarCORS() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }
}

// Aplica tratamento CORS para todas as requisições
tratarCORS();

/**
 * Middleware para verificar autenticação
 */
function verificarAutenticacao() {
    $auth = AuthManager::getInstance();
    
    if (!$auth->verificarAutenticacao()) {
        responderErro('Não autorizado', 401);
    }
    
    return $auth->getUsuarioAutenticado();
} 