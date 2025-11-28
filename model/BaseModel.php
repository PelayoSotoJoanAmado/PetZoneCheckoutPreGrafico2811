<?php
abstract class BaseModel {
    protected $db;
    protected $table;
    
    public function __construct() {
        $this->db = $this->getDB();
    }
    
    protected function getDB() {
        require_once __DIR__ . '/../config/conexion.php';
        return getDB();
    }
    
    protected function getRequestData() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }
        return $data ?? [];
    }
    
    public function getAll($conditions = [], $orderBy = 'id DESC', $limit = null) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];
        
        foreach ($conditions as $field => $value) {
            $sql .= " AND {$field} = ?";
            $params[] = $value;
        }
        
        $sql .= " ORDER BY {$orderBy}";
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $fields = array_keys($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        
        $sql = "INSERT INTO {$this->table} (" . implode(',', $fields) . ") VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute(array_values($data));
    }
    
    public function update($id, $data) {
        $fields = array_keys($data);
        $setClause = implode(' = ?, ', $fields) . ' = ?';
        
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE id = ?";
        $params = array_values($data);
        $params[] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE 1=1";
        $params = [];
        
        foreach ($conditions as $field => $value) {
            $sql .= " AND {$field} = ?";
            $params[] = $value;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    }
}