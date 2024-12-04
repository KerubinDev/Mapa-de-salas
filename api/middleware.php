<?php
require_once __DIR__ . '/auth/AuthManager.php';

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