<?php
require_once 'config.php';  // ← CARGA LA CONFIGURACIÓN

class Conexion {
    private $host = Config::DB_HOST;
    private $usuario = Config::DB_USER;
    private $password = Config::DB_PASS;
    private $database = Config::DB_NAME;
    private $conn;

    // Usa la clave de la configuración centralizada
    private $clave_secreta = Config::ENCRYPTION_KEY;

    public function __construct() {
        try {
            $this->conn = new mysqli($this->host, $this->usuario, $this->password, $this->database, 3306);

            
            if ($this->conn->connect_error) {
                throw new Exception("Error de conexión MySQL: " . $this->conn->connect_error);
            }
            
            $this->conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log("Error de base de datos: " . $e->getMessage());
            die("Error en el sistema. Por favor, intenta más tarde.");
        }
    }

    public function getConexion() {
        return $this->conn;
    }

    public function cerrar() {
        if ($this->conn) {
            $this->conn->close();
        }
    }

    // Método mejorado para encriptar contraseñas
    public function encriptarContrasena($contrasena) {
        try {
            // Generar IV único para cada encriptación
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
            
            // Encriptar usando AES-256-CBC
            $contrasena_encriptada = openssl_encrypt(
                $contrasena, 
                'aes-256-cbc', 
                $this->clave_secreta, 
                0, 
                $iv
            );
            
            if ($contrasena_encriptada === false) {
                throw new Exception("Error en encriptación");
            }
            
            // Combinar IV con texto encriptado
            return base64_encode($contrasena_encriptada . '::' . $iv);
            
        } catch (Exception $e) {
            error_log("Error al encriptar: " . $e->getMessage());
            return false;
        }
    }

    // Método mejorado para desencriptar contraseñas
    public function desencriptarContrasena($contrasena_encriptada) {
        try {
            // Separar el IV del texto encriptado
            $parts = explode('::', base64_decode($contrasena_encriptada), 2);
            
            if (count($parts) !== 2) {
                throw new Exception("Formato de contraseña encriptada inválido");
            }
            
            list($contrasena, $iv) = $parts;
            
            // Desencriptar
            $contrasena_desencriptada = openssl_decrypt(
                $contrasena, 
                'aes-256-cbc', 
                $this->clave_secreta, 
                0, 
                $iv
            );
            
            if ($contrasena_desencriptada === false) {
                throw new Exception("Error en desencriptación");
            }
            
            return $contrasena_desencriptada;
            
        } catch (Exception $e) {
            error_log("Error al desencriptar: " . $e->getMessage());
            return false;
        }
    }
}
?>
