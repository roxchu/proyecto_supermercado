<?php
// control_acceso.php - versión mejorada
// Se incluye al inicio de cada página protegida.

function verificar_rol($rol_requerido) {
    // Si la sesión no ha sido iniciada, la iniciamos
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1) Verificar si está logueado
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        // Ajusta la ruta al login según tu estructura
        header('Location: /proyecto_supermercado/login/login.html');
        exit;
    }

    // Normalizamos $rol_requerido a array
    $roles_permitidos = is_array($rol_requerido) ? $rol_requerido : [$rol_requerido];

    // Soportamos dos formas en sesión:
    // - $_SESSION['rol'] puede ser el nombre ('admin') o
    // - $_SESSION['id_rol'] puede ser el id numérico (1,2,...)
    $rol_actual = $_SESSION['rol'] ?? null;       // nombre textual
    $id_rol_actual = $_SESSION['id_rol'] ?? null; // id numérico

    // Comparación directa (permite que el dev pase id o nombre en $rol_requerido)
    foreach ($roles_permitidos as $permitido) {
        // si el dev pasó un id (ej. 2) y en sesión tenemos id, coincidirá
        if ($rol_actual !== null && (string)$rol_actual === (string)$permitido) {
            return; // permitido
        }
        if ($id_rol_actual !== null && (string)$id_rol_actual === (string)$permitido) {
            return; // permitido
        }
    }

    // Si no hubo coincidencia directa, intentamos mapear id -> nombre consultando BD
    // (esto hace la función más tolerante y evita inconsistencias entre guardar id o nombre)
    $dbPath = __DIR__ . '/../db.php';
    if (file_exists($dbPath)) {
        require_once $dbPath; // crea $pdo
        if (isset($pdo) && $id_rol_actual) {
            try {
                $stmt = $pdo->prepare('SELECT nombre_rol FROM rol WHERE id_rol = ?');
                $stmt->execute([$id_rol_actual]);
                $nombre_rol = $stmt->fetchColumn();
                if ($nombre_rol) {
                    foreach ($roles_permitidos as $permitido) {
                        if ((string)$nombre_rol === (string)$permitido) {
                            return; // permitido
                        }
                    }
                }
            } catch (PDOException $e) {
                // No interrumpimos la ejecución por un fallo aquí; seguiremos denegando acceso más abajo.
            }
        }
    }

    // Si llegamos aquí, acceso denegado
    header('Location: /proyecto_supermercado/sin_permiso.php');
    exit;
}
?>