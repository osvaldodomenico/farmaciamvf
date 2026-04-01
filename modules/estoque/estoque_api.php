<?php
session_start();
ob_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

if (!function_exists('handleList')) {
function handleList() {
    $estoque = fetchAll("
        SELECT 
            m.id as medicamento_id,
            m.nome,
            m.principio_ativo,
            m.dosagem_concentracao,
            m.unidade_medida,
            m.forma_farmaceutica,
            m.estoque_minimo,
            m.controlado,
            m.temperatura_armazenamento as refrigerado,
            COALESCE(SUM(e.quantidade_atual), 0) as quantidade_total,
            COUNT(e.id) as total_lotes,
            MIN(e.data_validade) as proxima_validade
        FROM " . tableName('medicamentos') . " m
        LEFT JOIN " . tableName('estoque') . " e ON m.id = e.medicamento_id AND e.quantidade_atual > 0
        WHERE m.ativo = 1
        GROUP BY m.id, m.nome, m.principio_ativo, m.dosagem_concentracao, m.unidade_medida, m.forma_farmaceutica, m.estoque_minimo, m.controlado, m.temperatura_armazenamento
    ");
    
    echo json_encode(['data' => $estoque]);
}

function handleGetLotes() {
    $medicamento_id = $_GET['medicamento_id'] ?? 0;
    
    $lotes = fetchAll("
        SELECT e.*, m.nome as medicamento_nome
        FROM " . tableName('estoque') . " e
        LEFT JOIN " . tableName('medicamentos') . " m ON e.medicamento_id = m.id
        WHERE e.medicamento_id = ?
        AND e.quantidade_atual > 0
        ORDER BY e.data_validade ASC
    ", [$medicamento_id]);
    
    echo json_encode($lotes);
}

function handleEstatisticas() {
    $stats = [
        'total_medicamentos' => (int) fetchColumn("SELECT COUNT(*) FROM " . tableName('medicamentos') . " WHERE ativo = 1"),
        'total_itens' => (int) fetchColumn("SELECT COALESCE(SUM(quantidade_atual), 0) FROM " . tableName('estoque') . " WHERE quantidade_atual > 0"),
        'total_lotes' => (int) fetchColumn("SELECT COUNT(*) FROM " . tableName('estoque') . " WHERE quantidade_atual > 0"),
        'vencidos' => (int) fetchColumn("
            SELECT COUNT(DISTINCT medicamento_id) FROM " . tableName('estoque') . " 
            WHERE quantidade_atual > 0 AND data_validade < CURDATE()
        "),
        'vencendo_30_dias' => (int) fetchColumn("
            SELECT COUNT(DISTINCT medicamento_id) FROM " . tableName('estoque') . " 
            WHERE quantidade_atual > 0 
            AND data_validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        "),
        'criticos' => (int) fetchColumn("
            SELECT COUNT(*) FROM (
                SELECT m.id, COALESCE(SUM(e.quantidade_atual), 0) as total_estoque, m.estoque_minimo
                FROM " . tableName('medicamentos') . " m
                LEFT JOIN " . tableName('estoque') . " e ON m.id = e.medicamento_id AND e.quantidade_atual > 0
                WHERE m.ativo = 1
                GROUP BY m.id, m.estoque_minimo
                HAVING total_estoque <= estoque_minimo
            ) as subquery
        ")
    ];
    
    // Debug log
    error_log("API Stats: " . json_encode($stats));
    
    echo json_encode($stats);
}

function handleProximosVencimento() {
    $dias = $_GET['dias'] ?? 30;
    
    $proximos = fetchAll("
        SELECT 
            e.*,
            m.nome as medicamento_nome,
            m.principio_ativo,
            DATEDIFF(e.data_validade, CURDATE()) as dias_para_vencer
        FROM " . tableName('estoque') . " e
        INNER JOIN " . tableName('medicamentos') . " m ON e.medicamento_id = m.id
        WHERE e.quantidade_atual > 0
        AND e.data_validade <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
        ORDER BY e.data_validade ASC
        LIMIT 50
    ", [$dias]);
    
    echo json_encode($proximos);
}

function handleCriticos() {
    $criticos = fetchAll("
        SELECT 
            m.id,
            m.nome,
            m.principio_ativo,
            m.estoque_minimo,
            COALESCE(SUM(e.quantidade_atual), 0) as quantidade_atual
        FROM " . tableName('medicamentos') . " m
        LEFT JOIN " . tableName('estoque') . " e ON m.id = e.medicamento_id AND e.quantidade_atual > 0
        WHERE m.ativo = 1
        GROUP BY m.id
        HAVING quantidade_atual <= m.estoque_minimo
        ORDER BY quantidade_atual ASC
        LIMIT 50
    ");
    
    echo json_encode($criticos);
}

function handleInfoMedicamento() {
    $medicamento_id = $_GET['medicamento_id'] ?? 0;
    
    $info = fetchOne("
        SELECT 
            m.*,
            COALESCE(SUM(e.quantidade_atual), 0) as quantidade_total,
            COUNT(e.id) as total_lotes,
            MIN(e.data_validade) as proxima_validade
        FROM " . tableName('medicamentos') . " m
        LEFT JOIN " . tableName('estoque') . " e ON m.id = e.medicamento_id AND e.quantidade_atual > 0
        WHERE m.id = ?
        GROUP BY m.id
    ", [$medicamento_id]);
    
    echo json_encode($info ?: ['error' => 'Medicamento não encontrado']);
}

function handleUltimasEntradas() {
    $entradas = fetchAll("
        SELECT 
            me.id,
            me.quantidade,
            me.data_movimentacao,
            DATE_FORMAT(me.data_movimentacao, '%d/%m/%Y %H:%i') as data_formatada,
            m.nome as medicamento_nome,
            e.lote
        FROM " . tableName('movimentacoes_estoque') . " me
        INNER JOIN " . tableName('medicamentos') . " m ON me.medicamento_id = m.id
        LEFT JOIN " . tableName('estoque') . " e ON me.estoque_id = e.id
        WHERE me.tipo_movimentacao = 'entrada'
        ORDER BY me.data_movimentacao DESC
        LIMIT 20
    ");
    
    echo json_encode($entradas);
}

function handleEntrada() {
    $medicamento_id = intval($_POST['medicamento_id'] ?? 0);
    $quantidade = intval($_POST['quantidade'] ?? 0);
    $lote = trim($_POST['lote'] ?? '');
    $data_validade = $_POST['data_validade'] ?? '';
    $data_fabricacao = $_POST['data_fabricacao'] ?? null;
    $localizacao = trim($_POST['localizacao'] ?? '');
    $nota_fiscal = null;
    $observacoes = trim($_POST['observacoes'] ?? '');
    $origem = $_POST['origem'] ?? 'doacao';
    $doacao_id = $_POST['doacao_id'] ?? null;
    
    // Validações
    if ($medicamento_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Selecione um medicamento']);
        return;
    }
    if ($quantidade <= 0) {
        echo json_encode(['success' => false, 'error' => 'Quantidade inválida']);
        return;
    }
    if (empty($data_validade)) {
        echo json_encode(['success' => false, 'error' => 'Informe a data de validade']);
        return;
    }
    
    // Garantir que coluna lote permite NULL (aplica automaticamente se necessário)
    ensureEstoqueLoteNullable();
    
    // Sempre criar novo registro de estoque (lote opcional)
    $sql = "INSERT INTO " . tableName('estoque') . " (medicamento_id, lote, data_fabricacao, data_validade, quantidade_atual, quantidade_inicial, localizacao, nota_fiscal, observacoes, data_entrada) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    execute($sql, [
        $medicamento_id,
        $lote ?: null,
        $data_fabricacao ?: null,
        $data_validade,
        $quantidade,
        $quantidade,
        $localizacao ?: null,
        $nota_fiscal,
        $observacoes ?: null
    ]);
    $estoqueId = getDb()->lastInsertId();
    
    // Registrar movimentação
    $sqlMov = "INSERT INTO " . tableName('movimentacoes_estoque') . " (medicamento_id, estoque_id, tipo_movimentacao, quantidade, motivo, observacoes, usuario_id, data_movimentacao)
               VALUES (?, ?, 'entrada', ?, ?, ?, ?, NOW())";
    execute($sqlMov, [
        $medicamento_id,
        $estoqueId,
        $quantidade,
        ucfirst($origem),
        $observacoes ?: null,
        $_SESSION['usuario_id']
    ]);
    
    // Log
    logAction('ENTRADA_ESTOQUE', 'estoque', $estoqueId, "Entrada de $quantidade unidades");
    
    echo json_encode(['success' => true, 'message' => 'Entrada registrada com sucesso', 'estoque_id' => $estoqueId]);
}

function handleTransferencia() {
    $estoque_id = intval($_POST['estoque_id'] ?? 0);
    $quantidade = intval($_POST['quantidade'] ?? 0);
    $destino_tipo = trim($_POST['destino_tipo'] ?? '');
    $destino_id = $_POST['destino_id'] ?? null;
    $destino_nome = trim($_POST['destino_nome'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    
    if ($estoque_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Selecione o lote']);
        return;
    }
    if ($quantidade <= 0) {
        echo json_encode(['success' => false, 'error' => 'Quantidade inválida']);
        return;
    }
    if (empty($destino_tipo)) {
        echo json_encode(['success' => false, 'error' => 'Informe o destino']);
        return;
    }
    $estoque = fetchOne("SELECT * FROM " . tableName('estoque') . " WHERE id = ?", [$estoque_id]);
    if (!$estoque) {
        echo json_encode(['success' => false, 'error' => 'Lote não encontrado']);
        return;
    }
    if ($estoque['quantidade_atual'] < $quantidade) {
        echo json_encode(['success' => false, 'error' => 'Quantidade insuficiente no lote']);
        return;
    }
    
    execute("UPDATE " . tableName('estoque') . " SET quantidade_atual = quantidade_atual - ?, updated_at = NOW() WHERE id = ?", [$quantidade, $estoque_id]);
    
    $motivo = 'Transferência para: ' . $destino_tipo . ' ' . ($destino_nome ?: ($destino_id ? ('#' . $destino_id) : ''));
    $sqlMov = "INSERT INTO " . tableName('movimentacoes_estoque') . " (medicamento_id, estoque_id, tipo_movimentacao, quantidade, motivo, observacoes, usuario_id, data_movimentacao)
               VALUES (?, ?, 'transferencia', ?, ?, ?, ?, NOW())";
    execute($sqlMov, [
        $estoque['medicamento_id'],
        $estoque_id,
        $quantidade,
        $motivo,
        $observacoes ?: null,
        $_SESSION['usuario_id']
    ]);
    
    logAction('TRANSFERENCIA_ESTOQUE', 'estoque', $estoque_id, "Transferência de $quantidade unidades");
    
    echo json_encode(['success' => true, 'message' => 'Transferência registrada com sucesso']);
}

function handleAjuste() {
    $estoque_id = intval($_POST['estoque_id'] ?? 0);
    $quantidade_nova = intval($_POST['quantidade_nova'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? '');
    
    if ($estoque_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Lote não informado']);
        return;
    }
    if (empty($motivo)) {
        echo json_encode(['success' => false, 'error' => 'Motivo do ajuste é obrigatório']);
        return;
    }
    
    $estoque = fetchOne("SELECT * FROM " . tableName('estoque') . " WHERE id = ?", [$estoque_id]);
    if (!$estoque) {
        echo json_encode(['success' => false, 'error' => 'Lote não encontrado']);
        return;
    }
    
    $quantidade_anterior = $estoque['quantidade_atual'];
    $diferenca = $quantidade_nova - $quantidade_anterior;
    
    // Atualizar estoque
    execute("UPDATE " . tableName('estoque') . " SET quantidade_atual = ?, updated_at = NOW() WHERE id = ?", [$quantidade_nova, $estoque_id]);
    
    // Registrar movimentação
    $tipo = $diferenca >= 0 ? 'ajuste' : 'ajuste';
    $sqlMov = "INSERT INTO " . tableName('movimentacoes_estoque') . " (medicamento_id, estoque_id, tipo_movimentacao, quantidade, quantidade_anterior, quantidade_posterior, motivo, usuario_id, data_movimentacao)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    execute($sqlMov, [
        $estoque['medicamento_id'],
        $estoque_id,
        $tipo,
        abs($diferenca),
        $quantidade_anterior,
        $quantidade_nova,
        $motivo,
        $_SESSION['usuario_id']
    ]);
    
    // Log
    logAction('AJUSTE_ESTOQUE', 'estoque', $estoque_id, "Ajuste de $quantidade_anterior para $quantidade_nova - Motivo: $motivo");
    
    echo json_encode(['success' => true, 'message' => 'Ajuste realizado com sucesso']);
}

function ensureEstoqueLoteNullable() {
    try {
        $t = tableName('estoque');
        $col = fetchOne("SHOW COLUMNS FROM {$t} LIKE 'lote'");
        if ($col && strtoupper($col['Null']) === 'NO') {
            $type = $col['Type'] ?? 'VARCHAR(100)';
            execute("ALTER TABLE {$t} MODIFY lote {$type} NULL");
        }
    } catch (Exception $e) {
        // Silencioso: se não tiver permissão, segue com tentativa de inserir
    }
}

function handleMovimentacoes() {
    $periodo = $_GET['periodo'] ?? '30';
    $data_inicio = $_GET['data_inicio'] ?? '';
    $data_fim = $_GET['data_fim'] ?? '';
    $tipo = $_GET['tipo'] ?? '';
    $medicamento_id = $_GET['medicamento_id'] ?? '';
    
    // Calcular datas baseado no período
    if ($periodo !== 'custom') {
        $data_fim = date('Y-m-d');
        $data_inicio = date('Y-m-d', strtotime("-{$periodo} days"));
    }
    
    $params = [$data_inicio, $data_fim];
    $where = "me.data_movimentacao BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";
    
    if (!empty($tipo)) {
        $where .= " AND me.tipo_movimentacao = ?";
        $params[] = $tipo;
    }
    if (!empty($medicamento_id)) {
        $where .= " AND me.medicamento_id = ?";
        $params[] = $medicamento_id;
    }
    
    $movimentacoes = fetchAll("
        SELECT 
            me.*,
            m.nome as medicamento_nome,
            m.principio_ativo,
            e.lote,
            u.nome_completo as usuario_nome
        FROM " . tableName('movimentacoes_estoque') . " me
        INNER JOIN " . tableName('medicamentos') . " m ON me.medicamento_id = m.id
        LEFT JOIN " . tableName('estoque') . " e ON me.estoque_id = e.id
        LEFT JOIN " . tableName('usuarios') . " u ON me.usuario_id = u.id
        WHERE $where
        ORDER BY me.data_movimentacao DESC
        LIMIT 500
    ", $params);
    
    echo json_encode(['data' => $movimentacoes]);
}

function handleResumoMovimentacoes() {
    $periodo = $_GET['periodo'] ?? '30';
    $data_inicio = $_GET['data_inicio'] ?? '';
    $data_fim = $_GET['data_fim'] ?? '';
    $tipo = $_GET['tipo'] ?? '';
    $medicamento_id = $_GET['medicamento_id'] ?? '';
    
    // Calcular datas baseado no período
    if ($periodo !== 'custom') {
        $data_fim = date('Y-m-d');
        $data_inicio = date('Y-m-d', strtotime("-{$periodo} days"));
    }
    
    $params = [$data_inicio, $data_fim];
    $where = "data_movimentacao BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";
    
    if (!empty($medicamento_id)) {
        $where .= " AND medicamento_id = ?";
        $params[] = $medicamento_id;
    }
    
    $resumo = [
        'entradas' => fetchColumn("SELECT COALESCE(SUM(quantidade), 0) FROM " . tableName('movimentacoes_estoque') . " WHERE tipo_movimentacao = 'entrada' AND $where", $params),
        'saidas' => fetchColumn("SELECT COALESCE(SUM(quantidade), 0) FROM " . tableName('movimentacoes_estoque') . " WHERE tipo_movimentacao = 'saida' AND $where", $params),
        'ajustes' => fetchColumn("SELECT COUNT(*) FROM " . tableName('movimentacoes_estoque') . " WHERE tipo_movimentacao = 'ajuste' AND $where", $params)
    ];
    
    echo json_encode($resumo);
}

function handleExportarMovimentacoes() {
    // Esta função seria para exportar em Excel - simplificado para CSV
    $periodo = $_GET['periodo'] ?? '30';
    $data_inicio = $_GET['data_inicio'] ?? '';
    $data_fim = $_GET['data_fim'] ?? '';
    
    if ($periodo !== 'custom') {
        $data_fim = date('Y-m-d');
        $data_inicio = date('Y-m-d', strtotime("-{$periodo} days"));
    }
    
    $movimentacoes = fetchAll("
        SELECT 
            DATE_FORMAT(me.data_movimentacao, '%d/%m/%Y %H:%i') as data,
            m.nome as medicamento,
            me.tipo_movimentacao as tipo,
            me.quantidade,
            e.lote,
            me.motivo,
            u.nome_completo as usuario
        FROM " . tableName('movimentacoes_estoque') . " me
        INNER JOIN " . tableName('medicamentos') . " m ON me.medicamento_id = m.id
        LEFT JOIN " . tableName('estoque') . " e ON me.estoque_id = e.id
        LEFT JOIN " . tableName('usuarios') . " u ON me.usuario_id = u.id
        WHERE me.data_movimentacao BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
        ORDER BY me.data_movimentacao DESC
    ", [$data_inicio, $data_fim]);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="movimentacoes_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM para UTF-8
    
    fputcsv($output, ['Data', 'Medicamento', 'Tipo', 'Quantidade', 'Lote', 'Motivo', 'Usuário'], ';');
    
    foreach ($movimentacoes as $m) {
        fputcsv($output, $m, ';');
    }
    
    fclose($output);
    exit;
}

function logAction($acao, $tabela, $registro_id, $detalhes = null) {
    try {
        $sql = "INSERT INTO " . tableName('logs_sistema') . " (usuario_id, acao, tabela, registro_id, dados_novos, ip_address, data_log)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        execute($sql, [
            $_SESSION['usuario_id'],
            $acao,
            $tabela,
            $registro_id,
            $detalhes,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    } catch (Exception $e) {
        // Silently fail on log errors
    }
}
}

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
ob_clean();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    header('Content-Type: application/json');
    try {
        switch ($action) {
            case 'entrada':
                handleEntrada();
                break;
            case 'ajuste':
                handleAjuste();
                break;
            case 'transferencia':
                handleTransferencia();
                break;
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Ação inválida']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Ações GET
header('Content-Type: application/json');
try {
    switch ($action) {
        case 'list':
            handleList();
            break;
        case 'get_lotes':
            handleGetLotes();
            break;
        case 'estatisticas':
            handleEstatisticas();
            break;
        case 'proximos_vencimento':
            handleProximosVencimento();
            break;
        case 'criticos':
            handleCriticos();
            break;
        case 'info_medicamento':
            handleInfoMedicamento();
            break;
        case 'ultimas_entradas':
            handleUltimasEntradas();
            break;
        case 'movimentacoes':
            handleMovimentacoes();
            break;
        case 'resumo_movimentacoes':
            handleResumoMovimentacoes();
            break;
        case 'exportar_movimentacoes':
            handleExportarMovimentacoes();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ação inválida']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
