<?php

?>
<style>
    .notif-nav-item {
        position: relative;
        display: inline-flex;
        align-items: center;
    }
    #notif-bell-btn {
        color: darkblue;
        font-size: 26px;
        cursor: pointer;
        position: relative;
        display: inline-flex;
        align-items: center;
        text-decoration: none;
        padding: 6px 10px;
    }
    #notif-bell-btn:hover {
        color: #0d6efd;
    }
    #notif-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        background: #dc3545;
        color: #fff;
        border-radius: 50%;
        font-size: 12px;
        font-weight: bold;
        min-width: 20px;
        height: 20px;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 0 4px;
        line-height: 1;
        border: 2px solid #fff;
    }
    #notif-panel {
        position: fixed;
        top: 70px;
        right: 25px;
        width: 380px;
        max-width: 92vw;
        max-height: 70vh;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        z-index: 9999;
        display: none;
        flex-direction: column;
        overflow: hidden;
    }
    #notif-panel-header {
        background: #0d6efd;
        color: #fff;
        padding: 14px 16px;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }
    #notif-panel-header-actions {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    #notif-marcar-todas {
        background: none;
        border: none;
        color: #fff;
        font-size: 12.5px;
        cursor: pointer;
        text-decoration: underline;
        padding: 0;
    }
    #notif-panel-close {
        background: none;
        border: none;
        color: #fff;
        font-size: 20px;
        line-height: 1;
        cursor: pointer;
    }
    #notif-panel-body {
        overflow-y: auto;
        flex: 1;
    }
    .notif-item {
        display: flex;
        gap: 10px;
        padding: 12px 16px;
        border-bottom: 1px solid #eee;
        text-decoration: none;
        color: #212529;
        cursor: pointer;
    }
    .notif-item:hover {
        background: #f8f9fa;
    }
    .notif-item.leida {
        opacity: 0.5;
    }
    .notif-dot {
        flex-shrink: 0;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-top: 5px;
    }
    .notif-item.leida .notif-dot {
        background: #adb5bd !important;
    }
    .notif-dot.alta { background: #dc3545; }
    .notif-dot.media { background: #fd7e14; }
    .notif-dot.baja { background: #ffc107; }
    .notif-texto {
        font-size: 13.5px;
        line-height: 1.4;
        color: #212529;
    }
    .notif-monto {
        font-weight: 700;
        color: #198754;
        margin-top: 2px;
        display: block;
    }
    .notif-item.leida .notif-monto {
        color: #6c757d;
    }
    #notif-empty {
        padding: 30px 16px;
        text-align: center;
        color: #888;
        font-size: 14px;
    }

    @media (max-width: 768px) {
        #notif-panel {
            top: 60px;
            right: 10px;
            left: 10px;
            width: auto;
        }
    }
</style>

<div id="notif-panel">
    <div id="notif-panel-header">
        <span><i class="bi bi-bell-fill"></i> Notificaciones</span>
        <div id="notif-panel-header-actions">
            <button id="notif-marcar-todas" type="button">Marcar todas</button>
            <button id="notif-panel-close" type="button">&times;</button>
        </div>
    </div>
    <div id="notif-panel-body">
        <div id="notif-empty">Cargando notificaciones...</div>
    </div>
</div>

<script>
(function () {

    const AJAX_URL = '/Sofi-main/ajax/notificaciones.php';

    function insertarCampanaEnNav() {
        const navUl = document.querySelector('#navbar > ul');
        if (!navUl) {
            return false;
        }

        const li = document.createElement('li');
        li.className = 'notif-nav-item';
        li.innerHTML = `
            <a href="#" id="notif-bell-btn" title="Centro de notificaciones">
                <i class="bi bi-bell-fill"></i>
                <span id="notif-badge">0</span>
            </a>
        `;

        navUl.insertBefore(li, navUl.firstChild);
        return true;
    }

    function inicializar() {
        if (!insertarCampanaEnNav()) {
            setTimeout(inicializar, 100);
            return;
        }

        const btn = document.getElementById('notif-bell-btn');
        const panel = document.getElementById('notif-panel');
        const closeBtn = document.getElementById('notif-panel-close');
        const marcarTodasBtn = document.getElementById('notif-marcar-todas');
        const badge = document.getElementById('notif-badge');
        const body = document.getElementById('notif-panel-body');

        function formatearMoneda(valor) {
            const numero = parseFloat(valor) || 0;
            return '$' + numero.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function actualizarBadge(noLeidas) {
            if (noLeidas === 0) {
                badge.style.display = 'none';
            } else {
                badge.textContent = noLeidas > 99 ? '99+' : noLeidas;
                badge.style.display = 'flex';
            }
        }

        function mostrarVacio() {
            body.innerHTML = '<div id="notif-empty"><i class="bi bi-check-circle-fill" style="font-size:28px;color:#28a745;display:block;margin-bottom:8px;"></i>No hay notificaciones pendientes</div>';
        }

        function renderizar(data) {
            actualizarBadge(data.no_leidas);

            if (data.total === 0) {
                mostrarVacio();
                return;
            }

            body.innerHTML = '';
            data.notificaciones.forEach(n => {
                const a = document.createElement('a');
                a.href = '/Sofi-main/' + n.link;
                a.className = 'notif-item' + (n.leida ? ' leida' : '');
                a.dataset.key = n.key;
                a.innerHTML = `
                    <span class="notif-dot ${n.severidad}"></span>
                    <div class="notif-texto">
                        ${n.mensaje}
                        ${n.monto > 0 ? `<span class="notif-monto">${formatearMoneda(n.monto)}</span>` : ''}
                    </div>
                `;

                // Al hacer clic: marcar como leída en el servidor y LUEGO navegar.
                // La notificación NO se quita del panel (solo se atenuará la
                // próxima vez que se abra), porque puede seguir activa.
                a.addEventListener('click', function (e) {
                    e.preventDefault();
                    const destino = a.href;

                    fetch(AJAX_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'accion=marcarLeida&key=' + encodeURIComponent(n.key)
                    }).finally(() => {
                        window.location.href = destino;
                    });
                });

                body.appendChild(a);
            });
        }

        function cargarNotificaciones() {
            fetch(AJAX_URL + '?accion=listar')
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        body.innerHTML = '<div id="notif-empty">No se pudieron cargar las notificaciones</div>';
                        return;
                    }
                    renderizar(data);
                })
                .catch(() => {
                    body.innerHTML = '<div id="notif-empty">Error al cargar notificaciones</div>';
                });
        }

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const visible = panel.style.display === 'flex';
            panel.style.display = visible ? 'none' : 'flex';
            if (!visible) {
                cargarNotificaciones();
            }
        });

        closeBtn.addEventListener('click', function () {
            panel.style.display = 'none';
        });

        marcarTodasBtn.addEventListener('click', function () {
            fetch(AJAX_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'accion=marcarTodas'
            })
            .then(res => res.json())
            .then(() => {
                // Las notificaciones siguen visibles, solo cambia su estado a "leída"
                cargarNotificaciones();
            });
        });

        document.addEventListener('click', function (e) {
            if (!panel.contains(e.target) && !btn.contains(e.target)) {
                panel.style.display = 'none';
            }
        });

        // Cargar el conteo (badge) apenas carga la página, sin abrir el panel
        cargarNotificaciones();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializar);
    } else {
        inicializar();
    }
})();
</script>