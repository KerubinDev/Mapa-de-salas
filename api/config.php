<?php
/**
 * Configurações globais do sistema
 */

// Configurações de CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json');
    exit(0);
}

// Configurações padrão para outras requisições
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Constantes do sistema
define('HORARIOS_VALIDOS', [
    '07:30 - 08:20', '08:20 - 09:10', '09:10 - 10:00',
    '10:20 - 11:10', '11:10 - 12:00',
    '13:30 - 14:20', '14:20 - 15:10', '15:10 - 16:00',
    '16:20 - 17:10', '17:10 - 18:00',
    '19:00 - 19:50', '19:50 - 20:40', '20:40 - 21:30',
    '21:40 - 22:30'
]);

/**
 * Funções auxiliares
 */
function responderJson($dados, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($dados);
    exit;
}

function responderErro($mensagem, $codigo = 400) {
    http_response_code($codigo);
    echo json_encode(['erro' => $mensagem]);
    exit;
} 