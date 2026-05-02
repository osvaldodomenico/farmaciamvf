<?php
/**
 * Arquivo de Configuração do Banco de Dados
 * Sistema Farmácia Popular
 */

// ===================================================================
// CARREGAMENTO DE VARIÁVEIS DE AMBIENTE
// ===================================================================

require_once __DIR__ . '/env.php';
loadEnv(__DIR__ . '/../.env');

// ===================================================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// ===================================================================

define('DB_HOST',     env('DB_HOST', '66.179.191.53'));
define('DB_NAME',     env('DB_NAME', 'projetos'));
define('DB_USER',     env('DB_USER', 'shiftworks'));
define('DB_PASS',     env('DB_PASS', 'Jesus7714@!'));
define('DB_CHARSET',  env('DB_CHARSET', 'utf8mb4'));
define('TABLE_PREFIX', env('TABLE_PREFIX', 'farmacia_'));

// ===================================================================
// CONFIGURAÇÕES DA APLICAÇÃO
// ===================================================================

define('APP_NAME',    env('APP_NAME', 'Farmácia Mão Amiga'));
define('APP_VERSION', '2.0.0');
define('APP_URL',     env('APP_URL', 'http://localhost:8000'));

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// ===================================================================
// CONFIGURAÇÕES DE SESSÃO
// ===================================================================

define('SESSION_LIFETIME', 7200);    // 2 horas em segundos
define('SESSION_NAME', 'FARMACIA_SESSION');

// ===================================================================
// CONFIGURAÇÕES DE SEGURANÇA
// ===================================================================

define('PASSWORD_HASH_ALGO', PASSWORD_DEFAULT);
define('PASSWORD_COST', 12);

// ===================================================================
// CONFIGURAÇÕES DE UPLOAD
// ===================================================================

define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_MAX_SIZE', 5242880);  // 5MB em bytes
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png']);

// ===================================================================
// CONFIGURAÇÕES DE EMAIL (se aplicável)
// ===================================================================

define('SMTP_HOST',      env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT',      (int) env('SMTP_PORT', 587));
define('SMTP_USER',      env('SMTP_USER', ''));
define('SMTP_PASS',      env('SMTP_PASS', ''));
define('SMTP_FROM',      env('SMTP_FROM', ''));
define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', 'Farmácia Mão Amiga'));

// ===================================================================
// CLASSE DE CONEXÃO COM O BANCO DE DADOS
// ===================================================================

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->connection->exec("SET NAMES " . DB_CHARSET); // Executar após conexão ao invés de usar constante deprecated
            
        } catch (PDOException $e) {
            error_log("Erro na conexão com o banco de dados: " . $e->getMessage());
            
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                die("Erro na conexão: " . $e->getMessage() . "<br><br><b>Possíveis causas:</b><br>1. O arquivo .env não está sendo carregado corretamente.<br>2. As credenciais no .env estão incorretas.<br>3. O servidor de banco de dados (66.179.191.53) não permite conexões remotas do seu IP.<br>4. A extensão pdo_mysql não está ativa no seu Apache/PHP.");
            }
            
            die("Erro ao conectar com o banco de dados. Contate o administrador.");
        }
    }
    
    /**
     * Singleton - retorna a instância única da conexão
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Retorna a conexão PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Previne clonagem
     */
    private function __clone() {}
    
    /**
     * Previne deserialização
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// ===================================================================
// FUNÇÕES AUXILIARES DE BANCO DE DADOS
// ===================================================================

/**
 * Retorna a conexão com o banco de dados
 */
function getDb() {
    return Database::getInstance()->getConnection();
}

function tableName($base) {
    return TABLE_PREFIX . $base;
}

/**
 * Executa uma query preparada
 * 
 * @param string $sql Query SQL
 * @param array $params Parâmetros da query
 * @return PDOStatement
 */
function query($sql, $params = []) {
    $db = getDb();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Busca um único registro
 * 
 * @param string $sql Query SQL
 * @param array $params Parâmetros da query
 * @return array|false
 */
function fetchOne($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetch();
}

/**
 * Busca todos os registros
 * 
 * @param string $sql Query SQL
 * @param array $params Parâmetros da query
 * @return array
 */
function fetchAll($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Busca uma única coluna
 * 
 * @param string $sql Query SQL
 * @param array $params Parâmetros da query
 * @return mixed
 */
function fetchColumn($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetchColumn();
}

/**
 * Executa uma query (alias para query)
 * Útil para INSERT, UPDATE, DELETE
 * 
 * @param string $sql Query SQL
 * @param array $params Parâmetros da query
 * @return PDOStatement
 */
function execute($sql, $params = []) {
    return query($sql, $params);
}

/**
 * Insere um registro e retorna o ID
 * 
 * @param string $table Nome da tabela
 * @param array $data Dados a inserir (coluna => valor)
 * @return int ID inserido
 */
function insert($table, $data) {
    $db = getDb();
    
    $columns = array_keys($data);
    $values = array_values($data);
    
    $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") 
            VALUES (" . implode(', ', array_fill(0, count($values), '?')) . ")";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($values);
    
    return $db->lastInsertId();
}

/**
 * Atualiza registros
 * 
 * @param string $table Nome da tabela
 * @param array $data Dados a atualizar (coluna => valor)
 * @param string $where Condição WHERE
 * @param array $whereParams Parâmetros da condição WHERE
 * @return int Número de linhas afetadas
 */
function update($table, $data, $where, $whereParams = []) {
    $db = getDb();
    
    $set = [];
    $values = [];
    
    foreach ($data as $column => $value) {
        $set[] = "{$column} = ?";
        $values[] = $value;
    }
    
    $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$where}";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge($values, $whereParams));
    
    return $stmt->rowCount();
}

/**
 * Deleta registros
 * 
 * @param string $table Nome da tabela
 * @param string $where Condição WHERE
 * @param array $params Parâmetros da condição WHERE
 * @return int Número de linhas afetadas
 */
function delete($table, $where, $params = []) {
    $db = getDb();
    $sql = "DELETE FROM {$table} WHERE {$where}";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Inicia uma transação
 */
function beginTransaction() {
    return getDb()->beginTransaction();
}

/**
 * Confirma uma transação
 */
function commit() {
    return getDb()->commit();
}

/**
 * Reverte uma transação
 */
function rollback() {
    return getDb()->rollBack();
}

/**
 * Verifica se está em uma transação
 */
function inTransaction() {
    return getDb()->inTransaction();
}

// ===================================================================
// FUNÇÕES DE LOG E AUDITORIA
// ===================================================================

/**
 * Registra um log no sistema
 * 
 * @param int $usuario_id ID do usuário
 * @param string $acao Ação realizada
 * @param string $tabela Tabela afetada
 * @param int $registro_id ID do registro
 * @param array $dados_anteriores Dados antes da alteração
 * @param array $dados_novos Dados após a alteração
 */
function registrarLog($usuario_id, $acao, $tabela = null, $registro_id = null, 
                      $dados_anteriores = null, $dados_novos = null) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        insert(tableName('logs_sistema'), [
            'usuario_id' => $usuario_id,
            'acao' => $acao,
            'tabela' => $tabela,
            'registro_id' => $registro_id,
            'dados_anteriores' => $dados_anteriores ? json_encode($dados_anteriores) : null,
            'dados_novos' => $dados_novos ? json_encode($dados_novos) : null,
            'ip_address' => $ip,
            'user_agent' => $user_agent
        ]);
    } catch (Exception $e) {
        error_log("Erro ao registrar log: " . $e->getMessage());
    }
}

// ===================================================================
// FUNÇÕES DE VALIDAÇÃO
// ===================================================================

/**
 * Valida CPF
 */
function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) != 11) {
        return false;
    }
    
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    
    return true;
}

/**
 * Valida CNPJ
 */
function validarCNPJ($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    
    if (strlen($cnpj) != 14) {
        return false;
    }
    
    if (preg_match('/(\d)\1{13}/', $cnpj)) {
        return false;
    }
    
    $tamanho = strlen($cnpj) - 2;
    $numeros = substr($cnpj, 0, $tamanho);
    $digitos = substr($cnpj, $tamanho);
    $soma = 0;
    $pos = $tamanho - 7;
    
    for ($i = $tamanho; $i >= 1; $i--) {
        $soma += $numeros[$tamanho - $i] * $pos--;
        if ($pos < 2) {
            $pos = 9;
        }
    }
    
    $resultado = $soma % 11 < 2 ? 0 : 11 - $soma % 11;
    
    if ($resultado != $digitos[0]) {
        return false;
    }
    
    $tamanho = $tamanho + 1;
    $numeros = substr($cnpj, 0, $tamanho);
    $soma = 0;
    $pos = $tamanho - 7;
    
    for ($i = $tamanho; $i >= 1; $i--) {
        $soma += $numeros[$tamanho - $i] * $pos--;
        if ($pos < 2) {
            $pos = 9;
        }
    }
    
    $resultado = $soma % 11 < 2 ? 0 : 11 - $soma % 11;
    
    if ($resultado != $digitos[1]) {
        return false;
    }
    
    return true;
}

/**
 * Formata valor monetário
 */
function formatarMoeda($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/**
 * Formata data para exibição
 */
function formatarData($data, $formato = 'd/m/Y') {
    if (empty($data)) return '';
    $dt = new DateTime($data);
    return $dt->format($formato);
}

/**
 * Formata data e hora para exibição
 */
function formatarDataHora($data, $formato = 'd/m/Y H:i:s') {
    if (empty($data)) return '';
    $dt = new DateTime($data);
    return $dt->format($formato);
}

// ===================================================================
// AUTOLOAD DE CLASSES (se usar orientação a objetos)
// ===================================================================

spl_autoload_register(function ($class) {
    $directories = [
        __DIR__ . '/src/models/',
        __DIR__ . '/src/controllers/',
        __DIR__ . '/src/services/',
        __DIR__ . '/src/helpers/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ===================================================================
// MODO DE DESENVOLVIMENTO/PRODUÇÃO
// ===================================================================

define('ENVIRONMENT', 'production'); // 'development' ou 'production'

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php-errors.log');
}
