<?php
// Force error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Debug Server - Farmácia</title>
    <style>
        body { font-family: sans-serif; padding: 20px; line-height: 1.6; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .box { background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #ddd; }
    </style>
</head>
<body>";

echo "<h1>🛠 Diagnóstico do Servidor</h1>";

echo "<div class='box'>";
echo "<h2>1. Ambiente PHP</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "<br>";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "<br>";
echo "Loaded Configuration File: " . php_ini_loaded_file() . "<br>";
echo "</div>";

echo "<div class='box'>";
echo "<h2>2. Arquivo de Configuração</h2>";
if (file_exists(__DIR__ . '/config/database.php')) {
    echo "<span class='success'>✅ Arquivo config/database.php encontrado.</span><br>";
    try {
        require_once __DIR__ . '/config/database.php';
        echo "Constantes carregadas.<br>";
        echo "<strong>DB_HOST:</strong> " . DB_HOST . "<br>";
        echo "<strong>DB_NAME:</strong> " . DB_NAME . "<br>";
        echo "<strong>DB_USER:</strong> " . DB_USER . "<br>";
        echo "<strong>APP_URL:</strong> " . APP_URL . "<br>";
    } catch (Exception $e) {
        echo "<span class='error'>❌ Erro ao carregar config: " . $e->getMessage() . "</span>";
    }
} else {
    echo "<span class='error'>❌ Arquivo config/database.php NÃO encontrado na raiz!</span>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h2>3. Teste de Conexão com Banco de Dados</h2>";
if (defined('DB_HOST')) {
    try {
        require_once __DIR__ . '/config/database.php';
        $db = getDb();
        $start = microtime(true);
        $time = number_format(microtime(true) - $start, 4);
        
        echo "<span class='success'>✅ Conexão estabelecida com sucesso! ({$time}s)</span><br>";
        
        // Test query com prefixo
        $tUsuarios = tableName('usuarios');
        $stmt = $db->query("SELECT COUNT(*) FROM {$tUsuarios}");
        $count = $stmt->fetchColumn();
        echo "Teste de leitura (Tabela {$tUsuarios}): <strong>$count registros encontrados</strong>.<br>";
        
        $stmt2 = $db->query("SELECT VERSION()");
        $version = $stmt2->fetchColumn();
        echo "Versão do MySQL: $version<br>";
        
    } catch (Exception $e) {
        echo "<span class='error'>❌ Falha: " . $e->getMessage() . "</span><br>";
    }
} else {
    echo "Aguardando carregamento das configurações...";
}
echo "</div>";

echo "</body></html>";
?>
