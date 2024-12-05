<?php
require_once '../config.php';
require_once '../middleware.php';

// Verifica autenticação
try {
    $usuario = verificarAutenticacao();
    if ($usuario['tipo'] !== 'admin') {
        responderErro('Acesso negado', 403);
    }
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode());
}

class GerenciadorLogs {
    private $_db;
    
    public function __construct() {
        $this->_db = JsonDatabase::getInstance();
    }
    
    /**
     * Lista os logs do sistema
     */
    public function listar() {
        // Obtém parâmetros de filtro
        $filtros = [
            'usuarioId' => $_GET['usuario'] ?? null,
            'acao' => $_GET['acao'] ?? null,
            'dataInicio' => $_GET['inicio'] ?? null,
            'dataFim' => $_GET['fim'] ?? null
        ];
        
        // Remove filtros vazios
        $filtros = array_filter($filtros);
        
        // Busca todos os logs
        $logs = $this->_db->getData('logs');
        
        // Aplica filtros
        if (!empty($filtros)) {
            $logs = array_filter($logs, function($log) use ($filtros) {
                if (!empty($filtros['usuarioId']) && $log['usuarioId'] !== $filtros['usuarioId']) {
                    return false;
                }
                
                if (!empty($filtros['acao']) && $log['acao'] !== $filtros['acao']) {
                    return false;
                }
                
                if (!empty($filtros['dataInicio']) && 
                    strtotime($log['dataCriacao']) < strtotime($filtros['dataInicio'])) {
                    return false;
                }
                
                if (!empty($filtros['dataFim']) && 
                    strtotime($log['dataCriacao']) > strtotime($filtros['dataFim'])) {
                    return false;
                }
                
                return true;
            });
        }
        
        // Adiciona informações do usuário
        $usuarios = $this->_db->getData('usuarios');
        $usuariosIndex = array_column($usuarios, null, 'id');
        
        foreach ($logs as &$log) {
            if (isset($usuariosIndex[$log['usuarioId']])) {
                $usuario = $usuariosIndex[$log['usuarioId']];
                $log['usuario'] = [
                    'id' => $usuario['id'],
                    'nome' => $usuario['nome'],
                    'email' => $usuario['email'],
                    'tipo' => $usuario['tipo']
                ];
            }
        }
        
        // Ordena por data decrescente
        usort($logs, function($a, $b) {
            return strtotime($b['dataCriacao']) - strtotime($a['dataCriacao']);
        });
        
        // Paginação
        $pagina = max(1, intval($_GET['pagina'] ?? 1));
        $porPagina = max(10, min(100, intval($_GET['limite'] ?? 50)));
        
        $total = count($logs);
        $logs = array_slice($logs, ($pagina - 1) * $porPagina, $porPagina);
        
        responderJson([
            'logs' => $logs,
            'paginacao' => [
                'total' => $total,
                'pagina' => $pagina,
                'porPagina' => $porPagina,
                'totalPaginas' => ceil($total / $porPagina)
            ]
        ]);
    }
    
    /**
     * Retorna estatísticas dos logs
     */
    public function estatisticas() {
        $logs = $this->_db->getData('logs');
        
        // Contagem por ação
        $acoes = [];
        foreach ($logs as $log) {
            $acao = $log['acao'];
            if (!isset($acoes[$acao])) {
                $acoes[$acao] = 0;
            }
            $acoes[$acao]++;
        }
        
        // Contagem por usuário
        $usuarios = [];
        foreach ($logs as $log) {
            $usuarioId = $log['usuarioId'];
            if (!isset($usuarios[$usuarioId])) {
                $usuarios[$usuarioId] = 0;
            }
            $usuarios[$usuarioId]++;
        }
        
        // Busca nomes dos usuários
        $todosUsuarios = $this->_db->getData('usuarios');
        $usuariosIndex = array_column($todosUsuarios, null, 'id');
        
        $usuariosStats = [];
        foreach ($usuarios as $id => $total) {
            if (isset($usuariosIndex[$id])) {
                $usuariosStats[] = [
                    'usuario' => [
                        'id' => $id,
                        'nome' => $usuariosIndex[$id]['nome'],
                        'email' => $usuariosIndex[$id]['email']
                    ],
                    'total' => $total
                ];
            }
        }
        
        // Ordena usuários por total decrescente
        usort($usuariosStats, function($a, $b) {
            return $b['total'] - $a['total'];
        });
        
        responderJson([
            'total' => count($logs),
            'acoes' => $acoes,
            'usuarios' => array_slice($usuariosStats, 0, 10), // Top 10 usuários
            'ultimaAtualizacao' => date('Y-m-d H:i:s')
        ]);
    }
}

// Roteamento
$gerenciador = new GerenciadorLogs();
$metodo = $_SERVER['REQUEST_METHOD'];
$rota = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

try {
    if (strpos($rota, '/estatisticas') !== false) {
        $gerenciador->estatisticas();
    } else {
        $gerenciador->listar();
    }
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 