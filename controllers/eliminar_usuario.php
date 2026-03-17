<?php
// Llamamos a la conexión
require_once '../config/database.php';


if (isset($_GET['id'])) {
    $id_usuario = $_GET['id'];

    // Nos conectamos a MariaDB
    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        // Iniciamos la transacción de borrado seguro
        $db->beginTransaction();

        // Borramos primero de la tabla personas 
        $query_persona = "DELETE FROM personas WHERE id_usuario = :id";
        $stmt_persona = $db->prepare($query_persona);
        $stmt_persona->execute([':id' => $id_usuario]);

        //  Borramos finalmente al usuario del sistema
        $query_usuario = "DELETE FROM usuarios WHERE id_usuario = :id";
        $stmt_usuario = $db->prepare($query_usuario);
        $stmt_usuario->execute([':id' => $id_usuario]);

        // Confirmamos el borrado
        $db->commit();

    } catch (PDOException $e) {
        // Si hay error, cancelamos el borrado
        $db->rollBack();
        echo "Error al eliminar: " . $e->getMessage();
        exit();
    }
}

// 4. Regresamos a la tabla sin que el usuario note nada
header("Location: ../views/gestion_usuarios.php");
exit();
?>