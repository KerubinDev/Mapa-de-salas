<?php
require_once 'config.php';
require_once 'middleware.php';

// Verifica autenticação para métodos que modificam dados
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $usuario = verificarAutenticacao();
}

class GerenciadorTurma {
    private $_db;
    private $_usuario;
    
    public function __construct($usuario = null) {
        $this->_db = JsonDatabase::getInstance();
        $this->_usuario = $usuario;
    }
    
    /**
     * Lista todas as turmas
     */
    public function listar() {
        $turmas = $this->_db->getData('turmas');
        
        // Adiciona estatísticas de reservas
        foreach ($turmas as &$turma) {
            $reservas = $this->_db->query('reservas', ['turmaId' => $turma['id']]);
            $salas = array_unique(array_column($reservas, 'salaId'));
            
            $turma['estatisticas'] = [
                'totalReservas' => count($reservas),
                'totalSalas' => count($salas)
            ];
        }
        
        // Ordena por nome
        usort($turmas, function($a, $b) {
            return strcmp($a['nome'], $b['nome']);
        });
        
        responderJson($turmas);
    }
    
    /**
     * Cria uma nova turma
     */
    public function criar() {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        // Validações
        if (empty($dados['nome'])) {
            throw new Exception('Nome da turma é obrigatório');
        }
        
        if (empty($dados['professor'])) {
            throw new Exception('Professor responsável é obrigatório');
        }
        
        if (empty($dados['numeroAlunos']) || !is_numeric($dados['numeroAlunos'])) {
            throw new Exception('Número de alunos deve ser um valor válido');
        }
        
        if (empty($dados['turno']) || !in_array($dados['turno'], ['manha', 'tarde', 'noite'])) {
            throw new Exception('Turno deve ser: manha, tarde ou noite');
        }
        
        // Verifica se já existe turma com mesmo nome
        $turmaExistente = $this->_db->query('turmas', ['nome' => $dados['nome']]);
        if (!empty($turmaExistente)) {
            throw new Exception('Já existe uma turma com este nome');
        }
        
        // Cria a turma
        $turma = $this->_db->insert('turmas', [
            'nome' => $dados['nome'],
            'professor' => $dados['professor'],
            'numeroAlunos' => (int)$dados['numeroAlunos'],
            'turno' => $dados['turno'],
            'disciplina' => $dados['disciplina'] ?? '',
            'semestre' => $dados['semestre'] ?? ''
        ]);
        
        // Registra no log
        registrarLog($this->_usuario['id'], 'turma_criar', "Turma criada: {$turma['nome']}");
        
        responderJson($turma, 201);
    }
    
    /**
     * Atualiza uma turma
     */
    public function atualizar($id) {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        // Verifica se a turma existe
        $turmaExistente = $this->_db->query('turmas', ['id' => $id]);
        if (empty($turmaExistente)) {
            throw new Exception('Turma não encontrada', 404);
        }
        $turmaAtual = reset($turmaExistente);
        
        // Validações
        if (isset($dados['nome'])) {
            $outraTurma = $this->_db->query('turmas', ['nome' => $dados['nome']]);
            if (!empty($outraTurma) && reset($outraTurma)['id'] !== $id) {
                throw new Exception('Já existe uma turma com este nome');
            }
        }
        
        if (isset($dados['numeroAlunos']) && !is_numeric($dados['numeroAlunos'])) {
            throw new Exception('Número de alunos deve ser um valor válido');
        }
        
        if (isset($dados['turno']) && !in_array($dados['turno'], ['manha', 'tarde', 'noite'])) {
            throw new Exception('Turno deve ser: manha, tarde ou noite');
        }
        
        // Atualiza a turma
        $turma = $this->_db->update('turmas', $id, $dados);
        
        // Registra no log
        registrarLog($this->_usuario['id'], 'turma_atualizar', "Turma atualizada: {$turma['nome']}");
        
        responderJson($turma);
    }
    
    /**
     * Remove uma turma
     */
    public function remover($id) {
        // Verifica se existem reservas para esta turma
        $reservas = $this->_db->query('reservas', ['turmaId' => $id]);
        if (!empty($reservas)) {
            throw new Exception('Não é possível remover uma turma com reservas ativas');
        }
        
        // Busca a turma antes de remover para o log
        $turma = reset($this->_db->query('turmas', ['id' => $id]));
        if (!$turma) {
            throw new Exception('Turma não encontrada', 404);
        }
        
        // Remove a turma
        if (!$this->_db->delete('turmas', $id)) {
            throw new Exception('Erro ao remover turma');
        }
        
        // Registra no log
        registrarLog($this->_usuario['id'], 'turma_remover', "Turma removida: {$turma['nome']}");
        
        responderJson(['mensagem' => 'Turma removida com sucesso']);
    }
}

// Roteamento
$gerenciador = new GerenciadorTurma($usuario ?? null);
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
            if (!$id) throw new Exception('ID da turma não informado');
            $gerenciador->atualizar($id);
            break;
            
        case 'DELETE':
            if (!$id) throw new Exception('ID da turma não informado');
            $gerenciador->remover($id);
            break;
            
        default:
            throw new Exception('Método não suportado', 405);
    }
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 