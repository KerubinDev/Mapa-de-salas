<?php
require_once __DIR__ . '/../database/JsonDatabase.php';

try {
    $db = JsonDatabase::getInstance();
    
    echo "Verificando banco de dados...\n\n";
    
    // Verifica se as coleções existem
    $colecoes = [
        'usuarios',
        'salas',
        'turmas',
        'reservas',
        'configuracoes',
        'logs'
    ];
    
    foreach ($colecoes as $colecao) {
        $dados = $db->getData($colecao);
        if ($dados === null) {
            throw new Exception("Coleção '$colecao' não encontrada");
        }
        echo "OK: Coleção '$colecao' encontrada\n";
    }
    
    // Verifica se existe usuário admin
    $usuarios = $db->query('usuarios', ['email' => 'admin@sistema.local']);
    if (empty($usuarios)) {
        throw new Exception("Usuário admin não encontrado");
    }
    $admin = reset($usuarios);
    if ($admin['tipo'] !== 'admin') {
        throw new Exception("Usuário admin com tipo incorreto");
    }
    echo "OK: Usuário admin encontrado\n";
    
    // Verifica configurações básicas
    $configsNecessarias = [
        'horarioAbertura',
        'horarioFechamento',
        'diasFuncionamento'
    ];
    
    foreach ($configsNecessarias as $chave) {
        $configs = $db->query('configuracoes', ['chave' => $chave]);
        if (empty($configs)) {
            throw new Exception("Configuração '$chave' não encontrada");
        }
        echo "OK: Configuração '$chave' encontrada\n";
    }
    
    // Verifica se o banco está vazio
    foreach ($colecoes as $colecao) {
        $total = count($db->getData($colecao));
        echo "INFO: Coleção '$colecao' tem $total registros\n";
    }
    
    echo "\nBanco de dados verificado com sucesso!\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
} 