<?php
/**
 * Configurações globais da aplicação
 */

// Configurações do banco de dados
define('DB_FILE', __DIR__ . '/database/data.json');

// Configurações de autenticação
define('JWT_SECRET', 'sua_chave_secreta_aqui');
define('JWT_EXPIRATION', 3600); // 1 hora em segundos

// Configurações de diretórios
define('BASE_DIR', __DIR__);
define('API_DIR', __DIR__ . '/api');

// Configurações de timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de erro
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Funções utilitárias globais
function responderJson($dados, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function responderErro($mensagem, $codigo = 400) {
    responderJson([
        'sucesso' => false,
        'erro' => [
            'codigo' => $codigo,
            'mensagem' => $mensagem
        ]
    ], $codigo);
} 