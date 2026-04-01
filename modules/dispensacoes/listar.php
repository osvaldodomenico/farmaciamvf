<?php
require_once __DIR__ . '/../../templates/header.php';
?>

<!-- pdfmake (para gerar PDF do comprovante) -->
<script src="<?php echo $base_path; ?>templates/includes/lib/data-table/pdfmake.min.js"></script>
<script src="<?php echo $base_path; ?>templates/includes/lib/data-table/vfs_fonts.js"></script>

<script>document.getElementById('page-title').textContent = 'Dispensações';</script>

<div class="page-title">
    <h1><i class="bi bi-clipboard2-pulse"></i> Dispensações</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Dispensações</li>
        </ol>
    </nav>
</div>

<!-- Cards de estatísticas -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Hoje</h6>
                        <h2 class="mb-0" id="disp-hoje">0</h2>
                    </div>
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                        <i class="bi bi-calendar-check text-primary" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Este Mês</h6>
                        <h2 class="mb-0" id="disp-mes">0</h2>
                    </div>
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                        <i class="bi bi-clipboard2-pulse text-primary" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Itens Dispensados</h6>
                        <h2 class="mb-0" id="itens-mes">0</h2>
                    </div>
                    <div class="bg-info bg-opacity-10 rounded-circle p-3">
                        <i class="bi bi-capsule text-info" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Clientes Atendidos</h6>
                        <h2 class="mb-0" id="clientes-mes">0</h2>
                    </div>
                    <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                        <i class="bi bi-people text-warning" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Histórico de Dispensações</h5>
        <a href="nova.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nova Dispensação
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="dispensacoesTable" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Data/Hora</th>
                        <th>Cliente</th>
                        <th>Itens</th>
                        <th>Quantidade</th>
                        <th>Receita</th>
                        <th>Atendente</th>
                        <th width="100">Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Detalhes -->
<div class="modal fade" id="modalDetalhes" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clipboard2-pulse"></i> Detalhes da Dispensação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalhes-content">
                <!-- Conteúdo dinâmico -->
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<script>
$(document).ready(function() {
    // Carregar estatísticas
    $.getJSON('dispensacoes_api.php?action=estatisticas&v=' + new Date().getTime(), function(data) {
        $('#disp-hoje').text(data.dispensacoes_hoje);
        $('#disp-mes').text(data.dispensacoes_mes);
        $('#itens-mes').text(data.itens_dispensados_mes);
        $('#clientes-mes').text(data.clientes_atendidos_mes);
    }).fail(function() {
        console.error('Erro ao carregar estatísticas');
    });
    
    const table = $('#dispensacoesTable').DataTable({
        ajax: {
            url: 'dispensacoes_api.php?action=list',
            dataSrc: function(json) {
                if (!json.data) {
                    console.error('API Response missing data:', json);
                    return [];
                }
                return json.data;
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables Error:', error, thrown);
                // alert('Erro ao carregar dispensações. Verifique o console.');
            }
        },
        pageLength: 10,
        columns: [
            { 
                data: 'numero_dispensacao',
                render: data => `<strong>${data || '-'}</strong>`
            },
            { 
                data: 'data_dispensacao',
                render: data => data ? new Date(data).toLocaleString('pt-BR') : '-'
            },
            { 
                data: 'cliente_nome',
                render: data => data || '<em class="text-muted">Não identificado</em>'
            },
            { 
                data: 'total_itens',
                render: data => `<span class="badge bg-secondary">${data}</span>`
            },
            { 
                data: 'quantidade_total',
                render: data => `<span class="badge bg-info">${data || 0} un.</span>`
            },
            { 
                data: 'receita_medica',
                render: data => data == 1 ? 
                    '<span class="badge bg-success">Sim</span>' : 
                    '<span class="badge bg-secondary">Não</span>'
            },
            { data: 'usuario_nome' },
            {
                data: null,
                orderable: false,
                render: (data, type, row) => `
                    <button onclick="verDetalhes(${row.id})" class="btn btn-sm btn-info" title="Ver Detalhes">
                        <i class="bi bi-eye"></i>
                    </button>
                    <a href="imprimir.php?id=${row.id}" class="btn btn-sm btn-secondary" title="Imprimir" target="_blank">
                        <i class="bi bi-printer"></i>
                    </a>
                `
            }
        ],
        order: [[1, 'desc']]
    });
});

function isPdfReady() {
    return !!(window.pdfMake && pdfMake.createPdf && pdfMake.vfs);
}

function verDetalhes(id) {
    $.getJSON(`dispensacoes_api.php?action=get&id=${id}`, function(disp) {
        let html = `
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Número:</strong> ${disp.numero_dispensacao}<br>
                    <strong>Data:</strong> ${new Date(disp.data_dispensacao).toLocaleString('pt-BR')}<br>
                    <strong>Cliente:</strong> ${disp.cliente_nome || 'Não identificado'}
                    ${disp.cliente_cpf ? ` (CPF: ${disp.cliente_cpf})` : ''}
                </div>
                <div class="col-md-6">
                    <strong>Atendente:</strong> ${disp.usuario_nome}<br>
                    <strong>Receita:</strong> ${disp.receita_medica == 1 ? 'Sim' : 'Não'}
                    ${disp.numero_receita ? ` - Nº ${disp.numero_receita}` : ''}<br>
                    ${disp.medico_responsavel ? `<strong>Médico:</strong> ${disp.medico_responsavel}` : ''}
                    <div class="mt-2 d-flex gap-2">
                        <a href="imprimir.php?id=${disp.id}&print=1" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-printer"></i> Imprimir
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="baixarPdfDispensacao(${disp.id})">
                            <i class="bi bi-file-earmark-pdf"></i> Gerar PDF
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="whatsappPdfDispensacao(${disp.id})">
                            <i class="bi bi-whatsapp"></i> WhatsApp (PDF)
                        </button>
                    </div>
                </div>
            </div>
            ${disp.observacoes ? `<p><strong>Observações:</strong> ${disp.observacoes}</p>` : ''}
            <hr>
            <h6><i class="bi bi-capsule"></i> Itens Dispensados</h6>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Medicamento</th>
                        <th>Lote</th>
                        <th>Validade</th>
                        <th>Qtd</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        disp.itens.forEach(item => {
            html += `
                <tr>
                    <td>${item.medicamento_nome} ${item.principio_ativo ? `(${item.principio_ativo})` : ''}</td>
                    <td>${item.lote}</td>
                    <td>${new Date(item.data_validade).toLocaleDateString('pt-BR')}</td>
                    <td><span class="badge bg-info">${item.quantidade}</span></td>
                </tr>
            `;
        });
        
        html += '</tbody></table>';
        
    $('#detalhes-content').html(html);
    new bootstrap.Modal(document.getElementById('modalDetalhes')).show();
    });
}

function montarDocPdf(disp) {
    const itensTableBody = [
        [
            {text:'Medicamento', bold:true, alignment:'center'},
            {text:'Qtd', bold:true, alignment:'center'},
            {text:'Apresentação', bold:true, alignment:'center'},
            {text:'Lote', bold:true, alignment:'center'},
            {text:'Validade', bold:true, alignment:'center'}
        ]
    ];
    (disp.itens || []).forEach(item => {
        const nome = (item.medicamento_nome || '') + (item.principio_ativo ? ` (${item.principio_ativo})` : '');
        const qtd = String(item.quantidade != null ? item.quantidade : 0);
        const apresentacao = (() => {
            const conc = item.dosagem_concentracao || '';
            const unidade = item.unidade_medida ? (item.unidade_medida === 'PORCENTAGEM' ? '%' : ` ${item.unidade_medida}`) : '';
            const forma = item.forma_farmaceutica ? ` / ${String(item.forma_farmaceutica).charAt(0).toUpperCase() + String(item.forma_farmaceutica).slice(1)}` : '';
            const base = (conc + unidade).trim();
            return (base ? base : '-') + (forma || '');
        })();
        const lote = item.lote || '-';
        const validade = item.data_validade ? new Date(item.data_validade).toLocaleDateString('pt-BR') : '-';
        itensTableBody.push([
            nome,
            { text: qtd, alignment: 'center' },
            apresentacao,
            { text: lote, alignment: 'center' },
            { text: validade, alignment: 'center' }
        ]);
    });
    const doc = {
        pageSize: 'A4',
        pageMargins: [40, 40, 40, 60],
        content: [
            { text: 'Comprovante de Dispensação', style: 'title' },
            {
                columns: [
                    [
                        { text: `Número: ${String(disp.numero_dispensacao || '-')}` },
                        { text: `Data: ${disp.data_dispensacao ? new Date(disp.data_dispensacao).toLocaleString('pt-BR') : '-'}` },
                        { text: `Cliente: ${String(disp.cliente_nome || 'Não identificado')}` }
                    ],
                    [
                        { text: `Atendente: ${String(disp.usuario_nome || '-')}` },
                        { text: `Receita: ${disp.receita_medica == 1 ? 'Sim' : 'Não'}${disp.numero_receita ? ' - Nº '+String(disp.numero_receita) : ''}` },
                        disp.medico_responsavel ? { text: `Médico: ${String(disp.medico_responsavel)}` } : { text: ' ' }
                    ]
                ]
            },
            { text: 'Itens Dispensados', style: 'section' },
            {
                table: { headerRows: 1, widths: ['*', 'auto', 'auto', 'auto', 'auto'], body: itensTableBody },
                layout: 'lightHorizontalLines'
            },
            disp.observacoes ? { text: `Observações: ${String(disp.observacoes)}`, margin: [0, 10, 0, 0] } : { text: ' ' }
        ],
        styles: {
            title: { fontSize: 16, bold: true, margin: [0,0,0,10] },
            section: { fontSize: 12, bold: true, margin: [0,10,0,6] }
        },
        defaultStyle: { fontSize: 11 }
    };
    return doc;
}

function baixarPdfDispensacao(id) {
    $.getJSON(`dispensacoes_api.php?action=get&id=${id}`, function(disp) {
        try {
            if (!window.pdfMake || !pdfMake.createPdf || !pdfMake.vfs) {
                toastr.error('Biblioteca de PDF não carregada');
                return;
            }
            const doc = montarDocPdf(disp);
            const nome = `Comprovante_${String(disp.numero_dispensacao || 'DISP')}.pdf`;
            pdfMake.createPdf(doc).download(nome);
        } catch (e) {
            toastr.error('Falha ao gerar PDF');
        }
    });
}

function whatsappPdfDispensacao(id) {
    $.getJSON(`dispensacoes_api.php?action=get&id=${id}`, async function(disp) {
        try {
            if (!window.pdfMake || !pdfMake.createPdf || !pdfMake.vfs) {
                toastr.error('Biblioteca de PDF não carregada');
                return;
            }
            const doc = montarDocPdf(disp);
            const nome = `Comprovante_${String(disp.numero_dispensacao || 'DISP')}.pdf`;
            pdfMake.createPdf(doc).getBlob(async function(blob) {
                try {
                    if (!blob || !blob.size) throw new Error('Blob vazio');
                    const file = new File([blob], nome, { type: 'application/pdf' });
                    if (navigator.canShare && navigator.canShare({ files: [file] })) {
                        await navigator.share({
                            files: [file],
                            text: `Comprovante de dispensação ${String(disp.numero_dispensacao || '')}`
                        });
                    } else {
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = nome;
                        a.click();
                        URL.revokeObjectURL(url);
                        toastr.info('PDF baixado. Anexe o arquivo no WhatsApp.');
                        window.open(`https://wa.me/?text=${encodeURIComponent('Comprovante de dispensação ' + String(disp.numero_dispensacao || ''))}`, '_blank');
                    }
                } catch (e) {
                    toastr.error('Não foi possível compartilhar. Baixando o PDF para anexar no WhatsApp.');
                    pdfMake.createPdf(doc).download(nome);
                    window.open(`https://wa.me/?text=${encodeURIComponent('Comprovante de dispensação ' + String(disp.numero_dispensacao || ''))}`, '_blank');
                }
            });
        } catch (e) {
            toastr.error('Falha ao gerar PDF');
        }
    });
}
</script>
