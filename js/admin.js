document.addEventListener('DOMContentLoaded', function() {
    // Verificar sesión de administrador
    verificarSesionAdmin();
    
    // Navegación entre secciones
    inicializarNavegacion();
    
    // Cargar datos iniciales
    cargarUsuarios();
    cargarBitacora();
    cargarEstadisticas();
    
    // Event listeners
    document.getElementById('btnRespaldo').addEventListener('click', generarRespaldo);
    document.getElementById('btnRestaurar').addEventListener('click', mostrarModalRestauracion);
    document.getElementById('btnListarRespaldos').addEventListener('click', listarRespaldos);
    document.getElementById('btnFiltrar').addEventListener('click', cargarBitacora);
    document.getElementById('btnExportar').addEventListener('click', exportarBitacora);
    document.getElementById('btnLogout').addEventListener('click', cerrarSesion);
    
    // Modal
    inicializarModal();
});

// ============================
// 🔹 Respaldos
// ============================
function generarRespaldo() {
    if (!confirm('¿Estás seguro de generar un respaldo de la base de datos? Esto puede tomar unos momentos.')) return;
    
    const progreso = document.getElementById('progresoRespaldo');
    const barra = document.getElementById('barraProgreso');
    const porcentaje = document.getElementById('porcentajeProgreso');
    const mensaje = document.getElementById('mensajeRespaldo');
    
    progreso.style.display = 'block';
    mensaje.innerHTML = '';
    
    // Simular progreso
    let progresoActual = 0;
    const intervalo = setInterval(() => {
        progresoActual += Math.random() * 10;
        if (progresoActual >= 90) clearInterval(intervalo);
        barra.style.width = progresoActual + '%';
        porcentaje.textContent = Math.round(progresoActual) + '%';
    }, 200);
    
    const formData = new FormData();
    formData.append('action', 'generar_respaldo');
    
    fetch('php/respaldo_bd.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        clearInterval(intervalo);
        barra.style.width = '100%';
        porcentaje.textContent = '100%';
        
        if (data.success) {
            setTimeout(() => {
                progreso.style.display = 'none';
                mensaje.innerHTML = `
                    <div class="message-success">
                        ✅ Respaldo generado correctamente<br>
                        <strong>Archivo:</strong> ${data.archivo}
                    </div>
                `;
                listarRespaldos();
                // Crear enlace para descargar
                descargarRespaldo(data.archivo);
            }, 500);
        } else {
            progreso.style.display = 'none';
            mensaje.innerHTML = `<div class="message-error">❌ ${data.message}</div>`;
        }
    })
    .catch(error => {
        clearInterval(intervalo);
        progreso.style.display = 'none';
        mensaje.innerHTML = `<div class="message-error">❌ Error al generar respaldo</div>`;
        console.error('Error:', error);
    });
}

function listarRespaldos() {
    fetch('php/respaldo_bd.php?action=listar_respaldos')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarRespaldos(data.respaldos); // Corregido: data.respaldos
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function mostrarRespaldos(respaldos) {
    const lista = document.getElementById('listaRespaldos');
    
    if (respaldos.length === 0) {
        lista.innerHTML = '<p>No hay respaldos disponibles.</p>';
        return;
    }
    
    lista.innerHTML = '<h3>📋 Respaldos Disponibles</h3>';
    
    respaldos.forEach(respaldo => {
        const item = document.createElement('div');
        item.className = 'backup-item';
        item.innerHTML = `
            <div class="backup-info">
                <strong>${respaldo.nombre}</strong><br>
                <small>Tamaño: ${respaldo.tamaño} - Fecha: ${respaldo.fecha}</small>
            </div>
            <div class="backup-actions">
                <button class="btn-action btn-download" onclick="descargarRespaldo('${respaldo.nombre}')">📥 Descargar</button>
                <button class="btn-action btn-delete" onclick="eliminarRespaldo('${respaldo.nombre}')">🗑️ Eliminar</button>
            </div>
        `;
        lista.appendChild(item);
    });
}

function descargarRespaldo(nombreArchivo) {
    const enlace = document.createElement('a');
    enlace.href = 'php/backups/' + encodeURIComponent(nombreArchivo);
    enlace.download = nombreArchivo;
    document.body.appendChild(enlace);
    enlace.click();
    enlace.remove();
}

function eliminarRespaldo(nombreArchivo) {
    if (!confirm('¿Seguro que quieres eliminar este respaldo?')) return;
    
    const formData = new FormData();
    formData.append('action', 'eliminar_respaldo');
    formData.append('archivo', nombreArchivo);
    
    fetch('php/respaldo_bd.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarMensaje('success', data.message);
            listarRespaldos();
        } else {
            mostrarError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('Error al eliminar respaldo');
    });
}
