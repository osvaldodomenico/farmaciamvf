<?php
require_once __DIR__ . '/../../templates/header.php';
?>

<script>document.getElementById('page-title').textContent = 'Relatório - Vigilância Sanitária';</script>

<div class="page-title">
    <h1><i class="bi bi-shield-check"></i> Vigilância Sanitária</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Relatórios</li>
            <li class="breadcrumb-item active">Vigilância Sanitária</li>
        </ol>
    </nav>
    <p class="text-muted mb-0">Listagem consolidada para inspeção: Nome do Medicamento, Composição, Quantidade em Estoque e Prateleira.</p>
    <small class="text-muted">Snapshot com base no estoque atual e entradas dentro do período.</small>
    <hr class="mt-3">
</div>

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
            <div class="col-md-3" id="dataInicioWrapper">
                <label class="form-label">Data Início</label>
                <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
            </div>
            <div class="col-md-3" id="dataFimWrapper">
                <label class="form-label">Data Fim</label>
                <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filtrar
                </button>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-secondary w-100" id="btnGerarPDF" title="Gerar PDF">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="2" y="2" width="20" height="20" rx="4" fill="#E41F26"/>
                        <path d="M8.5 16.2c1.2-.3 2.6-1.6 3.6-3.5 1.2-2.2 1.7-4.2 1.8-5.2.1-.6-.2-.9-.6-.9-.4 0-.8.3-1 .8-.3.9-.7 2.4-1.4 3.9-.6 1.4-1.4 2.7-2.1 3.6-.5.7-.9 1-1.5 1.2-.4.1-.7.4-.7.8 0 .5.5.8 1.1.7.3 0 .6-.1.8-.2z" fill="#fff"/>
                        <path d="M13.4 13.2c.7.2 1.8.4 2.7.4 1.3 0 2.2-.4 2.2-1.1 0-.6-.7-1-1.7-1-1.1 0-2.3.3-3.3.7-.4.2-.5.6-.4.9.1.1.3.2.5.1z" fill="#fff"/>
                        <path d="M9.2 12.2c1.1-.4 2.5-.7 3.6-.8.5 0 .7-.2.7-.5 0-.5-.6-.7-1.3-.7-.9.1-2.2.5-3.4 1.1-.5.3-.7.6-.6.9.1.2.5.2 1 0z" fill="#fff"/>
                    </svg>
                </button>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-primary w-100" id="btnImprimir">
                    <i class="bi bi-printer"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Itens do Relatório</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tabelaVigilancia" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Nome do Medicamento</th>
                        <th>Composição</th>
                        <th>Quantidade em Estoque</th>
                        <th>Data de Produção</th>
                        <th>Data de Vencimento</th>
                        <th>Prateleira</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <small class="text-muted">Atualizado em <?php echo date('d/m/Y H:i'); ?></small>
    </div>
 </div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<!-- DataTables Buttons + PDFMake (local libs já no projeto) -->
<link href="<?php echo $base_path; ?>assets/css/lib/data-table/buttons.bootstrap.min.css" rel="stylesheet">
<script src="<?php echo $base_path; ?>templates/includes/lib/data-table/pdfmake.min.js"></script>
<script src="<?php echo $base_path; ?>templates/includes/lib/data-table/vfs_fonts.js"></script>
<script src="<?php echo $base_path; ?>templates/includes/lib/data-table/dataTables.buttons.min.js"></script>
<script src="<?php echo $base_path; ?>templates/includes/lib/data-table/buttons.html5.min.js"></script>

<script>
function getParams() {
    const periodo = $('#periodo').val();
    const data_inicio = $('#data_inicio').val();
    const data_fim = $('#data_fim').val();
    const params = { periodo };
    // Se qualquer data estiver preenchida, força modo personalizado
    if (data_inicio || data_fim) {
        params.periodo = 'custom';
        params.data_inicio = data_inicio || '';
        params.data_fim = data_fim || '';
    }
    return params;
}

$(document).ready(function() {
    $('#periodo').on('change', function() {
        const custom = $(this).val() === 'custom';
        // Mantemos os campos sempre visíveis; apenas sugerimos preenchimento
        if (!custom) {
            // Ajustar datas conforme período para conveniência
            const days = parseInt($(this).val(), 10);
            const end = new Date();
            const start = new Date();
            start.setDate(end.getDate() - days);
            $('#data_inicio').val(start.toISOString().slice(0,10));
            $('#data_fim').val(end.toISOString().slice(0,10));
        }
    });
    
    const tabela = $('#tabelaVigilancia').DataTable({
        ajax: {
            url: 'relatorios_api.php',
            data: function() { 
                const p = getParams();
                p.action = 'listar_vigilancia';
                return p;
            },
            dataSrc: 'data'
        },
        pageLength: 25,
        columns: [
            { data: 'nome' },
            { data: 'composicao', defaultContent: '-' },
            { 
                data: 'estoque_total',
                render: (d) => `<span class="badge ${parseInt(d) === 0 ? 'bg-danger' : 'bg-success'}">${d}</span>`
            },
            { 
                data: 'data_producao',
                render: (d) => d ? new Date(d).toLocaleDateString('pt-BR') : '-'
            },
            { 
                data: 'data_vencimento',
                render: (d) => d ? new Date(d).toLocaleDateString('pt-BR') : '-'
            },
            { data: 'prateleira', defaultContent: '-' }
        ],
        order: [[0, 'asc']],
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'pdfHtml5',
                text: 'Gerar PDF',
                title: 'Relatório - Vigilância Sanitária',
                exportOptions: { columns: [0,1,2,3,4,5] },
                orientation: 'portrait',
                pageSize: 'A4'
            },
            {
                extend: 'print',
                text: 'Imprimir',
                exportOptions: { columns: [0,1,2,3,4,5] }
            }
        ]
    });
    
    $('#formFiltros').on('submit', function(e) {
        e.preventDefault();
        tabela.ajax.reload();
    });
    
    $('#btnGerarPDF').on('click', function() {
        $('.buttons-pdf').click();
    });
    $('#btnImprimir').on('click', function() {
        $('.buttons-print').click();
    });
});
</script>
