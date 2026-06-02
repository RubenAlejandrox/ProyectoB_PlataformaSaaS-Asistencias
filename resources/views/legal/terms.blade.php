{{--
/**
 * G.A.M.A. SOLUTIONS S.A. de C.V.
 * "El factor de cambio en tu tecnología"
 *
 * @descripcion    Vista de Términos y Condiciones de Uso para la plataforma SaaS (Proyecto B).
 * @autor          Rubén Alejandro Nolasco Ruiz
 * @autorizador    Rubén Alejandro Nolasco Ruiz
 * @prueba         Diego Miguel Hernandez Fabela  
 * @mantenimiento  Ghael Garcia Manjarrez 
 * @version        1.0.0
 * @creado         11/04/2026
 * @modificado     11/04/2026
 *
 * @cambios
 * Fecha       | Autor             | Descripción
 * ------------|-------------------|------------------------------------------
 * 11/04/2026  | Rubén Alejandro   | Creación de la interfaz de Términos y Condiciones con estilos institucionales.
 * 11/04/2026  | Rubén Alejandro   | Integración de cláusulas legales y roles de usuario (Docente/Alumno/Admin).
 */
--}}

@extends('layouts.app')

@section('title', 'Términos y Condiciones - G.A.M.A Solutions')

@section('content')
<div class="main-content">
    {{-- Page Header --}}
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>Términos y Condiciones de Uso</h1>
                <p>Condiciones generales para el uso de la Plataforma SaaS de Gestión de Asistencias y Cumplimiento Académico</p>
            </div>
        </div>
    </div>

    {{-- Terms Content --}}
    <div class="card">
        <div class="card-body">
            <div class="terms-content">
                <p class="intro-text">
                    <i class="fas fa-info-circle"></i>
                    Al registrarse o iniciar sesión en la Plataforma SaaS de Gestión de Asistencias y Cumplimiento Académico, el usuario acepta en su totalidad los presentes Términos.
                </p>

                <section class="terms-section">
                    <h2><span class="section-number">1</span> <i class="fas fa-book"></i> Definiciones Específicas del Proyecto B</h2>
                    <ol class="definitions-list numbered-list">
                        <li><strong>"Plataforma":</strong> la solución SaaS de gestión de asistencias desarrollada por G.A.M.A. Solutions.</li>
                        <li><strong>"Aula Virtual":</strong> espacio digital creado por el docente para gestionar la asistencia de un grupo.</li>
                        <li><strong>"QR de Sesión":</strong> código de uso temporal generado por el docente para que los alumnos registren asistencia.</li>
                        <li><strong>"Justificante":</strong> evidencia digital subida por el alumno para justificar una falta.</li>
                        <li><strong>"Membresía":</strong> plan de suscripción que define las funcionalidades disponibles para el docente (Free / Básico / Pro).</li>
                    </ol>
                </section>

                <section class="terms-section">
                    <h2><span class="section-number">2</span> <i class="fas fa-key"></i> Condiciones de Acceso</h2>
                    <ol class="terms-list numbered-list">
                        <li>El acceso al sistema está restringido a usuarios registrados con credenciales vigentes.</li>
                        <li>El usuario es responsable de mantener la confidencialidad de sus credenciales. G.A.M.A. Solutions no almacena contraseñas en texto plano ni en formato reversible.</li>
                        <li>En caso de compromiso de credenciales, el usuario deberá notificar inmediatamente al administrador del sistema.</li>
                    </ol>
                </section>

                <section class="terms-section">
                    <h2><span class="section-number">3</span> <i class="fas fa-check-circle"></i> Uso Aceptable</h2>
                    <p>El usuario se compromete a:</p>
                    <ol class="terms-list numbered-list">
                        <li>Utilizar el sistema exclusivamente para los fines institucionales o educativos para los que fue contratado.</li>
                        <li>No intentar acceder a módulos o datos para los cuales no tenga autorización según su rol asignado.</li>
                        <li>No realizar ingeniería inversa, descompilación ni análisis no autorizado del código fuente o infraestructura.</li>
                        <li>No sobrecargar intencionalmente los servidores superando los límites de peticiones establecidos.</li>
                        <li>No subir archivos con código malicioso, malware o contenido dañino.</li>
                        <li>Reportar vulnerabilidades de seguridad al equipo de G.A.M.A. Solutions en lugar de explotarlas.</li>
                    </ol>
                </section>

                <section class="terms-section">
                    <h2><span class="section-number">4</span> <i class="fas fa-copyright"></i> Propiedad Intelectual</h2>
                    <p>Todos los componentes del sistema —código fuente, diseño de interfaz (Cap. 8 MPL-GAMA-03), bases de datos, algoritmos y documentación— son propiedad exclusiva de G.A.M.A. Solutions. La Institución Cliente obtiene una licencia de uso limitada, no exclusiva y no transferible. Queda prohibida la reproducción, distribución o modificación sin autorización escrita.</p>
                </section>

                <section class="terms-section">
                    <h2><span class="section-number">5</span> <i class="fas fa-server"></i> Disponibilidad y Limitación de Responsabilidad</h2>
                    <ol class="terms-list numbered-list">
                        <li>G.A.M.A. Solutions realizará sus mejores esfuerzos para mantener la disponibilidad continua, sin garantizar disponibilidad ininterrumpida.</li>
                        <li>Las ventanas de mantenimiento se notificarán con al menos 24 horas de anticipación.</li>
                        <li>G.A.M.A. Solutions no será responsable por pérdida de datos por uso inadecuado, daños indirectos, ni por fallas en servicios de autenticación institucionales externos.</li>
                    </ol>
                </section>

                <section class="terms-section">
                    <h2><span class="section-number">6</span> <i class="fas fa-edit"></i> Modificaciones a los Términos</h2>
                    <p>Cualquier modificación entrará en vigor al momento de su publicación. Los usuarios serán notificados mediante modal de aceptación (Cap. 8.3.5 del MPL-GAMA-03) y deberán aceptar explícitamente antes de continuar. El uso continuado implica aceptación.</p>
                </section>

                <section class="terms-section">
                    <h2><span class="section-number">7</span> <i class="fas fa-gavel"></i> Legislación Aplicable</h2>
                    <p>Los presentes Términos se rigen por la legislación vigente en los Estados Unidos Mexicanos. Las partes se someten a la jurisdicción de los tribunales competentes en la Ciudad de México, renunciando a cualquier otro fuero que pudiera corresponderles.</p>
                </section>

                <section class="terms-section">
                    <h2><span class="section-number">8</span> <i class="fas fa-users-cog"></i> Responsabilidades por Rol — Proyecto B</h2>

                    <h3><span class="subsection-number">8.1</span> <i class="fas fa-chalkboard-teacher"></i> Docente</h3>
                    <ol class="terms-list numbered-list">
                        <li>Es responsable de la veracidad de los registros de asistencia y de la revisión oportuna de justificantes.</li>
                        <li>No debe compartir el código QR de sesión con personas no autorizadas, ya que su uso indebido puede falsificar asistencias. La regeneración del QR invalida el código anterior de forma irreversible.</li>
                        <li>El límite de aulas activas por plan de membresía es un límite técnico; al alcanzarlo, el botón de creación de nueva aula se deshabilitará con mensaje explicativo.</li>
                        <li>Al expirar la membresía, las funciones avanzadas se desactivan, pero los datos históricos se conservan íntegramente.</li>
                    </ol>

                    <h3><span class="subsection-number">8.2</span> <i class="fas fa-user-graduate"></i> Alumno</h3>
                    <ol class="terms-list numbered-list">
                        <li>Es responsable de subir justificantes verídicos en los formatos y dentro del plazo configurado en el aula. La falsificación de justificantes puede resultar en la cancelación de su acceso al portal.</li>
                        <li>Acepta que su porcentaje de asistencia y estatus de riesgo son visibles para el docente y el administrador del sistema.</li>
                        <li>El registro de asistencia mediante QR es individual e intransferible; registrar asistencia en nombre de otra persona constituye una infracción a los presentes Términos.</li>
                    </ol>

                    <h3><span class="subsection-number">8.3</span> <i class="fas fa-user-shield"></i> Administrador</h3>
                    <ol class="terms-list numbered-list">
                        <li>Es responsable de gestionar las claves de suscripción; cada clave es de un solo uso y no puede ser reutilizada.</li>
                        <li>Puede consultar el historial de activaciones y el estado general de la plataforma.</li>
                    </ol>
                </section>

                <section class="terms-section">
                    <h2><span class="section-number">9</span> <i class="fas fa-credit-card"></i> Plan SaaS y Condiciones de Suscripción</h2>
                    <ol class="terms-list numbered-list">
                        <li>El acceso a funciones avanzadas (exportar Excel, aulas ilimitadas, reportes analíticos) requiere plan Básico o Pro activo.</li>
                        <li>Las claves de activación son proporcionadas por el Administrador del sistema; G.A.M.A. Solutions no procesa pagos directamente a través de la plataforma.</li>
                        <li>La cancelación de la suscripción no implica la eliminación de datos históricos. Los datos se conservan durante el tiempo establecido en la política de retención aplicable.</li>
                    </ol>
                </section>
            </div>
        </div>
    </div>
</div>

<style>
.terms-content {
    max-width: none;
}

.terms-content .intro-text {
    background: var(--ice-blue);
    padding: 20px;
    border-radius: var(--radius-md);
    border-left: 4px solid var(--corp-orange);
    margin-bottom: 32px;
    font-size: 16px;
    line-height: 1.6;
}

.terms-content .intro-text i {
    color: var(--corp-orange);
    margin-right: 12px;
}

.terms-section {
    margin-bottom: 40px;
}

.terms-section h2 {
    font-size: 24px;
    font-weight: 600;
    color: var(--midnight);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.terms-section h2 .section-number {
    background: var(--deep-blue);
    color: white;
    padding: 4px 8px;
    border-radius: var(--radius-sm);
    font-size: 14px;
    font-weight: 700;
    min-width: 40px;
    text-align: center;
}

.terms-section h3 .subsection-number {
    background: var(--royal-blue);
    color: white;
    padding: 2px 6px;
    border-radius: var(--radius-sm);
    font-size: 12px;
    font-weight: 600;
    min-width: 30px;
    text-align: center;
}

.terms-section h2 i {
    color: var(--deep-blue);
    font-size: 20px;
}

.terms-section h3 {
    font-size: 20px;
    font-weight: 600;
    color: var(--royal-blue);
    margin: 24px 0 12px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.terms-section h3 i {
    color: var(--corp-orange);
    font-size: 18px;
}

.terms-section p {
    margin-bottom: 16px;
    line-height: 1.6;
    color: var(--dark-graphite);
}

.definitions-list,
.terms-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.definitions-list li,
.terms-list li {
    padding: 8px 0;
    padding-left: 32px;
    position: relative;
    line-height: 1.6;
    color: var(--dark-graphite);
}

.definitions-list li:before,
.terms-list li:before {
    content: "•";
    color: var(--corp-orange);
    font-weight: bold;
    position: absolute;
    left: 0;
    font-size: 18px;
}

.numbered-list {
    counter-reset: item-counter;
}

.numbered-list li:before {
    content: counter(item-counter) ".";
    counter-increment: item-counter;
    color: var(--deep-blue);
    font-weight: 600;
    font-size: 14px;
}

.definitions-list li strong {
    color: var(--midnight);
    font-weight: 600;
}
</style>
@endsection
