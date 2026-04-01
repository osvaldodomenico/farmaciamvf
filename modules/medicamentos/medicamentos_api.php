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

header('Content-Type: application/json; charset=utf-8');

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
        case 'check_codigo':
            handleCheckCodigo();
            break;
        case 'toggle_status':
            handleToggleStatus();
            break;
        case 'estatisticas':
            handleEstatisticas();
            break;
        case 'search':
            handleSearch();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ação inválida']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleSearch() {
    $term = $_GET['q'] ?? '';
    $com_estoque = isset($_GET['com_estoque']);
    
    $sql = "
        SELECT DISTINCT m.id, m.nome, m.principio_ativo, m.dosagem_concentracao, m.unidade_medida, m.controlado, m.codigo_barras,
               COALESCE(SUM(e.quantidade_atual), 0) as estoque_total
        FROM " . tableName('medicamentos') . " m
        LEFT JOIN " . tableName('estoque') . " e ON m.id = e.medicamento_id AND e.quantidade_atual > 0 AND e.data_validade >= CURDATE()
        WHERE m.ativo = 1
        AND (m.nome LIKE ? OR m.principio_ativo LIKE ? OR m.codigo_barras LIKE ?)
    ";
    
    $params = ["%$term%", "%$term%", "%$term%"];
    
    $sql .= " GROUP BY m.id";
    
    if ($com_estoque) {
        $sql .= " HAVING estoque_total > 0";
    }
    
    $sql .= " ORDER BY m.nome LIMIT 50";
    
    $medicamentos = fetchAll($sql, $params);
    echo json_encode(['results' => $medicamentos]);
}

function handleList() {
    $medicamentos = fetchAll("
        SELECT 
            m.*,
            COALESCE(SUM(e.quantidade_atual), 0) as estoque_total
        FROM " . tableName('medicamentos') . " m
        LEFT JOIN " . tableName('estoque') . " e ON m.id = e.medicamento_id
        WHERE m.ativo = 1
        GROUP BY m.id
        ORDER BY m.nome ASC
    ");
    
    echo json_encode(['data' => $medicamentos]);
}

function handleGet() {
    $id = $_GET['id'] ?? 0;
    
    $medicamento = fetchOne("
        SELECT 
            m.*,
            COALESCE(SUM(e.quantidade_atual), 0) as estoque_total
        FROM " . tableName('medicamentos') . " m
        LEFT JOIN " . tableName('estoque') . " e ON m.id = e.medicamento_id
        WHERE m.id = ?
        GROUP BY m.id
    ", [$id]);
    
    if (!$medicamento) {
        http_response_code(404);
        echo json_encode(['error' => 'Medicamento não encontrado']);
        return;
    }
    
    // Buscar lotes do estoque
    $lotes = fetchAll("
        SELECT * FROM " . tableName('estoque') . " 
        WHERE medicamento_id = ? AND quantidade_atual > 0
        ORDER BY data_validade ASC
    ", [$id]);
    
    $medicamento['lotes'] = $lotes;
    
    echo json_encode($medicamento);
}

function handleCheckCodigo() {
    $codigo = $_GET['codigo'] ?? '';
    $id = $_GET['id'] ?? 0;
    
    if (empty($codigo)) {
        echo json_encode(['disponivel' => true]);
        return;
    }
    
    $sql = "SELECT id FROM " . tableName('medicamentos') . " WHERE codigo_barras = ?";
    $params = [$codigo];
    
    if ($id > 0) {
        $sql .= " AND id != ?";
        $params[] = $id;
    }
    
    $existe = fetchOne($sql, $params);
    echo json_encode(['disponivel' => !$existe]);
}

function handleToggleStatus() {
    global $usuario_nivel;
    
    if (!in_array($usuario_nivel, ['admin', 'gerente'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Sem permissão']);
        return;
    }
    
    $id = $_POST['id'] ?? 0;
    $ativo = $_POST['ativo'] ?? 0;
    
    execute("UPDATE " . tableName('medicamentos') . " SET ativo = ? WHERE id = ?", [$ativo, $id]);
    
    registrarLog(
        $_SESSION['usuario_id'],
        $ativo ? 'ativou_medicamento' : 'desativou_medicamento',
        'medicamentos',
        $id
    );
    
    echo json_encode(['success' => true]);
}

function handleEstatisticas() {
    $stats = [
        'total' => (int) fetchColumn("SELECT COUNT(*) FROM " . tableName('medicamentos') . " WHERE ativo = 1"),
        'controlados' => (int) fetchColumn("SELECT COUNT(*) FROM " . tableName('medicamentos') . " WHERE ativo = 1 AND controlado = 1"),
        'refrigerado' => (int) fetchColumn("SELECT COUNT(*) FROM " . tableName('medicamentos') . " WHERE ativo = 1 AND temperatura_armazenamento = 'refrigerado'"),
        'estoque_critico' => (int) fetchColumn("
            SELECT COUNT(*) FROM (
                SELECT m.id, COALESCE(SUM(e.quantidade_atual), 0) as total, m.estoque_minimo
                FROM " . tableName('medicamentos') . " m
                LEFT JOIN " . tableName('estoque') . " e ON m.id = e.medicamento_id AND e.quantidade_atual > 0
                WHERE m.ativo = 1
                GROUP BY m.id, m.estoque_minimo
                HAVING total <= estoque_minimo
            ) as subquery
        ")
    ];
    
    error_log("Medicamentos Stats: " . json_encode($stats));
    
    echo json_encode($stats);
}
?>
