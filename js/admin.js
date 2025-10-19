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

function verificarSesionAdmin() {
    // En una implementación real, verificarías la sesión del servidor
    const adminNombre = localStorage.getItem('adminNombre');
    if (adminNombre) {
        document.getElementById('adminNombre').textContent = adminNombre;
    }
}

function inicializarNavegacion() {
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remover active de todos los links
            navLinks.forEach(l => l.classList.remove('active'));
            
            // Agregar active al link clickeado
            this.classList.add('active');
            
            // Ocultar todas las secciones
            document.querySelectorAll('.admin-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Mostrar sección correspondiente
            const targetId = this.getAttribute('href').substring(1);
            document.getElementById(targetId).classList.add('active');
            
            // Recargar datos si es necesario
            if (targetId === 'usuarios') {
                cargarUsuarios();
            } else if (targetId === 'bitacora') {
                cargarBitacora();
            } else if (targetId === 'estadisticas') {
                cargarEstadisticas();
            }
        });
    });
}

function cargarUsuarios() {
    fetch('php/admin_usuarios.php?action=obtener_usuarios')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarUsuarios(data.data);
            } else {
                mostrarError('Error al cargar usuarios: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error al cargar usuarios');
        });
}

function mostrarUsuarios(usuarios) {
    const tbody = document.getElementById('cuerpoTablaUsuarios');
    tbody.innerHTML = '';
    
    usuarios.forEach(usuario => {
        const tr = document.createElement('tr');
        
        tr.innerHTML = `
            <td>${usuario.nombre}</td>
            <td>${usuario.correo}</td>
            <td>${usuario.telefono}</td>
            <td>${usuario.contrasena_plana}</td>
            <td><span class="badge-${usuario.rol}">${usuario.rol}</span></td>
            <td>${new Date(usuario.fecha_registro).toLocaleDateString()}</td>
            <td>
                <button class="btn-action btn-edit" onclick="editarUsuario(${usuario.id})">✏️ Editar</button>
                <button class="btn-action btn-delete" onclick="eliminarUsuario(${usuario.id})">🗑️ Eliminar</button>
            </td>
        `;
        
        tbody.appendChild(tr);
    });
}

function cargarBitacora() {
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;
    
    let url = 'php/admin_bitacora.php';
    if (fechaInicio && fechaFin) {
        url += `?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarBitacora(data.data);
            } else {
                mostrarError('Error al cargar bitácora: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error al cargar bitácora');
        });
}

function mostrarBitacora(registros) {
    const tbody = document.getElementById('cuerpoTablaBitacora');
    tbody.innerHTML = '';
    
    registros.forEach(registro => {
        const tr = document.createElement('tr');
        
        tr.innerHTML = `
            <td>${registro.usuario}</td>
            <td>${registro.fecha_entrada}</td>
            <td>${registro.fecha_salida}</td>
            <td>${registro.tiempo_sesion}</td>
            <td>${registro.estado}</td>
        `;
        
        tbody.appendChild(tr);
    });
}

function cargarEstadisticas() {
    fetch('php/admin_estadisticas.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalUsuarios').textContent = data.totalUsuarios;
                document.getElementById('totalVariables').textContent = data.totalVariables;
                document.getElementById('totalBlogs').textContent = data.totalBlogs;
                document.getElementById('sesionesHoy').textContent = data.sesionesHoy;
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function generarRespaldo() {
    if (!confirm('¿Estás seguro de generar un respaldo de la base de datos? Esto puede tomar unos momentos.')) {
        return;
    }
    
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
        if (progresoActual >= 90) {
            clearInterval(intervalo);
        }
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
                        ✅ ${data.message}<br>
                        <strong>Archivo:</strong> ${data.archivo}
                    </div>
                `;
                listarRespaldos();
            }, 1000);
        } else {
            progreso.style.display = 'none';
            mensaje.innerHTML = `
                <div class="message-error">
                    ❌ ${data.message}
                </div>
            `;
        }
    })
    .catch(error => {
        clearInterval(intervalo);
        progreso.style.display = 'none';
        mensaje.innerHTML = `
            <div class="message-error">
                ❌ Error al generar respaldo
            </div>
        `;
        console.error('Error:', error);
    });
}

function listarRespaldos() {
    fetch('php/respaldo_bd.php?action=listar_respaldos')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarRespaldos(data.data);
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

function mostrarModalRestauracion() {
    const contenido = `
        <div class="warning-message">
            <h4>⚠️ Advertencia Importante</h4>
            <p>La restauración de la base de datos:</p>
            <ul>
                <li>Sobrescribirá todos los datos actuales</li>
                <li>Eliminará registros recientes no incluidos en el respaldo</li>
                <li>Puede tomar varios minutos dependiendo del tamaño</li>
                <li>No se puede deshacer esta acción</li>
            </ul>
            <p><strong>¿Estás absolutamente seguro de continuar?</strong></p>
        </div>
        <form id="formRestaurar" class="restore-form" enctype="multipart/form-data">
            <div class="form-group-file">
                <label for="archivo_respaldo">Seleccionar archivo de respaldo (.zip):</label>
                <input type="file" id="archivo_respaldo" name="archivo_respaldo" accept=".zip" required>
            </div>
            <button type="submit" class="btn btn-restore">✅ Sí, restaurar base de datos</button>
            <button type="button" class="btn btn-logout" onclick="document.getElementById('modalRestaurar').style.display='none'">❌ Cancelar</button>
        </form>
    `;
    
    mostrarModalPersonalizado('Restaurar Base de Datos', contenido, 'modalRestaurar');
    
    document.getElementById('formRestaurar').addEventListener('submit', function(e) {
        e.preventDefault();
        restaurarRespaldo();
    });
}

function restaurarRespaldo() {
    const archivoInput = document.getElementById('archivo_respaldo');
    const archivo = archivoInput.files[0];
    
    if (!archivo) {
        alert('Por favor selecciona un archivo de respaldo');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'restaurar_respaldo');
    formData.append('archivo_respaldo', archivo);
    
    const progreso = document.getElementById('progresoRespaldo');
    const barra = document.getElementById('barraProgreso');
    const porcentaje = document.getElementById('porcentajeProgreso');
    const mensaje = document.getElementById('mensajeRespaldo');
    
    progreso.style.display = 'block';
    barra.style.width = '0%';
    porcentaje.textContent = '0%';
    
    // Simular progreso
    let progresoActual = 0;
    const intervalo = setInterval(() => {
        progresoActual += Math.random() * 8;
        if (progresoActual >= 85) {
            clearInterval(intervalo);
        }
        barra.style.width = progresoActual + '%';
        porcentaje.textContent = Math.round(progresoActual) + '%';
    }, 300);
    
    fetch('php/respaldo_bd.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        clearInterval(intervalo);
        barra.style.width = '100%';
        porcentaje.textContent = '100%';
        
        document.getElementById('modalRestaurar').style.display = 'none';
        
        if (data.success) {
            setTimeout(() => {
                progreso.style.display = 'none';
                mensaje.innerHTML = `
                    <div class="message-success">
                        ✅ ${data.message}
                    </div>
                `;
                // Recargar datos
                cargarUsuarios();
                cargarBitacora();
                cargarEstadisticas();
            }, 1000);
        } else {
            progreso.style.display = 'none';
            mensaje.innerHTML = `
                <div class="message-error">
                    ❌ ${data.message}
                </div>
            `;
        }
    })
    .catch(error => {
        clearInterval(intervalo);
        progreso.style.display = 'none';
        mensaje.innerHTML = `
            <div class="message-error">
                ❌ Error al restaurar respaldo
            </div>
        `;
        console.error('Error:', error);
    });
}

function editarUsuario(id) {
    fetch(`php/admin_usuarios.php?action=obtener_usuario&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarModalEditar(data.data);
            } else {
                mostrarError('Error al cargar usuario: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error al cargar usuario');
        });
}

function mostrarModalEditar(usuario) {
    const modal = document.getElementById('modalEditar');
    const form = document.getElementById('formEditarUsuario');
    
    document.getElementById('editarId').value = usuario.id;
    document.getElementById('editarNombre').value = usuario.nombre;
    document.getElementById('editarCorreo').value = usuario.correo;
    document.getElementById('editarTelefono').value = usuario.telefono;
    document.getElementById('editarContrasena').value = '';
    document.getElementById('editarRol').value = usuario.rol;
    
    modal.style.display = 'block';
    
    form.onsubmit = function(e) {
        e.preventDefault();
        guardarCambiosUsuario();
    };
}

function guardarCambiosUsuario() {
    const formData = new FormData();
    formData.append('action', 'actualizar_usuario');
    formData.append('id', document.getElementById('editarId').value);
    formData.append('nombre', document.getElementById('editarNombre').value);
    formData.append('correo', document.getElementById('editarCorreo').value);
    formData.append('telefono', document.getElementById('editarTelefono').value);
    formData.append('contrasena', document.getElementById('editarContrasena').value);
    formData.append('rol', document.getElementById('editarRol').value);
    
    fetch('php/admin_usuarios.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('modalEditar').style.display = 'none';
            mostrarMensaje('success', 'Usuario actualizado exitosamente');
            cargarUsuarios();
        } else {
            mostrarError('Error al actualizar usuario: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('Error al actualizar usuario');
    });
}

function eliminarUsuario(id) {
    if (!confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'eliminar_usuario');
    formData.append('id', id);
    
    fetch('php/admin_usuarios.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarMensaje('success', 'Usuario eliminado exitosamente');
            cargarUsuarios();
        } else {
            mostrarError('Error al eliminar usuario: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('Error al eliminar usuario');
    });
}

function exportarBitacora() {
    // Implementar exportación a CSV
    alert('Funcionalidad de exportación en desarrollo');
}

function inicializarModal() {
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        const span = modal.querySelector('.close');
        if (span) {
            span.onclick = function() {
                modal.style.display = 'none';
            };
        }
    });
    
    window.onclick = function(event) {
        modals.forEach(modal => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    };
}

function mostrarModalPersonalizado(titulo, contenido, id) {
    const modalHTML = `
        <div id="${id}" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3>${titulo}</h3>
                ${contenido}
            </div>
        </div>
    `;
    
    // Remover modal existente si hay
    const modalExistente = document.getElementById(id);
    if (modalExistente) {
        modalExistente.remove();
    }
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    const modal = document.getElementById(id);
    const span = modal.getElementsByClassName('close')[0];
    
    span.onclick = function() {
        modal.style.display = 'none';
    };
    
    modal.style.display = 'block';
}

function cerrarSesion() {
    if (confirm('¿Estás seguro de cerrar sesión?')) {
        window.location.href = 'php/logout.php';
    }
}

function mostrarMensaje(tipo, mensaje) {
    // Implementar sistema de notificaciones toast
    const notification = document.createElement('div');
    notification.className = `message-${tipo}`;
    notification.textContent = mensaje;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '1001';
    notification.style.padding = '15px';
    notification.style.borderRadius = '5px';
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function mostrarError(mensaje) {
    mostrarMensaje('error', mensaje);
}