<?php
session_start();
require_once '../config/database.php';

$matricula = isset($_GET['matricula']) ? $_GET['matricula'] : '';

if (isset($_GET['id_carga'])) {
    $conexion = new Conexion();
    $db = $conexion->getConnection();
    try {
        $stmt = $db->prepare("DELETE FROM carga_academica WHERE id_carga = :id");
        $stmt->execute([':id' => $_GET['id_carga']]);
    } catch(PDOException $e) { }
}

// Lo regresamos a la pantalla de gestión, pero le pegamos la matrícula para que la vuelva a buscar solito
header("Location: ../views/gestion_academica.php" . ($matricula ? "?matricula=" . urlencode($matricula) : ""));
exit();
?>