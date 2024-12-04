<?php
require_once '../config.php';
require_once 'AuthManager.php';

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
    
    // Realiza o login
    $auth = AuthManager::getInstance();
    $usuario = $auth->login($dados['email'], $dados['senha']);
    
    // Gera um token único
    $token = bin2hex(random_bytes(32));
    
    // Atualiza o usuário com o token
    $dadosDB = lerDados();
    foreach ($dadosDB['usuarios'] as &$u) {
        if ($u['id'] === $usuario['id']) {
            $u['token'] = $token;
            $usuario['token'] = $token;
            break;
        }
    }
    salvarDados($dadosDB);

    // Remove informações sensíveis antes de enviar
    unset($usuario['senha']);

    // Retorna os dados do usuário com o token
    responderJson($usuario);
} catch (Exception $e) {
    responderErro($e->getMessage(), 401);
} 