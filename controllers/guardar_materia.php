<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $clave_materia = trim($_POST['clave_materia'] ?? '');
    $nombre_materia = trim($_POST['nombre_materia']);
    $creditos = $_POST['creditos'];
    $semestre = $_POST['semestre'];
    $id_carrera = empty($_POST['id_carrera']) ? NULL : $_POST['id_carrera'];

    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        $stmt = $db->prepare("INSERT INTO materias (clave_materia, nombre_materia, creditos, semestre_sugerido, id_carrera) VALUES (:clave, :nombre, :creditos, :semestre, :id_carrera)");
        $stmt->execute([
            ':clave' => $clave_materia,
            ':nombre' => $nombre_materia,
            ':creditos' => $creditos,
            ':semestre' => $semestre,
            ':id_carrera' => $id_carrera
        ]);
        header("Location: ../views/gestion_academica.php");
        exit();
    } catch (PDOException $e) {
        echo "Error al guardar la materia: " . $e->getMessage();
    }
}
