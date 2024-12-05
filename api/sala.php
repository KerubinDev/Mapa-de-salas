<?php
require_once 'config.php';
require_once 'middleware.php';

// Verifica autenticação para métodos que modificam dados
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $usuario = verificarAutenticacao();
    verificarPermissao($usuario, 'gerenciarSalas');
}

class GerenciadorSala {
    private $_db;
    private $_usuario;
    
    public function __construct($usuario = null) {
        $this->_db = JsonDatabase::getInstance();
        $this->_usuario = $usuario;
    }
    
    /**
     * Lista todas as salas
     */
    public function listar() {
        $salas = $this->_db->getData('salas');
        
        // Adiciona estatísticas de uso
        foreach ($salas as &$sala) {
            $reservas = $this->_db->query('reservas', ['salaId' => $sala['id']]);
            $sala['estatisticas'] = [
                'totalReservas' => count($reservas),
                'ultimaReserva' => $this->_buscarUltimaReserva($reservas)
            ];
        }
        
        // Ordena por nome
        usort($salas, function($a, $b) {
            return strcmp($a['nome'], $b['nome']);
        });
        
        responderJson($salas);
    }
    
    /**
     * Cria uma nova sala
     */
    public function criar() {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        // Validações
        if (empty($dados['nome'])) {
            throw new Exception('Nome da sala é obrigatório');
        }
        
        if (empty($dados['capacidade']) || !is_numeric($dados['capacidade'])) {
            throw new Exception('Capacidade deve ser um número válido');
        }
        
        // Verifica se já existe sala com mesmo nome
        $salaExistente = $this->_db->query('salas', ['nome' => $dados['nome']]);
        if (!empty($salaExistente)) {
            throw new Exception('Já existe uma sala com este nome');
        }
        
        // Cria a sala
        $sala = $this->_db->insert('salas', [
            'nome' => $dados['nome'],
            'capacidade' => (int)$dados['capacidade'],
            'descricao' => $dados['descricao'] ?? '',
            'recursos' => $dados['recursos'] ?? []
        ]);
        
        // Registra no log
        $this->_db->insert('logs', [
            'usuarioId' => $usuario['id'],
            'acao' => 'sala_criar',
            'detalhes' => "Sala criada: {$sala['nome']}",
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        responderJson($sala, 201);
    }
    
    /**
     * Atualiza uma sala
     */
    public function atualizar($id) {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        // Verifica se a sala existe
        $salaExistente = $this->_db->query('salas', ['id' => $id]);
        if (empty($salaExistente)) {
            throw new Exception('Sala não encontrada', 404);
        }
        $salaAtual = reset($salaExistente);
        
        // Validações
        if (isset($dados['nome'])) {
            $outraSala = $this->_db->query('salas', ['nome' => $dados['nome']]);
            if (!empty($outraSala) && reset($outraSala)['id'] !== $id) {
                throw new Exception('Já existe uma sala com este nome');
            }
        }
        
        if (isset($dados['capacidade']) && !is_numeric($dados['capacidade'])) {
            throw new Exception('Capacidade deve ser um número válido');
        }
        
        // Atualiza a sala
        $sala = $this->_db->update('salas', $id, $dados);
        
        // Registra no log
        $this->_db->insert('logs', [
            'usuarioId' => $usuario['id'],
            'acao' => 'sala_atualizar',
            'detalhes' => "Sala atualizada: {$sala['nome']}",
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        responderJson($sala);
    }
    
    /**
     * Remove uma sala
     */
    public function remover($id) {
        // Verifica se existem reservas para esta sala
        $reservas = $this->_db->query('reservas', ['salaId' => $id]);
        if (!empty($reservas)) {
            throw new Exception('Não é possível remover uma sala com reservas ativas');
        }
        
        // Busca a sala antes de remover para o log
        $sala = reset($this->_db->query('salas', ['id' => $id]));
        if (!$sala) {
            throw new Exception('Sala não encontrada', 404);
        }
        
        // Remove a sala
        if (!$this->_db->delete('salas', $id)) {
            throw new Exception('Erro ao remover sala');
        }
        
        // Registra no log
        $this->_db->insert('logs', [
            'usuarioId' => $usuario['id'],
            'acao' => 'sala_remover',
            'detalhes' => "Sala removida: {$sala['nome']}",
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        responderJson(['mensagem' => 'Sala removida com sucesso']);
    }
    
    /**
     * Busca a última reserva de uma sala
     */
    private function _buscarUltimaReserva($reservas) {
        if (empty($reservas)) return null;
        
        usort($reservas, function($a, $b) {
            return strcmp($b['data'], $a['data']);
        });
        
        return reset($reservas);
    }
}

// Roteamento
$gerenciador = new GerenciadorSala();
$metodo = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

try {
    switch ($metodo) {
        case 'GET':
            $gerenciador->listar();
            break;
            
        case 'POST':
            $gerenciador->criar();
            break;
            
        case 'PUT':
            if (!$id) throw new Exception('ID da sala não informado');
            $gerenciador->atualizar($id);
            break;
            
        case 'DELETE':
            if (!$id) throw new Exception('ID da sala não informado');
            $gerenciador->remover($id);
            break;
            
        default:
            throw new Exception('Método não suportado', 405);
    }
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 