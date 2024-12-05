<?php
require_once __DIR__ . '/api/config.php';

// Configurações de erro para desenvolvimento
if (APP_DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Log para debug
error_log("Requisição recebida: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);

// Mapeia extensões para tipos MIME
$mimeTypes = [
    'js' => 'application/javascript',
    'webmanifest' => 'application/manifest+json',
    'json' => 'application/json',
    'css' => 'text/css',
    'html' => 'text/html',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'gif' => 'image/gif'
];

// Obtém o método e caminho da requisição
$metodo = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = '/' . trim($uri, '/');

// Log para debug
error_log("Método: $metodo, URI: $uri");

// Define as rotas da API
$rotas = [
    // Rotas de autenticação
    'POST:/api/auth/login' => 'api/auth/login.php',
    'POST:/api/auth/logout' => 'api/auth/logout.php',
    'GET:/api/auth/perfil' => 'api/auth/perfil.php',
    'PUT:/api/auth/perfil' => 'api/auth/perfil.php',
    
    // Rotas de salas
    'GET:/api/sala' => 'api/sala.php',
    'POST:/api/sala' => 'api/sala.php',
    'PUT:/api/sala' => 'api/sala.php',
    'DELETE:/api/sala' => 'api/sala.php',
    
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

// Verifica se é um arquivo estático
$ext = pathinfo($uri, PATHINFO_EXTENSION);
if ($ext && file_exists(__DIR__ . $uri)) {
    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
    }
    readfile(__DIR__ . $uri);
    exit;
}

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
        error_log("Arquivo não encontrado!");
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
            'rotaChave' => $rotaChave
        ]
    ]
]);
 