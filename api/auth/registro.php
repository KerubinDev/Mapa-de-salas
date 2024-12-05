<?php
require_once '../config.php';
require_once '../middleware.php';
require_once __DIR__ . '/../../database/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
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
    $stmt = $db->prepare('SELECT id FROM usuarios WHERE email = ?');
    $stmt->execute([$dados['email']]);
    if ($stmt->fetch()) {
        throw new Exception('Email já cadastrado');
    }
    
    try {
        $db->beginTransaction();
        
        // Insere o usuário
        $stmt = $db->prepare('
            INSERT INTO usuarios (id, nome, email, senha, tipo)
            VALUES (?, ?, ?, ?, ?)
        ');
        
        $id = uniqid();
        $stmt->execute([
            $id,
            $dados['nome'],
            $dados['email'],
            password_hash($dados['senha'], PASSWORD_DEFAULT),
            'usuario' // Tipo padrão para novos registros
        ]);
        
        // Registra o registro no log
        $stmt = $db->prepare('
            INSERT INTO logs (id, usuario_id, acao, detalhes, ip, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            uniqid(),
            $id,
            'registro',
            'Novo usuário registrado',
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        // Busca o usuário criado
        $stmt = $db->prepare('
            SELECT id, nome, email, tipo, data_criacao
            FROM usuarios WHERE id = ?
        ');
        $stmt->execute([$id]);
        $usuario = $stmt->fetch();
        
        // Gera um token de acesso
        $token = bin2hex(random_bytes(32));
        
        // Atualiza o token do usuário
        $stmt = $db->prepare('
            UPDATE usuarios 
            SET token = ?, data_atualizacao = CURRENT_TIMESTAMP 
            WHERE id = ?
        ');
        $stmt->execute([$token, $id]);
        
        $usuario['token'] = $token;
        
        $db->commit();
        responderJson($usuario, 201);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 