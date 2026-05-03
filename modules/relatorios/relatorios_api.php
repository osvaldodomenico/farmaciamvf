<?php
session_start();
ob_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

// Verificar permissão para relatórios
ini_set('display_errors', 0);
$nivel = $_SESSION['nivel_acesso'] ?? '';
if (!in_array($nivel, ['admin', 'gerente', 'farmaceutico'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Sem permissão para acessar relatórios']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
ob_clean();

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        // Relatórios de Doações
        case 'resumo_doacoes':
            getResumoDoacoes();
            break;
        case 'grafico_doacoes':
            getGraficoDoacoes();
            break;
        case 'top_doadores':
            getTopDoadores();
            break;
        case 'listar_doacoes':
            listarDoacoes();
            break;
            
        // Relatórios de Dispensações
        case 'resumo_dispensacoes':
            getResumoDispensacoes();
            break;
        case 'grafico_dispensacoes':
            getGraficoDispensacoes();
            break;
        case 'top_medicamentos':
            getTopMedicamentos();
            break;
        case 'listar_dispensacoes':
            listarDispensacoes();
            break;
            
        // Relatórios de Estoque
        case 'grafico_estoque_status':
            getGraficoEstoqueStatus();
            break;
        case 'grafico_top_estoque':
            getGraficoTopEstoque();
            break;
        case 'exportar_estoque':
            exportarEstoque();
            break;
        case 'exportar_dispensacoes_pdf':
            // TODO: Implementar exportação PDF
            http_response_code(501);
            echo json_encode(['error' => 'Exportação PDF não implementada']);
            break;
        case 'exportar_doacoes_pdf':
            // TODO: Implementar exportação PDF
            http_response_code(501);
            echo json_encode(['error' => 'Exportação PDF não implementada']);
            break;
        
        // Relatório de Vigilância Sanitária
        case 'listar_vigilancia':
            listarVigilancia();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ação inválida']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// Helper para calcular datas
function calcularDatas($periodo, $data_inicio, $data_fim) {
    if ($periodo !== 'custom') {
        $data_fim = date('Y-m-d');
        $data_inicio = date('Y-m-d', strtotime("-{$periodo} days"));
    }
    return [$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59'];
}

// ===================================================================
// FUNÇÕES DE RELATÓRIO DE DOAÇÕES
// ===================================================================

function getResumoDoacoes() {
    $periodo = $_GET['periodo'] ?? '30';
    $data_inicio = $_GET['data_inicio'] ?? '';
    $data_fim = $_GET['data_fim'] ?? '';
    $doador_id = $_GET['doador_id'] ?? '';
    
    list($data_inicio, $data_fim) = calcularDatas($periodo, $data_inicio, $data_fim);
    
    $params = [$data_inicio, $data_fim];
    $where = "d.data_doacao BETWEEN ? AND ?";
    
    if ($doador_id) {
        $where .= " AND d.doador_id = ?";
        $params[] = $doador_id;
    }
    
    $resumo = [
        'total_doacoes' => fetchColumn("SELECT COUNT(*) FROM " . tableName('doacoes') . " d WHERE $where", $params),
        'total_itens' => fetchColumn("SELECT COALESCE(SUM(id.quantidade), 0) FROM " . tableName('itens_doacao') . " id INNER JOIN " . tableName('doacoes') . " d ON id.doacao_id = d.id WHERE $where", $params),
        'total_doadores' => fetchColumn("SELECT COUNT(DISTINCT d.doador_id) FROM " . tableName('doacoes') . " d WHERE $where", $params),
        'media_mensal' => fetchColumn("
            SELECT COALESCE(COUNT(*) / GREATEST(DATEDIFF(?, ?) / 30, 1), 0) 
            FROM " . tableName('doacoes') . " d WHERE $where
        ", array_merge([$data_fim, $data_inicio], $params))
    ];
    
    echo json_encode($resumo);
}

function getGraficoDoacoes() {
    $periodo = $_GET['periodo'] ?? '30';
    $data_inicio = $_GET['data_inicio'] ?? '';
    $data_fim = $_GET['data_fim'] ?? '';
    
    list($data_inicio, $data_fim) = calcularDatas($periodo, $data_inicio, $data_fim);
    
    // Agrupar por dia ou mês dependendo do período
    $dias = (strtotime($data_fim) - strtotime($data_inicio)) / 86400;
    
    if ($dias <= 31) {
        $formato = '%d/%m';
        $groupBy = 'DATE(data_doacao)';
    } else {
        $formato = '%m/%Y';
        $groupBy = "DATE_FORMAT(data_doacao, '%Y-%m')";
    }
    
    $dados = fetchAll("
        SELECT 
            DATE_FORMAT(data_doacao, ?) as label,
            COUNT(*) as total
        FROM " . tableName('doacoes') . "
        WHERE data_doacao BETWEEN ? AND ?
        GROUP BY label
        ORDER BY MIN(data_doacao) ASC
    ", [$formato, $data_inicio, $data_fim]);
    
    echo json_encode([
        'labels' => array_column($dados, 'label'),
        'valores' => array_column($dados, 'total')
    ]);
}

function getTopDoadores() {
    $periodo = $_GET['periodo'] ?? '30';
    $data_inicio = $_GET['data_inicio'] ?? '';
    $data_fim = $_GET['data_fim'] ?? '';
    
    list($data_inicio, $data_fim) = calcularDatas($periodo, $data_inicio, $data_fim);
    
    $doadores = fetchAll("
        SELECT 
            do.id,
            do.nome_completo as nome,
            COUNT(d.id) as total_doacoes,
            COALESCE(SUM(id.quantidade), 0) as total_itens
        FROM " . tableName('doadores') . " do
        INNER JOIN " . tableName('doacoes') . " d ON do.id = d.doador_id
        LEFT JOIN " . tableName('itens_doacao') . " id ON d.id = id.doacao_id
        WHERE d.data_doacao BETWEEN ? AND ?
        GROUP BY do.id, do.nome_completo
        ORDER BY total_doacoes DESC
        LIMIT 10
    ", [$data_inicio, $data_fim]);
    
    echo json_encode($doadores);
}

function listarDoacoes() {
    $periodo = $_GET['periodo'] ?? '30';
    $data_inicio = $_GET['data_inicio'] ?? '';
    $data_fim = $_GET['data_fim'] ?? '';
    $doador_id = $_GET['doador_id'] ?? '';
    
    list($data_inicio, $data_fim) = calcularDatas($periodo, $data_inicio, $data_fim);
    
    $params = [$data_inicio, $data_fim];
    $where = "d.data_doacao BETWEEN ? AND ?";
    
    if ($doador_id) {
        $where .= " AND d.doador_id = ?";
        $params[] = $doador_id;
    }
    
    $doacoes = fetchAll("
        SELECT 
            d.id,
            d.data_doacao,
            do.nome_completo as doador_nome,
            u.nome_completo as usuario_nome,
            (SELECT COUNT(*) FROM " . tableName('itens_doacao') . " WHERE doacao_id = d.id) as total_itens,
            (SELECT GROUP_CONCAT(m.nome SEPARATOR ', ') 
             FROM " . tableName('itens_doacao') . " id2 
             INNER JOIN " . tableName('medicamentos') . " m ON id2.medicamento_id = m.id 
             WHERE id2.doacao_id = d.id) as medicamentos
        FROM " . tableName('doacoes') . " d
        LEFT JOIN " . tableName('doadores') . " do ON d.doador_id = do.id
        LEFT JOIN " . tableName('usuarios') . " u ON d.usuario_id = u.id
        WHERE $where
        ORDER BY d.data_doacao DESC
        LIMIT 500
    ", $params);
    
    echo json_encode(['data' => $doacoes]);
}

// ===================================================================
// FUNÇÕES DE RELATÓRIO DE DISPENSAÇÕES
// ===================================================================

function getResumoDispensacoes() {
    $periodo = $_GET['periodo'] ?? '30';
    $data_inicio = $_GET['data_inicio'] ?? '';
    $data_fim = $_GET['data_fim'] ?? '';
    $cliente_id = $_GET['cliente_id'] ?? '';
    
    list($data_inicio, $data_fim) = calcularDatas($periodo, $data_inicio, $data_fim);
    
    $params = [$data_inicio, $data_fim];
    $where = "d.data_dispensacao BETWEEN ? AND ?";
    
    if ($cliente_id) {
        $where .= " AND d.cliente_id = ?";
        $params[] = $cliente_id;
    }
    
    $dias = max(1, (strtotime($data_fim) - strtotime($data_inicio)) / 86400);
    
    $resumo = [
        'total_dispensacoes' => fetchColumn("SELECT COUNT(*) FROM " . tableName('dispensacoes') . " d WHERE $where", $params),
        'total_itens' => fetchColumn("SELECT COALESCE(SUM(d.quantidade), 0) FROM " . tableName('dispensacoes') . " d WHERE $where", $params),
        'total_clientes' => fetchColumn("SELECT COUNT(DISTINCT d.cliente_id) FROM " . tableName('dispensacoes') . " d WHERE $where", $params),
        'media_diaria' => fetchColumn("SELECT COUNT(*) / ? FROM " . tableName('dispensacoes') . " d WHERE $where", array_merge([$dias], $params))
    ];
    
    echo json_encode($resumo);
}

function getGraficoDispensacoes() {
    $periodo = $_GET['periodo'] ?? '30';
    $data_inicio = $_GET['data_inicio'] ?? '';
    $data_fim = $_GET['data_fim'] ?? '';
    
    list($data_inicio, $data_fim) = calcularDatas($periodo, $data_inicio, $data_fim);
    
    // Agrupar por dia ou mês dependendo do período
    $dias = (strtotime($data_fim) - strtotime($data_inicio)) / 86400;
    
    if ($dias <= 31) {
        $formato = '%d/%m';
        $groupBy = 'DATE(data_dispensacao)';
    } else {
        $formato = '%m/%Y';
        $groupBy = "DATE_FORMAT(data_dispensacao, '%Y-%m')";
    }
    
    $dados = fetchAll("
        SELECT 
            DATE_FORMAT(data_dispensacao, ?) as label,
            COUNT(*) as total
        FROM " . tableName('dispensacoes') . "
        WHERE data_dispensacao BETWEEN ? AND ?
        GROUP BY label
        ORDER BY MIN(data_dispensacao) ASC
    ", [$formato, $data_inicio, $data_fim]);
    
    echo json_encode([
        'labels' => array_column($dados, 'label'),
        'valores' => array_column($dados, 'total')
    ]);
}

function getTopMedicamentos() {
    $periodo = $_GET['periodo'] ?? '30';
    $data_inicio = $_GET['data_inicio'] ?? '';
    $data_fim = $_GET['data_fim'] ?? '';
    
    list($data_inicio, $data_fim) = calcularDatas($periodo, $data_inicio, $data_fim);
    
    $medicamentos = fetchAll("
        SELECT 
            m.id,
            m.nome,
            SUM(d.quantidade) as quantidade
        FROM " . tableName('dispensacoes') . " d
        INNER JOIN " . tableName('medicamentos') . " m ON d.medicamento_id = m.id
        WHERE d.data_dispensacao BETWEEN ? AND ?
        GROUP BY m.id, m.nome
        ORDER BY quantidade DESC
        LIMIT 10
    ", [$data_inicio, $data_fim]);
    
    echo json_encode($medicamentos);
}

function listarDispensacoes() {
    $periodo = $_GET['periodo'] ?? '30';
    $data_inicio = $_GET['data_inicio'] ?? '';
    $data_fim = $_GET['data_fim'] ?? '';
    $cliente_id = $_GET['cliente_id'] ?? '';
    
    list($data_inicio, $data_fim) = calcularDatas($periodo, $data_inicio, $data_fim);
    
    $params = [$data_inicio, $data_fim];
    $where = "d.data_dispensacao BETWEEN ? AND ?";
    
    if ($cliente_id) {
        $where .= " AND d.cliente_id = ?";
        $params[] = $cliente_id;
    }
    
    $dispensacoes = fetchAll("
        SELECT 
            d.id,
            d.data_dispensacao,
            p.nome_completo as cliente_nome,
            u.nome_completo as usuario_nome,
            d.quantidade as total_itens,
            m.nome as medicamentos
        FROM " . tableName('dispensacoes') . " d
        LEFT JOIN " . tableName('clientes') . " p ON d.cliente_id = p.id
        LEFT JOIN " . tableName('usuarios') . " u ON d.usuario_id = u.id
        LEFT JOIN " . tableName('medicamentos') . " m ON d.medicamento_id = m.id
        WHERE $where
        ORDER BY d.data_dispensacao DESC
        LIMIT 500
    ", $params);
    
    echo json_encode(['data' => $dispensacoes]);
}

// ===================================================================
// FUNÇÕES DE RELATÓRIO DE ESTOQUE
// ===================================================================

function getGraficoEstoqueStatus() {
    $tMedicamentos = tableName('medicamentos');
    $tEstoque = tableName('estoque');
    $stats = fetchAll("
        SELECT 
            CASE 
                WHEN COALESCE(SUM(e.quantidade_atual), 0) = 0 THEN 'Zerado'
                WHEN COALESCE(SUM(e.quantidade_atual), 0) <= m.estoque_minimo THEN 'Crítico'
                WHEN COALESCE(SUM(e.quantidade_atual), 0) <= m.estoque_minimo * 1.5 THEN 'Baixo'
                ELSE 'Normal'
            END as status,
            COUNT(*) as total
        FROM {$tMedicamentos} m
        LEFT JOIN {$tEstoque} e ON m.id = e.medicamento_id AND e.quantidade_atual > 0
        WHERE m.ativo = 1
        GROUP BY m.id, m.estoque_minimo
    ");
    
    // Agrupar por status
    $resumo = ['Normal' => 0, 'Baixo' => 0, 'Crítico' => 0, 'Zerado' => 0];
    foreach ($stats as $s) {
        $resumo[$s['status']] = ($resumo[$s['status']] ?? 0) + 1;
    }
    
    echo json_encode([
        'labels' => array_keys($resumo),
        'valores' => array_values($resumo)
    ]);
}

function getGraficoTopEstoque() {
    $tMedicamentos = tableName('medicamentos');
    $tEstoque = tableName('estoque');
    $topMedicamentos = fetchAll("
        SELECT 
            m.nome,
            COALESCE(SUM(e.quantidade_atual), 0) as quantidade
        FROM {$tMedicamentos} m
        LEFT JOIN {$tEstoque} e ON m.id = e.medicamento_id AND e.quantidade_atual > 0
        WHERE m.ativo = 1
        GROUP BY m.id, m.nome
        HAVING quantidade > 0
        ORDER BY quantidade DESC
        LIMIT 10
    ");
    
    echo json_encode([
        'labels' => array_column($topMedicamentos, 'nome'),
        'valores' => array_column($topMedicamentos, 'quantidade')
    ]);
}

function exportarEstoque() {
    $tMedicamentos = tableName('medicamentos');
    $tEstoque = tableName('estoque');
    $estoque = fetchAll("
        SELECT 
            m.nome as 'Medicamento',
            m.principio_ativo as 'Princípio Ativo',
            COALESCE(SUM(e.quantidade_atual), 0) as 'Quantidade',
            m.estoque_minimo as 'Estoque Mínimo',
            COUNT(e.id) as 'Lotes',
            MIN(e.data_validade) as 'Próx. Vencimento'
        FROM {$tMedicamentos} m
        LEFT JOIN {$tEstoque} e ON m.id = e.medicamento_id AND e.quantidade_atual > 0
        WHERE m.ativo = 1
        GROUP BY m.id
        ORDER BY m.nome
    ");
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="estoque_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM para UTF-8
    
    // Header
    fputcsv($output, ['Medicamento', 'Princípio Ativo', 'Quantidade', 'Estoque Mínimo', 'Lotes', 'Próx. Vencimento'], ';');
    
    foreach ($estoque as $item) {
        fputcsv($output, $item, ';');
    }
    
    fclose($output);
    exit;
}

// ===================================================================
// FUNÇÕES DE RELATÓRIO - VIGILÂNCIA SANITÁRIA
// ===================================================================
function listarVigilancia() {
    $periodo = $_GET['periodo'] ?? '30';
    $data_inicio = $_GET['data_inicio'] ?? '';
    $data_fim = $_GET['data_fim'] ?? '';
    
    list($data_inicio, $data_fim) = calcularDatas($periodo, $data_inicio, $data_fim);
    
    $tMedicamentos = tableName('medicamentos');
    $tEstoque = tableName('estoque');
    
    $dados = fetchAll("
        SELECT 
            m.id,
            m.nome,
            m.dosagem_concentracao,
            m.unidade,
            MIN(e.localizacao) as prateleira,
            MIN(e.data_fabricacao) as data_producao,
            MIN(e.data_validade) as data_vencimento,
            COALESCE(SUM(e.quantidade_atual), 0) as estoque_total
        FROM {$tMedicamentos} m
        LEFT JOIN {$tEstoque} e
            ON m.id = e.medicamento_id
            AND e.quantidade_atual > 0
        WHERE m.ativo = 1
        GROUP BY m.id, m.nome, m.dosagem_concentracao, m.unidade
        ORDER BY m.nome ASC
        LIMIT 2000
    ");
    
    foreach ($dados as &$d) {
        $conc = $d['dosagem_concentracao'] ?? '';
        $um = $d['unidade'] ?? '';
        if ($um) {
            $conc .= ($um === 'PORCENTAGEM' ? '%' : ($conc ? ' ' : '') . $um);
        }
        $d['composicao'] = $conc ?: '-';
        unset($d['dosagem_concentracao'], $d['unidade']);
    }
    unset($d);
    
    echo json_encode(['data' => $dados]);
}
?>
