<?php
// Llamamos a la conexión
require_once '../config/database.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
   
    $matricula = $_POST['matricula'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo    = $_POST['correo'];
    $rol = $_POST['rol'];
    $password_plana = $_POST['password'];

    // Encriptamos la contraseña por seguridad
    $password_hash = password_hash($password_plana, PASSWORD_DEFAULT);

  
    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
       
        $db->beginTransaction();

        
        $query_usuario = "INSERT INTO usuarios (matricula, correo, password, rol, estatus) VALUES (:matricula, :correo, :password, :rol, 'Activo')";
        $stmt_usuario = $db->prepare($query_usuario);
        $stmt_usuario->execute([
            ':matricula' => $matricula,
            ':correo' => $correo,
            ':password' => $password_hash,
            ':rol' => $rol,
        ]);

        
        $id_nuevo_usuario = $db->lastInsertId();

       
        $query_persona = "INSERT INTO personas (id_usuario, nombre, apellido_paterno) VALUES (:id_usuario, :nombre, :apellido)";
        $stmt_persona = $db->prepare($query_persona);
        $stmt_persona->execute([
            ':id_usuario' => $id_nuevo_usuario,
            ':nombre' => $nombre,
            ':apellido' => $apellido
        ]);

       
        $db->commit();        
        header("Location: ../views/gestion_usuarios.php");
        exit();

    } catch (PDOException $e) {
        $db->rollBack();
        
        if ($e->getCode() == 23000) {
           
            header("Location: ../views/gestion_usuarios.php?error=duplicado");
        } else {
          
            header("Location: ../views/gestion_usuarios.php?error=general");
        }
        exit();
    }
} else {
    
    header("Location: ../views/gestion_usuarios.php");
    exit();
}
?>