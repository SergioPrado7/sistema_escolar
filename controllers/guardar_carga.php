<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_alumno = $_POST['id_alumno'];
    $id_grupo = $_POST['id_grupo']; // Ahora recibimos el Grupo completo
    $matricula_buscada = $_POST['matricula_buscada'];

    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        // 1. Buscar todos los horarios (Lunes, Miércoles, etc.) que tiene este grupo
        $stmt_horarios = $db->prepare("SELECT id_horario FROM horarios WHERE id_grupo = :id_grupo");
        $stmt_horarios->execute([':id_grupo' => $id_grupo]);
        $dias_del_grupo = $stmt_horarios->fetchAll(PDO::FETCH_ASSOC);

        // 2. Inscribir al alumno en cada uno de esos días con un ciclo
        $stmt_insert = $db->prepare("INSERT INTO carga_academica (id_alumno, id_horario) VALUES (:id_alumno, :id_horario)");
        
        foreach ($dias_del_grupo as $dia) {
            // Verificamos que no esté inscrito ya en ese día para que no marque error la BD
            $stmt_check = $db->prepare("SELECT id_carga FROM carga_academica WHERE id_alumno = :id_alumno AND id_horario = :id_horario");
            $stmt_check->execute([':id_alumno' => $id_alumno, ':id_horario' => $dia['id_horario']]);
            
            if ($stmt_check->rowCount() == 0) {
                // Si no está inscrito, lo guardamos
                $stmt_insert->execute([
                    ':id_alumno' => $id_alumno,
                    ':id_horario' => $dia['id_horario']
                ]);
            }
        }

        // Lo regresamos a la página con la matrícula cargada
        header("Location: ../views/gestion_academica.php?matricula=" . urlencode($matricula_buscada));
        exit();

    } catch(PDOException $e) {
        echo "Error al asignar materia: " . $e->getMessage();
    }
} else {
    header("Location: ../views/gestion_academica.php");
    exit();
}
?>