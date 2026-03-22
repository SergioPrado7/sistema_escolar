<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recibimos lo que escribiste en la ventanita
    $clave = trim($_POST['clave']);
    $nombre = trim($_POST['nombre']);
    $creditos = $_POST['creditos'];
    $semestre = $_POST['semestre'];

    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        // Preparamos la inyección a la base de datos de forma segura (PDO)
        $query = "INSERT INTO materias (clave_materia, nombre_materia, creditos, semestre_sugerido) 
                  VALUES (:clave, :nombre, :creditos, :semestre)";
        $stmt = $db->prepare($query);
        
        $stmt->execute([
            ':clave' => $clave,
            ':nombre' => $nombre,
            ':creditos' => $creditos,
            ':semestre' => $semestre
        ]);

        // Si se guardó bien, lo regresamos a la pantalla de gestión
        header("Location: ../views/gestion_academica.php");
        exit();

    } catch(PDOException $e) {
        echo "Error de base de datos al guardar la materia: " . $e->getMessage();
    }
}
?>