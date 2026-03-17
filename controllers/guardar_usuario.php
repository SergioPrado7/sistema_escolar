<?php
// Llamamos a la conexión
require_once '../config/database.php';

// Verificamos que los datos hayan llegado desde el formulario por el método POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Recibimos los datos que el usuario escribió en las cajitas de texto
    $matricula = $_POST['matricula'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $rol = $_POST['rol'];
    $password_plana = $_POST['password'];

    // Encriptamos la contraseña por seguridad (Modo Pro)
    $password_hash = password_hash($password_plana, PASSWORD_DEFAULT);

    // Nos conectamos a MariaDB
    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        // Iniciamos una "Transacción" (Si algo falla, no se guarda nada a medias)
        $db->beginTransaction();

        // PASO A: Insertar primero en la tabla `usuarios`
        $query_usuario = "INSERT INTO usuarios (matricula, password, rol, estatus) VALUES (:matricula, :password, :rol, 'Activo')";
        $stmt_usuario = $db->prepare($query_usuario);
        $stmt_usuario->execute([
            ':matricula' => $matricula,
            ':password' => $password_hash,
            ':rol' => $rol
        ]);

        // Obtenemos el ID que MariaDB le acaba de asignar a ese nuevo usuario
        $id_nuevo_usuario = $db->lastInsertId();

        // PASO B: Insertar en la tabla `personas` usando el ID que acabamos de obtener
        $query_persona = "INSERT INTO personas (id_usuario, nombre, apellido_paterno) VALUES (:id_usuario, :nombre, :apellido)";
        $stmt_persona = $db->prepare($query_persona);
        $stmt_persona->execute([
            ':id_usuario' => $id_nuevo_usuario,
            ':nombre' => $nombre,
            ':apellido' => $apellido
        ]);

        // Si todo salió bien, confirmamos y guardamos permanentemente
        $db->commit();

        // 6. Redirigimos mágicamente de vuelta a la pantalla visual
        header("Location: ../views/gestion_usuarios.php");
        exit();

    } catch (PDOException $e) {
        // Si hay un error (ejemplo: matrícula repetida), cancelamos todo y mostramos el error
        $db->rollBack();
        echo "Error al guardar el usuario: " . $e->getMessage();
    }
} else {
    // Si alguien intenta entrar a este archivo directo desde el navegador, lo regresamos
    header("Location: ../views/gestion_usuarios.php");
    exit();
}
?>