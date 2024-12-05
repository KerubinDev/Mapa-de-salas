<?php
require_once 'config.php';

/**
 * Verifica se o usuário está autenticado
 */
function verificarAutenticacao() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        throw new Exception('Token não fornecido', 401);
    }
    
    $token = $matches[1];
    $db = JsonDatabase::getInstance();
    
    // Busca o usuário pelo token
    $usuarios = $db->query('usuarios', ['token' => $token]);
    $usuario = reset($usuarios);
    
    if (!$usuario) {
        throw new Exception('Token inválido', 401);
    }
    
    return $usuario;
}

/**
 * Trata requisições CORS
 */
function tratarCORS() {
    // Permite acesso de qualquer origem em desenvolvimento
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Max-Age: 86400'); // 24 horas
        exit(0);
    }

    // Configurações para outras requisições
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

/**
 * Verifica permissões do usuário
 */
function verificarPermissao($usuario, $permissao) {
    $permissoes = [
        'gerenciarUsuarios' => ['admin'],
        'gerenciarSalas' => ['admin', 'coordenador'],
        'gerenciarTurmas' => ['admin', 'coordenador'],
        'fazerReservas' => ['admin', 'coordenador', 'professor'],
        'verLogs' => ['admin'],
        'fazerBackup' => ['admin']
    ];
    
    if (!isset($permissoes[$permissao])) {
        throw new Exception('Permissão inválida');
    }
    
    if (!in_array($usuario['tipo'], $permissoes[$permissao])) {
        throw new Exception('Acesso negado', 403);
    }
    
    return true;
}

/**
 * Registra uma ação no log
 */
function registrarLog($usuarioId, $acao, $detalhes) {
    $db = JsonDatabase::getInstance();
    
    return $db->insert('logs', [
        'usuarioId' => $usuarioId,
        'acao' => $acao,
        'detalhes' => $detalhes,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

// Aplica tratamento CORS para todas as requisições
tratarCORS();
 