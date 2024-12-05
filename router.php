<?php
// Configurações de erro para desenvolvimento
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Carrega as dependências
require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/database/Database.php';

// Define as rotas da API
$rotas = [
    'POST:/api/auth/login' => 'api/auth/login.php',
    'POST:/api/auth/logout' => 'api/auth/logout.php',
    'POST:/api/auth/registro' => 'api/auth/registro.php',
    'GET:/api/auth/verificar' => 'api/auth/verificar.php',
    'GET:/api/auth/perfil' => 'api/auth/perfil.php',
    'PUT:/api/auth/perfil' => 'api/auth/perfil.php',
    'GET:/api/sala' => 'api/sala.php',
    'POST:/api/sala' => 'api/sala.php',
    'PUT:/api/sala' => 'api/sala.php',
    'DELETE:/api/sala' => 'api/sala.php',
    // ... outras rotas
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
    require $rotas[$rotaChave];
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
        echo '404 - Página não encontrada';
    }
 