<?php
require_once 'templates/header.php';

// --- VISÃO GERAL DIÁRIA — query consolidada ---
$tDisp  = tableName('dispensacoes');
$tItens = tableName('dispensacoes_itens');
$tMed   = tableName('medicamentos');
$tEst   = tableName('estoque');
$tCli   = tableName('clientes');
$tUsr   = tableName('usuarios');

$stats = fetchOne("
    SELECT
        (SELECT COUNT(*)               FROM {$tDisp}  WHERE DATE(data_dispensacao) = CURDATE())                       AS disp_hoje,
        (SELECT COUNT(DISTINCT cliente_id) FROM {$tDisp} WHERE DATE(data_dispensacao) = CURDATE())                    AS pessoas_hoje,
        (SELECT COALESCE(SUM(di.quantidade),0) FROM {$tItens} di
            JOIN {$tDisp} d ON di.dispensacao_id = d.id
            WHERE DATE(d.data_dispensacao) = CURDATE())                                                                AS itens_hoje,
        (SELECT COUNT(*)               FROM {$tMed}   WHERE ativo = 1)                                                AS total_remedios,
        (SELECT COUNT(*)               FROM {$tDisp}  WHERE DATE(data_dispensacao) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)) AS disp_ontem,
        (SELECT COUNT(DISTINCT cliente_id) FROM {$tDisp} WHERE DATE(data_dispensacao) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)) AS pessoas_ontem
");

$disp_hoje     = $stats['disp_hoje']     ?? 0;
$pessoas_hoje  = $stats['pessoas_hoje']  ?? 0;
$itens_hoje    = $stats['itens_hoje']    ?? 0;
$total_remedios = $stats['total_remedios'] ?? 0;
$disp_ontem    = $stats['disp_ontem']    ?? 0;
$pessoas_ontem = $stats['pessoas_ontem'] ?? 0;

// Helper para setas
function getArrow($hoje, $ontem) {
    if ($hoje > $ontem) return '<span class="text-success">▲</span>';
    if ($hoje < $ontem) return '<span class="text-danger">▼</span>';
    return '<span class="text-muted">=</span>';
}

// --- ALERTAS E STATUS ---
// Estoque Critico (Contagem e Top 1)
$criticos_dados = fetchAll("
    SELECT m.nome, (m.estoque_minimo - COALESCE(SUM(e.quantidade_atual), 0)) as deficit
    FROM " . tableName('medicamentos') . " m
    LEFT JOIN " . tableName('estoque') . " e ON m.id = e.medicamento_id AND e.quantidade_atual > 0
    WHERE m.ativo = 1
    GROUP BY m.id, m.nome, m.estoque_minimo
    HAVING COALESCE(SUM(e.quantidade_atual), 0) <= m.estoque_minimo
    ORDER BY deficit DESC
");
$criticos_count = count($criticos_dados);
$top_critico = $criticos_dados[0] ?? null;

// Vencimento (Contagem e Top 1 - 30 dias)
$vencendo_dados = fetchAll("
    SELECT m.nome, DATEDIFF(e.data_validade, CURDATE()) as dias
    FROM " . tableName('estoque') . " e
    JOIN " . tableName('medicamentos') . " m ON e.medicamento_id = m.id
    WHERE e.quantidade_atual > 0
    AND e.data_validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY e.data_validade ASC
");
$vencendo_count = count($vencendo_dados);
$top_vencendo = $vencendo_dados[0] ?? null;

// Status Geral
$status_cor = 'success';
$status_msg = 'Operação Normal';
if ($vencendo_count > 0) {
    $status_cor = 'warning';
    $status_msg = 'Atenção Necessária';
}
if ($criticos_count > 0) {
    $status_cor = 'danger';
    $status_msg = 'Ação Imediata Requerida';
}

// --- ÚLTIMA ATIVIDADE ---
$ultima_disp = fetchOne("
    SELECT d.data_dispensacao, m.nome as medicamento, u.nome_completo as usuario
    FROM " . tableName('dispensacoes') . " d 
    JOIN " . tableName('dispensacoes_itens') . " di ON d.id = di.dispensacao_id 
    JOIN " . tableName('medicamentos') . " m ON di.medicamento_id = m.id 
    JOIN " . tableName('usuarios') . " u ON d.usuario_id = u.id
    ORDER BY d.data_dispensacao DESC 
    LIMIT 1
");

// --- HISTÓRICO RECENTE ---
$ultimas = fetchAll("
    SELECT d.numero_dispensacao, d.data_dispensacao, c.nome_completo as cliente
    FROM " . tableName('dispensacoes') . " d
    LEFT JOIN " . tableName('clientes') . " c ON d.cliente_id = c.id
    ORDER BY d.data_dispensacao DESC
    LIMIT 5
");

// --- TOP 10 MEDICAMENTOS (30 DIAS) ---
$top_medicamentos = fetchAll("
    SELECT m.nome, SUM(di.quantidade) as total_saida
    FROM " . tableName('dispensacoes_itens') . " di
    JOIN " . tableName('dispensacoes') . " d ON di.dispensacao_id = d.id
    JOIN " . tableName('medicamentos') . " m ON di.medicamento_id = m.id
    WHERE d.data_dispensacao >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY m.id, m.nome
    ORDER BY total_saida DESC
    LIMIT 10
");

// Preparar dados para o gráfico
$chart_labels = [];
$chart_data = [];
foreach ($top_medicamentos as $tm) {
    $chart_labels[] = $tm['nome'];
    $chart_data[] = floatval($tm['total_saida']);
}

?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>document.getElementById('page-title').textContent = 'Dashboard';</script>

<div class="premium-page">
    <div class="premium-header">
        <div class="premium-header-content">
            <h1><i class="bi bi-speedometer2"></i> Dashboard Operacional</h1>
            <div class="premium-breadcrumb">
                <span>Visão geral do sistema</span>
            </div>
        </div>
    </div>

    <div class="container-fluid px-4">
        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card animate-in pb-4">
                <div class="stat-card-header">
                    <div class="stat-card-title">Dispensações Hoje</div>
                    <div class="stat-card-help" data-tooltip="Total de dispensações realizadas hoje">?</div>
                </div>
                <div class="stat-card-value"><?php echo $disp_hoje; ?></div>
                <div class="stat-card-subtitle">
                    <?php echo getArrow($disp_hoje, $disp_ontem); ?> vs ontem (<?php echo $disp_ontem; ?>)
                </div>
                <span class="stat-badge stat-badge-teal">
                    <i class="bi bi-prescription2"></i> Total Dia
                </span>
            </div>

            <div class="stat-card animate-in pb-4" style="animation-delay: 0.1s;">
                <div class="stat-card-header">
                    <div class="stat-card-title">Pessoas Atendidas</div>
                    <div class="stat-card-help" data-tooltip="Total de pessoas únicas atendidas hoje">?</div>
                </div>
                <div class="stat-card-value"><?php echo $pessoas_hoje; ?></div>
                <div class="stat-card-subtitle">
                    <?php echo getArrow($pessoas_hoje, $pessoas_ontem); ?> vs ontem (<?php echo $pessoas_ontem; ?>)
                </div>
                <span class="stat-badge stat-badge-green">
                    <i class="bi bi-people"></i> Clientes Únicos
                </span>
            </div>

            <div class="stat-card animate-in pb-4" style="animation-delay: 0.2s;">
                <div class="stat-card-header">
                    <div class="stat-card-title">Itens Dispensados</div>
                    <div class="stat-card-help" data-tooltip="Total de unidades de medicamentos saídas">?</div>
                </div>
                <div class="stat-card-value"><?php echo number_format($itens_hoje); ?></div>
                <div class="stat-card-subtitle">
                    Saída total do dia
                </div>
                <span class="stat-badge stat-badge-gold">
                    <i class="bi bi-capsule"></i> Unidades
                </span>
            </div>

            <div class="stat-card animate-in pb-4" style="animation-delay: 0.3s;">
                <div class="stat-card-header">
                    <div class="stat-card-title">Total de Remédios</div>
                    <div class="stat-card-help" data-tooltip="Total de medicamentos cadastrados e ativos no sistema">?</div>
                </div>
                <div class="stat-card-value"><?php echo number_format($total_remedios); ?></div>
                <div class="stat-card-subtitle">
                    Medicamentos ativos
                </div>
                <span class="stat-badge stat-badge-pink">
                    <i class="bi bi-capsule-pill"></i> Cadastrados
                </span>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <!-- Left Column -->
            <div class="col-lg-7">
                <!-- Alerts Section -->
                 <div class="premium-card mb-4 animate-in" style="animation-delay: 0.4s;">
                    <div class="premium-card-header">
                        <div class="premium-card-header-icon <?php echo $status_cor == 'success' ? 'teal' : ($status_cor == 'warning' ? 'gold' : 'purple'); ?>">
                            <i class="bi bi-activity"></i>
                        </div>
                        <div>
                            <h5 class="premium-card-title">Status da Operação</h5>
                            <p class="premium-card-subtitle"><?php echo $status_msg; ?></p>
                        </div>
                    </div>
                    <div class="premium-card-body">
                        <div class="row g-3">
                            <!-- Estoque Critico -->
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded border h-100">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-bold text-danger text-uppercase" style="font-size: 0.8rem;">
                                            <i class="bi bi-exclamation-octagon me-2"></i>Estoque Crítico
                                        </span>
                                        <span class="badge bg-danger rounded-pill"><?php echo $criticos_count; ?></span>
                                    </div>
                                    
                                    <?php if ($criticos_count > 0): ?>
                                        <p class="mb-2 text-dark fw-medium" style="font-size: 0.9rem;">
                                            <?php echo $criticos_count; ?> itens abaixo do mínimo.
                                        </p>
                                        <div class="bg-white p-2 rounded border border-danger-subtle mb-3">
                                            <small class="text-muted d-block text-uppercase" style="font-size: 0.65rem;">Mais crítico</small>
                                            <strong class="text-danger"><?php echo htmlspecialchars($top_critico['nome']); ?></strong>
                                            <div class="d-flex justify-content-between mt-1">
                                                <small class="text-danger fw-bold">Deficit: -<?php echo $top_critico['deficit']; ?></small>
                                            </div>
                                        </div>
                                        <a href="modules/estoque/listar.php" class="premium-btn premium-btn-danger premium-btn-sm w-100">
                                            Resolver Agora
                                        </a>
                                    <?php else: ?>
                                        <p class="mb-0 text-muted" style="font-size: 0.9rem;">Nenhum item em nível crítico.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Vencimento -->
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded border h-100">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-bold text-warning text-uppercase" style="font-size: 0.8rem; color: #d68910;">
                                            <i class="bi bi-clock-history me-2"></i>Vencendo em 30 dias
                                        </span>
                                        <span class="badge bg-warning text-dark rounded-pill"><?php echo $vencendo_count; ?></span>
                                    </div>

                                    <?php if ($vencendo_count > 0): ?>
                                        <p class="mb-2 text-dark fw-medium" style="font-size: 0.9rem;">
                                            <?php echo $vencendo_count; ?> lotes vencem em breve.
                                        </p>
                                        <div class="bg-white p-2 rounded border border-warning-subtle mb-3">
                                            <small class="text-muted d-block text-uppercase" style="font-size: 0.65rem;">Mais urgente</small>
                                            <strong class="text-dark"><?php echo htmlspecialchars($top_vencendo['nome']); ?></strong>
                                            <div class="d-flex justify-content-between mt-1">
                                                <small class="text-warning text-dark fw-bold"><?php echo $top_vencendo['dias']; ?> dias restantes</small>
                                            </div>
                                        </div>
                                        <a href="modules/estoque/listar.php" class="premium-btn premium-btn-secondary premium-btn-sm w-100">
                                            Verificar Lotes
                                        </a>
                                    <?php else: ?>
                                        <p class="mb-0 text-muted" style="font-size: 0.9rem;">Nenhum produto vencendo em 30 dias.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                 </div>

                 <!-- Top 10 Chart -->
                 <div class="premium-card animate-in" style="animation-delay: 0.5s;">
                    <div class="premium-card-header">
                        <div class="premium-card-header-icon blue">
                            <i class="bi bi-bar-chart-fill"></i>
                        </div>
                        <div>
                            <h5 class="premium-card-title">Top 10 Saídas</h5>
                            <p class="premium-card-subtitle">Medicamentos mais dispensados (Últimos 30 dias)</p>
                        </div>
                    </div>
                    <div class="premium-card-body">
                        <div style="height: 300px;">
                            <canvas id="chartTopMedicamentos"></canvas>
                        </div>
                    </div>
                 </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-5">
                <!-- Last Activity -->
                <?php if ($ultima_disp): ?>
                <div class="premium-card mb-4 animate-in" style="animation-delay: 0.5s;">
                    <div class="premium-card-header">
                        <div class="premium-card-header-icon teal">
                            <i class="bi bi-clock"></i>
                        </div>
                        <div>
                            <h5 class="premium-card-title">Última Atividade</h5>
                            <p class="premium-card-subtitle">Registrada às <?php echo date('H:i', strtotime($ultima_disp['data_dispensacao'])); ?></p>
                        </div>
                    </div>
                    <div class="premium-card-body d-flex align-items-center gap-3">
                        <div class="bg-light p-3 rounded-circle">
                            <i class="bi bi-check-lg text-success fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($ultima_disp['medicamento']); ?></h6>
                            <p class="mb-0 text-muted small">Dispensado por <?php echo htmlspecialchars($ultima_disp['usuario']); ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent History -->
                <div class="premium-card animate-in" style="animation-delay: 0.6s;">
                    <div class="premium-card-header">
                        <div class="premium-card-header-icon purple">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <h5 class="premium-card-title">Dispensações Recentes</h5>
                            <p class="premium-card-subtitle">Últimas 5 saídas registradas</p>
                        </div>
                    </div>
                    <div class="premium-card-body p-0">
                        <div class="table-responsive">
                            <table class="table premium-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Hora</th>
                                        <th>Cliente</th>
                                        <th class="text-end pe-4">ID</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ultimas as $u): ?>
                                    <tr>
                                        <td class="ps-4 text-muted fw-medium"><?php echo date('H:i', strtotime($u['data_dispensacao'])); ?></td>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($u['cliente'] ?? 'Consumidor Final'); ?></td>
                                        <td class="text-end pe-4">
                                            <span class="badge bg-light text-primary border">#<?php echo $u['numero_dispensacao']; ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($ultimas)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox d-block mb-2" style="font-size: 1.5rem;"></i>
                                            Nenhuma dispensação hoje.
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="premium-card-footer text-center">
                        <a href="modules/dispensacoes/listar.php" class="premium-btn premium-btn-secondary premium-btn-sm">
                            Ver Histórico Completo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh a cada 5 minutos (sem recarregar a página inteira)
    let refreshTimer = setTimeout(function autoRefresh() {
        // Recarregar apenas os números dos cards via fetch silencioso
        fetch(window.location.href, { credentials: 'same-origin' })
            .then(r => r.text())
            .then(html => {
                const parser = new DOMParser();
                const doc    = parser.parseFromString(html, 'text/html');
                // Atualizar apenas os valores dos stat-cards
                document.querySelectorAll('.stat-card-value').forEach((el, i) => {
                    const newEl = doc.querySelectorAll('.stat-card-value')[i];
                    if (newEl) el.textContent = newEl.textContent;
                });
                document.querySelectorAll('.stat-card-subtitle').forEach((el, i) => {
                    const newEl = doc.querySelectorAll('.stat-card-subtitle')[i];
                    if (newEl) el.innerHTML = newEl.innerHTML;
                });
            })
            .catch(() => {}) // silencioso em caso de erro
            .finally(() => {
                refreshTimer = setTimeout(autoRefresh, 5 * 60 * 1000);
            });
    }, 5 * 60 * 1000);

    // Indicador visual de última atualização
    const headerContent = document.querySelector('.premium-header-content');
    if (headerContent) {
        const updateBadge = document.createElement('div');
        updateBadge.id = 'last-update-badge';
        updateBadge.style.cssText = 'font-size:0.78rem; opacity:0.7; margin-top:4px;';
        updateBadge.textContent = 'Atualizado agora';
        headerContent.appendChild(updateBadge);

        setInterval(() => {
            // Mostrar "Atualiza em Xmin"
        }, 60000);
    }

    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-tooltip]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            title: tooltipTriggerEl.getAttribute('data-tooltip'),
            placement: 'top',
            container: 'body'
        });
    });

    // Gráfico
    const ctx = document.getElementById('chartTopMedicamentos');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Quantidade Dispensada',
                    data: <?php echo json_encode($chart_data); ?>,
                    backgroundColor: '#0f4c75', // Premium Blue
                    hoverBackgroundColor: '#3282b8',
                    borderRadius: 4,
                    barThickness: 20,
                    maxBarThickness: 30
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1a2b3c',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: { family: 'Inter', size: 13 },
                        bodyFont: { family: 'Inter', size: 13 },
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' unidades';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f0f4f7', drawBorder: false },
                        ticks: { font: { family: 'Inter', size: 11 }, color: '#8a9aac' }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { family: 'Inter', size: 11 }, color: '#8a9aac' }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once 'templates/footer.php'; ?>
