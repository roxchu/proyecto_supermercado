<?php
// control_acceso.php 
// Se incluye al inicio de cada página protegida.
// Permitir el uso de sesión entre subcarpetas

session_start();

function verificar_rol($rol_requerido) {
    // 1. GESTIÓN DE SESIÓN Y LOGIN
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: /proyecto_supermercado/login/login.php');
        exit;
    }

    // 2. PREPARACIÓN DE ROLES (Normalizar todo a string minúscula)
    $roles_permitidos = is_array($rol_requerido) ? $rol_requerido : [$rol_requerido];
    
    // Normalizamos el input de la función a un array de strings en minúsculas
    $roles_permitidos_lower = array_map('strtolower', $roles_permitidos); 

    $rol_actual = $_SESSION['rol'] ?? null;
    $id_rol_actual = $_SESSION['id_rol'] ?? null;
    
    // Normalizamos el rol de la sesión a string en minúsculas
    $rol_actual_lower = strtolower((string)($rol_actual ?? '')); 

    // 3. COMPARACIÓN ESTRICTA (Sin consultas a BD, solo sesión vs. requeridos)
    foreach ($roles_permitidos_lower as $permitido_lower) {
        
        // Compara el nombre de rol de la sesión (ej. 'admin')
        if ($rol_actual_lower !== '' && $rol_actual_lower === $permitido_lower) { 
            return; // ¡Permitido por nombre!
        }

        // Compara el ID de rol de la sesión (ej. 1) con el rol requerido (ej. '1' o 'admin')
        if ($id_rol_actual !== null && (string)$id_rol_actual === $permitido_lower) { 
            return; // ¡Permitido por ID!
        }
    }

    // 4. FALLBACK: CONSULTA A LA BASE DE DATOS
    // Si la comparación estricta falla, intentamos mapear el ID a nombre consultando la BD.
    $dbPath = __DIR__ . '/../db.php';
    if (file_exists($dbPath)) {
        require_once $dbPath; 
        if (isset($pdo) && $id_rol_actual) {
            try {
                $stmt = $pdo->prepare('SELECT nombre_rol FROM rol WHERE id_rol = ?');
                $stmt->execute([$id_rol_actual]);
                $nombre_rol = $stmt->fetchColumn();
                
                if ($nombre_rol) {
                    $nombre_rol_lower = strtolower($nombre_rol); 
                    
                    foreach ($roles_permitidos_lower as $permitido_lower) {
                        if ($nombre_rol_lower === $permitido_lower) { 
                            return; // ¡Permitido por BD!
                        }
                    }
                }
            } catch (PDOException $e) {
                // Fallo de BD, se deniega acceso
            }
        }
    }
    
    // 5. DENIEGO DE ACCESO
    // Si la ejecución llega hasta aquí, el acceso es denegado
    header('Location: /proyecto_supermercado/login/sin_permiso.php'); 
    exit;
}
?>