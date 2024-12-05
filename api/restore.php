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
    // Verifica se foi enviado um arquivo
    if (!isset($_FILES['backup']) || $_FILES['backup']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Nenhum arquivo de backup enviado');
    }

    $arquivo = $_FILES['backup'];
    
    // Verifica o tipo do arquivo
    if (pathinfo($arquivo['name'], PATHINFO_EXTENSION) !== 'json') {
        throw new Exception('O arquivo deve ter extensão .json');
    }

    // Verifica o tamanho do arquivo (máximo 10MB)
    if ($arquivo['size'] > 10 * 1024 * 1024) {
        throw new Exception('O arquivo é muito grande (máximo 10MB)');
    }

    // Lê o conteúdo do arquivo
    $conteudo = file_get_contents($arquivo['tmp_name']);
    if ($conteudo === false) {
        throw new Exception('Erro ao ler arquivo de backup');
    }

    // Decodifica o JSON
    $dados = json_decode($conteudo, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Arquivo de backup inválido');
    }

    // Verifica se todas as coleções necessárias existem
    $colecoesNecessarias = ['usuarios', 'salas', 'turmas', 'reservas', 'configuracoes', 'logs'];
    foreach ($colecoesNecessarias as $colecao) {
        if (!isset($dados[$colecao])) {
            throw new Exception("Coleção {$colecao} não encontrada no backup");
        }
    }

    // Restaura os dados
    $db = JsonDatabase::getInstance();
    foreach ($colecoesNecessarias as $colecao) {
        $db->setData($colecao, $dados[$colecao]);
    }

    // Registra a restauração no log
    $db->insert('logs', [
        'usuarioId' => $usuario['id'],
        'acao' => 'restore',
        'detalhes' => "Backup restaurado: {$arquivo['name']}",
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    responderJson([
        'mensagem' => 'Backup restaurado com sucesso',
        'arquivo' => $arquivo['name']
    ]);

} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 