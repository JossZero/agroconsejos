document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formVerificacion');
    const mensajeVerificacion = document.getElementById('mensajeVerificacion');

    // Auto-focus en el primer input
    document.getElementById('codigo1').focus();

    // Mover automáticamente entre inputs
    const inputs = document.querySelectorAll('.codigo-input');
    inputs.forEach((input, index) => {
        input.addEventListener('input', function() {
            if (this.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && this.value.length === 0 && index > 0) {
                inputs[index - 1].focus();
            }
        });
    });

    // Envío del formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        verificarCodigo();
    });
});

function verificarCodigo() {
    const codigo1 = document.getElementById('codigo1').value;
    const codigo2 = document.getElementById('codigo2').value;
    const codigo3 = document.getElementById('codigo3').value;
    const codigo4 = document.getElementById('codigo4').value;
    const codigo5 = document.getElementById('codigo5').value;
    const codigo6 = document.getElementById('codigo6').value;

    const codigoCompleto = codigo1 + codigo2 + codigo3 + codigo4 + codigo5 + codigo6;
    const mensajeVerificacion = document.getElementById('mensajeVerificacion');

    // Validar que todos los dígitos estén completos
    if (codigoCompleto.length !== 6) {
        mensajeVerificacion.textContent = 'Por favor ingresa el código completo de 6 dígitos';
        mensajeVerificacion.className = 'error-message';
        mensajeVerificacion.style.display = 'block';
        return;
    }

    const formData = new FormData();
    formData.append('codigo', codigoCompleto);

    // Mostrar mensaje de carga
    mensajeVerificacion.textContent = 'Verificando código...';
    mensajeVerificacion.className = 'success-message';
    mensajeVerificacion.style.display = 'block';

    fetch('php/verificar_codigo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        console.log("Respuesta del servidor:", text);
        
        try {
            const data = JSON.parse(text);
            
            if (data.success) {
                mensajeVerificacion.textContent = data.message;
                mensajeVerificacion.className = 'success-message';
                
                // Redirigir a la página de cambio de contraseña
                setTimeout(() => {
                    window.location.href = 'cambiar_contrasena.html';
                }, 1500);
            } else {
                mensajeVerificacion.textContent = data.message;
                mensajeVerificacion.className = 'error-message';
            }
        } catch (error) {
            console.error("Error parsing JSON:", text);
            mensajeVerificacion.textContent = 'Error del servidor: ' + text;
            mensajeVerificacion.className = 'error-message';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mensajeVerificacion.textContent = 'Error de conexión. Intenta nuevamente.';
        mensajeVerificacion.className = 'error-message';
    });
}