<?php
require_once '../config.php';
require_once '../middleware.php';

// Verifica autenticação
try {
    $usuario = verificarAutenticacao();
    if ($usuario['tipo'] !== 'admin') {
        responderErro('Acesso negado', 403);
    }
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode());
}

try {
    $auth = AuthManager::getInstance();
    $usuarios = $auth->listarUsuarios();
    responderJson($usuarios);
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 