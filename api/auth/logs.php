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
    
    // Obtém filtros da query string
    $filtros = [];
    if (isset($_GET['dataInicio'])) $filtros['dataInicio'] = $_GET['dataInicio'];
    if (isset($_GET['dataFim'])) $filtros['dataFim'] = $_GET['dataFim'];
    if (isset($_GET['usuarioId'])) $filtros['usuarioId'] = $_GET['usuarioId'];
    if (isset($_GET['acao'])) $filtros['acao'] = $_GET['acao'];
    
    // Obtém os logs com os filtros aplicados
    $logs = $auth->listarLogs($filtros);
    
    // Adiciona informações do usuário em cada log
    $usuarios = $auth->listarUsuarios();
    $usuariosPorId = array_column($usuarios, null, 'id');
    
    foreach ($logs as &$log) {
        $log['usuario'] = $usuariosPorId[$log['usuarioId']] ?? null;
    }
    
    responderJson($logs);
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 