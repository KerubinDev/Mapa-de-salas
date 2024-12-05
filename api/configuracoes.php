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

class GerenciadorConfiguracoes {
    private $_db;
    
    public function __construct() {
        $this->_db = JsonDatabase::getInstance();
    }
    
    /**
     * Lista as configurações atuais
     */
    public function listar() {
        $configuracoes = $this->_db->getData('configuracoes');
        
        // Converte para formato chave-valor
        $config = [];
        foreach ($configuracoes as $item) {
            // Trata tipos especiais
            $valor = $item['valor'];
            switch ($item['chave']) {
                case 'diasFuncionamento':
                    $valor = array_map('intval', explode(',', $valor));
                    break;
                case 'notificarReservas':
                case 'notificarCancelamentos':
                case 'notificarConflitos':
                case 'backupAutomatico':
                    $valor = $valor === 'true' || $valor === '1';
                    break;
                case 'duracaoMinima':
                case 'intervaloReservas':
                    $valor = (int)$valor;
                    break;
            }
            $config[$item['chave']] = $valor;
        }
        
        responderJson($config);
    }
    
    /**
     * Atualiza as configurações
     */
    public function atualizar($dados) {
        // Validações
        if (isset($dados['horarioAbertura']) && !$this->validarHorario($dados['horarioAbertura'])) {
            throw new Exception('Horário de abertura inválido');
        }
        
        if (isset($dados['horarioFechamento']) && !$this->validarHorario($dados['horarioFechamento'])) {
            throw new Exception('Horário de fechamento inválido');
        }
        
        if (isset($dados['diasFuncionamento'])) {
            foreach ($dados['diasFuncionamento'] as $dia) {
                if (!is_numeric($dia) || $dia < 0 || $dia > 6) {
                    throw new Exception('Dias de funcionamento inválidos');
                }
            }
            $dados['diasFuncionamento'] = implode(',', $dados['diasFuncionamento']);
        }
        
        // Atualiza cada configuração
        foreach ($dados as $chave => $valor) {
            $configExistente = $this->_db->query('configuracoes', ['chave' => $chave]);
            
            if (empty($configExistente)) {
                $this->_db->insert('configuracoes', [
                    'chave' => $chave,
                    'valor' => $valor
                ]);
            } else {
                $config = reset($configExistente);
                $this->_db->update('configuracoes', $config['id'], [
                    'valor' => $valor
                ]);
            }
        }
        
        // Registra a atualização no log
        $this->_db->insert('logs', [
            'usuarioId' => $usuario['id'],
            'acao' => 'configuracoes',
            'detalhes' => 'Configurações atualizadas',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        // Retorna as configurações atualizadas
        $this->listar();
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