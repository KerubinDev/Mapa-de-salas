<?php
require_once '../config.php';
require_once '../middleware.php';

try {
    // Obtém os dados do corpo da requisição
    $dados = json_decode(file_get_contents('php://input'), true);
    
    // Validação dos campos
    if (empty($dados['nome'])) {
        throw new Exception('Nome é obrigatório');
    }
    
    if (empty($dados['email'])) {
        throw new Exception('Email é obrigatório');
    }
    
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
    }
    
    if (empty($dados['senha'])) {
        throw new Exception('Senha é obrigatória');
    }
    
    if (strlen($dados['senha']) < 6) {
        throw new Exception('A senha deve ter no mínimo 6 caracteres');
    }
    
    // Verifica se o email já está em uso
    $usuarioExistente = $db->query('usuarios', ['email' => $dados['email']]);
    if (!empty($usuarioExistente)) {
        throw new Exception('Email já cadastrado');
    }
    
    // Gera um token de acesso
    $token = bin2hex(random_bytes(32));
    
    // Cria o usuário
    $usuario = $db->insert('usuarios', [
        'nome' => $dados['nome'],
        'email' => $dados['email'],
        'senha' => password_hash($dados['senha'], PASSWORD_DEFAULT),
        'tipo' => 'usuario',
        'token' => $token
    ]);
    
    // Remove dados sensíveis antes de retornar
    unset($usuario['senha']);
    
    // Registra o registro no log
    $db->insert('logs', [
        'usuarioId' => $usuario['id'],
        'acao' => 'registro',
        'detalhes' => 'Novo usuário registrado',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    responderJson($usuario, 201);
    
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 