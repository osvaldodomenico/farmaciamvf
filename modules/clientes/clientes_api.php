<?php
session_start();
ob_start();
require_once __DIR__ . '/../../config/database.php';

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            handleList();
            break;
        case 'get':
            handleGet();
            break;
        case 'search':
            handleSearch();
            break;
        case 'toggle_status':
            handleToggleStatus();
            break;
        case 'add':
            handleAdd();
            break;
        case 'estatisticas':
            handleEstatisticas();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ação inválida']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleList() {
    $tClientes = tableName('clientes');
    $tDispensacoes = tableName('dispensacoes');
    $clientes = fetchAll("
        SELECT 
            p.*,
            (SELECT COUNT(*) FROM {$tDispensacoes} d WHERE d.cliente_id = p.id) as total_dispensacoes
        FROM {$tClientes} p
        WHERE p.ativo = 1
        ORDER BY p.nome_completo ASC
    ");
    
    echo json_encode(['data' => $clientes]);
}

function handleGet() {
    $id = $_GET['id'] ?? 0;
    
    $tClientes = tableName('clientes');
    $cliente = fetchOne("
        SELECT * FROM {$tClientes} WHERE id = ?
    ", [$id]);
    
    if (!$cliente) {
        http_response_code(404);
        echo json_encode(['error' => 'Cliente não encontrado']);
        return;
    }
    
    // Buscar últimas dispensações
    $tDispensacoes = tableName('dispensacoes');
    $tUsuarios = tableName('usuarios');
    $dispensacoes = fetchAll("
        SELECT d.*, u.nome_completo as atendente
        FROM {$tDispensacoes} d
        LEFT JOIN {$tUsuarios} u ON d.usuario_id = u.id
        WHERE d.cliente_id = ?
        ORDER BY d.data_dispensacao DESC
        LIMIT 10
    ", [$id]);
    
    $cliente['dispensacoes'] = $dispensacoes;
    
    echo json_encode($cliente);
}

function handleSearch() {
    $termo = $_GET['termo'] ?? $_GET['q'] ?? '';
    
    if (strlen($termo) < 2) {
        echo json_encode([]);
        return;
    }
    
    $tClientes = tableName('clientes');
    $clientes = fetchAll("
        SELECT id, nome_completo, cpf, telefone, celular
        FROM {$tClientes}
        WHERE ativo = 1
        AND (
            nome_completo LIKE ? OR
            cpf LIKE ?
        )
        ORDER BY nome_completo ASC
        LIMIT 20
    ", ["%$termo%", "%$termo%"]);
    
    echo json_encode($clientes);
}

function handleToggleStatus() {
    $nivel = $_SESSION['nivel_acesso'] ?? '';
    
    if (!in_array($nivel, ['admin', 'gerente'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Sem permissão']);
        return;
    }
    
    $id = $_POST['id'] ?? 0;
    $ativo = $_POST['ativo'] ?? 0;
    
    $tClientes = tableName('clientes');
    execute("UPDATE {$tClientes} SET ativo = ? WHERE id = ?", [$ativo, $id]);
    
    registrarLog(
        $_SESSION['usuario_id'],
        $ativo ? 'ATIVAR_CLIENTE' : 'DESATIVAR_CLIENTE',
        'clientes',
        $id
    );
    
    echo json_encode(['success' => true]);
}

function handleEstatisticas() {
    $tClientes = tableName('clientes');
    $tDispensacoes = tableName('dispensacoes');
    $stats = [
        'total' => (int) fetchColumn("SELECT COUNT(*) FROM {$tClientes} WHERE ativo = 1"),
        'novos_mes' => (int) fetchColumn("
            SELECT COUNT(*) FROM {$tClientes} 
            WHERE ativo = 1 
            AND MONTH(created_at) = MONTH(CURDATE())
            AND YEAR(created_at) = YEAR(CURDATE())
        "),
        'com_dispensacao' => (int) fetchColumn("
            SELECT COUNT(DISTINCT cliente_id) FROM {$tDispensacoes}
            WHERE MONTH(data_dispensacao) = MONTH(CURDATE())
            AND YEAR(data_dispensacao) = YEAR(CURDATE())
        ")
    ];
    
    error_log("Clientes Stats: " . json_encode($stats));
    
    echo json_encode($stats);
}

function handleAdd() {
    try {
        $nome_completo = $_POST['nome_completo'] ?? '';
        $data_nascimento = $_POST['data_nascimento'] ?? '';
        $sexo = $_POST['sexo'] ?? '';
        $celular = $_POST['celular'] ?? '';

        if (empty($nome_completo) || empty($data_nascimento) || empty($sexo) || empty($celular)) {
            echo json_encode(['error' => 'Nome completo, data de nascimento, sexo e celular são obrigatórios']);
            return;
        }

        $cpf = $_POST['cpf'] ?? null ?: null;
        if ($cpf) {
            $tClientes = tableName('clientes');
            $existe = fetchOne("SELECT id FROM {$tClientes} WHERE cpf = ?", [$cpf]);
            if ($existe) {
                echo json_encode(['error' => 'Já existe um cliente com este CPF']);
                return;
            }
        }

        $id = insert(tableName('clientes'), [
            'nome_completo' => $nome_completo,
            'cpf' => $cpf,
            'rg' => $_POST['rg'] ?? null ?: null,
            'data_nascimento' => $_POST['data_nascimento'] ?? null ?: null,
            'sexo' => $_POST['sexo'] ?? 'outro',
            'celular' => $_POST['celular'] ?? null ?: null,
            'email' => $_POST['email'] ?? null ?: null,
            'cep' => $_POST['cep'] ?? null ?: null,
            'logradouro' => $_POST['logradouro'] ?? null ?: null,
            'numero' => $_POST['numero'] ?? null ?: null,
            'complemento' => $_POST['complemento'] ?? null ?: null,
            'bairro' => $_POST['bairro'] ?? null ?: null,
            'cidade' => $_POST['cidade'] ?? null ?: null,
            'estado' => $_POST['estado'] ?? null ?: null,
            'latitude' => $_POST['latitude'] ?? null ?: null,
            'longitude' => $_POST['longitude'] ?? null ?: null,
            'observacoes' => $_POST['observacoes'] ?? null ?: null
        ]);
        
        registrarLog($_SESSION['usuario_id'], 'criou_cliente_api', 'clientes', $id, $nome_completo);
        
        echo json_encode([
            'success' => true, 
            'id' => $id, 
            'nome_completo' => $nome_completo,
            'cpf' => $cpf
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Erro ao cadastrar cliente: ' . $e->getMessage()]);
    }
}
?>
