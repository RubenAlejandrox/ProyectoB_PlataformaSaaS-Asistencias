{{--
/**
 * G.A.M.A. SOLUTIONS S.A. de C.V.
 * "El factor de cambio en tu tecnología"
 *
 * @descripcion     Vista de Sidebar con navegación dinámica por rol
 * @autor           Rubén Alejandro Nolasco Ruiz
 * @autorizador     Rubén Alejandro Nolasco Ruiz
 * @prueba          Diego Miguel Hernandez Fabela
 * @mantenimiento   Ghael Garcia Manjarrez
 * @version         0.2.0
 * @creado          11/04/2026
 * @modificado      26/05/2026
 *
 * @cambios
 * Fecha       | Autor             | Descripción
 * ------------|-------------------|------------------------------------------
 * 03/04/2026  | Rubén Alejandro   | Implementación inicial de Sidebar y Toggle móvil.
 * 11/04/2026  | Rubén Alejandro   | Ajuste de estructura de prólogo según manual GAMA-MPL-03.
 * 07/05/2026  | Claude Code       | Actualización de nav-items a módulos reales Proyecto B.
 * 26/05/2026  | Claude Web        | Navegación dinámica por rol: Administrator, Teacher, Student.
 *             |                   | Avatar con iniciales reales. Logout con POST correcto.
 */
--}}

<!-- Mobile Menu Toggle -->
<button class="mobile-menu-toggle" id="mobileMenuToggle">
    <div class="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </div>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">

    <!-- Brand Header -->
    <div class="sidebar-brand">
        <div class="logo-icon">
            <img src="{{ asset('img/gama-logo.png') }}" alt="G.A.M.A Solutions">
        </div>
    </div>

    <!-- User Info -->
    <div class="sidebar-user">
        <div class="user-avatar">
            {{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}{{ strtoupper(substr(auth()->user()->last_name, 0, 1)) }}
        </div>
        <div class="user-info">
            <span class="user-name">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
            <span class="user-role">
                @if(auth()->user()->hasRole('Administrator'))
                    Administrador
                @elseif(auth()->user()->hasRole('Teacher'))
                    Docente
                @else
                    Alumno
                @endif
            </span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">

        <!-- Principal — todos los roles -->
        <div class="nav-section">
            <span class="nav-label">Principal</span>
            <a href="{{ route('dashboard') }}"
               class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
               data-tooltip="Dashboard">
                <i class="fas fa-home nav-icon"></i>
                <span class="nav-text">Dashboard</span>
            </a>
        </div>

        <!-- ── ADMINISTRATOR ─────────────────────────────────── -->
        @role('Administrator')
        <div class="nav-section">
            <span class="nav-label">Administración</span>

            <a href="{{ route('instituciones.index') }}"
               class="nav-item {{ request()->routeIs('instituciones.*') ? 'active' : '' }}"
               data-tooltip="Instituciones">
                <i class="fas fa-building nav-icon"></i>
                <span class="nav-text">Instituciones</span>
            </a>

            <a href="{{ route('membresias.index') }}"
               class="nav-item {{ request()->routeIs('membresias.*') ? 'active' : '' }}"
               data-tooltip="Membresías">
                <i class="fas fa-id-card nav-icon"></i>
                <span class="nav-text">Membresías</span>
            </a>

            <a href="{{ route('admin.edicion') }}"
               class="nav-item {{ request()->routeIs('admin.edicion') ? 'active' : '' }}"
               data-tooltip="Edición Admin">
                <i class="fas fa-user-edit nav-icon"></i>
                <span class="nav-text">Edición Admin</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-label">Módulos</span>

            <a href="{{ route('aulas.index') }}"
               class="nav-item {{ request()->routeIs('aulas.*') ? 'active' : '' }}"
               data-tooltip="Aulas">
                <i class="fas fa-chalkboard nav-icon"></i>
                <span class="nav-text">Aulas</span>
            </a>

            <a href="{{ route('justificantes.index') }}"
               class="nav-item {{ request()->routeIs('justificantes.*') ? 'active' : '' }}"
               data-tooltip="Justificantes">
                <i class="fas fa-file-alt nav-icon"></i>
                <span class="nav-text">Justificantes</span>
            </a>

            <a href="{{ route('reportes.index') }}"
               class="nav-item {{ request()->routeIs('reportes.*') ? 'active' : '' }}"
               data-tooltip="Reportes">
                <i class="fas fa-chart-bar nav-icon"></i>
                <span class="nav-text">Reportes</span>
            </a>
        </div>
        @endrole

        <!-- ── TEACHER ────────────────────────────────────────── -->
        @role('Teacher')
        <div class="nav-section">
            <span class="nav-label">Mis Aulas</span>

            <a href="{{ route('aulas.index') }}"
               class="nav-item {{ request()->routeIs('aulas.*') ? 'active' : '' }}"
               data-tooltip="Mis Aulas">
                <i class="fas fa-chalkboard nav-icon"></i>
                <span class="nav-text">Mis Aulas</span>
            </a>

            <a href="{{ route('asistencias.docente') }}"
               class="nav-item {{ request()->routeIs('asistencias.docente') ? 'active' : '' }}"
               data-tooltip="Sesiones">
                <i class="fas fa-key nav-icon"></i>
                <span class="nav-text">Sesiones</span>
            </a>

            <a href="{{ route('justificantes.index') }}"
               class="nav-item {{ request()->routeIs('justificantes.*') ? 'active' : '' }}"
               data-tooltip="Justificantes">
                <i class="fas fa-file-alt nav-icon"></i>
                <span class="nav-text">Justificantes</span>
            </a>

            <a href="{{ route('reportes.index') }}"
               class="nav-item {{ request()->routeIs('reportes.*') ? 'active' : '' }}"
               data-tooltip="Reportes">
                <i class="fas fa-chart-bar nav-icon"></i>
                <span class="nav-text">Reportes</span>
            </a>

            <a href="{{ route('ciclo.cierre') }}"
               class="nav-item {{ request()->routeIs('ciclo.*') ? 'active' : '' }}"
               data-tooltip="Cierre de Ciclo">
                <i class="fas fa-lock nav-icon"></i>
                <span class="nav-text">Cierre de Ciclo</span>
            </a>
        </div>
        @endrole

        <!-- ── STUDENT ────────────────────────────────────────── -->
        @role('Student')
        <div class="nav-section">
            <span class="nav-label">Mi Portal</span>

            <a href="{{ route('asistencias.alumno') }}"
               class="nav-item {{ request()->routeIs('asistencias.alumno') ? 'active' : '' }}"
               data-tooltip="Registrar Asistencia">
                <i class="fas fa-qrcode nav-icon"></i>
                <span class="nav-text">Registrar Asistencia</span>
            </a>

            <a href="{{ route('justificantes.index') }}"
               class="nav-item {{ request()->routeIs('justificantes.*') ? 'active' : '' }}"
               data-tooltip="Mis Justificantes">
                <i class="fas fa-file-upload nav-icon"></i>
                <span class="nav-text">Mis Justificantes</span>
            </a>

            <a href="{{ route('asistencias.alumno') }}"
               class="nav-item {{ request()->routeIs('asistencias.alumno') ? 'active' : '' }}"
               data-tooltip="Mi Historial">
                <i class="fas fa-history nav-icon"></i>
                <span class="nav-text">Mi Historial</span>
            </a>
        </div>
        @endrole

    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <a href="#" class="nav-item" data-tooltip="Mi Perfil">
            <i class="fas fa-user-circle nav-icon"></i>
            <span class="nav-text">Mi Perfil</span>
        </a>

        {{-- Logout con POST correcto --}}
        <form method="POST" action="{{ route('auth.logout') }}" id="logoutForm">
            @csrf
            <button type="submit"
                    class="nav-item logout"
                    style="width:100%; background:none; border:none; cursor:pointer; text-align:left;"
                    data-tooltip="Cerrar sesión">
                <i class="fas fa-sign-out-alt nav-icon"></i>
                <span class="nav-text">Cerrar sesión</span>
            </button>
        </form>
    </div>

</aside>

<script>
    // Mobile menu toggle
    const sidebar        = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');

    mobileMenuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        sidebarOverlay.classList.toggle('active');
        mobileMenuToggle.classList.toggle('active');
    });

    sidebarOverlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        mobileMenuToggle.classList.remove('active');
    });

    // Marcar nav-item activo según la URL actual
    document.querySelectorAll('.nav-item').forEach(item => {
        if (item.href && item.href === window.location.href) {
            item.classList.add('active');
        }
    });
</script>