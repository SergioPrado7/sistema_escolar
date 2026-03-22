<?php
require_once '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario = $_POST['id_usuario'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];
    $rol = $_POST['rol'];
    $estatus = $_POST['estatus'];

    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        $db->beginTransaction();

        // Actualizar el nombre en la tabla 'personas'
        $query_persona = "UPDATE personas SET nombre = :nombre, apellido_paterno = :apellido WHERE id_usuario = :id";
        $stmt_persona = $db->prepare($query_persona);
        $stmt_persona->execute([
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':id' => $id_usuario
        ]);

        //  Actualizar el rol y estatus en la tabla 'usuarios'
        $query_usuario = "UPDATE usuarios SET correo = :correo, rol = :rol, estatus = :estatus WHERE id_usuario = :id";
        $stmt_usuario = $db->prepare($query_usuario);
        $stmt_usuario->execute([
            ':correo' => $correo,
            ':rol' => $rol,
            ':estatus' => $estatus,
            ':id' => $id_usuario
        ]);

        $db->commit();
        
        // Regresamos a la tabla principal
        header("Location: ../views/gestion_usuarios.php");
        exit();

    } catch (PDOException $e) {
        $db->rollBack();
        echo "Error al actualizar: " . $e->getMessage();
    }
} else {
    header("Location: ../views/gestion_usuarios.php");
    exit();
}
?>