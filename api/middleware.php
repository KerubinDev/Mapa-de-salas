<?php
require_once __DIR__ . '/config.php';

/**
 * Configura os cabeçalhos CORS
 */
function configurarCORS() {
    // Em desenvolvimento, permite todas as origens
    if (APP_ENV === 'development') {
        header('Access-Control-Allow-Origin: *');
    } else {
        // Em produção, permite apenas origens específicas
        $origensPermitidas = [
            'https://seu-dominio.com',
            'https://app.seu-dominio.com'
        ];
        
        $origem = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (in_array($origem, $origensPermitidas)) {
            header("Access-Control-Allow-Origin: $origem");
        }
    }

    // Configura outros cabeçalhos CORS
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400'); // 24 horas
    
    // Permite credenciais
    header('Access-Control-Allow-Credentials: true');
    
    // Responde imediatamente para requisições OPTIONS
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit();
    }
}

/**
 * Verifica autenticação do usuário
 */
function verificarAutenticacao() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        throw new Exception('Token não fornecido', 401);
    }
    
    $token = $matches[1];
    $db = JsonDatabase::getInstance();
    
    // Busca usuário pelo token
    $usuarios = $db->query('usuarios', ['token' => $token]);
    $usuario = reset($usuarios);
    
    if (!$usuario) {
        throw new Exception('Token inválido', 401);
    }
    
    return $usuario;
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

// Aplica configurações CORS para todas as requisições
configurarCORS();
 