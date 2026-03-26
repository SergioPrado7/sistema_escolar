<?php
session_start();
require_once '../config/database.php';

if (isset($_GET['id'])) {
    $conexion = new Conexion();
    $db = $conexion->getConnection();
    $id_materia = $_GET['id'];
    
    try {
        $db->beginTransaction();

        $stmt1 = $db->prepare("DELETE FROM carga_academica WHERE id_horario IN (SELECT id_horario FROM horarios WHERE id_materia = :id)");
        $stmt1->execute([':id' => $id_materia]);

        $stmt2 = $db->prepare("DELETE FROM horarios WHERE id_materia = :id");
        $stmt2->execute([':id' => $id_materia]);

        $stmt3 = $db->prepare("DELETE FROM grupos WHERE id_materia = :id");
        $stmt3->execute([':id' => $id_materia]);

        $stmt4 = $db->prepare("DELETE FROM materias WHERE id_materia = :id");
        $stmt4->execute([':id' => $id_materia]);
        
        $db->commit();
        header("Location: ../views/gestion_academica.php");
        exit();
        
    } catch (PDOException $e) {
        $db->rollBack();
        echo "<script>
                alert('Error del sistema al intentar aplicar el borrado en cascada: " . addslashes($e->getMessage()) . "'); 
                window.location.href='../views/gestion_academica.php';
              </script>";
        exit();
    }
} else {
    header("Location: ../views/gestion_academica.php");
    exit();
}
?>
