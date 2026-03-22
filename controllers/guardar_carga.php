<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_alumno = $_POST['id_alumno'];
    $id_horario = $_POST['id_horario'];
    $matricula = $_POST['matricula_buscada'];

    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        // Checamos que el alumno no tenga inscrita esa clase ya
        $stmt_check = $db->prepare("SELECT id_carga FROM carga_academica WHERE id_alumno = :id_alumno AND id_horario = :id_horario");
        $stmt_check->execute([':id_alumno' => $id_alumno, ':id_horario' => $id_horario]);
        
        if (!$stmt_check->fetch()) {
            // Si no la tiene, lo inscribimos
            $stmt = $db->prepare("INSERT INTO carga_academica (id_alumno, id_horario) VALUES (:id_alumno, :id_horario)");
            $stmt->execute([':id_alumno' => $id_alumno, ':id_horario' => $id_horario]);
        }

        // Lo regresamos a la pantalla de gestión, cargando su matrícula automáticamente
        header("Location: ../views/gestion_academica.php?matricula=" . urlencode($matricula));
        exit();

    } catch(PDOException $e) {
        echo "Error al asignar la materia: " . $e->getMessage();
    }
}
?>