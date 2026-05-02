<?php
// config/database.php

$host = '66.179.191.53'; // Ou o endereço do seu servidor de banco de dados
$db_name = 'projetos'; // O nome do banco que você criou
$username = 'shiftworks'; // Seu usuário do MySQL/MariaDB
$password = 'Jesus7714@!';   // Sua senha do MySQL/MariaDB
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lança exceções em caso de erro
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retorna arrays associativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa prepared statements nativos
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // Em um ambiente de produção, você não exibiria o erro detalhado para o usuário.
    // Você o registraria em um arquivo de log e mostraria uma mensagem genérica.
    error_log("Erro de conexão com o banco de dados: " . $e->getMessage(), 0);
    // Para desenvolvimento, pode ser útil ver o erro:
    // die("Erro de conexão: " . $e->getMessage());
    die("Não foi possível conectar ao banco de dados. Verifique as configurações e tente novamente. Se o problema persistir, contate o administrador.");
}

// O objeto $pdo estará disponível para ser usado nos scripts que incluírem este arquivo.
// Exemplo: require_once __DIR__ . '/../config/database.php';
?>