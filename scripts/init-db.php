<?php
require_once __DIR__ . '/../database/JsonDatabase.php';

try {
    $db = JsonDatabase::getInstance();
    
    // Dados do usuário root
    $usuarioRoot = [
        'nome' => 'Administrador',
        'email' => 'admin@sistema.com',
        'senha' => password_hash('admin123', PASSWORD_DEFAULT),
        'tipo' => 'admin',
        'dataCriacao' => date('Y-m-d H:i:s'),
        'status' => 'ativo'
    ];
    
    // Verifica se já existe um usuário admin
    $usuarios = $db->query('usuarios', ['email' => $usuarioRoot['email']]);
    
    if (empty($usuarios)) {
        // Cria o usuário admin
        $db->insert('usuarios', $usuarioRoot);
        echo "Usuário admin criado com sucesso!\n";
        echo "Email: admin@sistema.com\n";
        echo "Senha: admin123\n";
    } else {
        // Atualiza a senha do admin
        $admin = reset($usuarios);
        $db->update('usuarios', $admin['id'], [
            'senha' => $usuarioRoot['senha'],
            'dataAtualizacao' => date('Y-m-d H:i:s')
        ]);
        
        echo "Senha do admin atualizada com sucesso!\n";
        echo "Email: admin@sistema.com\n";
        echo "Senha: admin123\n";
    }
    
    echo "\nVocê já pode fazer login no sistema.\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
} 