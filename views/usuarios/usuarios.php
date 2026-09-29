<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Sofi-main/auth_check.php'; ?>
<?php
require_once '../../config/database.php';
require_once '../../classes/Permisos.php';

// Solo el administrador entra aquí
if (($_SESSION['rol_id'] ?? null) != 1) {
    header('Location: ../../dashboard.php');
    exit;
}

$pdo = Database::getConnection();

$usuarios = $pdo->query("SELECT u.id, u.username, u.email, u.rol_id, r.nombre AS rol_nombre
                          FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id
                          ORDER BY u.username")->fetchAll();

$roles = $pdo->query("SELECT id, nombre FROM roles ORDER BY nombre")->fetchAll();

$modulos = $pdo->query("SELECT id, clave, nombre, grupo FROM modulos ORDER BY grupo, nombre")->fetchAll();

// Permisos actuales agrupados por rol_id => [modulo_id => ['ver'=>,'editar'=>]]
$permisosStmt = $pdo->query("SELECT rol_id, modulo_id, puede_ver, puede_editar FROM permisos_rol");
$permisosActuales = [];
foreach ($permisosStmt->fetchAll() as $p) {
    $permisosActuales[$p['rol_id']][$p['modulo_id']] = $p;
}

$gruposNombre = ['catalogos' => 'Catálogos', 'documentos' => 'Documentos', 'libros' => 'Libros', 'informes' => 'Informes'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Usuarios y Roles - SOFI</title>
  <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../../assets/css/improved-style.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body>

  <header id="header" class="fixed-top d-flex align-items-center">
    <div class="container d-flex align-items-center justify-content-between">
      <h1 class="logo"><a href="../../dashboard.php"><img src="../../Img/logosofi1.png" alt="Logo SOFI" class="logo-icon"> Software Financiero</a></h1>
      <nav id="navbar" class="navbar">
        <ul>
          <li><a class="nav-link scrollto active" href="../../dashboard.php" style="color: darkblue;">Inicio</a></li>
          <li><a class="nav-link scrollto active" href="../../perfil.php" style="color: darkblue;">Mi Negocio</a></li>
          <li><a class="nav-link scrollto active" href="usuarios.php" style="color: darkblue;">Usuarios</a></li>
          <li><a class="nav-link scrollto active" href="../../inicioSesion/cerrarSesion.php" style="color: darkblue;">Cerrar Sesión</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="container" style="margin-top: 120px; margin-bottom: 60px;">
    <h2>Usuarios</h2>
    <div class="table-container">
    <table id="tablaUsuarios">
      <thead><tr><th>Usuario</th><th>Correo</th><th>Rol actual</th><th>Cambiar rol</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
        <tr>
          <td><?= htmlspecialchars($u['username']) ?></td>
          <td><?= $u['email'] ? htmlspecialchars($u['email']) : '<span class="text-muted">Sin correo</span>' ?></td>
          <td><?= htmlspecialchars($u['rol_nombre']) ?></td>
          <td>
            <select class="form-select form-select-sm selRol" data-user-id="<?= $u['id'] ?>">
              <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id'] ?>" <?= $r['id'] == $u['rol_id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-primary btnEditarUsuario"
                    data-id="<?= $u['id'] ?>" data-username="<?= htmlspecialchars($u['username']) ?>"
                    data-email="<?= htmlspecialchars($u['email'] ?? '') ?>"
                    data-bs-toggle="modal" data-bs-target="#modalEditarUsuario" title="Editar usuario">
              <i class="bi bi-pencil"></i>
            </button>
            <?php if ($u['id'] != ($_SESSION['user_id'] ?? 0)): ?>
            <button type="button" class="btn btn-sm btn-outline-danger btnEliminarUsuario"
                    data-id="<?= $u['id'] ?>" data-username="<?= htmlspecialchars($u['username']) ?>" title="Eliminar usuario">
              <i class="bi bi-trash"></i>
            </button>
            <?php else: ?>
            <span class="text-muted" title="No puedes eliminar tu propio usuario"><i class="bi bi-dash-circle"></i></span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <!-- MODAL EDITAR USUARIO -->
    <div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="formEditarUsuario">
            <div class="modal-header">
              <h5 class="modal-title">Editar usuario</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" id="editUsuarioId">
              <div class="mb-3">
                <label class="form-label">Usuario</label>
                <input type="text" class="form-control" id="editUsername" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="editEmail" placeholder="Para recuperar la contraseña">
              </div>
              <div class="mb-3">
                <label class="form-label">Nueva contraseña</label>
                <input type="password" class="form-control" id="editPassword" placeholder="Dejar en blanco para no cambiarla">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <hr>
    <h4>Crear usuario</h4>
    <form id="formCrearUsuario" class="row g-2 align-items-end">
      <div class="col-auto"><input type="text" class="form-control" id="nuevoUsername" placeholder="Usuario" required></div>
      <div class="col-auto"><input type="email" class="form-control" id="nuevoEmail" placeholder="Correo"></div>
      <div class="col-auto"><input type="password" class="form-control" id="nuevoPassword" placeholder="Contraseña" required></div>
      <div class="col-auto">
        <select class="form-select" id="nuevoRol">
          <?php foreach ($roles as $r): ?>
            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nombre']) ?></option>  
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto"><button type="submit" class="btn-crear">Crear</button></div>
    </form>

    <hr>
    <h2>Roles y permisos</h2>
    <form id="formCrearRol" class="row g-2 align-items-end mb-4">
      <div class="col-auto"><input type="text" class="form-control" id="nuevoRolNombre" placeholder="Nombre del nuevo rol" required></div>
      <div class="col-auto"><button type="submit" class="btn-crear-rol">Crear rol</button></div>
    </form>

    <div class="accordion" id="accordionRoles">
    <?php foreach ($roles as $r): if ($r['id'] == 1) continue; // admin no se configura, siempre ve/edita todo ?>
    <div class="accordion-item mb-2">
      <h2 class="accordion-header">
        <button class="accordion-button collapsed" type="button"
                data-bs-toggle="collapse" data-bs-target="#rolPermisos<?= $r['id'] ?>">
          <strong><?= htmlspecialchars($r['nombre']) ?></strong>
        </button>
      </h2>
      <div id="rolPermisos<?= $r['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#accordionRoles">
        <div class="accordion-body p-0">
          <div class="table-container mb-0">
          <table>
            <thead><tr><th>Módulo</th><th>Ver</th><th>Editar</th></tr></thead>
            <tbody>
              <?php $grupoActual = null; foreach ($modulos as $m): ?>
                <?php if ($grupoActual !== $m['grupo']): $grupoActual = $m['grupo']; ?>
                  <tr class="table-light"><td colspan="3"><strong><?= $gruposNombre[$grupoActual] ?? $grupoActual ?></strong></td></tr>
                <?php endif; ?>
                <?php
                  $actual = $permisosActuales[$r['id']][$m['id']] ?? ['puede_ver' => 0, 'puede_editar' => 0];
                ?>
                <tr>
                  <td><?= htmlspecialchars($m['nombre']) ?></td>
                  <td><input type="checkbox" class="chkPermiso" data-rol="<?= $r['id'] ?>" data-modulo="<?= $m['id'] ?>" data-accion="ver" <?= $actual['puede_ver'] ? 'checked' : '' ?>></td>
                  <td><input type="checkbox" class="chkPermiso" data-rol="<?= $r['id'] ?>" data-modulo="<?= $m['id'] ?>" data-accion="editar" <?= $actual['puede_editar'] ? 'checked' : '' ?>></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    </div>
  </main>

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <p>Universidad de Santander - Ingeniería de Software</p>
    <p>Todos los derechos reservados © 2025</p>
    <p>Creado por iniciativa del programa de Contaduría Pública</p>
  </footer><!-- End Footer -->

  <div id="preloader"></div>
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
    <i class="bi bi-arrow-up-short"></i>
  </a>

  <script>
  $(function () {
    $('.selRol').on('change', function () {
      $.post('../../ajax/usuarios_ajax.php', {
        accion: 'cambiar_rol', usuario_id: $(this).data('user-id'), rol_id: $(this).val()
      }, function (r) { Swal.fire('Listo', 'Rol actualizado', 'success'); });
    });

    $('#formCrearUsuario').on('submit', function (e) {
      e.preventDefault();
      $.post('../../ajax/usuarios_ajax.php', {
        accion: 'crear_usuario',
        username: $('#nuevoUsername').val(),
        email: $('#nuevoEmail').val(),
        password: $('#nuevoPassword').val(),
        rol_id: $('#nuevoRol').val()
      }, function (r) {
        if (r === 'ok') location.reload();
        else Swal.fire('Error', r, 'error');
      });
    });

    $('#formCrearRol').on('submit', function (e) {
      e.preventDefault();
      $.post('../../ajax/usuarios_ajax.php', {
        accion: 'crear_rol', nombre: $('#nuevoRolNombre').val()
      }, function (r) { location.reload(); });
    });

    // Precargar el modal de edición con los datos de la fila
    $('.btnEditarUsuario').on('click', function () {
      $('#editUsuarioId').val($(this).data('id'));
      $('#editUsername').val($(this).data('username'));
      $('#editEmail').val($(this).data('email') || '');
      $('#editPassword').val('');
    });

    $('#formEditarUsuario').on('submit', function (e) {
      e.preventDefault();
      $.post('../../ajax/usuarios_ajax.php', {
        accion: 'editar_usuario',
        usuario_id: $('#editUsuarioId').val(),
        username: $('#editUsername').val(),
        email: $('#editEmail').val(),
        password: $('#editPassword').val()
      }, function (r) {
        if (r === 'ok') location.reload();
        else Swal.fire('Error', r, 'error');
      });
    });

    $('.btnEliminarUsuario').on('click', function () {
      const id = $(this).data('id');
      const nombre = $(this).data('username');

      Swal.fire({
        title: '¿Eliminar usuario?',
        text: `Se eliminará el usuario "${nombre}" y no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#eb0404'
      }).then((result) => {
        if (result.isConfirmed) {
          $.post('../../ajax/usuarios_ajax.php', {
            accion: 'eliminar_usuario', usuario_id: id
          }, function (r) {
            if (r === 'ok') location.reload();
            else Swal.fire('Error', r, 'error');
          });
        }
      });
    });

    $('.chkPermiso').on('change', function () {
      $.post('../../ajax/usuarios_ajax.php', {
        accion: 'guardar_permiso',
        rol_id: $(this).data('rol'),
        modulo_id: $(this).data('modulo'),
        campo: $(this).data('accion'),
        valor: $(this).is(':checked') ? 1 : 0
      });
    });
  });
  </script>
</body>
</html>