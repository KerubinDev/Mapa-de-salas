<?php
require_once 'config.php';

/**
 * Gerenciador de Turmas
 * Endpoints:
 * GET / - Lista todas as turmas
 * POST / - Cria uma nova turma
 * PUT /{id} - Atualiza uma turma existente
 * DELETE /{id} - Remove uma turma
 */

class GerenciadorTurma {
    private $_dados;
    
    public function __construct() {
        $this->_dados = lerDados();
    }
    
    /**
     * Lista todas as turmas cadastradas
     */
    public function listar() {
        responderJson($this->_dados['turmas']);
    }
    
    /**
     * Cadastra uma nova turma
     */
    public function criar() {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        // Validação dos campos obrigatórios
        if (empty($dados['nome'])) {
            responderErro('Nome da turma é obrigatório');
        }
        
        if (empty($dados['professor'])) {
            responderErro('Professor responsável é obrigatório');
        }
        
        if (empty($dados['numeroAlunos']) || !is_numeric($dados['numeroAlunos'])) {
            responderErro('Número de alunos deve ser um valor válido');
        }
        
        if (empty($dados['turno']) || 
            !in_array($dados['turno'], ['manha', 'tarde', 'noite'])) {
            responderErro('Turno deve ser: manha, tarde ou noite');
        }
        
        // Verifica se já existe turma com mesmo nome
        $turmaExistente = array_filter($this->_dados['turmas'], 
            function($turma) use ($dados) {
                return strtolower($turma['nome']) === strtolower($dados['nome']);
            }
        );
        
        if (!empty($turmaExistente)) {
            responderErro('Já existe uma turma com este nome');
        }
        
        // Cria nova turma
        $novaTurma = [
            'id' => uniqid(),
            'nome' => $dados['nome'],
            'professor' => $dados['professor'],
            'numeroAlunos' => (int)$dados['numeroAlunos'],
            'turno' => $dados['turno'],
            'dataCriacao' => date('Y-m-d H:i:s')
        ];
        
        $this->_dados['turmas'][] = $novaTurma;
        salvarDados($this->_dados);
        
        responderJson($novaTurma, 201);
    }
    
    /**
     * Atualiza uma turma existente
     */
    public function atualizar($id) {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        // Localiza a turma
        $indice = array_search($id, array_column($this->_dados['turmas'], 'id'));
        
        if ($indice === false) {
            responderErro('Turma não encontrada', 404);
        }
        
        // Validações
        if (isset($dados['nome'])) {
            $turmaExistente = array_filter($this->_dados['turmas'], 
                function($turma) use ($dados, $id) {
                    return $turma['id'] !== $id && 
                           strtolower($turma['nome']) === strtolower($dados['nome']);
                }
            );
            
            if (!empty($turmaExistente)) {
                responderErro('Já existe uma turma com este nome');
            }
            
            $this->_dados['turmas'][$indice]['nome'] = $dados['nome'];
        }
        
        if (isset($dados['professor'])) {
            $this->_dados['turmas'][$indice]['professor'] = $dados['professor'];
        }
        
        if (isset($dados['numeroAlunos'])) {
            if (!is_numeric($dados['numeroAlunos'])) {
                responderErro('Número de alunos deve ser um valor válido');
            }
            $this->_dados['turmas'][$indice]['numeroAlunos'] = 
                (int)$dados['numeroAlunos'];
        }
        
        if (isset($dados['turno'])) {
            if (!in_array($dados['turno'], ['manha', 'tarde', 'noite'])) {
                responderErro('Turno deve ser: manha, tarde ou noite');
            }
            $this->_dados['turmas'][$indice]['turno'] = $dados['turno'];
        }
        
        $this->_dados['turmas'][$indice]['dataAtualizacao'] = date('Y-m-d H:i:s');
        salvarDados($this->_dados);
        
        responderJson($this->_dados['turmas'][$indice]);
    }
    
    /**
     * Remove uma turma
     */
    public function remover($id) {
        // Verifica se existem reservas para esta turma
        $reservasExistentes = array_filter($this->_dados['reservas'], 
            function($reserva) use ($id) {
                return $reserva['turmaId'] === $id;
            }
        );
        
        if (!empty($reservasExistentes)) {
            responderErro('Não é possível remover uma turma com reservas ativas');
        }
        
        // Localiza e remove a turma
        $indice = array_search($id, array_column($this->_dados['turmas'], 'id'));
        
        if ($indice === false) {
            responderErro('Turma não encontrada', 404);
        }
        
        array_splice($this->_dados['turmas'], $indice, 1);
        salvarDados($this->_dados);
        
        responderJson(['mensagem' => 'Turma removida com sucesso']);
    }
}

// Roteamento das requisições
$gerenciador = new GerenciadorTurma();
$metodo = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

switch ($metodo) {
    case 'GET':
        $gerenciador->listar();
        break;
        
    case 'POST':
        $gerenciador->criar();
        break;
        
    case 'PUT':
        if (!$id) responderErro('ID da turma não informado');
        $gerenciador->atualizar($id);
        break;
        
    case 'DELETE':
        if (!$id) responderErro('ID da turma não informado');
        $gerenciador->remover($id);
        break;
        
    default:
        responderErro('Método não suportado', 405);
} 