<?php
require_once __DIR__ . '/../../templates/header.php';
?>

<script>document.getElementById('page-title').textContent = 'Clientes';</script>

<div class="page-title">
    <h1><i class="bi bi-person-badge"></i> Gerenciar Clientes</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Clientes</li>
        </ol>
    </nav>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Lista de Clientes</h5>
        <a href="adicionar.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Novo Cliente
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="clientesTable" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Nome Completo</th>
                        <th>CPF</th>
                        <th>Contato</th>
                        <th>Cidade</th>
                        <th>Dispensações</th>
                        <th>Status</th>
                        <th width="120">Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Confirmação Bootstrap -->
<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-labelledby="confirmActionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmActionModalLabel"><i class="bi bi-exclamation-triangle text-danger"></i> Confirmar Ação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="confirmActionModalBody">
                Deseja realmente realizar esta ação?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a id="btnConfirmAction" href="#" class="btn btn-danger">Confirmar</a>
            </div>
        </div>
    </div>
</div>


<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<script>
function showConfirmModal(url, message) {
    const modal = new bootstrap.Modal(document.getElementById('confirmActionModal'));
    document.getElementById('confirmActionModalBody').textContent = message;
    document.getElementById('btnConfirmAction').href = url;
    modal.show();
}

$(document).ready(function() {
    const table = $('#clientesTable').DataTable({
        ajax: {
            url: 'clientes_api.php?action=list',
            dataSrc: 'data'
        },
        columns: [
            { data: 'nome_completo' },
            { 
                data: 'cpf',
                render: data => data || '-'
            },
            { 
                data: null,
                render: (data, type, row) => row.celular || row.telefone || '-'
            },
            { 
                data: 'cidade',
                render: data => data || '-'
            },
            { 
                data: 'total_dispensacoes',
                render: data => `<span class="badge bg-info">${data}</span>`
            },
            { 
                data: 'ativo',
                render: data => data == 1 ? 
                    '<span class="badge bg-success">Ativo</span>' : 
                    '<span class="badge bg-secondary">Inativo</span>'
            },
            {
                data: null,
                orderable: false,
                render: (data, type, row) => `
                    <div class="btn-group-actions">
                        <a href="visualizar.php?id=${row.id}" class="btn btn-sm btn-info" title="Visualizar">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="editar.php?id=${row.id}" class="btn btn-sm btn-primary" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="javascript:void(0)" class="btn btn-sm btn-danger" 
                           onclick="showConfirmModal('deletar.php?id=${row.id}', 'Deseja realmente desativar este cliente?')" title="Desativar">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                `
            }
        ],
        order: [[0, 'asc']]
    });
});
</script>
