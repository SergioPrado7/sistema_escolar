<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_horario = $_POST['id_horario'];
    $id_profesor = $_POST['id_profesor'];
    $cupo_maximo = $_POST['cupo_maximo'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = $_POST['hora_fin'];

    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        $stmt = $db->prepare("UPDATE horarios SET id_profesor = :id_profesor, cupo_maximo = :cupo_maximo, hora_inicio = :hora_inicio, hora_fin = :hora_fin WHERE id_horario = :id");
        $stmt->execute([
            ':id_profesor' => $id_profesor,
            ':cupo_maximo' => $cupo_maximo,
            ':hora_inicio' => $hora_inicio,
            ':hora_fin' => $hora_fin,
            ':id' => $id_horario
        ]);
    } catch(PDOException $e) { }
}
header("Location: ../views/gestion_academica.php");
exit();
?>