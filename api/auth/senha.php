<?php
require_once '../config.php';
require_once '../middleware.php';
require_once __DIR__ . '/../../database/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    $metodo = $_SERVER['REQUEST_METHOD'];
    
    switch ($metodo) {
        case 'POST': // Solicita recuperação de senha
            $dados = json_decode(file_get_contents('php://input'), true);
            
            if (empty($dados['email'])) {
                throw new Exception('Email é obrigatório');
            }
            
            // Verifica se o usuário existe
            $stmt = $db->prepare('SELECT id, nome FROM usuarios WHERE email = ?');
            $stmt->execute([$dados['email']]);
            $usuario = $stmt->fetch();
            
            if (!$usuario) {
                throw new Exception('Email não encontrado');
            }
            
            // Gera um token de recuperação
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Salva o token
            $stmt = $db->prepare('
                UPDATE usuarios 
                SET token_recuperacao = ?, 
                    token_expiracao = ?,
                    data_atualizacao = CURRENT_TIMESTAMP 
                WHERE id = ?
            ');
            $stmt->execute([$token, $expira, $usuario['id']]);
            
            // TODO: Enviar email com o link de recuperação
            // Por enquanto, apenas retorna o token para testes
            responderJson([
                'mensagem' => 'Email de recuperação enviado',
                'token' => $token // Remover em produção
            ]);
            break;
            
        case 'PUT': // Altera a senha usando o token
            $dados = json_decode(file_get_contents('php://input'), true);
            
            if (empty($dados['token']) || empty($dados['senha'])) {
                throw new Exception('Token e nova senha são obrigatórios');
            }
            
            if (strlen($dados['senha']) < 6) {
                throw new Exception('A senha deve ter no mínimo 6 caracteres');
            }
            
            // Verifica o token
            $stmt = $db->prepare('
                SELECT id 
                FROM usuarios 
                WHERE token_recuperacao = ? 
                AND token_expiracao > CURRENT_TIMESTAMP
            ');
            $stmt->execute([$dados['token']]);
            $usuario = $stmt->fetch();
            
            if (!$usuario) {
                throw new Exception('Token inválido ou expirado');
            }
            
            try {
                $db->beginTransaction();
                
                // Atualiza a senha
                $stmt = $db->prepare('
                    UPDATE usuarios 
                    SET senha = ?,
                        token_recuperacao = NULL,
                        token_expiracao = NULL,
                        data_atualizacao = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ');
                
                $stmt->execute([
                    password_hash($dados['senha'], PASSWORD_DEFAULT),
                    $usuario['id']
                ]);
                
                // Registra no log
                $stmt = $db->prepare('
                    INSERT INTO logs (id, usuario_id, acao, detalhes, ip, user_agent)
                    VALUES (?, ?, ?, ?, ?, ?)
                ');
                
                $stmt->execute([
                    uniqid(),
                    $usuario['id'],
                    'senha',
                    'Senha alterada via recuperação',
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                $db->commit();
                responderJson(['mensagem' => 'Senha alterada com sucesso']);
                
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;
            
        default:
            throw new Exception('Método não suportado', 405);
    }
    
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 