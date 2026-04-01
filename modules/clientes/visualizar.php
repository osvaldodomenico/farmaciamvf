<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../templates/header.php';

$id = $_GET['id'] ?? 0;
$cliente = fetchOne("SELECT * FROM " . tableName('clientes') . " WHERE id = ?", [$id]);

if (!$cliente) {
    $_SESSION['error'] = 'Cliente não encontrado.';
    header('Location: listar.php');
    exit;
}

// Buscar histórico de dispensações
$tDispensacoes = tableName('dispensacoes');
$tDispItens = tableName('dispensacoes_itens');
$tUsuarios = tableName('usuarios');
$dispensacoes = fetchAll("
    SELECT d.*, u.nome_completo as atendente,
           (SELECT SUM(quantidade) FROM {$tDispItens} WHERE dispensacao_id = d.id) as total_itens
    FROM {$tDispensacoes} d
    LEFT JOIN {$tUsuarios} u ON d.usuario_id = u.id
    WHERE d.cliente_id = ?
    ORDER BY d.data_dispensacao DESC
    LIMIT 20
", [$id]);

if (!is_array($dispensacoes)) {
    $dispensacoes = [];
}

// Calcular idade
$idade = null;
if (!empty($cliente['data_nascimento']) && $cliente['data_nascimento'] !== '0000-00-00') {
    try {
        $nascimento = new DateTime($cliente['data_nascimento']);
        $hoje = new DateTime();
        $idade = $nascimento->diff($hoje)->y;
    } catch (Exception $e) {
        $idade = null;
    }
}
?>

<script>document.getElementById('page-title').textContent = 'Visualizar Cliente';</script>

<div class="page-title">
    <h1><i class="bi bi-person-badge"></i> Detalhes do Cliente</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="listar.php">Clientes</a></li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-circle"></i> Informações</h5>
            </div>
            <div class="card-body text-center">
                <div class="bg-primary bg-opacity-10 rounded-circle p-4 d-inline-block mb-3">
                    <i class="bi bi-person-fill text-primary" style="font-size: 4rem;"></i>
                </div>
                <h4><?php echo htmlspecialchars($cliente['nome_completo'] ?? ''); ?></h4>
                <?php if (!empty($cliente['cpf'])): ?>
                    <p class="text-muted mb-1">CPF: <?php echo $cliente['cpf']; ?></p>
                <?php endif; ?>
                <?php if ($idade !== null): ?>
                    <p class="text-muted mb-1"><strong><?php echo $idade; ?></strong> anos</p>
                <?php endif; ?>
                <?php if (!empty($cliente['celular'])): ?>
                    <p class="mb-0">
                        <i class="bi bi-telephone"></i> 
                        <?php echo $cliente['celular']; ?>
                    </p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <div class="d-flex gap-2">
                    <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                    <a href="../dispensacoes/nova.php?cliente_id=<?php echo $id; ?>" class="btn btn-success flex-grow-1">
                        <i class="bi bi-prescription2"></i> Dispensar
                    </a>
                </div>
            </div>
        </div>
        

    </div>
    
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Dados Completos</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted">Data de Nascimento</label>
                        <p class="mb-0"><?php 
                            $ts_nascimento = (!empty($cliente['data_nascimento']) && $cliente['data_nascimento'] != '0000-00-00' && $cliente['data_nascimento'] != '0000-00-00 00:00:00') ? strtotime($cliente['data_nascimento']) : false;
                            echo $ts_nascimento ? date('d/m/Y', $ts_nascimento) : '-'; 
                        ?></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted">Sexo</label>
                        <p class="mb-0">
                            <?php 
                            $sexos = ['M' => 'Masculino', 'F' => 'Feminino'];
                            echo $sexos[$cliente['sexo'] ?? ''] ?? 'Não informado';
                            ?>
                        </p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted">RG</label>
                        <p class="mb-0"><?php echo htmlspecialchars($cliente['rg'] ?? '-'); ?></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted">E-mail</label>
                        <p class="mb-0"><?php echo htmlspecialchars($cliente['email'] ?? '-'); ?></p>
                    </div>
                </div>
                
                <hr>
                <h6 class="text-primary"><i class="bi bi-geo-alt"></i> Endereço</h6>
                
                <?php if (!empty($cliente['logradouro'])): ?>
                    <p class="mb-0">
                        <?php echo htmlspecialchars($cliente['logradouro']); ?>
                        <?php echo !empty($cliente['numero']) ? ", " . htmlspecialchars($cliente['numero']) : ''; ?>
                        <?php echo !empty($cliente['complemento']) ? " - " . htmlspecialchars($cliente['complemento']) : ''; ?>
                        <br>
                        <?php echo htmlspecialchars($cliente['bairro'] ?? ''); ?>
                        <?php echo !empty($cliente['cidade']) ? " - " . htmlspecialchars($cliente['cidade']) : ''; ?>
                        <?php echo !empty($cliente['estado']) ? "/" . htmlspecialchars($cliente['estado']) : ''; ?>
                        <?php echo !empty($cliente['cep']) ? " - CEP: " . htmlspecialchars($cliente['cep']) : ''; ?>
                    </p>
                <?php else: ?>
                    <p class="text-muted">Endereço não informado</p>
                <?php endif; ?>
                
                <?php if (!empty($cliente['observacoes'])): ?>
                    <hr>
                    <h6 class="text-primary"><i class="bi bi-chat-text"></i> Observações</h6>
                    <p><?php echo nl2br(htmlspecialchars($cliente['observacoes'] ?? '')); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clipboard2-pulse"></i> Histórico de Dispensações</h5>
                <span class="badge bg-primary"><?php echo count($dispensacoes); ?></span>
            </div>
            <div class="card-body">
                <?php if (empty($dispensacoes)): ?>
                    <p class="text-muted text-center mb-0">Nenhuma dispensação registrada para este cliente.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Número</th>
                                    <th>Data</th>
                                    <th>Itens</th>
                                    <th>Receita</th>
                                    <th>Atendente</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dispensacoes as $d): ?>
                                    <tr>
                                        <td><strong><?php echo $d['numero_dispensacao']; ?></strong></td>
                                        <td><?php 
                                            $ts_disp = strtotime($d['data_dispensacao']);
                                            echo $ts_disp ? date('d/m/Y H:i', $ts_disp) : '-'; 
                                        ?></td>
                                        <td><span class="badge bg-info"><?php echo $d['total_itens'] ?? 0; ?> un.</span></td>
                                        <td>
                                            <?php echo $d['receita_medica'] ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>'; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($d['atendente'] ?? '-'); ?></td>
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

<div class="mt-3">
    <a href="listar.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
