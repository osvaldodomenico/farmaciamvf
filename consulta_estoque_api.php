<?php
/**
 * API Pública de Consulta de Estoque
 * Esta API não requer autenticação - uso exclusivo para consulta
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? 'buscar';
$termo = trim($_GET['q'] ?? '');

if ($action === 'buscar' && !empty($termo)) {
    // Buscar medicamentos pelo nome ou princípio ativo
    $termoBusca = "%{$termo}%";
    $tMedicamentos = tableName('medicamentos');
    $tEstoque = tableName('estoque');
    
    $resultados = fetchAll("
        SELECT 
            m.id,
            m.nome,
            m.principio_ativo,
            m.dosagem_concentracao,
            m.forma_farmaceutica,
            m.unidade_medida,
            m.controlado,
            m.temperatura_armazenamento as refrigerado,
            COALESCE(SUM(e.quantidade_atual), 0) as quantidade_estoque,
            COUNT(DISTINCT CASE WHEN e.quantidade_atual > 0 THEN e.id END) as total_lotes,
            MIN(CASE WHEN e.quantidade_atual > 0 THEN e.data_validade END) as proxima_validade
        FROM {$tMedicamentos} m
        LEFT JOIN {$tEstoque} e ON m.id = e.medicamento_id
        WHERE m.ativo = 1 
        AND (m.nome LIKE ? OR m.principio_ativo LIKE ?)
        GROUP BY m.id, m.nome, m.principio_ativo, m.dosagem_concentracao, 
                 m.forma_farmaceutica, m.unidade_medida, m.controlado, 
                 m.temperatura_armazenamento
        ORDER BY 
            CASE WHEN m.nome LIKE ? THEN 0 ELSE 1 END,
            m.nome ASC
        LIMIT 20
    ", [$termoBusca, $termoBusca, $termoBusca]);
    
    echo json_encode([
        'success' => true,
        'termo' => $termo,
        'total' => count($resultados),
        'resultados' => $resultados
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Informe um termo de busca',
        'resultados' => []
    ]);
}
?>
