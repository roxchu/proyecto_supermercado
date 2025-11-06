<?php
session_start();

// Limpiar COMPLETAMENTE la sesión
session_unset();
session_destroy();

// Conectar a la base de datos
try {
    $pdo = new PDO("mysql:host=localhost;dbname=supermercado;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Limpiar TODOS los carritos
    $stmt = $pdo->prepare("DELETE FROM carrito");
    $stmt->execute();
    
    echo "✅ SESIÓN Y CARRITO COMPLETAMENTE LIMPIADOS<br>";
    echo "<a href='login_rapido.php'>IR A LOGIN RÁPIDO</a>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>