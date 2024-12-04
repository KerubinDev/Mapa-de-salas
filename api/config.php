<?php
/**
 * Configurações globais do sistema
 */

// Configurações de CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Content-Type: application/json');
    exit(0);
}

// Configurações padrão para outras requisições
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Constantes do sistema
define('ARQUIVO_DB', __DIR__ . '/database.json');
define('HORARIOS_VALIDOS', [
    '07:30 - 08:20', '08:20 - 09:10', '09:10 - 10:00',
    '10:20 - 11:10', '11:10 - 12:00',
    '13:30 - 14:20', '14:20 - 15:10', '15:10 - 16:00',
    '16:20 - 17:10', '17:10 - 18:00',
    '19:00 - 19:50', '19:50 - 20:40', '20:40 - 21:30',
    '21:40 - 22:30'
]);

// Verifica permissões do arquivo de banco de dados
if (!file_exists(ARQUIVO_DB)) {
    file_put_contents(ARQUIVO_DB, json_encode([
        'salas' => [],
        'turmas' => [],
        'reservas' => []
    ], JSON_PRETTY_PRINT));
    chmod(ARQUIVO_DB, 0666);
}

/**
 * Funções auxiliares
 */
function lerDados() {
    $arquivo = __DIR__ . '/database.json';
    if (!file_exists($arquivo)) {
        return [];
    }
    return json_decode(file_get_contents($arquivo), true) ?: [];
}

function salvarDados($dados) {
    $arquivo = __DIR__ . '/database.json';
    return file_put_contents($arquivo, json_encode($dados, JSON_PRETTY_PRINT));
}

function responderJson($dados) {
    echo json_encode($dados);
    exit;
}

function responderErro($mensagem, $codigo = 400) {
    http_response_code($codigo);
    echo json_encode(['erro' => $mensagem]);
    exit;
} 