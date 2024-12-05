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
    $db = Database::getInstance()->getConnection();
    
    // Cria diretório de backup se não existir
    $backupDir = __DIR__ . '/backups';
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0777, true);
    }

    // Gera nome do arquivo de backup
    $data = date('Y-m-d_H-i-s');
    $nomeArquivo = "backup_{$data}.sql";
    $caminhoCompleto = "{$backupDir}/{$nomeArquivo}";

    // Inicia o arquivo de backup
    $backup = "-- Backup gerado em " . date('Y-m-d H:i:s') . "\n";
    $backup .= "-- Usuário: {$usuario['nome']}\n\n";

    // Tabelas para backup
    $tabelas = ['usuarios', 'salas', 'turmas', 'reservas', 'configuracoes', 'logs'];

    foreach ($tabelas as $tabela) {
        $backup .= "\n-- Dados da tabela {$tabela}\n";
        
        // Obtém a estrutura da tabela
        $stmt = $db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='{$tabela}'");
        $estrutura = $stmt->fetch();
        $backup .= $estrutura['sql'] . ";\n\n";

        // Obtém os dados da tabela
        $stmt = $db->query("SELECT * FROM {$tabela}");
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($dados as $registro) {
            $colunas = array_keys($registro);
            $valores = array_map(function($valor) use ($db) {
                if ($valor === null) return 'NULL';
                return $db->quote($valor);
            }, $registro);

            $backup .= "INSERT INTO {$tabela} (" . 
                      implode(', ', $colunas) . 
                      ") VALUES (" . 
                      implode(', ', $valores) . 
                      ");\n";
        }
    }

    // Salva o arquivo de backup
    if (!file_put_contents($caminhoCompleto, $backup)) {
        throw new Exception('Erro ao salvar arquivo de backup');
    }

    // Atualiza a data do último backup nas configurações
    $stmt = $db->prepare('
        INSERT OR REPLACE INTO configuracoes (chave, valor, data_atualizacao)
        VALUES (?, ?, CURRENT_TIMESTAMP)
    ');
    $stmt->execute(['ultimo_backup', date('Y-m-d H:i:s')]);

    // Registra o backup no log
    $stmt = $db->prepare('
        INSERT INTO logs (id, usuario_id, acao, detalhes, ip, user_agent)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        uniqid(),
        $usuario['id'],
        'backup',
        "Backup realizado: {$nomeArquivo}",
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    // Retorna sucesso
    responderJson([
        'mensagem' => 'Backup realizado com sucesso',
        'arquivo' => $nomeArquivo,
        'data' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 