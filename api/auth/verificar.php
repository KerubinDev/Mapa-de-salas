<?php
require_once '../config.php';
require_once '../middleware.php';
require_once __DIR__ . '/../../database/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtém o token do header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        throw new Exception('Token não fornecido', 401);
    }
    
    $token = $matches[1];
    
    // Verifica o token
    $stmt = $db->prepare('
        SELECT id, nome, email, tipo, data_criacao, data_atualizacao
        FROM usuarios 
        WHERE token = ?
    ');
    $stmt->execute([$token]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        throw new Exception('Token inválido', 401);
    }
    
    // Busca estatísticas do usuário
    if ($usuario['tipo'] === 'professor') {
        $stmt = $db->prepare('
            SELECT COUNT(*) as total_reservas,
                   COUNT(DISTINCT sala_id) as total_salas
            FROM reservas r
            JOIN turmas t ON r.turma_id = t.id
            WHERE t.professor = ?
        ');
        $stmt->execute([$usuario['nome']]);
        $stats = $stmt->fetch();
        
        $usuario['estatisticas'] = $stats;
    }
    
    // Busca últimas atividades
    $stmt = $db->prepare('
        SELECT acao, detalhes, data_criacao
        FROM logs
        WHERE usuario_id = ?
        ORDER BY data_criacao DESC
        LIMIT 5
    ');
    $stmt->execute([$usuario['id']]);
    $usuario['ultimas_atividades'] = $stmt->fetchAll();
    
    // Busca permissões do usuário
    $permissoes = [
        'gerenciarUsuarios' => $usuario['tipo'] === 'admin',
        'gerenciarSalas' => in_array($usuario['tipo'], ['admin', 'coordenador']),
        'gerenciarTurmas' => in_array($usuario['tipo'], ['admin', 'coordenador']),
        'fazerReservas' => in_array($usuario['tipo'], ['admin', 'coordenador', 'professor']),
        'verLogs' => $usuario['tipo'] === 'admin',
        'fazerBackup' => $usuario['tipo'] === 'admin'
    ];
    
    $usuario['permissoes'] = $permissoes;
    
    // Registra a verificação no log
    $stmt = $db->prepare('
        INSERT INTO logs (id, usuario_id, acao, detalhes, ip, user_agent)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    
    $stmt->execute([
        uniqid(),
        $usuario['id'],
        'verificar',
        'Token verificado com sucesso',
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    responderJson($usuario);
    
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 