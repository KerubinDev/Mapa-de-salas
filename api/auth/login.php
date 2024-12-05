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
    $db = JsonDatabase::getInstance();
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
    unset($usuario['tokenRecuperacao']);
    unset($usuario['tokenExpiracao']);
    
    // Registra o login no log
    registrarLog($usuario['id'], 'login', 'Login realizado com sucesso');
    
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