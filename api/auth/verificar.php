<?php
require_once '../config.php';
require_once '../middleware.php';

try {
    // Obtém o token do header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        throw new Exception('Token não fornecido', 401);
    }
    
    $token = $matches[1];
    
    // Verifica o token
    $usuarios = $db->query('usuarios', ['token' => $token]);
    $usuario = reset($usuarios);
    
    if (!$usuario) {
        throw new Exception('Token inválido', 401);
    }
    
    // Remove dados sensíveis
    unset($usuario['senha']);
    unset($usuario['token']);
    
    // Busca estatísticas do usuário
    if ($usuario['tipo'] === 'professor') {
        $reservas = $db->query('reservas', ['professorId' => $usuario['id']]);
        $salas = array_unique(array_column($reservas, 'salaId'));
        
        $usuario['estatisticas'] = [
            'totalReservas' => count($reservas),
            'totalSalas' => count($salas)
        ];
    }
    
    // Busca últimas atividades
    $logs = array_filter($db->getData('logs'), function($log) use ($usuario) {
        return $log['usuarioId'] === $usuario['id'];
    });
    
    // Ordena logs por data decrescente e pega os 5 últimos
    usort($logs, function($a, $b) {
        return strtotime($b['dataCriacao']) - strtotime($a['dataCriacao']);
    });
    
    $usuario['ultimasAtividades'] = array_slice($logs, 0, 5);
    
    // Define permissões do usuário
    $usuario['permissoes'] = [
        'gerenciarUsuarios' => $usuario['tipo'] === 'admin',
        'gerenciarSalas' => in_array($usuario['tipo'], ['admin', 'coordenador']),
        'gerenciarTurmas' => in_array($usuario['tipo'], ['admin', 'coordenador']),
        'fazerReservas' => in_array($usuario['tipo'], ['admin', 'coordenador', 'professor']),
        'verLogs' => $usuario['tipo'] === 'admin',
        'fazerBackup' => $usuario['tipo'] === 'admin'
    ];
    
    // Registra a verificação no log
    $db->insert('logs', [
        'usuarioId' => $usuario['id'],
        'acao' => 'verificar',
        'detalhes' => 'Token verificado com sucesso',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    responderJson($usuario);
    
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 