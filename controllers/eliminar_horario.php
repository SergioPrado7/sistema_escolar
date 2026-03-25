<?php
session_start();
require_once '../config/database.php';

if (isset($_GET['id'])) {
    $conexion = new Conexion();
    $db = $conexion->getConnection();
    try {
        $stmt = $db->prepare("DELETE FROM horarios WHERE id_horario = :id");
        $stmt->execute([':id' => $_GET['id']]);
    } catch (PDOException $e) {
    }
}
header("Location: ../views/gestion_academica.php");
exit();
