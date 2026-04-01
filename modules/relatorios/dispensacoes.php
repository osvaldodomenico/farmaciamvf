<?php
require_once __DIR__ . '/../../templates/header.php';
?>

<script>document.getElementById('page-title').textContent = 'Relatório de Dispensações';</script>

<div class="page-title">
    <h1><i class="bi bi-file-earmark-text"></i> Relatório de Dispensações</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Relatório de Dispensações</li>
        </ol>
    </nav>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form id="formFiltros" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Período</label>
                <select class="form-select" id="periodo" name="periodo">
                    <option value="7">Últimos 7 dias</option>
                    <option value="30" selected>Últimos 30 dias</option>
                    <option value="90">Últimos 90 dias</option>
                    <option value="365">Último ano</option>
                    <option value="custom">Personalizado</option>
                </select>
            </div>
            <div class="col-md-2" id="dataInicioWrapper" style="display: none;">
                <label class="form-label">Data Início</label>
                <input type="date" class="form-control" id="data_inicio" name="data_inicio">
            </div>
            <div class="col-md-2" id="dataFimWrapper" style="display: none;">
                <label class="form-label">Data Fim</label>
                <input type="date" class="form-control" id="data_fim" name="data_fim">
            </div>
            <div class="col-md-3">
                <label class="form-label">Cliente</label>
                <select class="form-select" id="cliente_id" name="cliente_id">
                    <option value="">Todos</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filtrar
                </button>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-secondary w-100" onclick="exportarPDF()">
                    <i class="bi bi-file-pdf"></i> Exportar PDF
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Cards de resumo -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h2 class="mb-0 text-primary" id="total-dispensacoes">0</h2>
                <small class="text-muted">Dispensações</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h2 class="mb-0 text-primary" id="total-itens">0</h2>
                <small class="text-muted">Itens Dispensados</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h2 class="mb-0 text-info" id="total-clientes">0</h2>
                <small class="text-muted">Clientes Atendidos</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h2 class="mb-0 text-warning" id="media-diaria">0</h2>
                <small class="text-muted">Média Diária</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Gráfico de evolução -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up-arrow"></i> Evolução das Dispensações</h5>
            </div>
            <div class="card-body">
                <canvas id="chartEvolucao" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Top medicamentos -->
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-capsule"></i> Medicamentos Mais Dispensados</h5>
            </div>
            <div class="card-body" id="topMedicamentos" style="max-height: 350px; overflow-y: auto;"></div>
        </div>
    </div>
</div>

<!-- Tabela de dispensações -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-table"></i> Detalhamento das Dispensações</h5>
    </div>
    <div class="card-body">
        <table id="dispensacoesTable" class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th>Medicamentos</th>
                    <th>Total Itens</th>
                    <th>Atendente</th>
                    <th>Ações</th>
                </tr>
            </thead>
        </table>
    </div>
</div>


<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script>
let chartEvolucao;

$(document).ready(function() {
    // Toggle datas personalizadas
    $('#periodo').change(function() {
        if ($(this).val() === 'custom') {
            $('#dataInicioWrapper, #dataFimWrapper').slideDown();
        } else {
            $('#dataInicioWrapper, #dataFimWrapper').slideUp();
        }
    });
    
    // Inicializar Select2 para cliente
    $('#cliente_id').select2({
        theme: 'bootstrap-5',
        ajax: {
            url: '../clientes/clientes_api.php',
            dataType: 'json',
            delay: 250,
            data: function(params) { return { action: 'search', q: params.term }; },
            processResults: function(data) {
                return {
                    results: data.map(item => ({ id: item.id, text: item.nome_completo }))
                };
            }
        },
        minimumInputLength: 2,
        placeholder: 'Todos clientes',
        allowClear: true
    });
    
    // Inicializar DataTable
    initDataTable();
    
    // Carregar dados (apenas gráficos e resumo, pois a tabela carrega sozinha no init)
    carregarDados(false);
    
    // Submit do formulário
    $('#formFiltros').submit(function(e) {
        e.preventDefault();
        carregarDados(true);
    });
});

function getParams() {
    return {
        periodo: $('#periodo').val(),
        data_inicio: $('#data_inicio').val(),
        data_fim: $('#data_fim').val(),
        cliente_id: $('#cliente_id').val() || ''
    };
}

function carregarDados(reloadTable = true) {
    const params = new URLSearchParams(getParams());
    
    // Carregar resumo
    $.getJSON('relatorios_api.php?action=resumo_dispensacoes&' + params.toString(), function(data) {
        $('#total-dispensacoes').text(data.total_dispensacoes || 0);
        $('#total-itens').text(data.total_itens || 0);
        $('#total-clientes').text(data.total_clientes || 0);
        $('#media-diaria').text(Math.round(data.media_diaria || 0));
    }).fail(function() {
        toastr.error('Erro ao carregar resumo de dispensações');
    });
    
    // Carregar gráfico
    $.getJSON('relatorios_api.php?action=grafico_dispensacoes&' + params.toString(), function(data) {
        renderizarGrafico(data);
    }).fail(function() {
        $('#chartEvolucao').parent().html('<p class="text-danger text-center">Falha ao carregar gráfico</p>');
    });
    
    // Carregar top medicamentos
    $.getJSON('relatorios_api.php?action=top_medicamentos&' + params.toString(), function(data) {
        renderizarTopMedicamentos(data);
    }).fail(function() {
        $('#topMedicamentos').html('<p class="text-danger text-center">Falha ao carregar top medicamentos</p>');
    });
    
    // Recarregar tabela se solicitado
    if (reloadTable && $.fn.DataTable.isDataTable('#dispensacoesTable')) {
        $('#dispensacoesTable').DataTable().ajax.reload();
    }
}

function initDataTable() {
    $('#dispensacoesTable').DataTable({
        ajax: {
            url: 'relatorios_api.php',
            dataSrc: 'data',
            data: function(d) {
                d.action = 'listar_dispensacoes';
                Object.assign(d, getParams());
            }
        },
        columns: [
            { data: 'id' },
            { 
                data: 'data_dispensacao',
                render: data => {
                    const dt = new Date(data);
                    return dt.toLocaleDateString('pt-BR');
                }
            },
            { data: 'cliente_nome' },
            { 
                data: 'medicamentos',
                render: data => data ? data.substring(0, 50) + (data.length > 50 ? '...' : '') : '-'
            },
            { data: 'total_itens' },
            { data: 'usuario_nome' },
            { 
                data: 'id',
                render: id => `
                    <a href="../dispensacoes/visualizar.php?id=${id}" class="btn btn-sm btn-outline-primary" title="Visualizar">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="../dispensacoes/imprimir.php?id=${id}" class="btn btn-sm btn-outline-secondary" title="Imprimir" target="_blank">
                        <i class="bi bi-printer"></i>
                    </a>
                `
            }
        ],
        order: [[1, 'desc']]
    });
}

function renderizarGrafico(data) {
    const ctx = document.getElementById('chartEvolucao').getContext('2d');
    
    if (chartEvolucao) {
        chartEvolucao.destroy();
    }
    
    chartEvolucao = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels || [],
            datasets: [{
                label: 'Dispensações',
                data: data.valores || [],
                backgroundColor: 'rgba(67, 97, 238, 0.2)',
                borderColor: 'rgba(67, 97, 238, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
}

function renderizarTopMedicamentos(data) {
    if (!data || data.length === 0) {
        $('#topMedicamentos').html('<p class="text-muted text-center mb-0">Nenhuma dispensação no período</p>');
        return;
    }
    
    let html = '';
    data.forEach((med, index) => {
        const percent = Math.round((med.quantidade / data[0].quantidade) * 100);
        html += `
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <strong>${index + 1}. ${med.nome}</strong>
                    <span class="badge bg-primary">${med.quantidade}</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar" role="progressbar" style="width: ${percent}%"></div>
                </div>
            </div>
        `;
    });
    
    $('#topMedicamentos').html(html);
}

function exportarPDF() {
    const params = new URLSearchParams(getParams());
    params.append('action', 'exportar_dispensacoes_pdf');
    window.open('relatorios_api.php?' + params.toString(), '_blank');
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
