<?php
// 1. Mandamos llamar a tu archivo de conexión
require_once 'config/database.php';

// 2. Creamos un nuevo objeto usando la clase que programaste
$conexion = new Conexion();

// 3. Ejecutamos la función para conectar
$db = $conexion->getConnection();

// 4. Comprobamos si funcionó
if($db){
    echo "<h1 style='color: green;'>¡Modo Pro Activado! Conexión a MariaDB exitosa, sarambambiche.</h1>";
    echo "<p>Tu base de datos 'sistema_escolar' está lista para recibir datos.</p>";
}
?>