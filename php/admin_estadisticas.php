<?php
require_once 'conexion.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$conexion = new Conexion();
$conn = $conexion->getConexion();

// Obtener estadísticas
$estadisticas = [];

// Total de usuarios
$sql = "SELECT COUNT(*) as total FROM usuarios";
$result = $conn->query($sql);
$estadisticas['totalUsuarios'] = $result->fetch_assoc()['total'];

// Total de variables agrícolas
$sql = "SELECT COUNT(*) as total FROM variables_agricolas";
$result = $conn->query($sql);
$estadisticas['totalVariables'] = $result->fetch_assoc()['total'];

// Total de entradas de blog
$sql = "SELECT COUNT(*) as total FROM blog_entradas";
$result = $conn->query($sql);
$estadisticas['totalBlogs'] = $result->fetch_assoc()['total'];

// Sesiones de hoy
$sql = "SELECT COUNT(*) as total FROM bitacora_accesos WHERE DATE(fecha_entrada) = CURDATE()";
$result = $conn->query($sql);
$estadisticas['sesionesHoy'] = $result->fetch_assoc()['total'];

echo json_encode(['success' => true] + $estadisticas);
$conexion->cerrar();
?>