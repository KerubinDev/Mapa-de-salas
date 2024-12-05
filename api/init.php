<?php
require_once __DIR__ . '/../database/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Inicia uma transação
    $db->beginTransaction();
    
    // Cria o usuário admin se não existir
    $stmt = $db->prepare('SELECT id FROM usuarios WHERE email = ?');
    $stmt->execute(['admin@sistema.local']);
    
    if (!$stmt->fetch()) {
        $stmt = $db->prepare('
            INSERT INTO usuarios (id, nome, email, senha, tipo)
            VALUES (?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            'admin',
            'Administrador',
            'admin@sistema.local',
            password_hash('admin123', PASSWORD_DEFAULT),
            'admin'
        ]);
    }
    
    // Insere configurações padrão
    $configuracoesDefault = [
        'horario_abertura' => '07:00',
        'horario_fechamento' => '22:00',
        'dias_funcionamento' => '1,2,3,4,5',
        'duracao_minima' => '15',
        'intervalo_reservas' => '0',
        'notificar_reservas' => '0',
        'notificar_cancelamentos' => '0',
        'notificar_conflitos' => '0',
        'backup_automatico' => '0'
    ];
    
    $stmt = $db->prepare('
        INSERT OR IGNORE INTO configuracoes (chave, valor)
        VALUES (?, ?)
    ');
    
    foreach ($configuracoesDefault as $chave => $valor) {
        $stmt->execute([$chave, $valor]);
    }
    
    // Cria diretórios necessários
    $diretorios = [
        __DIR__ . '/backups',
        __DIR__ . '/logs'
    ];
    
    foreach ($diretorios as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }
    
    $db->commit();
    echo "Sistema inicializado com sucesso!\n";
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo "Erro ao inicializar o sistema: " . $e->getMessage() . "\n";
    exit(1);
} 