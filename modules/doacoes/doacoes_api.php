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
    ob_clean();
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
        case 'estatisticas':
            handleEstatisticas();
            break;
        case 'listar_pendentes':
            handleListarPendentes();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ação inválida']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// Função para garantir saída JSON limpa
function outputJson($data) {
    ob_clean(); // Limpa qualquer buffer anterior (avisos, HTML, etc)
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function handleList() {
    try {
        $tDoacoes = tableName('doacoes');
        $tDoacoesItens = tableName('doacoes_itens');
        $tDoadores = tableName('doadores');
        $tUsuarios = tableName('usuarios');
        $doacoes = fetchAll("
            SELECT 
                d.*,
                do.nome_completo as doador_nome,
                do.tipo as doador_tipo,
                u.nome_completo as usuario_nome,
                (SELECT COUNT(*) FROM {$tDoacoesItens} WHERE doacao_id = d.id) as total_itens,
                (SELECT COALESCE(SUM(quantidade), 0) FROM {$tDoacoesItens} WHERE doacao_id = d.id) as quantidade_total
            FROM {$tDoacoes} d
            LEFT JOIN {$tDoadores} do ON d.doador_id = do.id
            LEFT JOIN {$tUsuarios} u ON d.usuario_id = u.id
            ORDER BY d.data_recebimento DESC
        ");
        
        outputJson(['data' => $doacoes]);
    } catch (Exception $e) {
        http_response_code(500);
        outputJson(['error' => $e->getMessage()]);
    }
}

function handleGet() {
    $id = $_GET['id'] ?? 0;
    
    $tDoacoes = tableName('doacoes');
    $tDoadores = tableName('doadores');
    $tUsuarios = tableName('usuarios');
    $doacao = fetchOne("
        SELECT 
            d.*,
            do.nome_completo as doador_nome,
            u.nome_completo as usuario_nome
        FROM {$tDoacoes} d
        LEFT JOIN {$tDoadores} do ON d.doador_id = do.id
        LEFT JOIN {$tUsuarios} u ON d.usuario_id = u.id
        WHERE d.id = ?
    ", [$id]);
    
    if (!$doacao) {
        http_response_code(404);
        echo json_encode(['error' => 'Doação não encontrada']);
        return;
    }
    
    // Buscar itens da doação
    $tDoacoesItens = tableName('doacoes_itens');
    $tMedicamentos = tableName('medicamentos');
    $itens = fetchAll("
        SELECT 
            di.*,
            m.nome as medicamento_nome,
            m.principio_ativo
        FROM {$tDoacoesItens} di
        LEFT JOIN {$tMedicamentos} m ON di.medicamento_id = m.id
        WHERE di.doacao_id = ?
    ", [$id]);
    
    $doacao['itens'] = $itens;
    
    echo json_encode($doacao);
}

function handleGetItens() {
    $id = $_GET['id'] ?? 0;
    
    $tDoacoesItens = tableName('doacoes_itens');
    $tMedicamentos = tableName('medicamentos');
    $itens = fetchAll("
        SELECT 
            di.*,
            m.nome as medicamento_nome,
            m.principio_ativo,
            m.concentracao,
            m.forma_farmaceutica
        FROM {$tDoacoesItens} di
        LEFT JOIN {$tMedicamentos} m ON di.medicamento_id = m.id
        WHERE di.doacao_id = ?
    ", [$id]);
    
    echo json_encode($itens);
}

function handleGerarNumero() {
    $data = date('Ymd');
    $tDoacoes = tableName('doacoes');
    $count = fetchColumn("SELECT COUNT(*) + 1 FROM {$tDoacoes} WHERE DATE(data_recebimento) = CURDATE()");
    $numero = 'DOA-' . $data . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    
    echo json_encode(['numero' => $numero]);
}

function handleEstatisticas() {
    $tDoacoes = tableName('doacoes');
    $tDoacoesItens = tableName('doacoes_itens');
    $stats = [
        'total_doacoes' => (int) fetchColumn("SELECT COUNT(*) FROM {$tDoacoes}"),
        'doacoes_mes' => (int) fetchColumn("
            SELECT COUNT(*) FROM {$tDoacoes} 
            WHERE MONTH(data_recebimento) = MONTH(CURDATE())
            AND YEAR(data_recebimento) = YEAR(CURDATE())
        "),
        'itens_recebidos_mes' => (int) fetchColumn("
            SELECT COALESCE(SUM(di.quantidade), 0)
            FROM {$tDoacoesItens} di
            INNER JOIN {$tDoacoes} d ON di.doacao_id = d.id
            WHERE MONTH(d.data_recebimento) = MONTH(CURDATE())
            AND YEAR(d.data_recebimento) = YEAR(CURDATE())
        "),
        'valor_estimado_mes' => (float) fetchColumn("
            SELECT COALESCE(SUM(valor_estimado), 0) FROM {$tDoacoes} 
            WHERE MONTH(data_recebimento) = MONTH(CURDATE())
            AND YEAR(data_recebimento) = YEAR(CURDATE())
        ")
    ];
    
    error_log("Doacoes Stats: " . json_encode($stats));
    
    echo json_encode($stats);
}
function handleListarPendentes() {
    try {
        $tDoacoes = tableName('doacoes');
        $tDoadores = tableName('doadores');
        
        $doacoes = fetchAll("
            SELECT 
                d.id,
                d.numero_doacao,
                d.data_recebimento as data_doacao,
                do.nome_completo as doador_nome
            FROM {$tDoacoes} d
            LEFT JOIN {$tDoadores} do ON d.doador_id = do.id
            ORDER BY d.data_recebimento DESC
            LIMIT 50
        ");
        
        echo json_encode($doacoes);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
