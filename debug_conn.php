<?php
/**
 * Script de Diagnóstico de Conexão - Farmácia Popular
 * Use este arquivo para identificar o motivo real da falha na conexão com o banco de dados.
 * ATENÇÃO: EXCLUA ESTE ARQUIVO APÓS O TESTE POR SEGURANÇA.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO DE CONEXÃO ===\n\n";

// 1. Informações básicas
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n";
echo "Versão PHP: " . phpversion() . "\n";
echo "Sistema Operacional: " . PHP_OS . "\n";
echo "Caminho do Script: " . __FILE__ . "\n";
echo "Caminho Raiz: " . realpath(__DIR__) . "\n";

echo "\n--- EXTENSÕES ---\n";
$extensions = ['pdo', 'pdo_mysql', 'mysqli'];
foreach ($extensions as $ext) {
    echo "Extensão '$ext': " . (extension_loaded($ext) ? "INSTALADA" : "NÃO ENCONTRADA") . "\n";
}

echo "\n--- VARIÁVEIS DE AMBIENTE (.env) ---\n";
$env_path = __DIR__ . '/.env';
if (!file_exists($env_path)) {
    echo "ERRO: Arquivo .env não encontrado em: $env_path\n";
} else {
    echo "Arquivo .env encontrado.\n";
    echo "Arquivo legível: " . (is_readable($env_path) ? "SIM" : "NÃO") . "\n";
    
    // Testar carregador
    require_once __DIR__ . '/config/env.php';
    loadEnv($env_path);
    
    $vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
    foreach ($vars as $v) {
        $val = getenv($v);
        if ($val === false) {
            echo "$v: NÃO DEFINIDO\n";
        } else {
            // Ofuscar senha
            if ($v === 'DB_PASS') {
                echo "$v: " . substr($val, 0, 2) . "****" . substr($val, -2) . " (Tamanho: " . strlen($val) . ")\n";
            } else {
                echo "$v: $val\n";
            }
        }
    }
}

echo "\n--- TESTE DE CONEXÃO PDO ---\n";
try {
    $host = getenv('DB_HOST') ?: 'localhost';
    $name = getenv('DB_NAME') ?: 'farmacia';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    
    $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
    echo "Tentando conectar ao DSN: mysql:host=$host;dbname=$name\n";
    
    $start = microtime(true);
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5 // Timeout curto para teste
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    $end = microtime(true);
    
    echo "SUCESSO: Conexão estabelecida em " . round(($end - $start) * 1000, 2) . " ms.\n";
    
    // Testar se as tabelas principais existem
    $prefix = getenv('TABLE_PREFIX') ?: 'farmacia_';
    $table = $prefix . 'usuarios';
    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
    if ($stmt->fetch()) {
        echo "Tabela '$table': OK\n";
    } else {
        echo "AVISO: Tabela '$table' não encontrada no banco '$name'.\n";
    }

} catch (PDOException $e) {
    echo "FALHA NA CONEXÃO:\n";
    echo "Código: " . $e->getCode() . "\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    
    if (strpos($e->getMessage(), 'Connection timed out') !== false) {
        echo "\nDICA: O servidor remoto está demorando muito para responder. Verifique se o IP $host está correto e se o firewall do servidor permite conexões do IP de saída do seu servidor.\n";
    } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "\nDICA: O usuário ou senha estão incorretos para este banco de dados.\n";
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "\nDICA: O nome do banco de dados '$name' não existe no servidor.\n";
    }
}

echo "\n=== FIM DO DIAGNÓSTICO ===\n";
