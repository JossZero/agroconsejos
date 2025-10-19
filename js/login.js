document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formLogin');
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

    // Envío del formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        iniciarSesion();
    });
});

function iniciarSesion() {
    const formData = new FormData(document.getElementById('formLogin'));
    const mensajeLogin = document.getElementById('mensajeLogin');

    fetch('php/procesar_login.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mensajeLogin.textContent = 'Inicio de sesión exitoso. Redirigiendo...';
            mensajeLogin.className = 'success-message';
            mensajeLogin.style.display = 'block';
            
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1500);
        } else {
            mensajeLogin.textContent = data.message;
            mensajeLogin.className = 'error-message';
            mensajeLogin.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mensajeLogin.textContent = 'Error al procesar la solicitud';
        mensajeLogin.className = 'error-message';
        mensajeLogin.style.display = 'block';
    });
}