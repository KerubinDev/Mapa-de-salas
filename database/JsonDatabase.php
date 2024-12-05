<?php
class JsonDatabase {
    private static $_instance = null;
    private $_data;
    private $_arquivo;
    
    private function __construct() {
        $this->_arquivo = __DIR__ . '/database.json';
        $this->_carregarDados();
    }
    
    /**
     * Retorna a instância única do banco de dados
     */
    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Carrega os dados do arquivo JSON
     */
    private function _carregarDados() {
        if (!file_exists($this->_arquivo)) {
            $this->_data = [
                'usuarios' => [],
                'salas' => [],
                'turmas' => [],
                'reservas' => [],
                'logs' => [],
                'configuracoes' => []
            ];
            $this->_salvarDados();
        } else {
            $conteudo = file_get_contents($this->_arquivo);
            $this->_data = json_decode($conteudo, true);
        }
    }
    
    /**
     * Salva os dados no arquivo JSON
     */
    private function _salvarDados() {
        $conteudo = json_encode($this->_data, JSON_PRETTY_PRINT);
        if (file_put_contents($this->_arquivo, $conteudo) === false) {
            throw new Exception('Erro ao salvar dados no arquivo');
        }
    }
    
    /**
     * Retorna todos os dados de uma coleção
     */
    public function getData($colecao) {
        return $this->_data[$colecao] ?? [];
    }
    
    /**
     * Define todos os dados de uma coleção
     */
    public function setData($colecao, $dados) {
        $this->_data[$colecao] = $dados;
        $this->_salvarDados();
    }
    
    /**
     * Busca registros em uma coleção com base em critérios
     */
    public function query($colecao, $criterios = []) {
        $dados = $this->getData($colecao);
        
        if (empty($criterios)) {
            return $dados;
        }
        
        return array_filter($dados, function($item) use ($criterios) {
            foreach ($criterios as $campo => $valor) {
                if (!isset($item[$campo]) || $item[$campo] !== $valor) {
                    return false;
                }
            }
            return true;
        });
    }
    
    /**
     * Insere um novo registro em uma coleção
     */
    public function insert($colecao, $dados) {
        $dados['id'] = uniqid();
        $dados['dataCriacao'] = date('Y-m-d H:i:s');
        
        $this->_data[$colecao][] = $dados;
        $this->_salvarDados();
        
        return $dados;
    }
    
    /**
     * Atualiza um registro em uma coleção
     */
    public function update($colecao, $id, $dados) {
        foreach ($this->_data[$colecao] as $indice => $item) {
            if ($item['id'] === $id) {
                $dados['id'] = $id;
                $dados['dataAtualizacao'] = date('Y-m-d H:i:s');
                $this->_data[$colecao][$indice] = array_merge($item, $dados);
                $this->_salvarDados();
                return $this->_data[$colecao][$indice];
            }
        }
        return null;
    }
    
    /**
     * Remove um registro de uma coleção
     */
    public function delete($colecao, $id) {
        foreach ($this->_data[$colecao] as $indice => $item) {
            if ($item['id'] === $id) {
                unset($this->_data[$colecao][$indice]);
                $this->_data[$colecao] = array_values($this->_data[$colecao]);
                $this->_salvarDados();
                return true;
            }
        }
        return false;
    }
}
