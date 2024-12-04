<?php
// Configurações de erro para desenvolvimento
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Roteamento básico
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = ltrim($uri, '/');

// Mapeamento de rotas para arquivos PHP
$rotas = [
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
    require 'index.html';
} 