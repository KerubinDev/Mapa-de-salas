<?php
require_once 'config.php';
require_once 'middleware.php';

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
    // Cria diretório de backup se não existir
    $backupDir = __DIR__ . '/backups';
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0777, true);
    }

    // Gera nome do arquivo de backup
    $data = date('Y-m-d_H-i-s');
    $nomeArquivo = "backup_{$data}.json";
    $caminhoCompleto = "{$backupDir}/{$nomeArquivo}";

    // Obtém os dados do banco
    $db = JsonDatabase::getInstance();
    $dados = [
        'usuarios' => $db->getData('usuarios'),
        'salas' => $db->getData('salas'),
        'turmas' => $db->getData('turmas'),
        'reservas' => $db->getData('reservas'),
        'configuracoes' => $db->getData('configuracoes'),
        'logs' => $db->getData('logs'),
        'metadados' => [
            'versao' => '1.0',
            'data' => date('Y-m-d H:i:s'),
            'usuario' => $usuario['nome'],
            'email' => $usuario['email']
        ]
    ];

    // Salva o arquivo de backup
    if (!file_put_contents($caminhoCompleto, json_encode($dados, JSON_PRETTY_PRINT))) {
        throw new Exception('Erro ao salvar arquivo de backup');
    }

    // Atualiza a data do último backup nas configurações
    $configsExistentes = $db->query('configuracoes', ['chave' => 'ultimoBackup']);
    if (empty($configsExistentes)) {
        $db->insert('configuracoes', [
            'chave' => 'ultimoBackup',
            'valor' => date('Y-m-d H:i:s')
        ]);
    } else {
        $config = reset($configsExistentes);
        $db->update('configuracoes', $config['id'], [
            'valor' => date('Y-m-d H:i:s')
        ]);
    }

    // Registra o backup no log
    $db->insert('logs', [
        'usuarioId' => $usuario['id'],
        'acao' => 'backup',
        'detalhes' => "Backup realizado: {$nomeArquivo}",
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
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