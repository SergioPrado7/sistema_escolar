<?php
session_start();
require_once '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['id_usuario'])) {
    $id_usuario = $_SESSION['id_usuario'];
    $old_pass = $_POST['old_pass'];
    $new_pass = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_pass'];

    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        $db->beginTransaction();

        $query_check = "SELECT password FROM usuarios WHERE id_usuario = :id";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->execute([':id' => $id_usuario]);
        $user = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['password'] === $old_pass) {
            
            if ($new_pass === $confirm_pass) {
                $query_update = "UPDATE usuarios SET password = :pass WHERE id_usuario = :id";
                $stmt_update = $db->prepare($query_update);
                $stmt_update->execute([
                    ':pass' => $new_pass,
                    ':id' => $id_usuario
                ]);

                $db->commit();
                header("Location: ../views/dashboard.php?success=password");
                exit();
            } else {
                $db->rollBack();
                header("Location: ../views/dashboard.php?error=coincidencia");
                exit();
            }
        } else {
            $db->rollBack();
            header("Location: ../views/dashboard.php?error=incorrecta");
            exit();
        }

    } catch (PDOException $e) {
        $db->rollBack();
        echo "Error al actualizar: " . $e->getMessage();
    }
} else {
    header("Location: ../views/dashboard.php");
    exit();
}