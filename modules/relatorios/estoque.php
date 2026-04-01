<?php
require_once __DIR__ . '/../../templates/header.php';
?>

<script>document.getElementById('page-title').textContent = 'Relatório de Estoque';</script>

<div class="page-title">
    <h1><i class="bi bi-boxes"></i> Relatório de Estoque</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Relatório de Estoque</li>
        </ol>
    </nav>
</div>

<!-- Cards de resumo -->
<div class="row g-4 mb-4">
    <div class="col-md-2">
        <div class="card">
            <div class="card-body text-center">
                <h2 class="mb-0 text-primary" id="total-medicamentos">0</h2>
                <small class="text-muted">Medicamentos</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card">
            <div class="card-body text-center">
                <h2 class="mb-0 text-primary" id="total-itens">0</h2>
                <small class="text-muted">Total em Estoque</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card">
            <div class="card-body text-center">
                <h2 class="mb-0 text-info" id="total-lotes">0</h2>
                <small class="text-muted">Lotes Ativos</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card">
            <div class="card-body text-center">
                <h2 class="mb-0 text-warning" id="vencendo">0</h2>
                <small class="text-muted">Vencendo (30d)</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card">
            <div class="card-body text-center">
                <h2 class="mb-0 text-danger" id="vencidos">0</h2>
                <small class="text-muted">Vencidos</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-danger">
            <div class="card-body text-center">
                <h2 class="mb-0 text-danger" id="criticos">0</h2>
                <small class="text-muted">Críticos</small>
            </div>
        </div>
    </div>
</div>

<!-- Tabs de relatórios -->
<ul class="nav nav-tabs mb-4" id="estoqueTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="geral-tab" data-bs-toggle="tab" data-bs-target="#geral" type="button">
            <i class="bi bi-list-ul"></i> Estoque Geral
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="vencimento-tab" data-bs-toggle="tab" data-bs-target="#vencimento" type="button">
            <i class="bi bi-calendar-x"></i> Próximos a Vencer
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="critico-tab" data-bs-toggle="tab" data-bs-target="#critico" type="button">
            <i class="bi bi-exclamation-triangle"></i> Estoque Crítico
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="grafico-tab" data-bs-toggle="tab" data-bs-target="#grafico" type="button">
            <i class="bi bi-pie-chart"></i> Gráficos
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- Tab Estoque Geral -->
    <div class="tab-pane fade show active" id="geral" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-box-seam"></i> Estoque Completo</h5>
                <button class="btn btn-sm btn-outline-secondary" onclick="exportarEstoque()">
                    <i class="bi bi-file-excel"></i> Exportar Excel
                </button>
            </div>
            <div class="card-body">
                <table id="estoqueGeralTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Medicamento</th>
                            <th>Princípio Ativo</th>
                            <th>Quantidade</th>
                            <th>Estoque Mín.</th>
                            <th>Lotes</th>
                            <th>Próx. Vencimento</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Tab Próximos a Vencer -->
    <div class="tab-pane fade" id="vencimento" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-calendar-x"></i> Medicamentos Próximos ao Vencimento</h5>
                <div>
                    <select class="form-select form-select-sm d-inline-block" id="diasVencimento" style="width: auto;">
                        <option value="30">30 dias</option>
                        <option value="60">60 dias</option>
                        <option value="90">90 dias</option>
                        <option value="180">180 dias</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <table id="vencimentoTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Medicamento</th>
                            <th>Lote</th>
                            <th>Quantidade</th>
                            <th>Validade</th>
                            <th>Dias Restantes</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Tab Estoque Crítico -->
    <div class="tab-pane fade" id="critico" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Medicamentos em Estoque Crítico</h5>
            </div>
            <div class="card-body">
                <table id="criticoTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Medicamento</th>
                            <th>Princípio Ativo</th>
                            <th>Estoque Atual</th>
                            <th>Estoque Mínimo</th>
                            <th>Diferença</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Tab Gráficos -->
    <div class="tab-pane fade" id="grafico" role="tabpanel">
        <div class="row">
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Distribuição por Status</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="chartStatus" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Top 10 Medicamentos</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="chartTop" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script>
let estoqueGeralTable, vencimentoTable, criticoTable;
let chartStatus, chartTop;

$(document).ready(function() {
    // Carregar resumo
    carregarResumo();
    
    // Inicializar tabelas
    initEstoqueGeralTable();
    initVencimentoTable();
    initCriticoTable();
    
    // Carregar gráficos quando a tab for ativada
    $('button[data-bs-target="#grafico"]').on('shown.bs.tab', function() {
        carregarGraficos();
    });
    
    // Filtro de dias para vencimento
    $('#diasVencimento').change(function() {
        vencimentoTable.ajax.reload();
    });
});

function carregarResumo() {
    $.getJSON('../estoque/estoque_api.php?action=estatisticas', function(data) {
        $('#total-medicamentos').text(data.total_medicamentos || 0);
        $('#total-itens').text(data.total_itens || 0);
        $('#total-lotes').text(data.total_lotes || 0);
        $('#vencendo').text(data.vencendo_30_dias || 0);
        $('#vencidos').text(data.vencidos || 0);
        $('#criticos').text(data.criticos || 0);
    }).fail(function() {
        toastr.error('Erro ao carregar resumo de estoque');
    });
}

function initEstoqueGeralTable() {
    estoqueGeralTable = $('#estoqueGeralTable').DataTable({
        ajax: {
            url: '../estoque/estoque_api.php?action=list',
            dataSrc: 'data'
        },
        columns: [
            { data: 'nome' },
            { data: 'principio_ativo', defaultContent: '-' },
            { 
                data: 'quantidade_total',
                render: data => `<strong>${data}</strong>`
            },
            { data: 'estoque_minimo' },
            { data: 'total_lotes' },
            { 
                data: 'proxima_validade',
                render: data => {
                    if (!data) return '-';
                    const dt = new Date(data);
                    return dt.toLocaleDateString('pt-BR');
                }
            },
            { 
                data: null,
                render: (data, type, row) => {
                    const qtd = parseInt(row.quantidade_total);
                    const min = parseInt(row.estoque_minimo);
                    
                    if (qtd === 0) return '<span class="badge bg-dark">Zerado</span>';
                    if (qtd <= min) return '<span class="badge bg-danger">Crítico</span>';
                    if (qtd <= min * 1.5) return '<span class="badge bg-warning text-dark">Baixo</span>';
                    return '<span class="badge bg-primary">Normal</span>';
                }
            }
        ],
        order: [[0, 'asc']]
    });
}

function initVencimentoTable() {
    vencimentoTable = $('#vencimentoTable').DataTable({
        ajax: {
            url: '../estoque/estoque_api.php',
            dataSrc: '',
            data: function(d) {
                d.action = 'proximos_vencimento';
                d.dias = $('#diasVencimento').val();
            }
        },
        columns: [
            { data: 'medicamento_nome' },
            { data: 'lote' },
            { data: 'quantidade_atual' },
            { 
                data: 'data_validade',
                render: data => new Date(data).toLocaleDateString('pt-BR')
            },
            { 
                data: 'dias_para_vencer',
                render: data => {
                    if (data < 0) return `<span class="text-danger fw-bold">Vencido há ${Math.abs(data)} dias</span>`;
                    return `<span class="${data <= 30 ? 'text-danger' : 'text-warning'} fw-bold">${data} dias</span>`;
                }
            },
            { 
                data: 'dias_para_vencer',
                render: data => {
                    if (data < 0) return '<span class="badge bg-dark">Vencido</span>';
                    if (data <= 15) return '<span class="badge bg-danger">Urgente</span>';
                    if (data <= 30) return '<span class="badge bg-warning text-dark">Atenção</span>';
                    return '<span class="badge bg-info">Monitorar</span>';
                }
            }
        ],
        order: [[4, 'asc']]
    });
}

function initCriticoTable() {
    criticoTable = $('#criticoTable').DataTable({
        ajax: {
            url: '../estoque/estoque_api.php?action=criticos',
            dataSrc: ''
        },
        columns: [
            { data: 'nome' },
            { data: 'principio_ativo', defaultContent: '-' },
            { 
                data: 'quantidade_atual',
                render: data => `<span class="text-danger fw-bold">${data}</span>`
            },
            { data: 'estoque_minimo' },
            { 
                data: null,
                render: (data, type, row) => {
                    const diff = row.quantidade_atual - row.estoque_minimo;
                    return `<span class="text-danger">${diff}</span>`;
                }
            },
            { 
                data: 'id',
                render: id => `
                    <a href="../estoque/entrada.php?medicamento=${id}" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle"></i> Adicionar
                    </a>
                `
            }
        ],
        order: [[2, 'asc']]
    });
}

function carregarGraficos() {
    // Gráfico de Status
    $.getJSON('relatorios_api.php?action=grafico_estoque_status', function(data) {
        const ctx = document.getElementById('chartStatus').getContext('2d');
        
        if (chartStatus) chartStatus.destroy();
        
        chartStatus = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels || [],
                datasets: [{
                    data: data.valores || [],
                    backgroundColor: ['#0d6efd', '#ffc107', '#dc3545', '#212529']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    });
    
    // Gráfico Top medicamentos
    $.getJSON('relatorios_api.php?action=grafico_top_estoque', function(data) {
        const ctx = document.getElementById('chartTop').getContext('2d');
        
        if (chartTop) chartTop.destroy();
        
        chartTop = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels || [],
                datasets: [{
                    label: 'Quantidade em Estoque',
                    data: data.valores || [],
                    backgroundColor: 'rgba(67, 97, 238, 0.7)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: { beginAtZero: true }
                }
            }
        });
    });
}

function exportarEstoque() {
    window.location.href = 'relatorios_api.php?action=exportar_estoque';
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
