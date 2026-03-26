<?php
session_start();
require_once '../config/database.php';


if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
    header("Location: ../index.php");
    exit();
}

$matricula = isset($_GET['matricula']) ? trim($_GET['matricula']) : '';


if (isset($_GET['id_carga']) && !empty($_GET['id_carga'])) {
    
    $conexion = new Conexion();
    $db = $conexion->getConnection();
    
    try {
   
        $id_carga = intval($_GET['id_carga']); 
        
        $stmt = $db->prepare("DELETE FROM carga_academica WHERE id_carga = :id");
        $stmt->execute([':id' => $id_carga]);
        
    } catch (PDOException $e) {
    }
}


header("Location: ../views/gestion_academica.php" . ($matricula ? "?matricula=" . urlencode($matricula) : ""));
exit();
?>