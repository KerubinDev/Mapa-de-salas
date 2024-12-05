<?php
require_once __DIR__ . '/../config.php';

/**
 * Verifica se o usuário está autenticado
 * @return array Dados do usuário
 */
function verificarAutenticacao() {
    // Obtém o token do header Authorization
    $headers = getallheaders();
    $token = null;

    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (strpos($auth, 'Bearer ') === 0) {
            $token = substr($auth, 7);
        }
    }

    if (!$token) {
        responderErro('Token não fornecido', 401);
    }

    // Verifica o token
    try {
        // Aqui você deve implementar a verificação do JWT
        // Por enquanto, vamos simular um usuário autenticado
        return [
            'id' => 1,
            'nome' => 'Administrador',
            'email' => 'admin@exemplo.com',
            'tipo' => 'admin'
        ];
    } catch (Exception $e) {
        responderErro('Token inválido', 401);
    }
}
 