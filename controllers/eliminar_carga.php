<?php
session_start();
require_once '../config/database.php';

if (isset($_GET['id_carga']) && isset($_GET['matricula'])) {
    
    $id_carga = $_GET['id_carga'];
    $matricula = $_GET['matricula'];

    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        $query = "DELETE FROM carga_academica WHERE id_carga = :id_carga";
        $stmt = $db->prepare($query);
        
        $stmt->execute([
            ':id_carga' => $id_carga
        ]);

        header("Location: ../views/carga_academica.php?matricula=" . urlencode($matricula));
        exit();

    } catch(PDOException $e) {
        echo "Error al eliminar la materia: " . $e->getMessage();
    }
} else {
   
    echo "¡Acceso denegado! Faltan datos para procesar la solicitud.";
}
?>