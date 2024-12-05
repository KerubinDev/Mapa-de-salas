<?php
// Configurações de erro para desenvolvimento
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Carrega as dependências
require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/database/Database.php';

// Tratamento de CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratamento específico para OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Define as rotas da API
$rotas = [
    // Rotas de autenticação
    'POST:/api/auth/login' => 'api/auth/login.php',
    'POST:/api/auth/logout' => 'api/auth/logout.php',
    'POST:/api/auth/registro' => 'api/auth/registro.php',
    'GET:/api/auth/verificar' => 'api/auth/verificar.php',
    'GET:/api/auth/perfil' => 'api/auth/perfil.php',
    'PUT:/api/auth/perfil' => 'api/auth/perfil.php',
    'POST:/api/auth/senha' => 'api/auth/senha.php',
    'PUT:/api/auth/senha' => 'api/auth/senha.php',
    
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
    'DELETE:/api/reserva' => 'api/reserva.php',
    'GET:/api/reserva/verificar' => 'api/reserva.php',
    
    // Rotas de configurações
    'GET:/api/configuracoes' => 'api/configuracoes.php',
    'POST:/api/configuracoes' => 'api/configuracoes.php',
    
    // Rotas de backup
    'POST:/api/backup' => 'api/backup.php',
    'POST:/api/restore' => 'api/restore.php',
    
    // Rotas de logs
    'GET:/api/logs' => 'api/auth/logs.php'
];

// Obtém o método e caminho da requisição
$metodo = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove barras duplicadas e trailing slash
$uri = '/' . trim($uri, '/');

// Verifica se é uma rota da API
$rotaChave = "{$metodo}:{$uri}";

if (isset($rotas[$rotaChave])) {
    // É uma rota da API
    $arquivo = __DIR__ . '/' . $rotas[$rotaChave];
    if (file_exists($arquivo)) {
        require $arquivo;
    } else {
        header('HTTP/1.1 404 Not Found');
        header('Content-Type: application/json');
        echo json_encode(['erro' => 'Endpoint não encontrado']);
    }
    exit;
} else if (preg_match('/\.(js|css|png|jpg|gif|ico|svg|webmanifest)$/', $uri)) {
    // É um arquivo estático
    $arquivo = __DIR__ . $uri;
    if (file_exists($arquivo)) {
        $ext = pathinfo($arquivo, PATHINFO_EXTENSION);
        $mimeTypes = [
            'js' => 'application/javascript',
            'css' => 'text/css',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
            'webmanifest' => 'application/manifest+json'
        ];
        
        header('Content-Type: ' . ($mimeTypes[$ext] ?? 'text/plain'));
        readfile($arquivo);
        exit;
    }
} else {
    // Serve o arquivo HTML apropriado
    $arquivo = __DIR__;
    
    if ($uri === '/') {
        $arquivo .= '/index.html';
    } else if (preg_match('/\.(html?)$/', $uri)) {
        $arquivo .= $uri;
    } else {
        $arquivo .= $uri . '.html';
    }
    
    if (file_exists($arquivo)) {
        header('Content-Type: text/html');
        readfile($arquivo);
    } else {
        header('HTTP/1.1 404 Not Found');
        header('Content-Type: text/html');
        echo '<h1>404 - Página não encontrada</h1>';
    }
}
 