<?php
require_once __DIR__ . '/../database/JsonDatabase.php';

try {
    $db = JsonDatabase::getInstance();
    
    // Verifica se já existe um usuário admin
    $usuarios = $db->query('usuarios', ['email' => 'admin@sistema.local']);
    
    if (empty($usuarios)) {
        // Cria o usuário admin
        $db->insert('usuarios', [
            'nome' => 'Administrador',
            'email' => 'admin@sistema.local',
            'senha' => password_hash('admin123', PASSWORD_DEFAULT),
            'tipo' => 'admin'
        ]);
        
        echo "Usuário admin criado com sucesso!\n";
    } else {
        // Atualiza a senha do admin
        $admin = reset($usuarios);
        $db->update('usuarios', $admin['id'], [
            'senha' => password_hash('admin123', PASSWORD_DEFAULT)
        ]);
        
        echo "Senha do admin atualizada com sucesso!\n";
    }
    
    // Cria configurações padrão
    $configuracoes = [
        'horarioAbertura' => '07:00',
        'horarioFechamento' => '22:00',
        'diasFuncionamento' => [1,2,3,4,5],
        'duracaoMinima' => 15,
        'intervaloReservas' => 0,
        'notificarReservas' => false,
        'notificarCancelamentos' => false,
        'notificarConflitos' => false,
        'backupAutomatico' => false
    ];
    
    foreach ($configuracoes as $chave => $valor) {
        $configExistente = $db->query('configuracoes', ['chave' => $chave]);
        if (empty($configExistente)) {
            $db->insert('configuracoes', [
                'chave' => $chave,
                'valor' => is_array($valor) ? implode(',', $valor) : $valor
            ]);
        }
    }
    
    echo "Configurações inicializadas com sucesso!\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
} 