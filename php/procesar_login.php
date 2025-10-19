<?php
require_once 'conexion.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$conexion = new Conexion();
$conn = $conexion->getConexion();

$correo = trim($_POST['correo'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';
$rol = $_POST['rol'] ?? '';

// Buscar usuario
$sql = "SELECT id, nombre, correo, contrasena, rol FROM usuarios WHERE correo = ? AND activo = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    exit;
}

$usuario = $result->fetch_assoc();
$stmt->close();

// Verificar contraseña desencriptando
$contrasena_desencriptada = $conexion->desencriptarContrasena($usuario['contrasena']);

if ($contrasena_desencriptada !== $contrasena) {
    echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta']);
    exit;
}

// Verificar rol
if ($usuario['rol'] !== $rol) {
    echo json_encode(['success' => false, 'message' => 'El rol seleccionado no coincide con este usuario']);
    exit;
}

// Iniciar sesión
$_SESSION['usuario_id'] = $usuario['id'];
$_SESSION['usuario_nombre'] = $usuario['nombre'];
$_SESSION['usuario_correo'] = $usuario['correo'];
$_SESSION['usuario_rol'] = $usuario['rol'];

// Registrar en bitácora
$sql_bitacora = "INSERT INTO bitacora_accesos (usuario_id) VALUES (?)";
$stmt_bitacora = $conn->prepare($sql_bitacora);
$stmt_bitacora->bind_param("i", $usuario['id']);
$stmt_bitacora->execute();
$_SESSION['bitacora_id'] = $stmt_bitacora->insert_id;
$stmt_bitacora->close();

// Redirigir según rol
if ($rol === 'administrador') {
    echo json_encode([
        'success' => true, 
        'redirect' => 'administrador.html'
    ]);
} else {
    echo json_encode([
        'success' => true, 
        'redirect' => 'usuario.html'
    ]);
}

$conexion->cerrar();
?>