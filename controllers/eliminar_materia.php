<?php
session_start();
require_once '../config/database.php';

if (isset($_GET['id'])) {
    $conexion = new Conexion();
    $db = $conexion->getConnection();
    try {
        $stmt = $db->prepare("DELETE FROM materias WHERE id_materia = :id");
        $stmt->execute([':id' => $_GET['id']]);
    } catch(PDOException $e) {
        // Si marca error es porque la materia ya está asignada a un grupo y no se puede borrar
    }
}
header("Location: ../views/gestion_academica.php");
exit();
?>