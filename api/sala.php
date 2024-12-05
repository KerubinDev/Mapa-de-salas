<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/middleware.php';

// Verifica autenticação
$usuario = verificarAutenticacao();

// Lê o arquivo de banco de dados
$salas = [];
if (file_exists(DB_FILE)) {
    $db = json_decode(file_get_contents(DB_FILE), true);
    $salas = $db['salas'] ?? [];
}

// Retorna a lista de salas
responderJson([
    'sucesso' => true,
    'dados' => $salas
]); 