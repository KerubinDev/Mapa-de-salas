<?php
require_once 'config.php';
require_once 'middleware.php';

// Verifica autenticação
$usuario = verificarAutenticacao();

class GerenciadorReserva {
    private $_db;
    private $_usuario;
    
    public function __construct($usuario) {
        $this->_db = JsonDatabase::getInstance();
        $this->_usuario = $usuario;
    }
    
    /**
     * Lista todas as reservas
     */
    public function listar() {
        $reservas = $this->_db->getData('reservas');
        
        // Filtra por professor se não for admin/coordenador
        if (!in_array($this->_usuario['tipo'], ['admin', 'coordenador'])) {
            $reservas = array_filter($reservas, function($reserva) {
                return $reserva['professorId'] === $this->_usuario['id'];
            });
        }
        
        // Adiciona informações relacionadas
        $salas = array_column($this->_db->getData('salas'), null, 'id');
        $turmas = array_column($this->_db->getData('turmas'), null, 'id');
        
        foreach ($reservas as &$reserva) {
            $reserva['sala'] = $salas[$reserva['salaId']] ?? null;
            $reserva['turma'] = $turmas[$reserva['turmaId']] ?? null;
        }
        
        // Ordena por data e horário
        usort($reservas, function($a, $b) {
            $cmp = strcmp($a['data'], $b['data']);
            return $cmp !== 0 ? $cmp : strcmp($a['horarioInicio'], $b['horarioInicio']);
        });
        
        responderJson($reservas);
    }
    
    /**
     * Verifica conflitos de horário
     */
    public function verificarConflito($params) {
        $data = $params['data'] ?? '';
        $horarioInicio = $params['horarioInicio'] ?? '';
        $horarioFim = $params['horarioFim'] ?? '';
        $salaId = $params['salaId'] ?? '';
        $excluirId = $params['excluirId'] ?? null;
        
        if (!$data || !$horarioInicio || !$horarioFim || !$salaId) {
            throw new Exception('Parâmetros inválidos');
        }
        
        // Verifica se os horários são válidos
        if (!$this->_validarHorario($horarioInicio) || !$this->_validarHorario($horarioFim)) {
            throw new Exception('Horário inválido');
        }
        
        // Busca reservas da sala no mesmo dia
        $reservas = array_filter($this->_db->getData('reservas'), function($r) use ($data, $salaId, $excluirId) {
            return $r['salaId'] === $salaId && 
                   $r['data'] === $data && 
                   (!$excluirId || $r['id'] !== $excluirId);
        });
        
        // Verifica conflitos
        foreach ($reservas as $reserva) {
            if ($this->_verificarSobreposicaoHorarios(
                $horarioInicio, $horarioFim,
                $reserva['horarioInicio'], $reserva['horarioFim']
            )) {
                responderJson(['conflito' => true, 'reserva' => $reserva]);
                return;
            }
        }
        
        responderJson(['conflito' => false]);
    }
    
    /**
     * Cria uma nova reserva
     */
    public function criar() {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        // Validações básicas
        if (empty($dados['data']) || empty($dados['horarioInicio']) || 
            empty($dados['horarioFim']) || empty($dados['salaId']) || 
            empty($dados['turmaId'])) {
            throw new Exception('Todos os campos são obrigatórios');
        }
        
        // Verifica se a sala existe
        $sala = reset($this->_db->query('salas', ['id' => $dados['salaId']]));
        if (!$sala) {
            throw new Exception('Sala não encontrada');
        }
        
        // Verifica se a turma existe
        $turma = reset($this->_db->query('turmas', ['id' => $dados['turmaId']]));
        if (!$turma) {
            throw new Exception('Turma não encontrada');
        }
        
        // Verifica se o usuário pode reservar para esta turma
        if (!in_array($this->_usuario['tipo'], ['admin', 'coordenador']) && 
            $turma['professor'] !== $this->_usuario['nome']) {
            throw new Exception('Você não tem permissão para fazer reservas para esta turma');
        }
        
        // Verifica conflitos
        $conflito = $this->_verificarConflito([
            'data' => $dados['data'],
            'horarioInicio' => $dados['horarioInicio'],
            'horarioFim' => $dados['horarioFim'],
            'salaId' => $dados['salaId']
        ]);
        
        if ($conflito['conflito']) {
            throw new Exception('Já existe uma reserva neste horário');
        }
        
        // Cria a reserva
        $reserva = $this->_db->insert('reservas', array_merge($dados, [
            'professorId' => $this->_usuario['id'],
            'status' => 'confirmada'
        ]));
        
        // Adiciona informações relacionadas
        $reserva['sala'] = $sala;
        $reserva['turma'] = $turma;
        
        // Registra no log
        registrarLog($this->_usuario['id'], 'reserva_criar', 
            "Reserva criada: Sala {$sala['nome']} para {$turma['nome']}");
        
        responderJson($reserva, 201);
    }
    
    /**
     * Remove uma reserva
     */
    public function remover($id) {
        // Busca a reserva
        $reserva = reset($this->_db->query('reservas', ['id' => $id]));
        if (!$reserva) {
            throw new Exception('Reserva não encontrada', 404);
        }
        
        // Verifica permissão
        if (!in_array($this->_usuario['tipo'], ['admin', 'coordenador']) && 
            $reserva['professorId'] !== $this->_usuario['id']) {
            throw new Exception('Você não tem permissão para remover esta reserva');
        }
        
        // Remove a reserva
        if (!$this->_db->delete('reservas', $id)) {
            throw new Exception('Erro ao remover reserva');
        }
        
        // Registra no log
        registrarLog($this->_usuario['id'], 'reserva_remover', 
            "Reserva removida: ID {$id}");
        
        responderJson(['mensagem' => 'Reserva removida com sucesso']);
    }
    
    /**
     * Valida o formato do horário
     */
    private function _validarHorario($horario) {
        if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $horario)) {
            return false;
        }
        
        list($hora, $minuto) = explode(':', $horario);
        return ($minuto % 15) === 0;
    }
    
    /**
     * Verifica sobreposição de horários
     */
    private function _verificarSobreposicaoHorarios($inicio1, $fim1, $inicio2, $fim2) {
        return $inicio1 < $fim2 && $fim1 > $inicio2;
    }
}

// Roteamento
$gerenciador = new GerenciadorReserva($usuario);
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