<?php
class JsonDatabase {
    private static $_instance = null;
    private $_dataFile;
    private $_data;
    
    private function __construct() {
        $this->_dataFile = __DIR__ . '/data.json';
        $this->_loadData();
    }
    
    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    private function _loadData() {
        if (!file_exists($this->_dataFile)) {
            $this->_data = [
                'usuarios' => [],
                'salas' => [],
                'turmas' => [],
                'reservas' => [],
                'logs' => [],
                'configuracoes' => []
            ];
            $this->_saveData();
        } else {
            $content = file_get_contents($this->_dataFile);
            $this->_data = json_decode($content, true);
        }
    }
    
    private function _saveData() {
        file_put_contents($this->_dataFile, json_encode($this->_data, JSON_PRETTY_PRINT));
    }
    
    public function getData($collection) {
        return $this->_data[$collection] ?? [];
    }
    
    public function query($collection, $filters = []) {
        $data = $this->getData($collection);
        
        if (empty($filters)) {
            return $data;
        }
        
        return array_filter($data, function($item) use ($filters) {
            foreach ($filters as $key => $value) {
                if (!isset($item[$key]) || $item[$key] !== $value) {
                    return false;
                }
            }
            return true;
        });
    }
    
    public function insert($collection, $data) {
        if (!isset($this->_data[$collection])) {
            $this->_data[$collection] = [];
        }
        
        $data['id'] = uniqid();
        $data['dataCriacao'] = date('Y-m-d H:i:s');
        
        $this->_data[$collection][] = $data;
        $this->_saveData();
        
        return $data;
    }
    
    public function update($collection, $id, $data) {
        foreach ($this->_data[$collection] as $key => $item) {
            if ($item['id'] === $id) {
                $data['id'] = $id;
                $data['dataAtualizacao'] = date('Y-m-d H:i:s');
                $this->_data[$collection][$key] = array_merge($item, $data);
                $this->_saveData();
                return $this->_data[$collection][$key];
            }
        }
        return null;
    }
    
    public function delete($collection, $id) {
        foreach ($this->_data[$collection] as $key => $item) {
            if ($item['id'] === $id) {
                unset($this->_data[$collection][$key]);
                $this->_data[$collection] = array_values($this->_data[$collection]);
                $this->_saveData();
                return true;
            }
        }
        return false;
    }
}
