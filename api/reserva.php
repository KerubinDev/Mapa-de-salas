<?php
require_once 'config.php';
require_once 'middleware.php';

// Tratamento específico para OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0);
}

// Verifica autenticação para métodos que modificam dados
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $usuario = verificarAutenticacao();
}

/**
 * Gerenciador de Reservas
 * Endpoints:
 * GET / - Lista todas as reservas
 * GET /verificar - Verifica conflitos de horário
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
     * Verifica se existe conflito de horário
     */
    public function verificarConflito($params) {
        $data = $params['data'] ?? '';
        $horario = $params['horario'] ?? '';
        $salaId = $params['salaId'] ?? '';
        $turmaId = $params['turmaId'] ?? '';
        $excluirId = $params['excluirId'] ?? null;
        
        if (!$data || !$horario || !$salaId || !$turmaId) {
            responderErro('Parâmetros inválidos');
        }
        
        // Verifica se o horário é válido
        if (!$this->validarHorario($horario)) {
            responderErro('Horário inválido');
        }
        
        // Verifica se a sala e turma existem
        $sala = $this->buscarSala($salaId);
        $turma = $this->buscarTurma($turmaId);
        
        if (!$sala || !$turma) {
            responderErro('Sala ou turma não encontrada');
        }
        
        // Verifica se a sala comporta a turma
        if ($sala['capacidade'] < $turma['numeroAlunos']) {
            responderErro('Capacidade da sala insuficiente para a turma');
        }
        
        // Verifica conflitos
        $conflito = array_filter($this->_dados['reservas'], 
            function($reserva) use ($data, $horario, $salaId, $turmaId, $excluirId) {
                if ($excluirId && $reserva['id'] === $excluirId) {
                    return false;
                }
                
                return $reserva['data'] === $data &&
                       $reserva['horario'] === $horario &&
                       ($reserva['salaId'] === $salaId || 
                        $reserva['turmaId'] === $turmaId);
            }
        );
        
        responderJson(['conflito' => !empty($conflito)]);
    }
    
    /**
     * Cadastra uma nova reserva
     */
    public function criar() {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        // Validação dos campos obrigatórios
        if (empty($dados['data']) || empty($dados['horario']) || 
            empty($dados['salaId']) || empty($dados['turmaId'])) {
            responderErro('Todos os campos são obrigatórios');
        }
        
        // Valida o horário
        if (!$this->validarHorario($dados['horario'])) {
            responderErro('Horário inválido');
        }
        
        // Verifica se a sala e turma existem
        $sala = $this->buscarSala($dados['salaId']);
        $turma = $this->buscarTurma($dados['turmaId']);
        
        if (!$sala || !$turma) {
            responderErro('Sala ou turma não encontrada');
        }
        
        // Verifica se a sala comporta a turma
        if ($sala['capacidade'] < $turma['numeroAlunos']) {
            responderErro('Capacidade da sala insuficiente para a turma');
        }
        
        // Verifica conflitos
        $conflito = array_filter($this->_dados['reservas'], 
            function($reserva) use ($dados) {
                return $reserva['data'] === $dados['data'] &&
                       $reserva['horario'] === $dados['horario'] &&
                       ($reserva['salaId'] === $dados['salaId'] || 
                        $reserva['turmaId'] === $dados['turmaId']);
            }
        );
        
        if (!empty($conflito)) {
            responderErro('Já existe uma reserva para este horário');
        }
        
        // Cria nova reserva
        $novaReserva = [
            'id' => uniqid(),
            'data' => $dados['data'],
            'horario' => $dados['horario'],
            'salaId' => $dados['salaId'],
            'turmaId' => $dados['turmaId'],
            'dataCriacao' => date('Y-m-d H:i:s')
        ];
        
        $this->_dados['reservas'][] = $novaReserva;
        salvarDados($this->_dados);
        
        // Retorna reserva com dados completos
        responderJson(array_merge($novaReserva, [
            'sala' => $sala,
            'turma' => $turma
        ]), 201);
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
        
        // Validações
        if (!$this->validarHorario($dados['horario'])) {
            responderErro('Horário inválido');
        }
        
        $sala = $this->buscarSala($dados['salaId']);
        $turma = $this->buscarTurma($dados['turmaId']);
        
        if (!$sala || !$turma) {
            responderErro('Sala ou turma não encontrada');
        }
        
        if ($sala['capacidade'] < $turma['numeroAlunos']) {
            responderErro('Capacidade da sala insuficiente para a turma');
        }
        
        // Verifica conflitos
        $conflito = array_filter($this->_dados['reservas'], 
            function($reserva) use ($dados, $id) {
                if ($reserva['id'] === $id) return false;
                
                return $reserva['data'] === $dados['data'] &&
                       $reserva['horario'] === $dados['horario'] &&
                       ($reserva['salaId'] === $dados['salaId'] || 
                        $reserva['turmaId'] === $dados['turmaId']);
            }
        );
        
        if (!empty($conflito)) {
            responderErro('Já existe uma reserva para este horário');
        }
        
        // Atualiza a reserva
        $this->_dados['reservas'][$indice] = array_merge(
            $this->_dados['reservas'][$indice],
            [
                'data' => $dados['data'],
                'horario' => $dados['horario'],
                'salaId' => $dados['salaId'],
                'turmaId' => $dados['turmaId'],
                'dataAtualizacao' => date('Y-m-d H:i:s')
            ]
        );
        
        salvarDados($this->_dados);
        
        // Retorna reserva atualizada com dados completos
        responderJson(array_merge(
            $this->_dados['reservas'][$indice],
            ['sala' => $sala, 'turma' => $turma]
        ));
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
        foreach ($this->_dados['salas'] as $sala) {
            if ($sala['id'] === $id) return $sala;
        }
        return null;
    }
    
    private function buscarTurma($id) {
        foreach ($this->_dados['turmas'] as $turma) {
            if ($turma['id'] === $id) return $turma;
        }
        return null;
    }
    
    private function validarHorario($horario) {
        $partes = explode(':', $horario);
        if (count($partes) !== 2) return false;
        
        $hora = (int)$partes[0];
        $minuto = (int)$partes[1];
        
        // Verifica se está dentro do horário de funcionamento (7h às 22h)
        if ($hora < 7 || $hora > 22) return false;
        
        // Verifica se os minutos são múltiplos de 15
        if ($minuto % 15 !== 0) return false;
        
        return true;
    }
}

// Roteamento das requisições
$gerenciador = new GerenciadorReserva();
$metodo = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$id = $_GET['id'] ?? null;

try {
    // Rota especial para verificação de conflitos
    if (strpos($uri, '/verificar') !== false) {
        $gerenciador->verificarConflito($_GET);
        exit;
    }

    switch ($metodo) {
        case 'GET':
            $gerenciador->listar();
            break;
            
        case 'POST':
            $dados = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Dados JSON inválidos');
            }
            $gerenciador->criar();
            break;
            
        case 'PUT':
            if (!$id) throw new Exception('ID da reserva não informado');
            $gerenciador->atualizar($id);
            break;
            
        case 'DELETE':
            if (!$id) throw new Exception('ID da reserva não informado');
            $gerenciador->remover($id);
            break;
            
        default:
            throw new Exception('Método não suportado', 405);
    }
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 