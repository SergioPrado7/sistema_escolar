<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_materia = $_POST['id_materia'];
    $clave_materia = $_POST['clave_materia'];
    $nombre_materia = $_POST['nombre_materia'];
    $creditos = $_POST['creditos'];
    $semestre = $_POST['semestre'];
    $id_carrera = empty($_POST['id_carrera']) ? NULL : $_POST['id_carrera'];

    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        $stmt = $db->prepare("UPDATE materias SET clave_materia = :clave, nombre_materia = :nombre, creditos = :creditos, semestre_sugerido = :semestre, id_carrera = :id_carrera WHERE id_materia = :id");
        $stmt->execute([
            ':clave' => $clave_materia,
            ':nombre' => $nombre_materia,
            ':creditos' => $creditos,
            ':semestre' => $semestre,
            ':id_carrera' => $id_carrera,
            ':id' => $id_materia
        ]);
        header("Location: ../views/gestion_academica.php");
        exit();
    } catch (PDOException $e) {
        echo "Error al editar la materia: " . $e->getMessage();
    }
}
