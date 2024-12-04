<?php
require_once 'config.php';

/**
 * Gerenciador de Reservas
 * Endpoints:
 * GET / - Lista todas as reservas
 * POST / - Cria uma nova reserva
 * PUT /{id} - Atualiza uma reserva existente
 * DELETE /{id} - Remove uma reserva
 */

class GerenciadorReserva {
    private $_dados;
    
    public function __construct() {
        $this->_dados = lerDados();
    }
    
    /**
     * Lista todas as reservas cadastradas
     */
    public function listar() {
        // Enriquece as reservas com dados de sala e turma
        $reservasCompletas = array_map(function($reserva) {
            $sala = $this->buscarSala($reserva['salaId']);
            $turma = $this->buscarTurma($reserva['turmaId']);
            
            return array_merge($reserva, [
                'sala' => $sala,
                'turma' => $turma
            ]);
        }, $this->_dados['reservas']);
        
        responderJson($reservasCompletas);
    }
    
    /**
     * Cadastra uma nova reserva
     */
    public function criar() {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        // Validação dos campos obrigatórios
        if (empty($dados['salaId'])) {
            responderErro('Sala é obrigatória');
        }
        
        if (empty($dados['turmaId'])) {
            responderErro('Turma é obrigatória');
        }
        
        if (empty($dados['dia']) || 
            !in_array($dados['dia'], ['segunda', 'terca', 'quarta', 'quinta', 'sexta'])) {
            responderErro('Dia deve ser: segunda, terca, quarta, quinta ou sexta');
        }
        
        if (empty($dados['horario']) || !in_array($dados['horario'], HORARIOS_VALIDOS)) {
            responderErro('Horário inválido');
        }
        
        // Verifica se sala e turma existem
        $sala = $this->buscarSala($dados['salaId']);
        $turma = $this->buscarTurma($dados['turmaId']);
        
        if (!$sala) responderErro('Sala não encontrada');
        if (!$turma) responderErro('Turma não encontrada');
        
        // Verifica se a capacidade da sala comporta a turma
        if ($sala['capacidade'] < $turma['numeroAlunos']) {
            responderErro('Capacidade da sala insuficiente para a turma');
        }
        
        // Verifica conflitos de horário
        if ($this->verificarConflito($dados)) {
            responderErro('Já existe uma reserva para este horário');
        }
        
        // Cria nova reserva
        $novaReserva = [
            'id' => uniqid(),
            'salaId' => $dados['salaId'],
            'turmaId' => $dados['turmaId'],
            'dia' => $dados['dia'],
            'horario' => $dados['horario'],
            'dataCriacao' => date('Y-m-d H:i:s')
        ];
        
        $this->_dados['reservas'][] = $novaReserva;
        salvarDados($this->_dados);
        
        // Retorna reserva com dados completos
        $reservaCompleta = array_merge($novaReserva, [
            'sala' => $sala,
            'turma' => $turma
        ]);
        
        responderJson($reservaCompleta, 201);
    }
    
    /**
     * Atualiza uma reserva existente
     */
    public function atualizar($id) {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        // Localiza a reserva
        $indice = array_search($id, array_column($this->_dados['reservas'], 'id'));
        
        if ($indice === false) {
            responderErro('Reserva não encontrada', 404);
        }
        
        $reservaAtual = $this->_dados['reservas'][$indice];
        $dadosAtualizados = array_merge($reservaAtual, $dados);
        
        // Validações
        $sala = $this->buscarSala($dadosAtualizados['salaId']);
        $turma = $this->buscarTurma($dadosAtualizados['turmaId']);
        
        if (!$sala) responderErro('Sala não encontrada');
        if (!$turma) responderErro('Turma não encontrada');
        
        if ($sala['capacidade'] < $turma['numeroAlunos']) {
            responderErro('Capacidade da sala insuficiente para a turma');
        }
        
        if ($this->verificarConflito($dadosAtualizados, $id)) {
            responderErro('Já existe uma reserva para este horário');
        }
        
        // Atualiza a reserva
        $this->_dados['reservas'][$indice] = array_merge($reservaAtual, [
            'salaId' => $dadosAtualizados['salaId'],
            'turmaId' => $dadosAtualizados['turmaId'],
            'dia' => $dadosAtualizados['dia'],
            'horario' => $dadosAtualizados['horario'],
            'dataAtualizacao' => date('Y-m-d H:i:s')
        ]);
        
        salvarDados($this->_dados);
        
        // Retorna reserva atualizada com dados completos
        $reservaCompleta = array_merge(
            $this->_dados['reservas'][$indice],
            ['sala' => $sala, 'turma' => $turma]
        );
        
        responderJson($reservaCompleta);
    }
    
    /**
     * Remove uma reserva
     */
    public function remover($id) {
        $indice = array_search($id, array_column($this->_dados['reservas'], 'id'));
        
        if ($indice === false) {
            responderErro('Reserva não encontrada', 404);
        }
        
        array_splice($this->_dados['reservas'], $indice, 1);
        salvarDados($this->_dados);
        
        responderJson(['mensagem' => 'Reserva removida com sucesso']);
    }
    
    /**
     * Funções auxiliares
     */
    private function buscarSala($id) {
        $salas = array_filter($this->_dados['salas'], 
            function($sala) use ($id) {
                return $sala['id'] === $id;
            }
        );
        return !empty($salas) ? reset($salas) : null;
    }
    
    private function buscarTurma($id) {
        $turmas = array_filter($this->_dados['turmas'], 
            function($turma) use ($id) {
                return $turma['id'] === $id;
            }
        );
        return !empty($turmas) ? reset($turmas) : null;
    }
    
    private function verificarConflito($dados, $reservaId = null) {
        return !empty(array_filter($this->_dados['reservas'], 
            function($reserva) use ($dados, $reservaId) {
                // Ignora a própria reserva no caso de atualização
                if ($reservaId && $reserva['id'] === $reservaId) {
                    return false;
                }
                
                // Verifica conflito de sala no mesmo horário
                $conflitoDeSala = $reserva['salaId'] === $dados['salaId'] &&
                                $reserva['dia'] === $dados['dia'] &&
                                $reserva['horario'] === $dados['horario'];
                
                // Verifica conflito de turma no mesmo horário
                $conflitoDeTurma = $reserva['turmaId'] === $dados['turmaId'] &&
                                 $reserva['dia'] === $dados['dia'] &&
                                 $reserva['horario'] === $dados['horario'];
                
                return $conflitoDeSala || $conflitoDeTurma;
            }
        ));
    }
}

// Roteamento das requisições
$gerenciador = new GerenciadorReserva();
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
        if (!$id) responderErro('ID da reserva não informado');
        $gerenciador->atualizar($id);
        break;
        
    case 'DELETE':
        if (!$id) responderErro('ID da reserva não informado');
        $gerenciador->remover($id);
        break;
        
    default:
        responderErro('Método não suportado', 405);
} 