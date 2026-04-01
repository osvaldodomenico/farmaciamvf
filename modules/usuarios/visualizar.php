<?php
require_once '../../templates/header.php';

// Verificar permissão
if (!in_array($usuario_nivel, ['admin', 'gerente'])) {
    $_SESSION['error'] = 'Você não tem permissão para acessar esta página.';
    header('Location: ../../dashboard.php');
    exit();
}

$id = $_GET['id'] ?? 0;

// Buscar usuário
$usuario = fetchOne("SELECT * FROM " . tableName('usuarios') . " WHERE id = ?", [$id]);

if (!$usuario) {
    $_SESSION['error'] = 'Usuário não encontrado.';
    header('Location: listar.php');
    exit();
}

// Buscar logs do usuário
$logs = fetchAll("
    SELECT 
        acao,
        data_log,
        ip_address
    FROM " . tableName('logs_sistema') . "
    WHERE usuario_id = ?
    ORDER BY data_log DESC
    LIMIT 10
", [$id]);
?>

<script>
    document.getElementById('page-title').textContent = 'Visualizar Usuário';
</script>

<div class="page-title">
    <h1><i class="bi bi-person-circle"></i> Detalhes do Usuário</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="listar.php">Usuários</a></li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </nav>
</div>

<div class="row g-4">
    <!-- Informações do Usuário -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-info-circle"></i> Informações Pessoais</span>
                <div>
                    <a href="editar.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted mb-1" style="font-size: 0.9375rem;">Nome Completo</label>
                        <p class="mb-0" style="font-size: 1.0625rem; font-weight: 500;">
                            <?php echo htmlspecialchars($usuario['nome_completo']); ?>
                        </p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="text-muted mb-1" style="font-size: 0.9375rem;">CPF</label>
                        <p class="mb-0" style="font-size: 1.0625rem;">
                            <?php echo $usuario['cpf'] ? htmlspecialchars($usuario['cpf']) : '-'; ?>
                        </p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="text-muted mb-1" style="font-size: 0.9375rem;">Login</label>
                        <p class="mb-0" style="font-size: 1.0625rem;">
                            <code><?php echo htmlspecialchars($usuario['login']); ?></code>
                        </p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="text-muted mb-1" style="font-size: 0.9375rem;">Email</label>
                        <p class="mb-0" style="font-size: 1.0625rem;">
                            <?php echo $usuario['email'] ? htmlspecialchars($usuario['email']) : '-'; ?>
                        </p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="text-muted mb-1" style="font-size: 0.9375rem;">Telefone</label>
                        <p class="mb-0" style="font-size: 1.0625rem;">
                            <?php echo $usuario['telefone'] ? htmlspecialchars($usuario['telefone']) : '-'; ?>
                        </p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="text-muted mb-1" style="font-size: 0.9375rem;">CRF</label>
                        <p class="mb-0" style="font-size: 1.0625rem;">
                            <?php echo $usuario['crf'] ? htmlspecialchars($usuario['crf']) : '-'; ?>
                        </p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="text-muted mb-1" style="font-size: 0.9375rem;">Nível de Acesso</label>
                        <p class="mb-0">
                            <?php
                            $badges = [
                                'admin' => 'danger',
                                'gerente' => 'warning',
                                'farmaceutico' => 'info',
                                'atendente' => 'primary',
                                'caixa' => 'secondary'
                            ];
                            $badge = $badges[$usuario['nivel_acesso']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $badge; ?>" style="font-size: 1rem;">
                                <?php echo ucfirst($usuario['nivel_acesso']); ?>
                            </span>
                        </p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="text-muted mb-1" style="font-size: 0.9375rem;">Status</label>
                        <p class="mb-0">
                            <?php if ($usuario['ativo']): ?>
                                <span class="badge bg-success" style="font-size: 1rem;">Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-danger" style="font-size: 1rem;">Inativo</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="text-muted mb-1" style="font-size: 0.9375rem;">Data de Cadastro</label>
                        <p class="mb-0" style="font-size: 1.0625rem;">
                            <?php echo formatarDataHora($usuario['created_at']); ?>
                        </p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="text-muted mb-1" style="font-size: 0.9375rem;">Última Atualização</label>
                        <p class="mb-0" style="font-size: 1.0625rem;">
                            <?php echo formatarDataHora($usuario['updated_at']); ?>
                        </p>
                    </div>
                    
                    <?php if ($usuario['ultimo_acesso']): ?>
                    <div class="col-md-12">
                        <label class="text-muted mb-1" style="font-size: 0.9375rem;">Último Acesso</label>
                        <p class="mb-0" style="font-size: 1.0625rem;">
                            <?php echo formatarDataHora($usuario['ultimo_acesso']); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Estatísticas -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-bar-chart"></i> Estatísticas
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted mb-1" style="font-size: 0.9375rem;">Total de Acessos</label>
                    <h3 class="mb-0" style=font-size: 1.75rem;">
                        <?php 
                        $total_acessos = fetchColumn("SELECT COUNT(*) FROM " . tableName('logs_sistema') . " WHERE usuario_id = ? AND acao LIKE '%login%'", [$id]);
                        echo number_format($total_acessos ?? 0);
                        ?>
                    </h3>
                </div>
                
                <div class="mb-3">
                    <label class="text-muted mb-1" style="font-size: 0.9375rem;">Ações Registradas</label>
                    <h3 class="mb-0" style="font-size: 1.75rem;">
                        <?php 
                        $total_acoes = fetchColumn("SELECT COUNT(*) FROM " . tableName('logs_sistema') . " WHERE usuario_id = ?", [$id]);
                        echo number_format($total_acoes ?? 0);
                        ?>
                    </h3>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="bi bi-shield-check"></i> Segurança
            </div>
            <div class="card-body">
                <p style="font-size: 1rem;">
                    <i class="bi bi-lock text-success"></i> Senha criptografada
                </p>
                <p style="font-size: 1rem;">
                    <i class="bi bi-clock-history text-info"></i> 
                    Cadastrado há <?php 
                        $diff = date_diff(new DateTime($usuario['created_at']), new DateTime());
                        echo $diff->days . ' dias';
                    ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Últimas Ações -->
<div class="row g-4 mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history"></i> Últimas 10 Ações
            </div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                    <p class="text-muted mb-0" style="font-size: 1rem;">Nenhuma ação registrada.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" style="font-size: 1rem;">
                            <thead>
                                <tr>
                                    <th>Ação</th>
                                    <th>Data/Hora</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['acao']); ?></td>
                                    <td><?php echo formatarDataHora($log['data_log']); ?></td>
                                    <td><code><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></code></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
    <a href="listar.php" class="btn btn-secondary" style="font-size: 1rem;">
        <i class="bi bi-arrow-left"></i> Voltar para Lista
    </a>
</div>

<?php require_once '../../templates/footer.php'; ?>
