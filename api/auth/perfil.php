<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../middleware.php';

// Verifica autenticação
$usuario = verificarAutenticacao();

// Retorna os dados do usuário
responderJson([
    'sucesso' => true,
    'dados' => [
        'id' => $usuario['id'],
        'nome' => $usuario['nome'],
        'email' => $usuario['email'],
        'tipo' => $usuario['tipo']
    ]
]); 