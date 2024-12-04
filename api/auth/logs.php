<?php
require_once 'AuthManager.php';

// Tratamento específico para OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Content-Type: application/json');
    exit(0);
}

// Verifica autenticação
$auth = AuthManager::getInstance();
if (!$auth->verificarAutenticacao()) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autorizado']);
    exit;
}

// Obtém o usuário autenticado
$usuarioAtual = $auth->getUsuarioAutenticado();

// Apenas super admin pode ver logs
if ($usuarioAtual['tipo'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}

try {
    // Processa filtros
    $filtros = [];
    if (!empty($_GET['dataInicio'])) $filtros['dataInicio'] = $_GET['dataInicio'];
    if (!empty($_GET['dataFim'])) $filtros['dataFim'] = $_GET['dataFim'];
    if (!empty($_GET['usuarioId'])) $filtros['usuarioId'] = $_GET['usuarioId'];
    if (!empty($_GET['acao'])) $filtros['acao'] = $_GET['acao'];

    $logs = $auth->listarLogs($filtros);
    
    // Enriquece os logs com dados dos usuários
    $logsCompletos = array_map(function($log) use ($auth) {
        $usuario = $auth->buscarUsuarioPorId($log['usuarioId']);
        return array_merge($log, [
            'usuario' => $usuario ? [
                'nome' => $usuario['nome'],
                'email' => $usuario['email']
            ] : null
        ]);
    }, $logs);

    echo json_encode($logsCompletos);
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 400);
    echo json_encode(['erro' => $e->getMessage()]);
} 