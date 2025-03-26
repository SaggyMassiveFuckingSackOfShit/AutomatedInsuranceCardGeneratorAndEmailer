<?php
class DatabaseManager {
    private $conn;
    private $tableName;

    public function __construct($host, $username, $password, $database, $tableName) {
        try {
            $this->conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->tableName = $tableName;
        } catch(PDOException $e) {
            throw new Exception("Connection failed: " . $e->getMessage());
        }
    }

    public function insertExcelData($data) {
        try {
            // Get the number of columns in the first row
            $numColumns = count($data[0]);
            
            // Create column list
            $columns = [];
            for ($i = 1; $i <= $numColumns; $i++) {
                $columns[] = "column$i";
            }
            
            // Create placeholders for the SQL query
            $placeholders = str_repeat('?,', $numColumns - 1) . '?';
            
            // Prepare the SQL query with explicit column names
            $sql = "INSERT INTO {$this->tableName} (" . implode(',', $columns) . ") VALUES ($placeholders)";
            $stmt = $this->conn->prepare($sql);
            
            // Insert each row
            foreach ($data as $row) {
                $stmt->execute($row);
            }
            
            return true;
        } catch(PDOException $e) {
            throw new Exception("Error inserting data: " . $e->getMessage());
        }
    }

    public function close() {
        $this->conn = null;
    }
} 