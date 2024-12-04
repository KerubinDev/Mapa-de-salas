<?php
require_once 'config.php';
require_once 'middleware.php';

// Define o tipo de conteúdo como JSON
header('Content-Type: application/json');

// Tratamento específico para OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit(0);
}

// Verifica autenticação
try {
    $usuario = verificarAutenticacao();
    if ($usuario['tipo'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['erro' => 'Acesso negado']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autorizado']);
    exit;
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

    // Lê os dados atuais
    $dados = lerDados();

    // Adiciona metadados ao backup
    $backup = [
        'data' => date('Y-m-d H:i:s'),
        'usuario' => $usuario['nome'],
        'dados' => $dados
    ];

    // Salva o arquivo de backup
    if (!file_put_contents($caminhoCompleto, json_encode($backup, JSON_PRETTY_PRINT))) {
        throw new Exception('Erro ao salvar arquivo de backup');
    }

    // Atualiza a data do último backup nas configurações
    if (!isset($dados['configuracoes'])) {
        $dados['configuracoes'] = [];
    }
    $dados['configuracoes']['ultimoBackup'] = date('Y-m-d H:i:s');
    salvarDados($dados);

    // Registra o backup no log
    $auth = AuthManager::getInstance();
    $auth->registrarLog($usuario['id'], 'backup', "Backup realizado: {$nomeArquivo}");

    // Retorna sucesso
    responderJson([
        'mensagem' => 'Backup realizado com sucesso',
        'arquivo' => $nomeArquivo,
        'data' => $dados['configuracoes']['ultimoBackup']
    ]);
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 