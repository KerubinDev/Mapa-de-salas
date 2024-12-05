<?php
require_once '../config.php';
require_once '../middleware.php';
require_once __DIR__ . '/../../database/Database.php';

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
    
    $db = Database::getInstance()->getConnection();
    
    // Busca o usuário pelo email
    $stmt = $db->prepare('
        SELECT id, nome, email, senha, tipo, data_criacao, data_atualizacao
        FROM usuarios 
        WHERE email = ?
        LIMIT 1
    ');
    $stmt->execute([$dados['email']]);
    $usuario = $stmt->fetch();
    
    if (!$usuario || !password_verify($dados['senha'], $usuario['senha'])) {
        throw new Exception('Email ou senha inválidos', 401);
    }
    
    try {
        $db->beginTransaction();
        
        // Gera um novo token
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        // Cria uma nova sessão
        $stmt = $db->prepare('
            INSERT INTO sessoes (id, usuario_id, token, ip, user_agent, data_expiracao)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        
        $sessaoId = uniqid();
        $stmt->execute([
            $sessaoId,
            $usuario['id'],
            $token,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $expira
        ]);
        
        // Atualiza o token do usuário
        $stmt = $db->prepare('
            UPDATE usuarios 
            SET token = ?, data_atualizacao = CURRENT_TIMESTAMP 
            WHERE id = ?
        ');
        $stmt->execute([$token, $usuario['id']]);
        
        // Registra o login no log
        $stmt = $db->prepare('
            INSERT INTO logs (id, usuario_id, acao, detalhes, ip, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            uniqid(),
            $usuario['id'],
            'login',
            'Login realizado com sucesso',
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        // Remove a senha antes de retornar
        unset($usuario['senha']);
        $usuario['token'] = $token;
        $usuario['sessao_id'] = $sessaoId;
        
        $db->commit();
        responderJson($usuario);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 