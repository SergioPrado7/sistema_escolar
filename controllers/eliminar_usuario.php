<?php
// 1. Llamamos a la conexión
require_once '../config/database.php';

// 2. Verificamos que sí nos hayan mandado un ID por la URL al darle clic al botón
if (isset($_GET['id'])) {
    $id_usuario = $_GET['id'];

    // 3. Nos conectamos a MariaDB
    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        // Iniciamos la transacción de borrado seguro
        $db->beginTransaction();

        // PASO A: Borramos primero de la tabla personas (si es que existe ahí)
        $query_persona = "DELETE FROM personas WHERE id_usuario = :id";
        $stmt_persona = $db->prepare($query_persona);
        $stmt_persona->execute([':id' => $id_usuario]);

        // PASO B: Borramos finalmente al usuario del sistema
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

// 4. Regresamos mágicamente a la tabla sin que el usuario note nada
header("Location: ../views/gestion_usuarios.php");
exit();
?>