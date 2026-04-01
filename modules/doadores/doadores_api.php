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
    $doadores = fetchAll("
        SELECT 
            d.*,
            (SELECT COUNT(*) FROM " . tableName('doacoes') . " WHERE doador_id = d.id) as total_doacoes,
            (SELECT MAX(data_recebimento) FROM " . tableName('doacoes') . " WHERE doador_id = d.id) as ultima_doacao
        FROM " . tableName('doadores') . " d
        WHERE d.ativo = 1
        ORDER BY d.nome_completo ASC
    ");
    
    echo json_encode(['data' => $doadores]);
}

function handleGet() {
    $id = $_GET['id'] ?? 0;
    
    $doador = fetchOne("
        SELECT * FROM " . tableName('doadores') . " WHERE id = ?
    ", [$id]);
    
    if (!$doador) {
        http_response_code(404);
        echo json_encode(['error' => 'Doador não encontrado']);
        return;
    }
    
    // Buscar histórico de doações
    $doacoes = fetchAll("
        SELECT d.*, u.nome_completo as atendente
        FROM " . tableName('doacoes') . " d
        LEFT JOIN " . tableName('usuarios') . " u ON d.usuario_id = u.id
        WHERE d.doador_id = ?
        ORDER BY d.data_recebimento DESC
        LIMIT 10
    ", [$id]);
    
    $doador['doacoes'] = $doacoes;
    
    echo json_encode($doador);
}

function handleSearch() {
    $termo = $_GET['termo'] ?? '';
    
    if (strlen($termo) < 2) {
        echo json_encode([]);
        return;
    }
    
    $doadores = fetchAll("
        SELECT id, nome_completo, cpf_cnpj, tipo, telefone
        FROM " . tableName('doadores') . "
        WHERE ativo = 1
        AND (
            nome_completo LIKE ? OR
            cpf_cnpj LIKE ?
        )
        ORDER BY nome_completo ASC
        LIMIT 20
    ", ["%$termo%", "%$termo%"]);
    
    echo json_encode($doadores);
}

function handleToggleStatus() {
    global $usuario_nivel;
    
    if (!in_array($usuario_nivel ?? '', ['admin', 'gerente'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Sem permissão']);
        return;
    }
    
    $id = $_POST['id'] ?? 0;
    $ativo = $_POST['ativo'] ?? 0;
    
    execute("UPDATE " . tableName('doadores') . " SET ativo = ? WHERE id = ?", [$ativo, $id]);
    
    registrarLog(
        $_SESSION['usuario_id'],
        $ativo ? 'ativou_doador' : 'desativou_doador',
        'doadores',
        $id,
        ['id' => $id, 'ativo' => $ativo]
    );
    
    echo json_encode(['success' => true]);
}

function handleEstatisticas() {
    $stats = [
        'total' => (int) fetchColumn("SELECT COUNT(*) FROM " . tableName('doadores') . " WHERE ativo = 1"),
        'pessoas_fisicas' => (int) fetchColumn("SELECT COUNT(*) FROM " . tableName('doadores') . " WHERE ativo = 1 AND tipo = 'pessoa_fisica'"),
        'pessoas_juridicas' => (int) fetchColumn("SELECT COUNT(*) FROM " . tableName('doadores') . " WHERE ativo = 1 AND tipo = 'pessoa_juridica'"),
        'doacoes_mes' => (int) fetchColumn("
            SELECT COUNT(*) FROM " . tableName('doacoes') . " 
            WHERE MONTH(data_recebimento) = MONTH(CURDATE())
            AND YEAR(data_recebimento) = YEAR(CURDATE())
        ")
    ];
    
    error_log("Doadores Stats: " . json_encode($stats));
    
    echo json_encode($stats);
}
?>
