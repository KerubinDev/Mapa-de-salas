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
        responderJson($this->_dados['reservas']);
    }
    
    /**
     * Verifica se existe conflito de horário
     */
    public function verificarConflito($params) {
        $data = $params['data'] ?? '';
        $horarioInicio = $params['horarioInicio'] ?? '';
        $horarioFim = $params['horarioFim'] ?? '';
        $salaId = $params['salaId'] ?? '';
        $turmaId = $params['turmaId'] ?? '';
        $excluirId = $params['excluirId'] ?? null;
        
        if (!$data || !$horarioInicio || !$horarioFim || !$salaId || !$turmaId) {
            responderErro('Parâmetros inválidos');
        }
        
        // Verifica se os horários são válidos
        if (!$this->validarHorario($horarioInicio) || !$this->validarHorario($horarioFim)) {
            responderErro('Horário inválido');
        }
        
        if ($horarioFim <= $horarioInicio) {
            responderErro('O horário de término deve ser posterior ao horário de início');
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
            function($reserva) use ($data, $horarioInicio, $horarioFim, $salaId, $turmaId, $excluirId) {
                if ($excluirId && $reserva['id'] === $excluirId) {
                    return false;
                }
                
                if ($reserva['data'] !== $data) {
                    return false;
                }
                
                // Verifica se há sobreposição de horários
                $temSobreposicao = (
                    ($horarioInicio >= $reserva['horarioInicio'] && $horarioInicio < $reserva['horarioFim']) ||
                    ($horarioFim > $reserva['horarioInicio'] && $horarioFim <= $reserva['horarioFim']) ||
                    ($horarioInicio <= $reserva['horarioInicio'] && $horarioFim >= $reserva['horarioFim'])
                );
                
                return $temSobreposicao && 
                       ($reserva['salaId'] === $salaId || $reserva['turmaId'] === $turmaId);
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
        if (empty($dados['data']) || 
            empty($dados['horarioInicio']) || 
            empty($dados['horarioFim']) || 
            empty($dados['salaId']) || 
            empty($dados['turmaId'])) {
            responderErro('Todos os campos são obrigatórios');
        }
        
        // Valida os horários
        if (!$this->validarHorario($dados['horarioInicio']) || 
            !$this->validarHorario($dados['horarioFim'])) {
            responderErro('Horário inválido');
        }
        
        if ($dados['horarioFim'] <= $dados['horarioInicio']) {
            responderErro('O horário de término deve ser posterior ao horário de início');
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
                if ($reserva['data'] !== $dados['data']) {
                    return false;
                }
                
                // Verifica se há sobreposição de horários
                $temSobreposicao = (
                    ($dados['horarioInicio'] >= $reserva['horarioInicio'] && 
                     $dados['horarioInicio'] < $reserva['horarioFim']) ||
                    ($dados['horarioFim'] > $reserva['horarioInicio'] && 
                     $dados['horarioFim'] <= $reserva['horarioFim']) ||
                    ($dados['horarioInicio'] <= $reserva['horarioInicio'] && 
                     $dados['horarioFim'] >= $reserva['horarioFim'])
                );
                
                return $temSobreposicao && 
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
            'horarioInicio' => $dados['horarioInicio'],
            'horarioFim' => $dados['horarioFim'],
            'salaId' => $dados['salaId'],
            'turmaId' => $dados['turmaId'],
            'dataCriacao' => date('Y-m-d H:i:s')
        ];
        
        $this->_dados['reservas'][] = $novaReserva;
        salvarDados($this->_dados);
        
        // Retorna reserva com dados completos
        responderJson(array_merge(
            $novaReserva,
            [
                'sala' => $sala,
                'turma' => $turma
            ]
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