<?php
require_once __DIR__ . '/../database/JsonDatabase.php';

// Configurações gerais
define('APP_NAME', 'Sistema de Reservas');
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'America/Sao_Paulo');

// Configurações de ambiente
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_DEBUG', APP_ENV === 'development');

// Configurações de data/hora
date_default_timezone_set(APP_TIMEZONE);

// Configurações de erro
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configurações de sessão
session_start();

// Configurações de cabeçalhos
header('Content-Type: application/json; charset=UTF-8');

// Instância global do banco de dados
$db = JsonDatabase::getInstance();

/**
 * Responde com JSON
 */
function responderJson($dados, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode([
        'sucesso' => true,
        'dados' => $dados
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Responde com erro
 */
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

/**
 * Função de log para debug
 */
function debug($dados) {
    if (APP_DEBUG) {
        error_log(print_r($dados, true));
    }
}

/**
 * Função para validar data
 */
function validarData($data) {
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d && $d->format('Y-m-d') === $data;
}

/**
 * Função para validar horário
 */
function validarHorario($horario) {
    if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $horario)) {
        return false;
    }
    
    list($hora, $minuto) = explode(':', $horario);
    return ($minuto % 15) === 0;
}

/**
 * Função para formatar data/hora
 */
function formatarDataHora($data, $formato = 'd/m/Y H:i') {
    return date($formato, strtotime($data));
}
 