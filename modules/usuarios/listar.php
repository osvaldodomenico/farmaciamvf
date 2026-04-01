<?php
require_once '../../templates/header.php';

// Verificar permissão (apenas admin e gerente)
if (!in_array($usuario_nivel, ['admin', 'gerente'])) {
    $_SESSION['error'] = 'Você não tem permissão para acessar esta página.';
    header('Location: ../../dashboard.php');
    exit();
}

// Buscar todos os usuários
$usuarios = fetchAll("
    SELECT 
        id, 
        nome_completo, 
        cpf, 
        login, 
        email, 
        telefone, 
        nivel_acesso, 
        crf,
        ativo,
        created_at
    FROM " . tableName('usuarios') . "
    ORDER BY nome_completo ASC
");
?>

<script>
    document.getElementById('page-title').textContent = 'Usuários';
</script>

<div class="page-title">
    <h1><i class="bi bi-people"></i> Gerenciar Usuários</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Usuários</li>
        </ol>
    </nav>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span style="font-size: 1.125rem;"><i class="bi bi-list-ul"></i> Lista de Usuários</span>
        <a href="adicionar.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Novo Usuário
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tabelaUsuarios" class="table table-hover" style="font-size: 1rem;">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Login</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Nível de Acesso</th>
                        <th>CRF</th>
                        <th>Status</th>
                        <th>Cadastro</th>
                        <th width="120">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $user): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($user['nome_completo']); ?></strong></td>
                        <td><?php echo $user['cpf'] ? htmlspecialchars($user['cpf']) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($user['login']); ?></td>
                        <td><?php echo $user['email'] ? htmlspecialchars($user['email']) : '-'; ?></td>
                        <td><?php echo $user['telefone'] ? htmlspecialchars($user['telefone']) : '-'; ?></td>
                        <td>
                            <?php
                            $badges = [
                                'admin' => 'danger',
                                'gerente' => 'warning',
                                'farmaceutico' => 'info',
                                'atendente' => 'primary',
                                'caixa' => 'secondary'
                            ];
                            $badge = $badges[$user['nivel_acesso']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $badge; ?>" style="font-size: 0.9375rem;">
                                <?php echo ucfirst($user['nivel_acesso']); ?>
                            </span>
                        </td>
                        <td><?php echo $user['crf'] ? htmlspecialchars($user['crf']) : '-'; ?></td>
                        <td>
                            <?php if ($user['ativo']): ?>
                                <span class="badge bg-success" style="font-size: 0.9375rem;">Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-danger" style="font-size: 0.9375rem;">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatarData($user['created_at']); ?></td>
                        <td>
                            <div class="btn-group-actions">
                                <a href="visualizar.php?id=<?php echo $user['id']; ?>" 
                                   class="btn btn-sm btn-info" 
                                   title="Visualizar">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="editar.php?id=<?php echo $user['id']; ?>" 
                                   class="btn btn-sm btn-primary" 
                                   title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($usuario_nivel == 'admin' && $user['id'] != $_SESSION['usuario_id']): ?>
                                <a href="javascript:void(0);" 
                                   onclick="confirmarExclusao('deletar.php?id=<?php echo $user['id']; ?>', 'Deseja realmente desativar este usuário?')" 
                                   class="btn btn-sm btn-danger" 
                                   title="Desativar">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<?php require_once '../../templates/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#tabelaUsuarios').DataTable({
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: -1 }
        ]
    });
});
</script>
