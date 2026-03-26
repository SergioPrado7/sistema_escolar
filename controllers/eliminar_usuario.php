<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'Administrador') {
    header("Location: ../views/dashboard.php");
    exit();
}

if (isset($_GET['id'])) {
    $id_usuario = $_GET['id'];
    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        $stmt_info = $db->prepare("SELECT rol, estatus FROM usuarios WHERE id_usuario = :id");
        $stmt_info->execute([':id' => $id_usuario]);
        $usuario_info = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if ($usuario_info) {
            $rol = $usuario_info['rol'];
            $estatus = strtoupper(trim($usuario_info['estatus']));

            if ($estatus != 'INACTIVO' && $estatus != '0') {
                echo "<script>
                        alert('🛑 ACCIÓN DENEGADA: El usuario está ACTIVO.\\n\\nPor seguridad del sistema, primero debes editarlo y cambiar su estatus a INACTIVO antes de poder eliminarlo por completo.');
                        window.location.href = '../views/gestion_usuarios.php';
                      </script>";
                exit();
            }

            $tiene_datos = false;
            
            if ($rol == 'Alumno') {
                $stmt_check = $db->prepare("SELECT id_carga FROM carga_academica WHERE id_alumno = :id LIMIT 1");
                $stmt_check->execute([':id' => $id_usuario]);
                if ($stmt_check->fetch()) $tiene_datos = true;
                
            } elseif ($rol == 'Profesor') {
                $stmt_check = $db->prepare("SELECT id_horario FROM horarios WHERE id_profesor = :id LIMIT 1");
                $stmt_check->execute([':id' => $id_usuario]);
                if ($stmt_check->fetch()) $tiene_datos = true;
                

                $stmt_check_grupo = $db->prepare("SELECT id_grupo FROM grupos WHERE id_profesor = :id LIMIT 1");
                $stmt_check_grupo->execute([':id' => $id_usuario]);
                if ($stmt_check_grupo->fetch()) $tiene_datos = true;
            }

            
            if ($tiene_datos) {
                echo "<script>
                        alert('🛑 ACCIÓN DENEGADA: El usuario está Inactivo, pero AÚN TIENE MATERIAS o GRUPOS asignados en la Gestión Académica.\\n\\nDebes desasignar toda su carga antes de eliminarlo de la base de datos.');
                        window.location.href = '../views/gestion_usuarios.php';
                      </script>";
                exit();
            }
        }

        $db->beginTransaction();
        
        $stmt_persona = $db->prepare("DELETE FROM personas WHERE id_usuario = :id");
        $stmt_persona->execute([':id' => $id_usuario]);

        $stmt = $db->prepare("DELETE FROM usuarios WHERE id_usuario = :id");
        $stmt->execute([':id' => $id_usuario]);

        $db->commit();
        header("Location: ../views/gestion_usuarios.php");
        exit();

    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        
        if ($e->getCode() == 23000) {
            echo "<script>
                    alert('🛑 ACCIÓN DENEGADA: La base de datos protegió a este usuario porque tiene un historial permanente ligado a él (calificaciones pasadas, recibos, etc.).\\n\\n💡 Solución: Déjalo con estatus INACTIVO. Ya no podrá acceder al sistema.');
                    window.location.href = '../views/gestion_usuarios.php';
                  </script>";
        } else {
            echo "<script>
                    alert('Error inesperado al eliminar: " . addslashes($e->getMessage()) . "');
                    window.location.href = '../views/gestion_usuarios.php';
                  </script>";
        }
    }
} else {
    header("Location: ../views/gestion_usuarios.php");
    exit();
}
?>