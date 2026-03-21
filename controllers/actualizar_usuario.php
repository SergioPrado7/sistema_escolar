<?php
require_once '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario = $_POST['id_usuario'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $rol = $_POST['rol'];
    $estatus = $_POST['estatus'];
    
    // Atrapamos la carrera (si no viene, será null)
    $id_carrera = isset($_POST['id_carrera']) ? $_POST['id_carrera'] : null;

    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        $db->beginTransaction();

        // 1. Actualizar el nombre en la tabla 'personas'
        $query_persona = "UPDATE personas SET nombre = :nombre, apellido_paterno = :apellido WHERE id_usuario = :id";
        $stmt_persona = $db->prepare($query_persona);
        $stmt_persona->execute([
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':id' => $id_usuario
        ]);

        // 2. Actualizar el rol y estatus en la tabla 'usuarios'
        $query_usuario = "UPDATE usuarios SET rol = :rol, estatus = :estatus WHERE id_usuario = :id";
        $stmt_usuario = $db->prepare($query_usuario);
        $stmt_usuario->execute([
            ':rol' => $rol,
            ':estatus' => $estatus,
            ':id' => $id_usuario
        ]);

        // 3. LÓGICA DE LA CARRERA ('alumnos_detalles')
        if ($rol === 'Alumno' && !empty($id_carrera)) {
            // Verificamos si el alumno ya existía en la tabla detalles
            $query_check = "SELECT id_alumno FROM alumnos_detalles WHERE id_alumno = :id";
            $stmt_check = $db->prepare($query_check);
            $stmt_check->execute([':id' => $id_usuario]);

            if ($stmt_check->rowCount() > 0) {
                // Si ya existe, solo actualizamos su carrera
                $query_detalles = "UPDATE alumnos_detalles SET id_carrera = :id_carrera WHERE id_alumno = :id";
                $stmt_detalles = $db->prepare($query_detalles);
                $stmt_detalles->execute([
                    ':id_carrera' => $id_carrera,
                    ':id' => $id_usuario
                ]);
            } else {
                // Si no existía (ej. era Profesor y lo cambiaron a Alumno), lo insertamos
                $query_detalles = "INSERT INTO alumnos_detalles (id_alumno, id_carrera, semestre_actual, estatus_academico) VALUES (:id, :id_carrera, 1, 'Regular')";
                $stmt_detalles = $db->prepare($query_detalles);
                $stmt_detalles->execute([
                    ':id' => $id_usuario,
                    ':id_carrera' => $id_carrera
                ]);
            }
        } elseif ($rol !== 'Alumno') {
            // Si el rol ya no es Alumno (ej. lo subieron a Profesor), borramos su detalle de carrera para no dejar basura
            $query_del_detalles = "DELETE FROM alumnos_detalles WHERE id_alumno = :id";
            $stmt_del_detalles = $db->prepare($query_del_detalles);
            $stmt_del_detalles->execute([':id' => $id_usuario]);
        }

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