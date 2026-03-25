<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo_periodo = trim($_POST['codigo_periodo']);
    $nombre_periodo = trim($_POST['nombre_periodo']);
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];

    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        $query = "INSERT INTO periodos (codigo_periodo, nombre_periodo, fecha_inicio, fecha_fin) 
                  VALUES (:codigo_periodo, :nombre_periodo, :fecha_inicio, :fecha_fin)";
        $stmt = $db->prepare($query);

        $stmt->execute([
            ':codigo_periodo' => $codigo_periodo,
            ':nombre_periodo' => $nombre_periodo,
            ':fecha_inicio' => $fecha_inicio,
            ':fecha_fin' => $fecha_fin
        ]);

        header("Location: ../views/gestion_academica.php");
        exit();
    } catch (PDOException $e) {
        echo "Error de base de datos al guardar el periodo: " . $e->getMessage();
    }
}
