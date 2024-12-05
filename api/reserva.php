<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/middleware.php';

// Verifica autenticação
$usuario = verificarAutenticacao();

try {
    $db = JsonDatabase::getInstance();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $reservas = $db->getData('reservas');
            responderJson($reservas);
            break;
            
        default:
            throw new Exception('Método não permitido', 405);
    }
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 