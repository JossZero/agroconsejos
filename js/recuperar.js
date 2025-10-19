document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formRecuperar');
    const mensajeRecuperar = document.getElementById('mensajeRecuperar');

    // Envío del formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        solicitarRecuperacion();
    });
});

function solicitarRecuperacion() {
    const correo = document.getElementById('correo').value;
    const mensajeRecuperar = document.getElementById('mensajeRecuperar');

    // Validación básica del correo
    if (!validarCorreo(correo)) {
        mensajeRecuperar.textContent = 'Por favor ingresa un correo válido';
        mensajeRecuperar.className = 'error-message';
        mensajeRecuperar.style.display = 'block';
        return;
    }

    const formData = new FormData();
    formData.append('correo', correo);

    // Mostrar mensaje de carga
    mensajeRecuperar.textContent = 'Enviando código de verificación...';
    mensajeRecuperar.className = 'success-message';
    mensajeRecuperar.style.display = 'block';

    fetch('php/enviar_correo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        console.log("Respuesta del servidor:", text);
        
        try {
            const data = JSON.parse(text);
            
            if (data.success) {
                mensajeRecuperar.textContent = data.message;
                mensajeRecuperar.className = 'success-message';
                
                // Redirigir a la página de verificación de código después de 2 segundos
                setTimeout(() => {
                    window.location.href = 'codigo_verificacion.html';
                }, 2000);
            } else {
                mensajeRecuperar.textContent = data.message;
                mensajeRecuperar.className = 'error-message';
            }
        } catch (error) {
            console.error("Error parsing JSON:", text);
            mensajeRecuperar.textContent = 'Error del servidor: ' + text;
            mensajeRecuperar.className = 'error-message';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mensajeRecuperar.textContent = 'Error de conexión. Intenta nuevamente.';
        mensajeRecuperar.className = 'error-message';
        mensajeRecuperar.style.display = 'block';
    });
}

function validarCorreo(correo) {
    const regex = /^[^\s@]+@[^\s@]+\.(com|mx)$/;
    return regex.test(correo);
}