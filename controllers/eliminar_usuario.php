<?php
session_start();
require_once '../config/database.php';

// Verificamos que sea administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'Administrador') {
    header("Location: ../views/dashboard.php");
    exit();
}

if (isset($_GET['id'])) {
    $id_usuario = $_GET['id'];
    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        // Intentamos borrar al usuario
        $stmt = $db->prepare("DELETE FROM usuarios WHERE id_usuario = :id");
        $stmt->execute([':id' => $id_usuario]);
        
        // Si se pudo borrar, lo regresamos a la tabla
        header("Location: ../views/gestion_usuarios.php");
        exit();

    } catch (PDOException $e) {
        // AQUÍ ESTÁ EL ESCUDO
        // Si el error es de llave foránea (código 23000)
        if ($e->getCode() == 23000) {
            echo "<script>
                    alert('🛑 ACCIÓN DENEGADA: No puedes eliminar a este usuario porque ya tiene datos ligados en el sistema (grupos asignados, calificaciones, etc.).\\n\\n💡 Solución: Edita al usuario y cambia su estatus a INACTIVO.');
                    window.location.href = '../views/gestion_usuarios.php';
                  </script>";
        } else {
            // Si es otro error raro, lo mostramos
            echo "Error inesperado al eliminar: " . $e->getMessage();
        }
    }
} else {
    header("Location: ../views/gestion_usuarios.php");
    exit();
}
?>