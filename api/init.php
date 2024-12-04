<?php
if (!file_exists(__DIR__ . '/database.json')) {
    $dados = [
        'usuarios' => [],
        'salas' => [],
        'turmas' => [],
        'reservas' => []
    ];
    file_put_contents(__DIR__ . '/database.json', json_encode($dados, JSON_PRETTY_PRINT));
    chmod(__DIR__ . '/database.json', 0666);
} 