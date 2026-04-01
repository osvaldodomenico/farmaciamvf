<?php
require_once __DIR__ . '/../../templates/header.php';

// Verificar permissão
if (!in_array($usuario_nivel, ['admin', 'gerente', 'farmaceutico'])) {
    $_SESSION['error'] = 'Sem permissão para acessar esta página.';
    header('Location: ../../dashboard.php');
    exit;
}
?>

<script>document.getElementById('page-title').textContent = 'Medicamentos';</script>

<div class="page-title">
    <h1><i class="bi bi-capsule"></i> Gerenciar Medicamentos</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Medicamentos</li>
        </ol>
    </nav>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Lista de Medicamentos</h5>
        <a href="adicionar.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Novo Medicamento
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="medicamentosTable" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Composição</th>
                        <th>Princípio Ativo</th>
                        <th>Forma</th>
                        <th>Estoque</th>
                        <th>Est. Mínimo</th>
                        <th>Status</th>
                        <th width="120">Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Confirmação Bootstrap -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel"><i class="bi bi-exclamation-triangle text-danger"></i> Confirmar Desativação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Deseja realmente desativar este medicamento? ele não aparecerá mais nas buscas e dispensações.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a id="btnConfirmDelete" href="#" class="btn btn-danger">Sim, Desativar</a>
            </div>
        </div>
    </div>
</div>


<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<script>
function confirmarDesativacao(url) {
    const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
    document.getElementById('btnConfirmDelete').href = url;
    modal.show();
}

$(document).ready(function() {
    const table = $('#medicamentosTable').DataTable({
        ajax: {
            url: 'medicamentos_api.php?action=list',
            dataSrc: 'data'
        },
        pageLength: 10,
        columns: [
            { data: 'nome' },
            { 
                data: null,
                render: (data, type, row) => {
                    let conc = row.dosagem_concentracao || '';
                    if (row.unidade_medida) {
                        conc += (row.unidade_medida === 'PORCENTAGEM' ? '%' : ' ' + row.unidade_medida);
                    }
                    return conc || '-';
                }
            },
            { data: 'principio_ativo' },
            { 
                data: 'forma_farmaceutica',
                render: data => data ? data.charAt(0).toUpperCase() + data.slice(1) : '-'
            },
            { 
                data: 'estoque_total',
                render: (data, type, row) => {
                    const critical = parseInt(data) <= parseInt(row.estoque_minimo);
                    const badge = critical ? 'bg-danger' : 'bg-success';
                    return `<span class="badge ${badge}">${data}</span>`;
                }
            },
            { data: 'estoque_minimo' },
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
                           onclick="confirmarDesativacao('deletar.php?id=${row.id}')" title="Desativar">
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
