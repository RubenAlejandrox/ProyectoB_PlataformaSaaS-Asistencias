# GAMA-MD-F-08 — Formato de Pruebas

## Registro de ejecución de pruebas automatizadas (PHPUnit)

| **Campo corporativo** | **Valor** |
|---|---|
| **Nombre del Proyecto** | Sistema de Control de Asistencias y Gestión Escolar |
| **Departamento** | Control de Calidad / QA |
| **Sistema** | Aplicación Web de Gestión Académica (Laravel/PHP) |
| **Herramienta** | PHPUnit — `php artisan test` |
| **Repositorio** | ProyectoB_PlataformaSaaS-Asistencias |
| **Fecha de ejecución** | 01/06/2026 |
| **Duración total** | 598,48 s (~9 min 58 s) |
| **Resumen global** | **96** pruebas · **96** aprobadas · **0** no aprobadas · **265** aserciones · **Exit code: 0** |
| **Veredicto de auditoría** | **APROBADO** |

### Comando de reproducción

```bash
php artisan test
```

---

## Resumen por módulo (consolidado QA)

| Módulo | Casos | Aprobados | No aprobados | % Cumplimiento |
|---|:---:|:---:|:---:|:---:|
| Autenticación, registro y perfil | 16 | 16 | 0 | 100 % |
| Instituciones y seguridad (middleware / roles) | 12 | 12 | 0 | 100 % |
| Aulas e inscripción | 14 | 14 | 0 | 100 % |
| Control de asistencias (clave y sesión) | 11 | 11 | 0 | 100 % |
| Justificantes y almacenamiento | 7 | 7 | 0 | 100 % |
| Membresías, pagos y suscripciones | 14 | 14 | 0 | 100 % |
| Reportes y correo institucional | 4 | 4 | 0 | 100 % |
| Semáforo, progreso y notificaciones | 9 | 9 | 0 | 100 % |
| Cierre de ciclo y edición administrativa | 8 | 8 | 0 | 100 % |
| Historial de pagos | 5 | 5 | 0 | 100 % |
| Consulta académica (alumno) | 2 | 2 | 0 | 100 % |
| **TOTAL** | **96** | **96** | **0** | **100 %** |

### Resumen por suite (archivo de prueba)

| Suite | Tipo | Casos | Resultado |
|---|:---:|:---:|:---:|
| `PayPalServiceTest` | Unit | 6 | PASS |
| `ProgressServiceTest` | Unit | 4 | PASS |
| `ReportGeneratorServiceTest` | Unit | 2 | PASS |
| `SubscriptionServiceTest` | Unit | 2 | PASS |
| `SupabaseStorageServiceTest` | Unit | 2 | PASS |
| `AttendanceTest` | Feature | 11 | PASS |
| `AuditLogTest` | Feature | 5 | PASS |
| `AuthBcryptTest` | Feature | 7 | PASS |
| `ClassroomDetailTest` | Feature | 4 | PASS |
| `ClassroomGrupoTest` | Feature | 3 | PASS |
| `CycleClosureTest` | Feature | 3 | PASS |
| `EnrollmentTest` | Feature | 4 | PASS |
| `JustificationTest` | Feature | 5 | PASS |
| `MateriaDetailTest` | Feature | 2 | PASS |
| `MiddlewareTest` | Feature | 13 | PASS |
| `PaymentHistoryTest` | Feature | 5 | PASS |
| `ProfileTest` | Feature | 6 | PASS |
| `RegisterNameValidationTest` | Feature | 3 | PASS |
| `ReportMailTest` | Feature | 2 | PASS |
| `StudentClassroomsTest` | Feature | 1 | PASS |
| `StudentNotificationTest` | Feature | 3 | PASS |
| `SubscriptionMembershipTest` | Feature | 3 | PASS |

---

## Cuerpo del formato — Matriz de casos de prueba

| # | Caso de prueba (método PHPUnit) | Módulo | Identificador del requerimiento | Aprobado | No aprobado | Observaciones | Propuesta de solución |
|---:|---|---|---|:---:|:---:|---|---|
| 1 | `PayPalServiceTest::paypal_service_instantiates_correctly` | Módulo de Membresías y Pagos | RF-03 / RF-12 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 2 | `PayPalServiceTest::create_order_returns_order_id_and_approve_url` | Módulo de Membresías y Pagos | RF-03 / RF-12 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 3 | `PayPalServiceTest::failed_renewal_creates_payment_record_with_failed_status` | Módulo de Membresías y Pagos | RF-03 / RF-12 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 4 | `PayPalServiceTest::suspension_after_3_failed_attempts` | Módulo de Membresías y Pagos | RF-03 / RF-12 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 5 | `PayPalServiceTest::successful_renewal_creates_completed_payment_record` | Módulo de Membresías y Pagos | RF-03 / RF-12 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 6 | `PayPalServiceTest::free_plan_skips_paypal_and_renews_directly` | Módulo de Membresías y Pagos | RF-03 / RF-12 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 7 | `ProgressServiceTest::calculates_percentage_with_present_and_approved` | Módulo de Semáforo y Progreso Académico | RF-10 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 8 | `ProgressServiceTest::determines_traffic_lights_by_threshold` | Módulo de Semáforo y Progreso Académico | RF-10 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 9 | `ProgressServiceTest::zero_sessions_returns_zero_percent_and_red_light` | Módulo de Semáforo y Progreso Académico | RF-10 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 10 | `ProgressServiceTest::projects_remaining_absences_before_threshold` | Módulo de Semáforo y Progreso Académico | RF-10 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 11 | `ReportGeneratorServiceTest::builds_matrix_payload_with_a_f_j_values` | Módulo de Reportes | RF-09 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 12 | `ReportGeneratorServiceTest::builds_monthly_payload_with_expected_totals` | Módulo de Reportes | RF-09 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 13 | `SubscriptionServiceTest::change_plan_from_basic_to_pro_keeps_single_active_subscription` | Módulo de Membresías y Suscripciones | RF-03 / RF-12 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 14 | `SubscriptionServiceTest::assign_initial_rejects_institution_with_active_subscription` | Módulo de Membresías y Suscripciones | RF-03 / RF-12 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 15 | `SupabaseStorageServiceTest::default_justifications_bucket_is_justificantes_adjuntos` | Módulo de Justificantes | RF-08 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 16 | `SupabaseStorageServiceTest::allows_pdf_with_octet_stream_mime` | Módulo de Justificantes | RF-08 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 17 | `AttendanceTest::valid_key_registers_present_attendance` | Módulo de Control de Asistencias | RF-06 (Clave de Registro) | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 18 | `AttendanceTest::expired_key_returns_422` | Módulo de Control de Asistencias | RF-06 (Clave de Registro) | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 19 | `AttendanceTest::duplicate_registration_returns_409` | Módulo de Control de Asistencias | RF-06 (Clave de Registro) | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 20 | `AttendanceTest::student_not_enrolled_returns_403` | Módulo de Control de Asistencias | RF-06 (Clave de Registro) | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 21 | `AttendanceTest::progress_endpoint_uses_present_and_approved` | Módulo de Control de Asistencias | RF-10 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 22 | `AttendanceTest::session_key_respects_duration_minutes` | Módulo de Control de Asistencias | RF-06 (Clave de Registro) | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 23 | `AttendanceTest::close_session_marks_absents` | Módulo de Control de Asistencias | RF-07 (Cierre de Sesión) | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 24 | `AttendanceTest::teacher_can_stop_session_key_before_expiration_via_web` | Módulo de Control de Asistencias | RF-06 (Clave de Registro) | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 25 | `AttendanceTest::teacher_can_close_session_via_web` | Módulo de Control de Asistencias | RF-07 (Cierre de Sesión) | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 26 | `AttendanceTest::teacher_can_manually_mark_absent_for_justification_window` | Módulo de Control de Asistencias | RF-07 / RF-08 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 27 | `AttendanceTest::teacher_can_reset_attendance_to_pending` | Módulo de Control de Asistencias | RF-07 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 28 | `AuditLogTest::correction_generates_audit_log_entry` | Módulo de Edición Administrativa y Auditoría | RF-14 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 29 | `AuditLogTest::if_change_fails_audit_log_is_rolled_back` | Módulo de Edición Administrativa y Auditoría | RF-14 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 30 | `AuditLogTest::teacher_cannot_access_admin_web_routes` | Módulo de Seguridad y Control de Acceso | RF-13 / RF-14 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 31 | `AuditLogTest::teacher_cannot_execute_admin_api_actions` | Módulo de Seguridad y Control de Acceso | RF-13 / RF-14 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 32 | `AuditLogTest::teacher_and_student_cannot_access_admin_web_modules` | Módulo de Seguridad y Control de Acceso | RF-13 / RF-14 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 33 | `AuthBcryptTest::login_with_correct_credentials_returns_token` | Módulo de Autenticación y Sesión | RF-01 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 34 | `AuthBcryptTest::login_with_wrong_password_returns_401` | Módulo de Autenticación y Sesión | RF-01 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 35 | `AuthBcryptTest::account_locks_after_5_failed_attempts` | Módulo de Autenticación y Sesión | RF-01 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 36 | `AuthBcryptTest::locked_account_returns_423` | Módulo de Autenticación y Sesión | RF-01 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 37 | `AuthBcryptTest::successful_login_resets_failed_attempts` | Módulo de Autenticación y Sesión | RF-01 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 38 | `AuthBcryptTest::logout_revokes_token` | Módulo de Autenticación y Sesión | RF-01 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 39 | `AuthBcryptTest::double_session_is_prevented` | Módulo de Autenticación y Sesión | RF-01 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 40 | `ClassroomDetailTest::teacher_can_view_classroom_detail` | Módulo de Aulas | RF-04 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 41 | `ClassroomDetailTest::student_cannot_view_classroom_detail` | Módulo de Aulas | RF-04 / RF-13 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 42 | `ClassroomDetailTest::other_teacher_cannot_view_classroom_detail` | Módulo de Aulas | RF-04 / RF-13 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 43 | `ClassroomDetailTest::teacher_can_export_student_list` | Módulo de Aulas | RF-04 / RF-09 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 44 | `ClassroomGrupoTest::teacher_can_create_two_classrooms_same_subject_with_different_grupo` | Módulo de Aulas | RF-04 / RF-05 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 45 | `ClassroomGrupoTest::cannot_create_duplicate_subject_period_and_grupo` | Módulo de Aulas | RF-04 / RF-05 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 46 | `ClassroomGrupoTest::grupo_must_be_exactly_six_digits` | Módulo de Aulas | RF-04 / RF-05 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 47 | `CycleClosureTest::closes_cycle_with_correct_key` | Módulo de Cierre de Ciclo Escolar | RF-11 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 48 | `CycleClosureTest::blocks_after_three_failed_attempts` | Módulo de Cierre de Ciclo Escolar | RF-11 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 49 | `CycleClosureTest::rejects_closure_when_pending_justifications_exist` | Módulo de Cierre de Ciclo Escolar | RF-11 / RF-08 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 50 | `EnrollmentTest::student_register_with_classroom_code_creates_enrollment` | Módulo de Inscripción por Código | RF-05 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 51 | `EnrollmentTest::logged_in_student_can_join_classroom_with_code` | Módulo de Inscripción por Código | RF-05 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 52 | `EnrollmentTest::cannot_enroll_twice_in_same_classroom` | Módulo de Inscripción por Código | RF-05 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 53 | `EnrollmentTest::same_code_can_enroll_multiple_students_until_expiration` | Módulo de Inscripción por Código | RF-05 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 54 | `JustificationTest::student_uploads_valid_file_creates_pending_justification` | Módulo de Justificantes | RF-08 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 55 | `JustificationTest::student_cannot_justify_absence_after_72_hours` | Módulo de Justificantes | RF-08 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 56 | `JustificationTest::teacher_approval_sets_approved_and_reviewed_at` | Módulo de Justificantes | RF-08 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 57 | `JustificationTest::modifying_reviewed_at_after_review_throws_exception` | Módulo de Justificantes | RF-08 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 58 | `JustificationTest::student_cannot_review_justification_returns_403` | Módulo de Justificantes | RF-08 / RF-13 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 59 | `MateriaDetailTest::student_can_view_materia_detail` | Módulo de Consulta Académica (Alumno) | RF-10 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 60 | `MateriaDetailTest::projection_calculates_remaining_absences` | Módulo de Consulta Académica (Alumno) | RF-10 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 61 | `MiddlewareTest::unauthenticated_request_returns_401` | Módulo de Seguridad y Control de Acceso | RF-13 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 62 | `MiddlewareTest::administrator_can_access_institution_routes` | Módulo de Instituciones | RF-02 / RF-13 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 63 | `MiddlewareTest::teacher_cannot_access_institution_routes` | Módulo de Instituciones | RF-02 / RF-13 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 64 | `MiddlewareTest::student_cannot_access_institution_routes` | Módulo de Instituciones | RF-02 / RF-13 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 65 | `MiddlewareTest::teacher_can_access_classroom_routes` | Módulo de Aulas | RF-04 / RF-13 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 66 | `MiddlewareTest::student_cannot_access_classroom_routes` | Módulo de Aulas | RF-04 / RF-13 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 67 | `MiddlewareTest::expired_plan_blocks_post_requests` | Módulo de Membresías (Middleware de plan) | RF-03 / RF-13 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 68 | `MiddlewareTest::expired_plan_allows_get_requests` | Módulo de Membresías (Middleware de plan) | RF-03 / RF-13 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 69 | `MiddlewareTest::active_plan_allows_all_requests` | Módulo de Membresías (Middleware de plan) | RF-03 / RF-13 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 70 | `MiddlewareTest::post_request_creates_audit_log_entry` | Módulo de Bitácora de Auditoría | RF-14 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 71 | `MiddlewareTest::get_request_does_not_create_audit_log` | Módulo de Bitácora de Auditoría | RF-14 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 72 | `MiddlewareTest::login_route_is_not_audited` | Módulo de Bitácora de Auditoría | RF-14 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 73 | `MiddlewareTest::admin_can_create_institution_and_it_gets_audited` | Módulo de Instituciones | RF-02 / RF-14 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 74 | `PaymentHistoryTest::admin_can_view_payment_history` | Módulo de Historial de Pagos | RF-12 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 75 | `PaymentHistoryTest::teacher_cannot_view_payment_history` | Módulo de Historial de Pagos | RF-12 / RF-13 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 76 | `PaymentHistoryTest::admin_can_download_invoice_for_completed_payment` | Módulo de Historial de Pagos | RF-12 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 77 | `PaymentHistoryTest::cannot_download_invoice_for_failed_payment` | Módulo de Historial de Pagos | RF-12 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 78 | `PaymentHistoryTest::admin_can_filter_by_status` | Módulo de Historial de Pagos | RF-12 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 79 | `ProfileTest::guest_cannot_access_profile` | Módulo de Perfil de Usuario | RF-01 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 80 | `ProfileTest::any_role_can_view_profile` | Módulo de Perfil de Usuario | RF-01 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 81 | `ProfileTest::user_can_update_name_and_email` | Módulo de Perfil de Usuario | RF-01 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 82 | `ProfileTest::email_must_be_unique` | Módulo de Perfil de Usuario | RF-01 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 83 | `ProfileTest::password_change_requires_correct_current_password` | Módulo de Perfil de Usuario | RF-01 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 84 | `ProfileTest::user_can_change_password` | Módulo de Perfil de Usuario | RF-01 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 85 | `RegisterNameValidationTest::register_rejects_names_with_numbers` | Módulo de Registro de Usuarios | RF-01 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 86 | `RegisterNameValidationTest::register_rejects_empty_names` | Módulo de Registro de Usuarios | RF-01 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 87 | `RegisterNameValidationTest::register_accepts_valid_names_with_spaces_and_hyphens` | Módulo de Registro de Usuarios | RF-01 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 88 | `ReportMailTest::report_mail_renders_institutional_template` | Módulo de Reportes | RF-09 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 89 | `ReportMailTest::send_fails_when_mailer_is_log` | Módulo de Reportes | RF-09 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 90 | `StudentClassroomsTest::student_only_sees_enrolled_classrooms` | Módulo de Aulas (Vista Alumno) | RF-04 / RF-05 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 91 | `StudentNotificationTest::traffic_light_change_creates_notification` | Módulo de Notificaciones | RF-10 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 92 | `StudentNotificationTest::student_can_view_notifications_index` | Módulo de Notificaciones | RF-10 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 93 | `StudentNotificationTest::justification_review_creates_notification` | Módulo de Notificaciones | RF-08 / RF-10 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 94 | `SubscriptionMembershipTest::assign_modal_lists_only_institutions_without_active_subscription` | Módulo de Membresías y Suscripciones | RF-03 / RF-12 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 95 | `SubscriptionMembershipTest::cannot_assign_second_active_subscription_to_same_institution` | Módulo de Membresías y Suscripciones | RF-03 / RF-12 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |
| 96 | `SubscriptionMembershipTest::can_assign_initial_free_plan_to_institution_without_subscription` | Módulo de Membresías y Suscripciones | RF-03 / RF-12 | X | | El componente cumple con los criterios de aceptación definidos. | Ninguna (Operación Óptima) |

---

## Salida de terminal (referencia)

```
Tests:    96 passed (265 assertions)
Duration: 598.48s
Exit code: 0
```

Todas las suites reportaron `PASS`:

- `Tests\Unit\PayPalServiceTest` (6)
- `Tests\Unit\ProgressServiceTest` (4)
- `Tests\Unit\ReportGeneratorServiceTest` (2)
- `Tests\Unit\SubscriptionServiceTest` (2)
- `Tests\Unit\SupabaseStorageServiceTest` (2)
- `Tests\Feature\AttendanceTest` (11)
- `Tests\Feature\AuditLogTest` (5)
- `Tests\Feature\AuthBcryptTest` (7)
- `Tests\Feature\ClassroomDetailTest` (4)
- `Tests\Feature\ClassroomGrupoTest` (3)
- `Tests\Feature\CycleClosureTest` (3)
- `Tests\Feature\EnrollmentTest` (4)
- `Tests\Feature\JustificationTest` (5)
- `Tests\Feature\MateriaDetailTest` (2)
- `Tests\Feature\MiddlewareTest` (13)
- `Tests\Feature\PaymentHistoryTest` (5)
- `Tests\Feature\ProfileTest` (6)
- `Tests\Feature\RegisterNameValidationTest` (3)
- `Tests\Feature\ReportMailTest` (2)
- `Tests\Feature\StudentClassroomsTest` (1)
- `Tests\Feature\StudentNotificationTest` (3)
- `Tests\Feature\SubscriptionMembershipTest` (3)

---

## Conclusión del auditor

La ejecución integral de la suite automatizada finalizó sin incidencias. No se registraron fallos de aserción ni excepciones no controladas en los módulos críticos evaluados.

**Recomendación:** Aprobar el estado de calidad del build evaluado, complementando con pruebas funcionales manuales (UI/UX, PayPal sandbox) no cubiertas por esta corrida.

---

## Trazabilidad de requerimientos (referencia)

| Código | Ámbito funcional (inferido del repositorio) |
|---|---|
| RF-01 | Autenticación, registro y perfil |
| RF-02 | Instituciones |
| RF-03 / RF-12 | Membresías, planes y pagos |
| RF-04 / RF-05 | Aulas, grupo y códigos de inscripción |
| RF-06 | Clave de registro de asistencia |
| RF-07 | Cierre de sesión y faltas |
| RF-08 | Justificantes (ventana 72 h) |
| RF-09 | Reportes y envío por correo |
| RF-10 | Semáforo, progreso y notificaciones |
| RF-11 | Cierre de ciclo escolar |
| RF-13 | Middleware de roles y plan |
| RF-14 | Edición administrativa y bitácora |

---

*G.A.M.A. Solutions S.A. de C.V. — Control de Calidad / QA*  
*Documento: GAMA-MD-F-08 · Generado para traslado a Word (Dirección General)*  
*Última corrida documentada: 01/06/2026*
