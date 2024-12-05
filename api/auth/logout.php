<?php
require_once '../config.php';
require_once '../middleware.php';

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderErro('Método não permitido', 405);
}

try {
    // Obtém o token do header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
        
        // Busca o usuário pelo token
        $usuarios = $db->query('usuarios', ['token' => $token]);
        $usuario = reset($usuarios);
        
        if ($usuario) {
            // Remove o token do usuário
            $db->update('usuarios', $usuario['id'], [
                'token' => null
            ]);
            
            // Registra o logout no log
            $db->insert('logs', [
                'usuarioId' => $usuario['id'],
                'acao' => 'logout',
                'detalhes' => 'Logout realizado com sucesso',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        }
    }
    
    responderJson(['mensagem' => 'Logout realizado com sucesso']);
    
} catch (Exception $e) {
    responderErro($e->getMessage(), 400);
} 