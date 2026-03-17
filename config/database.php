<?php
class Conexion {
    private $host = "nue.domcloud.co";
    private $db_name = "sitiosergio_sistema_escolar"; 
    private $username = "sitiosergio";
    private $password = "j_6)55P_zdHiP8jY2W"; 
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
        <s></s>
    }
}
?>