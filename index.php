<?php
// Configurações de erro para desenvolvimento
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Função para obter o MIME type correto
function getMimeType($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mimeTypes = [
        'js' => 'application/javascript',
        'css' => 'text/css',
        'html' => 'text/html',
        'json' => 'application/json',
        'php' => 'text/html'
    ];
    return $mimeTypes[$ext] ?? 'text/plain';
}

// Roteamento básico
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = ltrim($uri, '/');

// Se for um arquivo estático (js, css, etc)
if (preg_match('/\.(js|css|html)$/', $uri)) {
    $arquivo = __DIR__ . '/' . $uri;
    if (file_exists($arquivo)) {
        header('Content-Type: ' . getMimeType($arquivo));
        readfile($arquivo);
        exit;
    }
}

// Mapeamento de rotas para arquivos PHP
$rotas = [
    'api/auth/login' => 'api/auth/login.php',
    'api/auth/logout' => 'api/auth/logout.php',
    'api/sala' => 'api/sala.php',
    'api/turma' => 'api/turma.php',
    'api/reserva' => 'api/reserva.php'
];

// Processa a rota
foreach ($rotas as $rota => $arquivo) {
    if (strpos($uri, $rota) === 0) {
        if (file_exists($arquivo)) {
            require $arquivo;
            exit;
        }
    }
}

// Se não for uma rota da API, serve o arquivo index.html
if (!strpos($uri, 'api/')) {
    header('Content-Type: text/html');
    require 'index.html';
} 