<?php
require_once '../config.php';
require_once '../middleware.php';

try {
    $metodo = $_SERVER['REQUEST_METHOD'];
    
    switch ($metodo) {
        case 'POST': // Solicita recuperação de senha
            $dados = json_decode(file_get_contents('php://input'), true);
            
            if (empty($dados['email'])) {
                throw new Exception('Email é obrigatório');
            }
            
            // Verifica se o usuário existe
            $usuarios = $db->query('usuarios', ['email' => $dados['email']]);
            $usuario = reset($usuarios);
            
            if (!$usuario) {
                throw new Exception('Email não encontrado');
            }
            
            // Gera um token de recuperação
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Atualiza o token de recuperação do usuário
            $db->update('usuarios', $usuario['id'], [
                'tokenRecuperacao' => $token,
                'tokenExpiracao' => $expira
            ]);
            
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
            
            // Busca o usuário pelo token
            $usuarios = $db->query('usuarios', ['tokenRecuperacao' => $dados['token']]);
            $usuario = reset($usuarios);
            
            if (!$usuario) {
                throw new Exception('Token inválido');
            }
            
            // Verifica se o token ainda é válido
            if (strtotime($usuario['tokenExpiracao']) < time()) {
                throw new Exception('Token expirado');
            }
            
            // Atualiza a senha e limpa o token
            $db->update('usuarios', $usuario['id'], [
                'senha' => password_hash($dados['senha'], PASSWORD_DEFAULT),
                'tokenRecuperacao' => null,
                'tokenExpiracao' => null
            ]);
            
            // Registra a alteração no log
            $db->insert('logs', [
                'usuarioId' => $usuario['id'],
                'acao' => 'senha',
                'detalhes' => 'Senha alterada via recuperação',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            responderJson(['mensagem' => 'Senha alterada com sucesso']);
            break;
            
        default:
            throw new Exception('Método não suportado', 405);
    }
    
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 