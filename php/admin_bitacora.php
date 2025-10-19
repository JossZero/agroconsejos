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

// Obtener parámetros de filtro
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

// Construir consulta con filtros
$sql = "SELECT b.id, u.nombre, u.correo, b.fecha_entrada, b.fecha_salida, 
               b.tiempo_sesion, b.activo,
               TIMESTAMPDIFF(MINUTE, b.fecha_entrada, COALESCE(b.fecha_salida, NOW())) as minutos_activo
        FROM bitacora_accesos b
        JOIN usuarios u ON b.usuario_id = u.id";

$whereConditions = [];
$params = [];
$types = '';

// Aplicar filtros de fecha
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $whereConditions[] = "DATE(b.fecha_entrada) BETWEEN ? AND ?";
    $params[] = $fecha_inicio;
    $params[] = $fecha_fin;
    $types .= 'ss';
}

if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}

$sql .= " ORDER BY b.fecha_entrada DESC LIMIT 1000";

$stmt = $conn->prepare($sql);

// Bind parameters si hay filtros
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$registros = [];
while ($row = $result->fetch_assoc()) {
    // Formatear tiempo de sesión
    if ($row['fecha_salida']) {
        $horas = floor($row['tiempo_sesion'] / 3600);
        $minutos = floor(($row['tiempo_sesion'] % 3600) / 60);
        $segundos = $row['tiempo_sesion'] % 60;
        
        if ($horas > 0) {
            $tiempo = sprintf("%02d:%02d:%02d", $horas, $minutos, $segundos);
        } else {
            $tiempo = sprintf("%02d:%02d", $minutos, $segundos);
        }
    } else {
        // Sesión activa, calcular tiempo transcurrido
        $minutos_activo = $row['minutos_activo'];
        if ($minutos_activo < 60) {
            $tiempo = $minutos_activo . ' min';
        } else {
            $horas = floor($minutos_activo / 60);
            $minutos = $minutos_activo % 60;
            $tiempo = $horas . 'h ' . $minutos . 'min';
        }
    }
    
    $registros[] = [
        'usuario' => $row['nombre'] . ' (' . $row['correo'] . ')',
        'fecha_entrada' => date('d/m/Y H:i:s', strtotime($row['fecha_entrada'])),
        'fecha_salida' => $row['fecha_salida'] ? date('d/m/Y H:i:s', strtotime($row['fecha_salida'])) : 'Sesión activa',
        'tiempo_sesion' => $tiempo,
        'estado' => $row['activo'] ? 
            '<span style="color: green; font-weight: bold;">● Activo</span>' : 
            '<span style="color: red;">● Inactivo</span>'
    ];
}

$stmt->close();

echo json_encode(['success' => true, 'data' => $registros]);
$conexion->cerrar();
?>