<?php
class Conexion {
    private $host = "localhost";
    private $db_name = "sistema_escolar"; 
    private $username = "root";
    private $password = "Servando_75."; 
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // Conexión usando PDO para mayor seguridad
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>