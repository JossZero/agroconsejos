document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formRegistro');
    const ojoContrasena = document.getElementById('ojoContrasena');
    const inputContrasena = document.getElementById('contrasena');

    // Función para mostrar/ocultar contraseña
    ojoContrasena.addEventListener('click', function() {
        if (inputContrasena.type === 'password') {
            inputContrasena.type = 'text';
            this.textContent = '🔒';
        } else {
            inputContrasena.type = 'password';
            this.textContent = '👁️';
        }
    });

    // Validación en tiempo real
    document.getElementById('nombre').addEventListener('blur', validarNombre);
    document.getElementById('correo').addEventListener('blur', validarCorreo);
    document.getElementById('telefono').addEventListener('blur', validarTelefono);
    document.getElementById('contrasena').addEventListener('blur', validarContrasena);

    // Envío del formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validarFormularioCompleto()) {
            registrarUsuario();
        }
    });
});

function registrarUsuario() {
    const formData = new FormData(document.getElementById('formRegistro'));

    fetch('php/procesar_registro.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const mensajeGeneral = document.getElementById('mensajeGeneral');
        
        if (data.success) {
            mensajeGeneral.textContent = data.message;
            mensajeGeneral.className = 'success-message';
            mensajeGeneral.style.display = 'block';
            
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
        } else {
            mensajeGeneral.textContent = data.message;
            mensajeGeneral.className = 'error-message';
            mensajeGeneral.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarErrorGeneral('Error al procesar la solicitud');
    });
}

function mostrarErrorGeneral(mensaje) {
    const mensajeGeneral = document.getElementById('mensajeGeneral');
    mensajeGeneral.textContent = mensaje;
    mensajeGeneral.className = 'error-message';
    mensajeGeneral.style.display = 'block';
}