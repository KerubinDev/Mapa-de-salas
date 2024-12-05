<?php
require_once '../config.php';
require_once '../middleware.php';

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderErro('Método não permitido', 405);
}

try {
    // Obtém os dados do corpo da requisição
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($dados['email']) || !isset($dados['senha'])) {
        throw new Exception('Email e senha são obrigatórios');
    }
    
    // Busca o usuário pelo email
    $usuarios = $db->query('usuarios', ['email' => $dados['email']]);
    $usuario = reset($usuarios);
    
    if (!$usuario || !password_verify($dados['senha'], $usuario['senha'])) {
        throw new Exception('Email ou senha inválidos', 401);
    }
    
    // Gera um novo token
    $token = bin2hex(random_bytes(32));
    
    // Atualiza o token do usuário
    $usuario = $db->update('usuarios', $usuario['id'], [
        'token' => $token,
        'ultimoLogin' => date('Y-m-d H:i:s')
    ]);
    
    // Remove dados sensíveis
    unset($usuario['senha']);
    
    // Registra o login no log
    $db->insert('logs', [
        'usuarioId' => $usuario['id'],
        'acao' => 'login',
        'detalhes' => 'Login realizado com sucesso',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
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
    
    responderJson($usuario);
    
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 