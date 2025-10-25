<?php
// test_db.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Test de conexión</h2>";

echo "<p>1. PHP funciona ✅</p>";

echo "<p>2. Intentando incluir db.php...</p>";
include '../db.php';
echo "<p>3. db.php incluido ✅</p>";

echo "<p>4. Probando conexión PDO...</p>";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "<p>5. Conexión a BD exitosa ✅</p>";
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<p>6. Iniciando sesión...</p>";
session_start();
echo "<p>7. Sesión iniciada ✅</p>";

echo "<p>8. Datos de sesión:</p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>
```

Sube ese archivo a `/login/test_db.php` y luego accede desde tu navegador:
```
http://localhost/tu_proyecto/login/test_db.php