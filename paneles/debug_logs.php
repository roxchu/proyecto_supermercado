<?php
// Ver logs de debug del servidor
$logFile = 'C:\xampp\php\logs\php_error_log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $recentLines = array_slice($lines, -50); // Últimas 50 líneas
    
    echo "<h3>🔍 Últimos logs del servidor:</h3>";
    echo "<pre style='background: #f4f4f4; padding: 10px; max-height: 400px; overflow-y: auto;'>";
    foreach (array_reverse($recentLines) as $line) {
        if (strpos($line, 'EMPLEADOS_ACTIOONS') !== false) {
            echo "<span style='color: blue;'>" . htmlspecialchars($line) . "</span>\n";
        } else {
            echo htmlspecialchars($line) . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p>No se encontraron logs en: $logFile</p>";
    echo "<p>Logs alternativos podrían estar en:</p>";
    echo "<ul>";
    echo "<li>C:\\xampp\\apache\\logs\\error.log</li>";
    echo "<li>C:\\xampp\\php\\logs\\php_error.log</li>";
    echo "</ul>";
}
?>