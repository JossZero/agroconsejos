document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formCambiarContrasena');
    const mensajeCambio = document.getElementById('mensajeCambio');
    const ojoContrasena = document.getElementById('ojoContrasena');
    const ojoConfirmar = document.getElementById('ojoConfirmar');
    const inputContrasena = document.getElementById('nueva_contrasena');
    const inputConfirmar = document.getElementById('confirmar_contrasena');

    // Funcionalidad para mostrar/ocultar contraseñas
    ojoContrasena.addEventListener('click', function() {
        togglePasswordVisibility(inputContrasena, this);
    });

    ojoConfirmar.addEventListener('click', function() {
        togglePasswordVisibility(inputConfirmar, this);
    });

    // Validación en tiempo real
    inputContrasena.addEventListener('blur', validarContrasena);
    inputConfirmar.addEventListener('blur', validarConfirmacion);

    // Envío del formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validarFormularioCompleto()) {
            cambiarContrasena();
        }
    });
});

function togglePasswordVisibility(input, eyeIcon) {
    if (input.type === 'password') {
        input.type = 'text';
        eyeIcon.textContent = '🔒';
    } else {
        input.type = 'password';
        eyeIcon.textContent = '👁️';
    }
}

function validarContrasena() {
    const contrasena = document.getElementById('nueva_contrasena');
    const error = document.getElementById('errorContrasena');
    const valor = contrasena.value;

    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/;
    
    if (!regex.test(valor)) {
        mostrarError(contrasena, error, 'La contraseña debe incluir mayúsculas, minúsculas, números y caracteres especiales');
        return false;
    }

    if (valor.length < 8) {
        mostrarError(contrasena, error, 'La contraseña debe tener al menos 8 caracteres');
        return false;
    }

    mostrarExito(contrasena, error);
    return true;
}

function validarConfirmacion() {
    const contrasena = document.getElementById('nueva_contrasena').value;
    const confirmar = document.getElementById('confirmar_contrasena');
    const error = document.getElementById('errorConfirmar');
    const valor = confirmar.value;

    if (valor !== contrasena) {
        mostrarError(confirmar, error, 'Las contraseñas no coinciden');
        return false;
    }

    mostrarExito(confirmar, error);
    return true;
}

function validarFormularioCompleto() {
    const contrasenaValida = validarContrasena();
    const confirmacionValida = validarConfirmacion();

    return contrasenaValida && confirmacionValida;
}

function mostrarError(input, errorElement, mensaje) {
    input.classList.add('input-error');
    input.classList.remove('input-success');
    errorElement.textContent = mensaje;
    errorElement.style.display = 'block';
}

function mostrarExito(input, errorElement) {
    input.classList.remove('input-error');
    input.classList.add('input-success');
    errorElement.style.display = 'none';
}

function cambiarContrasena() {
    const nuevaContrasena = document.getElementById('nueva_contrasena').value;
    const mensajeCambio = document.getElementById('mensajeCambio');

    const formData = new FormData();
    formData.append('nueva_contrasena', nuevaContrasena);

    // Mostrar mensaje de carga
    mensajeCambio.textContent = 'Cambiando contraseña...';
    mensajeCambio.className = 'success-message';
    mensajeCambio.style.display = 'block';

    fetch('php/cambiar_contrasena.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        console.log("Respuesta del servidor:", text);
        
        try {
            const data = JSON.parse(text);
            
            if (data.success) {
                mensajeCambio.textContent = data.message;
                mensajeCambio.className = 'success-message';
                
                // Redirigir al login después de 2 segundos
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            } else {
                mensajeCambio.textContent = data.message;
                mensajeCambio.className = 'error-message';
            }
        } catch (error) {
            console.error("Error parsing JSON:", text);
            mensajeCambio.textContent = 'Error del servidor: ' + text;
            mensajeCambio.className = 'error-message';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mensajeCambio.textContent = 'Error de conexión. Intenta nuevamente.';
        mensajeCambio.className = 'error-message';
    });
}