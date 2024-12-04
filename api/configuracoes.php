<?php
require_once 'config.php';
require_once 'middleware.php';

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
    private $_dados;
    
    public function __construct() {
        $this->_dados = lerDados();
        if (!isset($this->_dados['configuracoes'])) {
            $this->_dados['configuracoes'] = $this->getConfiguracoesDefault();
        }
    }
    
    /**
     * Retorna as configurações padrão
     */
    private function getConfiguracoesDefault() {
        return [
            'horarioAbertura' => '07:00',
            'horarioFechamento' => '22:00',
            'diasFuncionamento' => [1, 2, 3, 4, 5], // Segunda a Sexta
            'duracaoMinima' => 15,
            'intervaloReservas' => 0,
            'notificarReservas' => false,
            'notificarCancelamentos' => false,
            'notificarConflitos' => false,
            'backupAutomatico' => false,
            'ultimoBackup' => null
        ];
    }
    
    /**
     * Lista as configurações atuais
     */
    public function listar() {
        responderJson($this->_dados['configuracoes']);
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
        
        // Atualiza as configurações
        $this->_dados['configuracoes'] = array_merge(
            $this->_dados['configuracoes'],
            [
                'horarioAbertura' => $dados['horarioAbertura'],
                'horarioFechamento' => $dados['horarioFechamento'],
                'diasFuncionamento' => $dados['diasFuncionamento'],
                'duracaoMinima' => (int)$dados['duracaoMinima'],
                'intervaloReservas' => (int)$dados['intervaloReservas'],
                'notificarReservas' => (bool)($dados['notificarReservas'] ?? false),
                'notificarCancelamentos' => (bool)($dados['notificarCancelamentos'] ?? false),
                'notificarConflitos' => (bool)($dados['notificarConflitos'] ?? false),
                'backupAutomatico' => (bool)($dados['backupAutomatico'] ?? false)
            ]
        );
        
        salvarDados($this->_dados);
        responderJson($this->_dados['configuracoes']);
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