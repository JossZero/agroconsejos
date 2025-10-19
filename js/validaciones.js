// Validaciones específicas para cada campo
function validarNombre() {
    const nombre = document.getElementById('nombre');
    const error = document.getElementById('errorNombre');
    const valor = nombre.value.trim();

    if (valor.length < 2) {
        mostrarError(nombre, error, 'El nombre debe tener al menos 2 letras');
        return false;
    }

    if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(valor)) {
        mostrarError(nombre, error, 'El nombre solo puede contener letras y espacios');
        return false;
    }

    mostrarExito(nombre, error);
    return true;
}

function validarCorreo() {
    const correo = document.getElementById('correo');
    const error = document.getElementById('errorCorreo');
    const valor = correo.value.trim();

    const regex = /^[^\s@]+@[^\s@]+\.(com|mx)$/;
    if (!regex.test(valor)) {
        mostrarError(correo, error, 'El correo debe contener @ y terminar en .com o .mx');
        return false;
    }

    mostrarExito(correo, error);
    return true;
}

function validarTelefono() {
    const telefono = document.getElementById('telefono');
    const error = document.getElementById('errorTelefono');
    const valor = telefono.value.trim();

    if (!/^\d{10}$/.test(valor)) {
        mostrarError(telefono, error, 'El teléfono debe tener exactamente 10 dígitos');
        return false;
    }

    mostrarExito(telefono, error);
    return true;
}

function validarContrasena() {
    const contrasena = document.getElementById('contrasena');
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

// Función para validar formulario completo
function validarFormularioCompleto() {
    const nombreValido = validarNombre();
    const correoValido = validarCorreo();
    const telefonoValido = validarTelefono();
    const contrasenaValida = validarContrasena();

    return nombreValido && correoValido && telefonoValido && contrasenaValida;
}