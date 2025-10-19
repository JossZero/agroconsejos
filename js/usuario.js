document.addEventListener('DOMContentLoaded', function() {
    // Verificar sesión de usuario
    verificarSesionUsuario();
    
    // Navegación
    inicializarNavegacionUsuario();
    
    // Cargar datos iniciales
    cargarPerfilUsuario();
    cargarEntradasBlog();
    
    // Event listeners
    document.getElementById('btnComenzar').addEventListener('click', mostrarIntroduccion);
    document.getElementById('btnCerrarIntro').addEventListener('click', cerrarIntroduccion);
    document.getElementById('btnLogoutUsuario').addEventListener('click', cerrarSesion);
    document.getElementById('formVariables').addEventListener('submit', guardarVariables);
    document.getElementById('btnGenerarConsejos').addEventListener('click', generarConsejos);
    document.getElementById('btnGenerarReporte').addEventListener('click', generarReporte);
    document.getElementById('btnNuevaEntrada').addEventListener('click', mostrarFormularioBlog);
    document.getElementById('btnCancelarEntrada').addEventListener('click', ocultarFormularioBlog);
    document.getElementById('formBlog').addEventListener('submit', publicarEntradaBlog);
    document.getElementById('btnEditarPerfil').addEventListener('click', editarPerfil);
    
    // Modales
    inicializarModalesUsuario();
});

function verificarSesionUsuario() {
    const usuarioNombre = localStorage.getItem('usuarioNombre') || 'Usuario';
    document.getElementById('nombreUsuario').textContent = usuarioNombre;
    document.getElementById('usuarioNombre').textContent = usuarioNombre;
}

function inicializarNavegacionUsuario() {
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            
            document.querySelectorAll('.admin-section').forEach(section => {
                section.classList.remove('active');
            });
            
            const targetId = this.getAttribute('href').substring(1);
            document.getElementById(targetId).classList.add('active');
        });
    });
}

function mostrarIntroduccion() {
    document.getElementById('introduccion').style.display = 'block';
    document.getElementById('btnComenzar').style.display = 'none';
}

function cerrarIntroduccion() {
    document.getElementById('introduccion').style.display = 'none';
    document.getElementById('btnComenzar').style.display = 'block';
}

function cargarPerfilUsuario() {
    const usuario = {
        nombre: localStorage.getItem('usuarioNombre') || 'Diego Cuevas',
        correo: localStorage.getItem('usuarioCorreo') || 'diecuevas@outlook.com',
        telefono: localStorage.getItem('usuarioTelefono') || '4444249395',
        fechaRegistro: new Date().toLocaleDateString()
    };
    
    document.getElementById('infoUsuario').innerHTML = `
        <p><strong>Nombre:</strong> ${usuario.nombre}</p>
        <p><strong>Correo:</strong> ${usuario.correo}</p>
        <p><strong>Teléfono:</strong> ${usuario.telefono}</p>
        <p><strong>Miembro desde:</strong> ${usuario.fechaRegistro}</p>
    `;
}

function guardarVariables(e) {
    e.preventDefault();
    
    const formData = new FormData(document.getElementById('formVariables'));
    const variables = Object.fromEntries(formData.entries());
    
    const variablesFiltradas = Object.fromEntries(
        Object.entries(variables).filter(([_, value]) => value !== '')
    );
    
    if (Object.keys(variablesFiltradas).length === 0) {
        alert('Por favor ingresa al menos una variable');
        return;
    }
    
    mostrarMensajeUsuario('success', 'Variables guardadas exitosamente');
    document.getElementById('btnGenerarConsejos').style.display = 'block';
}

function generarConsejos() {
    const formData = new FormData(document.getElementById('formVariables'));
    const variables = Object.fromEntries(formData.entries());
    const consejos = [];

    if (variables.temperatura) {
        const temp = parseFloat(variables.temperatura);
        if (temp < 10) consejos.push('❄️ Temperatura baja: Usa invernaderos o cubiertas protectoras.');
        else if (temp > 30) consejos.push('🔥 Temperatura alta: Aumenta el riego y considera sombreado.');
        else consejos.push('✅ Temperatura óptima para la mayoría de cultivos.');
    }

    if (variables.humedad) {
        const hum = parseFloat(variables.humedad);
        if (hum < 40) consejos.push('🏜️ Humedad baja: Incrementa el riego.');
        else if (hum > 80) consejos.push('💦 Humedad alta: Vigila aparición de hongos.');
        else consejos.push('✅ Humedad adecuada.');
    }

    if (variables.ph_suelo) {
        const ph = parseFloat(variables.ph_suelo);
        if (ph < 6.0) consejos.push('🧪 pH ácido: Aplica cal agrícola.');
        else if (ph > 7.5) consejos.push('🧪 pH alcalino: Usa azufre o materia orgánica.');
        else consejos.push('✅ pH neutro ideal.');
    }

    if (variables.fertilidad) {
        switch(variables.fertilidad) {
            case 'baja': consejos.push('🌱 Fertilidad baja: Usa compost y rota cultivos.'); break;
            case 'media': consejos.push('🌱 Fertilidad media: Mantén tu programa actual.'); break;
            case 'alta': consejos.push('🌱 Fertilidad alta: Mantén manejo actual.'); break;
        }
    }

    document.getElementById('listaConsejos').innerHTML = consejos.map(c => `<div class="consejo-item">${c}</div>`).join('');
    document.getElementById('consejosAutomaticos').style.display = 'block';
}

function generarReporte() {
    const formData = new FormData(document.getElementById('formVariables'));
    const variables = Object.fromEntries(formData.entries());
    const fecha = new Date().toLocaleDateString();
    const hora = new Date().toLocaleTimeString();

    const contenidoReporte = `
        <div class="reporte-header">
            <h2>🌱 Reporte de Decisiones Agrícolas</h2>
            <p><strong>Fecha:</strong> ${fecha} ${hora}</p>
            <p><strong>Agricultor:</strong> ${localStorage.getItem('usuarioNombre') || 'Usuario'}</p>
        </div>
        <div class="reporte-variables">
            <h3>📊 Variables Registradas</h3>
            <table class="tabla-reporte">
                <tr><th>Variable</th><th>Valor</th></tr>
                ${Object.entries(variables).filter(([_, v]) => v !== '').map(([k, v]) => `
                    <tr><td>${obtenerNombreVariable(k)}</td><td>${v} ${obtenerUnidadVariable(k)}</td></tr>
                `).join('')}
            </table>
        </div>
        <div class="reporte-consejos">
            <h3>💡 Recomendaciones</h3>
            <div>${document.getElementById('listaConsejos').innerHTML}</div>
        </div>
        <div class="reporte-acciones">
            <h3>🎯 Plan de Acción Recomendado</h3>
            <ul>
                <li>Monitorear variables semanalmente</li>
                <li>Implementar las recomendaciones específicas</li>
                <li>Documentar cambios y resultados</li>
                <li>Consultar con especialistas si es necesario</li>
            </ul>
        </div>
    `;

    document.getElementById('contenidoReporte').innerHTML = contenidoReporte;
    document.getElementById('modalReporte').style.display = 'block';
}

function obtenerNombreVariable(key) {
    const nombres = {
        'temperatura': 'Temperatura',
        'humedad': 'Humedad',
        'radiacion': 'Radiación solar',
        'precipitacion': 'Precipitación',
        'ph_suelo': 'pH del suelo',
        'fertilidad': 'Nivel de fertilidad',
        'nutrientes': 'Nutrientes principales',
        'calidad_agua': 'Calidad del agua'
    };
    return nombres[key] || key;
}

function obtenerUnidadVariable(key) {
    const unidades = {
        'temperatura': '°C',
        'humedad': '%',
        'radiacion': 'W/m²',
        'precipitacion': 'mm',
        'ph_suelo': 'pH'
    };
    return unidades[key] || '';
}

function cargarEntradasBlog() {
    const entradas = JSON.parse(localStorage.getItem('entradasBlog') || '[]');
    const lista = document.getElementById('listaEntradasBlog');
    
    if (entradas.length === 0) {
        lista.innerHTML = `<p>No hay entradas publicadas aún.</p>`;
        return;
    }

    lista.innerHTML = entradas.map(e => `
        <div class="entrada-blog">
            <h4>${e.titulo}</h4>
            <div class="entrada-meta"><strong>Por:</strong> ${e.usuario} | <strong>Fecha:</strong> ${new Date(e.fecha).toLocaleDateString()}</div>
            <p>${e.contenido}</p>
        </div>
    `).join('');
}

function mostrarFormularioBlog() {
    document.getElementById('formNuevaEntrada').style.display = 'block';
    document.getElementById('btnNuevaEntrada').style.display = 'none';
}

function ocultarFormularioBlog() {
    document.getElementById('formNuevaEntrada').style.display = 'none';
    document.getElementById('btnNuevaEntrada').style.display = 'block';
    document.getElementById('formBlog').reset();
}

function publicarEntradaBlog(e) {
    e.preventDefault();
    const titulo = document.getElementById('tituloBlog').value;
    const contenido = document.getElementById('contenidoBlog').value;
    const usuario = localStorage.getItem('usuarioNombre') || 'Usuario';

    const nuevaEntrada = { titulo, contenido, usuario, fecha: new Date() };
    const entradas = JSON.parse(localStorage.getItem('entradasBlog') || '[]');
    entradas.unshift(nuevaEntrada);
    localStorage.setItem('entradasBlog', JSON.stringify(entradas));

    mostrarMensajeUsuario('success', 'Entrada publicada exitosamente');
    ocultarFormularioBlog();
    cargarEntradasBlog();
}

function editarPerfil() {
    alert('Funcionalidad de edición de perfil en desarrollo');
}

function inicializarModalesUsuario() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        const span = modal.querySelector('.close');
        if (span) span.onclick = () => modal.style.display = 'none';
    });
    
    window.onclick = e => modals.forEach(m => { if (e.target === m) m.style.display = 'none'; });
}

function mostrarMensajeUsuario(tipo, mensaje) {
    const notification = document.createElement('div');
    notification.className = `message-${tipo}`;
    notification.textContent = mensaje;
    Object.assign(notification.style, {
        position: 'fixed', top: '20px', right: '20px', zIndex: '1001',
        padding: '15px', borderRadius: '5px', maxWidth: '300px', background: tipo === 'success' ? '#4CAF50' : '#F44336', color: 'white'
    });
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}

function cerrarSesion() {
    if (confirm('¿Estás seguro de cerrar sesión?')) window.location.href = 'php/logout.php';
}
