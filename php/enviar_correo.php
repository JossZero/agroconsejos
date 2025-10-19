<?php
require_once 'conexion.php';

// Verificar si PHPMailer está disponible
$phpmailer_disponible = false;
if (file_exists(__DIR__ . '/../phpmailer/src/PHPMailer.php')) {
    require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../phpmailer/src/SMTP.php';
    require_once __DIR__ . '/../phpmailer/src/Exception.php';
    $phpmailer_disponible = true;
}

function enviarCorreoRecuperacion($correoDestino, $codigo) {
    global $phpmailer_disponible;
    
    if ($phpmailer_disponible) {
        return enviarConPHPMailer($correoDestino, $codigo);
    } else {
        return enviarConMailBasico($correoDestino, $codigo);
    }
}

function enviarConPHPMailer($correoDestino, $codigo) {
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Configuración del servidor SMTP de Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'diegokuevase16@gmail.com'; // REEMPLAZA CON TU GMAIL
        $mail->Password = 'dnbscipzpthqrmuk'; // CONTRASEÑA DE APLICACIÓN
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        
        // Configuración del remitente y destinatario
        $mail->setFrom('tu_correo@gmail.com', 'AgroConsejos');
        $mail->addAddress($correoDestino);
        $mail->addReplyTo('no-reply@agroconsejos.com', 'No Responder');
        
        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = 'Código de Recuperación - AgroConsejos';
        
        $mail->Body = crearCuerpoCorreoHTML($codigo);
        $mail->AltBody = crearCuerpoCorreoTexto($codigo);
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error PHPMailer: " . $mail->ErrorInfo);
        return false;
    }
}

function enviarConMailBasico($correoDestino, $codigo) {
    $asunto = "Código de Recuperación - AgroConsejos";
    $mensaje = crearCuerpoCorreoTexto($codigo);
    $headers = "From: AgroConsejos <no-reply@agroconsejos.com>\r\n";
    $headers .= "Reply-To: no-reply@agroconsejos.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    return mail($correoDestino, $asunto, $mensaje, $headers);
}

function crearCuerpoCorreoHTML($codigo) {
    return "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                background-color: #f4f4f4;
                margin: 0;
                padding: 20px;
            }
            .container { 
                max-width: 600px; 
                margin: 0 auto; 
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            .header { 
                background: linear-gradient(135deg, #4caf50, #2e7d32);
                color: white; 
                padding: 30px; 
                text-align: center; 
            }
            .content { 
                padding: 30px; 
                background: #f9f9f9; 
            }
            .codigo { 
                font-size: 32px; 
                font-weight: bold; 
                color: #4caf50; 
                text-align: center; 
                margin: 25px 0;
                padding: 15px;
                background: white;
                border-radius: 8px;
                border: 2px dashed #4caf50;
                letter-spacing: 5px;
            }
            .footer { 
                text-align: center; 
                padding: 20px; 
                color: #666; 
                font-size: 12px;
                background: white;
            }
            .warning {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                color: #856404;
                padding: 15px;
                border-radius: 5px;
                margin: 15px 0;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🌱 AgroConsejos</h1>
                <h2>Recuperación de Contraseña</h2>
            </div>
            <div class='content'>
                <p>Hemos recibido una solicitud para recuperar tu contraseña en <strong>AgroConsejos</strong>.</p>
                <p>Utiliza el siguiente código de verificación:</p>
                <div class='codigo'>$codigo</div>
                <div class='warning'>
                    <strong>⚠️ Importante:</strong> 
                    <ul>
                        <li>Este código expirará en <strong>5 minutos</strong></li>
                        <li>No compartas este código con nadie</li>
                        <li>Si no solicitaste este cambio, ignora este correo</li>
                    </ul>
                </div>
                <p>Ingresa este código en la página de verificación para continuar con el proceso de recuperación.</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " AgroConsejos. Todos los derechos reservados.</p>
                <p>Este es un mensaje automático, por favor no respondas a este correo.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

function crearCuerpoCorreoTexto($codigo) {
    return "
🌱 AGROCONSEJOS - RECUPERACIÓN DE CONTRASEÑA

Hemos recibido una solicitud para recuperar tu contraseña.

Tu código de verificación es: $codigo

⚠️ IMPORTANTE:
- Este código expirará en 5 minutos
- No compartas este código con nadie
- Si no solicitaste este cambio, ignora este correo

Ingresa este código en la página de verificación para continuar.

--
© " . date('Y') . " AgroConsejos
Este es un mensaje automático, por favor no respondas.
    ";
}

// Procesar solicitud de recuperación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    
    $conexion = new Conexion();
    $conn = $conexion->getConexion();
    
    // Verificar si el correo existe
    $sql = "SELECT id, nombre FROM usuarios WHERE correo = ? AND activo = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Correo no registrado en el sistema']);
        exit;
    }
    
    $usuario = $result->fetch_assoc();
    $stmt->close();
    
    // Generar código de 6 dígitos
    $codigo = sprintf("%06d", mt_rand(1, 999999));
    $expiracion = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    // Eliminar códigos anteriores del mismo usuario
    $sql = "DELETE FROM reseteo_contrasenas WHERE usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario['id']);
    $stmt->execute();
    $stmt->close();
    
    // Guardar código en la base de datos
    $sql = "INSERT INTO reseteo_contrasenas (usuario_id, codigo, expiracion) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $usuario['id'], $codigo, $expiracion);
    
    if ($stmt->execute()) {
        // Enviar correo
        if (enviarCorreoRecuperacion($correo, $codigo)) {
            session_start();
            $_SESSION['correo_recuperacion'] = $correo;
            $_SESSION['intentos_codigo'] = 0;
            $_SESSION['codigo_generado'] = $codigo; // Solo para pruebas
            
            echo json_encode([
                'success' => true, 
                'message' => 'Código de verificación enviado a tu correo electrónico. Revisa tu bandeja de entrada.',
                'phpmailer_usado' => $phpmailer_disponible
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Error al enviar el correo. Por favor, intenta nuevamente.',
                'phpmailer_usado' => $phpmailer_disponible
            ]);
        }
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al generar el código de verificación'
        ]);
    }
    
    $stmt->close();
    $conexion->cerrar();
}
?>