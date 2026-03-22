<?php
session_start();
require_once '../config/database.php';

if (isset($_GET['id'])) {
    $conexion = new Conexion();
    $db = $conexion->getConnection();
    try {
        $stmt = $db->prepare("DELETE FROM periodos WHERE id_periodo = :id");
        $stmt->execute([':id' => $_GET['id']]);
    } catch(PDOException $e) {
        // La base de datos nos protege: si el periodo ya tiene grupos reales con alumnos, 
        // bloqueará la eliminación para no borrar el historial de la escuela.
    }
}

// Lo regresamos a la pantalla maestra
header("Location: ../views/gestion_academica.php");
exit();
?>