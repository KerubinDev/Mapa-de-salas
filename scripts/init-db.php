<?php
require_once __DIR__ . '/../database/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Lê e executa o schema
    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    
    // Divide o schema em comandos individuais
    $comandos = array_filter(
        explode(';', $schema),
        function($cmd) { return trim($cmd) !== ''; }
    );
    
    // Executa cada comando
    foreach ($comandos as $comando) {
        $db->exec($comando);
    }
    
    // Cria o usuário admin com senha padrão
    $stmt = $db->prepare('
        UPDATE usuarios 
        SET senha = ? 
        WHERE email = ?
    ');
    
    $stmt->execute([
        password_hash('admin123', PASSWORD_DEFAULT),
        'admin@sistema.local'
    ]);
    
    echo "Banco de dados inicializado com sucesso!\n";
    
} catch (Exception $e) {
    echo "Erro ao inicializar banco de dados: " . $e->getMessage() . "\n";
    exit(1);
} 