<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conexion = new Conexion();
    $db = $conexion->getConnection();

    $id_alumno = $_POST['id_alumno'];
    $id_horario = $_POST['id_horario'];
    $matricula_buscada = $_POST['matricula_buscada'];

    try {
        $query = "INSERT INTO carga_academica (id_alumno, id_horario) VALUES (:id_alumno, :id_horario)";
        $stmt = $db->prepare($query);
        
        $stmt->execute([
            ':id_alumno' => $id_alumno,
            ':id_horario' => $id_horario
        ]);

        header("Location: ../views/carga_academica.php?matricula=" . urlencode($matricula_buscada));
        exit();

    } catch(PDOException $e) {
        echo "Error de base de datos: " . $e->getMessage();
    }
}
?>