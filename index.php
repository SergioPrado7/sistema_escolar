<?php

require_once 'config/database.php';


$conexion = new Conexion();


$db = $conexion->getConnection();


if($db){
    echo "<h1 style='color: green;'>¡Modo Pro Activado! Conexión a MariaDB exitosa</h1>";
    echo "<p>Tu base de datos 'sistema_escolar' está lista para recibir datos.</p>";
}
?>
