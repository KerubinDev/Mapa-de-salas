<?php
require_once __DIR__ . '/../database/JsonDatabase.php';

// Configurações gerais
define('APP_NAME', 'Sistema de Reservas');
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'America/Sao_Paulo');
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_DEBUG', APP_ENV === 'development');

// Configurações de data/hora
date_default_timezone_set(APP_TIMEZONE);

// Desativa a exibição de erros
error_reporting(0);
ini_set('display_errors', 0);

// Log de erros
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Configurações de cabeçalhos
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// Handler de erros personalizado
function errorHandler($errno, $errstr, $errfile, $errline) {
    error_log("Erro [$errno] $errstr em $errfile:$errline");
    
    if (APP_DEBUG) {
        responderErro("Erro interno do servidor: $errstr", 500);
    } else {
        responderErro('Erro interno do servidor', 500);
    }
}

// Handler de exceções personalizado
function exceptionHandler($e) {
    error_log($e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
    
    if (APP_DEBUG) {
        responderErro($e->getMessage(), $e->getCode() ?: 500);
    } else {
        responderErro('Erro interno do servidor', 500);
    }
}

// Registra os handlers
set_error_handler('errorHandler');
set_exception_handler('exceptionHandler');

// Funções auxiliares
function responderJson($dados, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode([
        'sucesso' => true,
        'dados' => $dados
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function responderErro($mensagem, $codigo = 400) {
    http_response_code($codigo);
    echo json_encode([
        'sucesso' => false,
        'erro' => [
            'codigo' => $codigo,
            'mensagem' => $mensagem
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Instância global do banco de dados
$db = JsonDatabase::getInstance();
 