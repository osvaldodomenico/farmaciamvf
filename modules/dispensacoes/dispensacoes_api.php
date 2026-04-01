<?php
// Ensure no output before JSON
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    ob_clean(); // Ensure clean output
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            handleList();
            break;
        case 'get':
            handleGet();
            break;
        case 'get_itens':
            handleGetItens();
            break;
        case 'gerar_numero':
            handleGerarNumero();
            break;
        case 'gerar_numero_receita':
            handleGerarNumeroReceita();
            break;
        case 'buscar_estoque':
            handleBuscarEstoque();
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

// Helper para garantir JSON limpo
function outputJson($data) {
    // Limpar qualquer saída anterior (warnings, notices, whitespace)
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Iniciar novo buffer para garantir pureza
    ob_start();
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
    
    // Enviar buffer e encerrar
    ob_end_flush();
    exit;
}

function handleList() {
    try {
        $query = "
            SELECT 
                d.*,
                p.nome_completo as cliente_nome,
                u.nome_completo as usuario_nome,
                (SELECT COUNT(*) FROM " . tableName('dispensacoes_itens') . " WHERE dispensacao_id = d.id) as total_itens
            FROM " . tableName('dispensacoes') . " d
            LEFT JOIN " . tableName('clientes') . " p ON d.cliente_id = p.id
            LEFT JOIN " . tableName('usuarios') . " u ON d.usuario_id = u.id
            ORDER BY d.data_dispensacao DESC
        ";

        $dispensacoes = fetchAll($query);

        outputJson(['data' => $dispensacoes]);

    } catch (Exception $e) {
        http_response_code(500);
        outputJson(['error' => $e->getMessage()]);
    }
}

function handleGet() {
    $id = $_GET['id'] ?? 0;
    
    $dispensacao = fetchOne("
        SELECT 
            d.*,
            p.nome_completo as cliente_nome,
            p.cpf as cliente_cpf,
            u.nome_completo as usuario_nome
        FROM " . tableName('dispensacoes') . " d
        LEFT JOIN " . tableName('clientes') . " p ON d.cliente_id = p.id
        LEFT JOIN " . tableName('usuarios') . " u ON d.usuario_id = u.id
        WHERE d.id = ?
    ", [$id]);
    
    if (!$dispensacao) {
        http_response_code(404);
        echo json_encode(['error' => 'Dispensação não encontrada']);
        return;
    }
    
    // Buscar itens
    $itens = fetchAll("
        SELECT 
            di.*,
            m.nome as medicamento_nome,
            m.principio_ativo,
            e.lote,
            e.data_validade
        FROM " . tableName('dispensacoes_itens') . " di
        LEFT JOIN " . tableName('medicamentos') . " m ON di.medicamento_id = m.id
        LEFT JOIN " . tableName('estoque') . " e ON di.estoque_id = e.id
        WHERE di.dispensacao_id = ?
    ", [$id]);
    
    $dispensacao['itens'] = $itens;
    
    echo json_encode($dispensacao);
}

function handleGetItens() {
    $id = $_GET['id'] ?? 0;
    
    $itens = fetchAll("
        SELECT 
            di.*,
            m.nome as medicamento_nome,
            m.principio_ativo,
            m.dosagem_concentracao,
            m.forma_farmaceutica,
            e.lote,
            e.data_validade
        FROM " . tableName('dispensacoes_itens') . " di
        LEFT JOIN " . tableName('medicamentos') . " m ON di.medicamento_id = m.id
        LEFT JOIN " . tableName('estoque') . " e ON di.estoque_id = e.id
        WHERE di.dispensacao_id = ?
    ", [$id]);
    
    echo json_encode($itens);
}

function handleGerarNumero() {
    $data = date('Ymd');
    $count = fetchColumn("SELECT COUNT(*) + 1 FROM " . tableName('dispensacoes') . " WHERE DATE(data_dispensacao) = CURDATE()");
    $numero = 'DISP-' . $data . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    
    echo json_encode(['numero' => $numero]);
}

function handleGerarNumeroReceita() {
    $cliente_id = (int)($_GET['cliente_id'] ?? 0);
    $data = $_GET['data'] ?? date('Y-m-d H:i:s');
    $diaMesAno = date('dmY', strtotime($data));
    
    if ($cliente_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Cliente inválido']);
        return;
    }
    
    $seq = fetchColumn("
        SELECT COUNT(*) + 1 
        FROM " . tableName('dispensacoes') . " 
        WHERE cliente_id = ? 
          AND DATE(data_dispensacao) = DATE(?) 
          AND numero_receita IS NOT NULL
    ", [$cliente_id, $data]);
    
    $numero = 'RM-' . $cliente_id . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT) . '-' . $diaMesAno;
    echo json_encode(['numero' => $numero]);
}

function handleBuscarEstoque() {
    $medicamento_id = $_GET['medicamento_id'] ?? 0;
    
    $lotes = fetchAll("
        SELECT 
            e.id,
            e.lote,
            e.data_validade,
            e.quantidade_atual,
            m.nome as medicamento_nome
        FROM " . tableName('estoque') . " e
        LEFT JOIN " . tableName('medicamentos') . " m ON e.medicamento_id = m.id
        WHERE e.medicamento_id = ?
        AND e.quantidade_atual > 0
        AND e.data_validade >= CURDATE()
        ORDER BY e.data_validade ASC
    ", [$medicamento_id]);
    
    echo json_encode($lotes);
}

function handleEstatisticas() {
    $startOfMonth = date('Y-m-01 00:00:00');
    $endOfMonth = date('Y-m-t 23:59:59');
    $todayStart = date('Y-m-d 00:00:00');
    $todayEnd = date('Y-m-d 23:59:59');

    // Debug log
    $logFile = __DIR__ . '/api_debug.log';
    $logData = "Time: " . date('Y-m-d H:i:s') . "\n";

    try {
        $total = fetchColumn("SELECT COUNT(*) FROM " . tableName('dispensacoes'));
        $mes = fetchColumn("SELECT COUNT(*) FROM " . tableName('dispensacoes') . " WHERE data_dispensacao BETWEEN ? AND ?", [$startOfMonth, $endOfMonth]);
        $hoje = fetchColumn("SELECT COUNT(*) FROM " . tableName('dispensacoes') . " WHERE data_dispensacao BETWEEN ? AND ?", [$todayStart, $todayEnd]);
        
        $itens = fetchColumn("
            SELECT COALESCE(SUM(di.quantidade), 0)
            FROM " . tableName('dispensacoes_itens') . " di
            INNER JOIN " . tableName('dispensacoes') . " d ON di.dispensacao_id = d.id
            WHERE d.data_dispensacao BETWEEN ? AND ?
        ", [$startOfMonth, $endOfMonth]);
        
        $clientes = fetchColumn("
            SELECT COUNT(DISTINCT cliente_id) FROM " . tableName('dispensacoes') . "
            WHERE data_dispensacao BETWEEN ? AND ?
        ", [$startOfMonth, $endOfMonth]);

        $stats = [
            'total_dispensacoes' => (int) $total,
            'dispensacoes_mes' => (int) $mes,
            'dispensacoes_hoje' => (int) $hoje,
            'itens_dispensados_mes' => (int) $itens,
            'clientes_atendidos_mes' => (int) $clientes
        ];
        
        $logData .= "Stats: " . json_encode($stats) . "\n";
        file_put_contents($logFile, $logData, FILE_APPEND);
        
        // Clear buffer ensuring no noise
        if (ob_get_length()) ob_clean();
        
        echo json_encode($stats);

    } catch (Exception $e) {
        $logData .= "Error: " . $e->getMessage() . "\n";
        file_put_contents($logFile, $logData, FILE_APPEND);
        throw $e;
    }
}
?>
