<?php
require_once '../config.php';
require_once '../middleware.php';
require_once __DIR__ . '/../../database/Database.php';

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
    
    // Monta a query base
    $sql = '
        SELECT l.*, u.nome as usuario_nome, u.email as usuario_email
        FROM logs l
        LEFT JOIN usuarios u ON l.usuario_id = u.id
        WHERE 1=1
    ';
    $params = [];
    
    // Aplica filtros
    if (!empty($_GET['dataInicio'])) {
        $sql .= ' AND l.data_criacao >= ?';
        $params[] = $_GET['dataInicio'];
    }
    
    if (!empty($_GET['dataFim'])) {
        $sql .= ' AND l.data_criacao <= ?';
        $params[] = $_GET['dataFim'];
    }
    
    if (!empty($_GET['usuarioId'])) {
        $sql .= ' AND l.usuario_id = ?';
        $params[] = $_GET['usuarioId'];
    }
    
    if (!empty($_GET['acao'])) {
        $sql .= ' AND l.acao = ?';
        $params[] = $_GET['acao'];
    }
    
    // Ordena por data decrescente
    $sql .= ' ORDER BY l.data_criacao DESC';
    
    // Aplica paginação se solicitado
    if (isset($_GET['limite'])) {
        $limite = (int)$_GET['limite'];
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $offset = ($pagina - 1) * $limite;
        
        $sql .= ' LIMIT ? OFFSET ?';
        $params[] = $limite;
        $params[] = $offset;
    }
    
    // Executa a query
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // Formata os dados para retorno
    $logs = array_map(function($log) {
        return [
            'id' => $log['id'],
            'usuario' => [
                'id' => $log['usuario_id'],
                'nome' => $log['usuario_nome'],
                'email' => $log['usuario_email']
            ],
            'acao' => $log['acao'],
            'detalhes' => $log['detalhes'],
            'ip' => $log['ip'],
            'userAgent' => $log['user_agent'],
            'data' => $log['data_criacao']
        ];
    }, $logs);
    
    responderJson($logs);
    
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 