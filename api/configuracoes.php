<?php
require_once 'config.php';
require_once 'middleware.php';
require_once __DIR__ . '/../database/Database.php';

// Verifica autenticação
try {
    $usuario = verificarAutenticacao();
    if ($usuario['tipo'] !== 'admin') {
        responderErro('Acesso negado', 403);
    }
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode());
}

/**
 * Gerenciador de Configurações
 */
class GerenciadorConfiguracoes {
    private $_db;
    
    public function __construct() {
        $this->_db = Database::getInstance()->getConnection();
    }
    
    /**
     * Lista as configurações atuais
     */
    public function listar() {
        $stmt = $this->_db->query('SELECT * FROM configuracoes');
        $configuracoes = $stmt->fetchAll();
        
        // Converte para formato chave-valor
        $config = [];
        foreach ($configuracoes as $item) {
            // Trata tipos especiais
            $valor = $item['valor'];
            if ($item['chave'] === 'dias_funcionamento') {
                $valor = array_map('intval', explode(',', $valor));
            } else if (in_array($item['chave'], [
                'notificar_reservas',
                'notificar_cancelamentos',
                'notificar_conflitos',
                'backup_automatico'
            ])) {
                $valor = $valor === '1' || $valor === 'true';
            } else if (in_array($item['chave'], [
                'duracao_minima',
                'intervalo_reservas'
            ])) {
                $valor = (int)$valor;
            }
            
            // Converte nome da chave de snake_case para camelCase
            $chave = lcfirst(str_replace('_', '', ucwords($item['chave'], '_')));
            $config[$chave] = $valor;
        }
        
        responderJson($config);
    }
    
    /**
     * Atualiza as configurações
     */
    public function atualizar($dados) {
        // Validação dos campos obrigatórios
        if (!isset($dados['horarioAbertura']) || 
            !isset($dados['horarioFechamento']) || 
            !isset($dados['diasFuncionamento']) || 
            !isset($dados['duracaoMinima']) || 
            !isset($dados['intervaloReservas'])) {
            responderErro('Campos obrigatórios não informados');
        }
        
        // Validação dos horários
        if (!$this->validarHorario($dados['horarioAbertura']) || 
            !$this->validarHorario($dados['horarioFechamento'])) {
            responderErro('Horário inválido');
        }
        
        if ($dados['horarioFechamento'] <= $dados['horarioAbertura']) {
            responderErro('O horário de fechamento deve ser posterior ao horário de abertura');
        }
        
        // Validação dos dias de funcionamento
        if (empty($dados['diasFuncionamento'])) {
            responderErro('Selecione pelo menos um dia de funcionamento');
        }
        
        foreach ($dados['diasFuncionamento'] as $dia) {
            if (!is_numeric($dia) || $dia < 0 || $dia > 6) {
                responderErro('Dia de funcionamento inválido');
            }
        }
        
        // Validação dos intervalos
        if ($dados['duracaoMinima'] < 15 || $dados['duracaoMinima'] % 15 !== 0) {
            responderErro('A duração mínima deve ser múltipla de 15 minutos');
        }
        
        if ($dados['intervaloReservas'] < 0) {
            responderErro('O intervalo entre reservas não pode ser negativo');
        }
        
        try {
            $this->_db->beginTransaction();
            
            // Prepara a query de atualização
            $stmt = $this->_db->prepare('
                INSERT OR REPLACE INTO configuracoes (chave, valor, data_atualizacao)
                VALUES (?, ?, CURRENT_TIMESTAMP)
            ');
            
            // Atualiza cada configuração
            $configs = [
                'horario_abertura' => $dados['horarioAbertura'],
                'horario_fechamento' => $dados['horarioFechamento'],
                'dias_funcionamento' => implode(',', $dados['diasFuncionamento']),
                'duracao_minima' => (int)$dados['duracaoMinima'],
                'intervalo_reservas' => (int)$dados['intervaloReservas'],
                'notificar_reservas' => $dados['notificarReservas'] ? '1' : '0',
                'notificar_cancelamentos' => $dados['notificarCancelamentos'] ? '1' : '0',
                'notificar_conflitos' => $dados['notificarConflitos'] ? '1' : '0',
                'backup_automatico' => $dados['backupAutomatico'] ? '1' : '0'
            ];
            
            foreach ($configs as $chave => $valor) {
                $stmt->execute([$chave, $valor]);
            }
            
            $this->_db->commit();
            
            // Retorna as configurações atualizadas
            $this->listar();
            
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Valida o formato do horário
     */
    private function validarHorario($horario) {
        if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $horario)) {
            return false;
        }
        
        list($hora, $minuto) = explode(':', $horario);
        $hora = (int)$hora;
        $minuto = (int)$minuto;
        
        return $hora >= 0 && $hora <= 23 && $minuto >= 0 && $minuto <= 59;
    }
}

// Roteamento das requisições
$gerenciador = new GerenciadorConfiguracoes();
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    switch ($metodo) {
        case 'GET':
            $gerenciador->listar();
            break;
            
        case 'POST':
            $dados = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Dados JSON inválidos');
            }
            $gerenciador->atualizar($dados);
            break;
            
        default:
            throw new Exception('Método não suportado', 405);
    }
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 