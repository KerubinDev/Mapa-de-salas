<?php
require_once __DIR__ . '/../database/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Verificando banco de dados...\n\n";
    
    // Verifica se as tabelas existem
    $tabelas = [
        'usuarios',
        'sessoes',
        'logs',
        'salas',
        'turmas',
        'reservas',
        'configuracoes'
    ];
    
    foreach ($tabelas as $tabela) {
        $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$tabela'");
        if (!$stmt->fetch()) {
            echo "ERRO: Tabela '$tabela' não encontrada!\n";
            exit(1);
        }
        echo "OK: Tabela '$tabela' existe\n";
    }
    
    // Verifica se o usuário admin existe
    $stmt = $db->query("SELECT * FROM usuarios WHERE email='admin@sistema.local'");
    $admin = $stmt->fetch();
    if (!$admin) {
        echo "ERRO: Usuário admin não encontrado!\n";
        exit(1);
    }
    echo "OK: Usuário admin existe\n";
    
    // Verifica se a senha do admin está correta
    if (!password_verify('admin123', $admin['senha'])) {
        echo "ERRO: Senha do admin está incorreta!\n";
        
        // Corrige a senha do admin
        $stmt = $db->prepare('UPDATE usuarios SET senha = ? WHERE email = ?');
        $stmt->execute([
            password_hash('admin123', PASSWORD_DEFAULT),
            'admin@sistema.local'
        ]);
        echo "OK: Senha do admin corrigida\n";
    }
    
    // Verifica se o banco está vazio
    foreach ($tabelas as $tabela) {
        $stmt = $db->query("SELECT COUNT(*) as total FROM $tabela");
        $count = $stmt->fetch()['total'];
        echo "INFO: Tabela '$tabela' tem $count registros\n";
    }
    
    echo "\nBanco de dados verificado com sucesso!\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
} 