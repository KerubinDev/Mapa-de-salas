<?php
require_once 'config.php';
require_once 'middleware.php';
require_once __DIR__ . '/../database/Database.php';

// Verifica autenticação para métodos que modificam dados
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $usuario = verificarAutenticacao();
}

/**
 * Gerenciador de Reservas
 */
class GerenciadorReserva {
    private $_db;
    
    public function __construct() {
        $this->_db = Database::getInstance()->getConnection();
    }
    
    /**
     * Lista todas as reservas cadastradas
     */
    public function listar() {
        $sql = '
            SELECT r.*, s.nome as sala_nome, s.capacidade as sala_capacidade,
                   t.nome as turma_nome, t.professor as turma_professor
            FROM reservas r
            JOIN salas s ON r.sala_id = s.id
            JOIN turmas t ON r.turma_id = t.id
            ORDER BY r.data, r.horario_inicio
        ';
        
        $stmt = $this->_db->query($sql);
        $reservas = $stmt->fetchAll();
        responderJson($reservas);
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
        $stmt = $this->_db->prepare('
            SELECT s.*, t.numero_alunos 
            FROM salas s, turmas t 
            WHERE s.id = ? AND t.id = ?
        ');
        $stmt->execute([$salaId, $turmaId]);
        $resultado = $stmt->fetch();
        
        if (!$resultado) {
            responderErro('Sala ou turma não encontrada');
        }
        
        // Verifica se a sala comporta a turma
        if ($resultado['capacidade'] < $resultado['numero_alunos']) {
            responderErro('Capacidade da sala insuficiente para a turma');
        }
        
        // Verifica conflitos
        $sql = '
            SELECT COUNT(*) as total
            FROM reservas
            WHERE data = ?
            AND ((horario_inicio >= ? AND horario_inicio < ?) OR
                 (horario_fim > ? AND horario_fim <= ?) OR
                 (horario_inicio <= ? AND horario_fim >= ?))
            AND (sala_id = ? OR turma_id = ?)
        ';
        
        if ($excluirId) {
            $sql .= ' AND id != ?';
        }
        
        $stmt = $this->_db->prepare($sql);
        $params = [
            $data,
            $horarioInicio, $horarioFim,
            $horarioInicio, $horarioFim,
            $horarioInicio, $horarioFim,
            $salaId, $turmaId
        ];
        
        if ($excluirId) {
            $params[] = $excluirId;
        }
        
        $stmt->execute($params);
        $resultado = $stmt->fetch();
        
        responderJson(['conflito' => $resultado['total'] > 0]);
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
        
        try {
            $this->_db->beginTransaction();
            
            // Verifica conflitos
            $params = [
                'data' => $dados['data'],
                'horarioInicio' => $dados['horarioInicio'],
                'horarioFim' => $dados['horarioFim'],
                'salaId' => $dados['salaId'],
                'turmaId' => $dados['turmaId']
            ];
            
            $this->verificarConflito($params);
            
            // Cria a reserva
            $stmt = $this->_db->prepare('
                INSERT INTO reservas (id, sala_id, turma_id, data, horario_inicio, horario_fim)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            
            $id = uniqid();
            $stmt->execute([
                $id,
                $dados['salaId'],
                $dados['turmaId'],
                $dados['data'],
                $dados['horarioInicio'],
                $dados['horarioFim']
            ]);
            
            // Busca a reserva criada com dados relacionados
            $stmt = $this->_db->prepare('
                SELECT r.*, s.nome as sala_nome, s.capacidade as sala_capacidade,
                       t.nome as turma_nome, t.professor as turma_professor
                FROM reservas r
                JOIN salas s ON r.sala_id = s.id
                JOIN turmas t ON r.turma_id = t.id
                WHERE r.id = ?
            ');
            $stmt->execute([$id]);
            $reserva = $stmt->fetch();
            
            $this->_db->commit();
            responderJson($reserva, 201);
            
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Remove uma reserva
     */
    public function remover($id) {
        try {
            $this->_db->beginTransaction();
            
            // Remove a reserva
            $stmt = $this->_db->prepare('DELETE FROM reservas WHERE id = ?');
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                responderErro('Reserva não encontrada', 404);
            }
            
            $this->_db->commit();
            responderJson(['mensagem' => 'Reserva removida com sucesso']);
            
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Valida o formato e intervalo do horário
     */
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