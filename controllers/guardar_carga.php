<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_alumno = $_POST['id_alumno'];
    $id_grupo = $_POST['id_grupo'];
    $matricula_buscada = $_POST['matricula_buscada'];

    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        $stmt_horarios = $db->prepare("SELECT id_horario FROM horarios WHERE id_grupo = :id_grupo");
        $stmt_horarios->execute([':id_grupo' => $id_grupo]);
        $dias_del_grupo = $stmt_horarios->fetchAll(PDO::FETCH_ASSOC);

        $stmt_insert = $db->prepare("INSERT INTO carga_academica (id_alumno, id_horario) VALUES (:id_alumno, :id_horario)");

        foreach ($dias_del_grupo as $dia) {
            $stmt_check = $db->prepare("SELECT id_carga FROM carga_academica WHERE id_alumno = :id_alumno AND id_horario = :id_horario");
            $stmt_check->execute([':id_alumno' => $id_alumno, ':id_horario' => $dia['id_horario']]);

            if ($stmt_check->rowCount() == 0) {
                $stmt_insert->execute([
                    ':id_alumno' => $id_alumno,
                    ':id_horario' => $dia['id_horario']
                ]);
            }
        }

        header("Location: ../views/gestion_academica.php?matricula=" . urlencode($matricula_buscada));
        exit();
    } catch (PDOException $e) {
        echo "Error al asignar materia: " . $e->getMessage();
    }
} else {
    header("Location: ../views/gestion_academica.php");
    exit();
}
