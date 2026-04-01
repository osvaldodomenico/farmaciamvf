<?php
// src/Models/Database.php

class Database {
    private static $host = '66.179.191.53'; // Definido em config/database.php, mas pode ser centralizado aqui
    private static $db_name = 'shiftworks'; // Definido em config/database.php
    private static $username = 'root'; // Definido em config/database.php
    private static $password = 'Jesus7714@!2469'; // Definido em config/database.php
    private static $table_prefix = 'farmacia_';
    private static $conn;

    public static function getConnection() {
        // Se já houver uma conexão, retorne-a
        if (self::$conn !== null) {
            return self::$conn;
        }

        // Carregar configurações do arquivo config/database.php
        // Isso é uma forma, outra seria passar os valores diretamente ou usar constantes
        // Para simplificar, vamos assumir que as constantes DB_HOST, etc. estão definidas
        // require_once __DIR__ . '/../../config/database.php'; // Cuidado com múltiplos includes

        // Se você não quiser usar as constantes de config/database.php diretamente aqui,
        // pode definir os valores diretamente nas propriedades estáticas acima.
        // Por ora, vamos usar os valores definidos aqui para evitar dependência de include no construtor.

        try {
            self::$conn = new PDO(
                "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=utf8mb4",
                self::$username,
                self::$password
            );
            self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Em produção, logue o erro e mostre uma mensagem genérica.
            error_log("Erro de conexão com o banco de dados: " . $e->getMessage());
            die("Erro ao conectar com o banco de dados. Por favor, tente mais tarde.");
        }
        return self::$conn;
    }

    public static function getTablePrefix() {
        return self::$table_prefix;
    }

    public static function tableName($base) {
        return self::$table_prefix . $base;
    }

    public static function validatePrefix($expected = 'farmacia_') {
        if (self::$table_prefix !== $expected) {
            return false;
        }
        $conn = self::getConnection();
        try {
            $stmt = $conn->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :schema AND table_name = :table');
            $stmt->bindValue(':schema', self::$db_name);
            $stmt->bindValue(':table', self::$table_prefix . 'medicamentos');
            $stmt->execute();
            $exists = (int)$stmt->fetchColumn();
            return $exists > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
?>
