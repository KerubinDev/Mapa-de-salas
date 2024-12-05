<?php
require_once __DIR__ . '/api/config.php';

// Configurações de erro para desenvolvimento
if (APP_DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Log para debug
error_log("Requisição recebida: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);

// Obtém o método e caminho da requisição
$metodo = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove o prefixo do path base se existir
$basePath = '/api';
if (strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}
$uri = '/' . trim($uri, '/');

error_log("URI processada: $uri");

// Define as rotas da API
$rotas = [
    // Rotas de autenticação
    'POST:/auth/login' => 'api/auth/login.php',
    'POST:/auth/logout' => 'api/auth/logout.php',
    'GET:/auth/perfil' => 'api/auth/perfil.php',
    'PUT:/auth/perfil' => 'api/auth/perfil.php',
    
    // Rotas de salas
    'GET:/sala' => 'api/sala.php',
    'POST:/sala' => 'api/sala.php',
    'PUT:/sala' => 'api/sala.php',
    'DELETE:/sala' => 'api/sala.php',
    
    // Rotas de turmas
    'GET:/api/turma' => 'api/turma.php',
    'POST:/api/turma' => 'api/turma.php',
    'PUT:/api/turma' => 'api/turma.php',
    'DELETE:/api/turma' => 'api/turma.php',
    
    // Rotas de reservas
    'GET:/api/reserva' => 'api/reserva.php',
    'POST:/api/reserva' => 'api/reserva.php',
    'PUT:/api/reserva' => 'api/reserva.php',
    'DELETE:/api/reserva' => 'api/reserva.php'
];

// Verifica se é uma rota da API
$rotaChave = "{$metodo}:{$uri}";
error_log("Procurando rota: $rotaChave");

if (isset($rotas[$rotaChave])) {
    $arquivo = __DIR__ . '/' . $rotas[$rotaChave];
    error_log("Arquivo a ser carregado: $arquivo");
    
    if (file_exists($arquivo)) {
        error_log("Arquivo encontrado, carregando...");
        require $arquivo;
        exit;
    } else {
        error_log("Arquivo não encontrado: $arquivo");
    }
}

// Se chegou aqui, retorna 404
error_log("Nenhuma rota encontrada para: $rotaChave");
http_response_code(404);
header('Content-Type: application/json');
echo json_encode([
    'sucesso' => false,
    'erro' => [
        'codigo' => 404,
        'mensagem' => 'Rota não encontrada',
        'detalhes' => [
            'metodo' => $metodo,
            'uri' => $uri,
            'rotaChave' => $rotaChave,
            'requestUri' => $_SERVER['REQUEST_URI']
        ]
    ]
]);
 