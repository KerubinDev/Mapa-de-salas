<?php
require_once 'config.php';
require_once 'middleware.php';
require_once __DIR__ . '/../database/Database.php';

// Verifica autenticação
try {
    $usuario = verificarAutenticacao();
    if ($usuario['tipo'] !== 'admin') {
        responderErro('Acesso negado', 403);
    }
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode());
}

try {
    // Verifica se foi enviado um arquivo
    if (!isset($_FILES['backup']) || $_FILES['backup']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Nenhum arquivo de backup enviado');
    }

    $arquivo = $_FILES['backup'];
    
    // Verifica o tipo do arquivo
    if (pathinfo($arquivo['name'], PATHINFO_EXTENSION) !== 'sql') {
        throw new Exception('O arquivo deve ter extensão .sql');
    }

    // Verifica o tamanho do arquivo (máximo 10MB)
    if ($arquivo['size'] > 10 * 1024 * 1024) {
        throw new Exception('O arquivo é muito grande (máximo 10MB)');
    }

    // Lê o conteúdo do arquivo
    $sql = file_get_contents($arquivo['tmp_name']);
    if ($sql === false) {
        throw new Exception('Erro ao ler arquivo de backup');
    }

    $db = Database::getInstance()->getConnection();
    
    // Inicia uma transação
    $db->beginTransaction();
    
    try {
        // Desativa as chaves estrangeiras temporariamente
        $db->exec('PRAGMA foreign_keys = OFF');

        // Limpa todas as tabelas
        $tabelas = ['usuarios', 'salas', 'turmas', 'reservas', 'configuracoes', 'logs'];
        foreach ($tabelas as $tabela) {
            $db->exec("DELETE FROM {$tabela}");
        }

        // Executa os comandos SQL do backup
        $comandos = array_filter(
            explode(";\n", $sql),
            function($cmd) { return trim($cmd) !== ''; }
        );

        foreach ($comandos as $comando) {
            if (trim($comando) !== '') {
                $db->exec($comando);
            }
        }

        // Reativa as chaves estrangeiras
        $db->exec('PRAGMA foreign_keys = ON');

        // Registra a restauração no log
        $stmt = $db->prepare('
            INSERT INTO logs (id, usuario_id, acao, detalhes, ip, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            uniqid(),
            $usuario['id'],
            'restore',
            "Backup restaurado: {$arquivo['name']}",
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);

        $db->commit();
        
        responderJson([
            'mensagem' => 'Backup restaurado com sucesso',
            'arquivo' => $arquivo['name']
        ]);

    } catch (Exception $e) {
        // Em caso de erro, tenta reverter para o estado anterior
        $db->rollBack();
        
        // Reativa as chaves estrangeiras
        $db->exec('PRAGMA foreign_keys = ON');
        
        throw new Exception('Erro ao restaurar backup: ' . $e->getMessage());
    }

} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 