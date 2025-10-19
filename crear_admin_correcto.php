<?php
require_once 'php/conexion.php';

$conexion = new Conexion();
$conn = $conexion->getConexion();

// La contraseña que quieres usar
$contrasena_plana = "Admin123!";

// Encriptar la contraseña usando tu sistema
$contrasena_encriptada = $conexion->encriptarContrasena($contrasena_plana);

if ($contrasena_encriptada) {
    // Insertar en la base de datos
    $sql = "INSERT INTO usuarios (nombre, correo, telefono, contrasena, rol) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    $nombre = "Administrador Principal";
    $correo = "admin@agroconsejos.com";
    $telefono = "1234567890";
    $rol = "administrador";
    
    $stmt->bind_param("sssss", $nombre, $correo, $telefono, $contrasena_encriptada, $rol);
    
    if ($stmt->execute()) {
        echo "✅ Administrador creado CORRECTAMENTE<br>";
        echo "📧 Correo: admin@agroconsejos.com<br>";
        echo "🔑 Contraseña: Admin123!<br>";
        echo "🔒 Contraseña encriptada en BD: " . $contrasena_encriptada . "<br>";
        echo "👤 Rol: Administrador";
    } else {
        echo "❌ Error al crear administrador: " . $conn->error;
    }
} else {
    echo "❌ Error al encriptar la contraseña";
}

$conexion->cerrar();
?>