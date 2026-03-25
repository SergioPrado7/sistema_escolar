<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_horario = $_POST['id_horario'];
    $id_grupo = $_POST['id_grupo'];
    $nombre_grupo = strtoupper(trim($_POST['nombre_grupo'])); 
    $id_profesor = $_POST['id_profesor'];
    $dia_semana = $_POST['dia_semana'];
    $cupo_maximo = $_POST['cupo_maximo'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = $_POST['hora_fin'];

    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        $db->beginTransaction();

        $stmt_bloque = $db->prepare("UPDATE horarios SET dia_semana = ?, hora_inicio = ?, hora_fin = ? WHERE id_horario = ?");
        $stmt_bloque->execute([$dia_semana, $hora_inicio, $hora_fin, $id_horario]);

        $stmt_grupo_global = $db->prepare("UPDATE horarios SET id_profesor = ?, cupo_maximo = ? WHERE id_grupo = ?");
        $stmt_grupo_global->execute([$id_profesor, $cupo_maximo, $id_grupo]);

        $stmt_tabla_grupos = $db->prepare("UPDATE grupos SET nombre_grupo = ?, id_profesor = ? WHERE id_grupo = ?");
        $stmt_tabla_grupos->execute([$nombre_grupo, $id_profesor, $id_grupo]);

        $db->commit();
        header("Location: ../views/gestion_academica.php");
        exit();

    } catch (PDOException $e) {
        $db->rollBack();
        die("Error al actualizar el horario: " . $e->getMessage());
    }
}
?>
