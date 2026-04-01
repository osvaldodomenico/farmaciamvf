<?php
session_start();
// Habilitar erros temporariamente para depuração no servidor online
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (ob_get_level() > 0) ob_end_clean(); 

require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php');
    exit;
}

$id = $_GET['id'] ?? 0;
$tDisp = tableName('dispensacoes');
$tClientes = tableName('clientes');
$tUsuarios = tableName('usuarios');
$tItens = tableName('dispensacoes_itens');
$tMedicamentos = tableName('medicamentos');
$tEstoque = tableName('estoque');
$tConfig = tableName('configuracoes');

$dispensacao = fetchOne("
    SELECT 
        d.*,
        p.nome_completo as cliente_nome,
        p.cpf as cliente_cpf,
        p.logradouro,
        p.numero,
        p.bairro,
        p.cidade,
        p.estado,
        u.nome_completo as usuario_nome
    FROM {$tDisp} d
    LEFT JOIN {$tClientes} p ON d.cliente_id = p.id
    LEFT JOIN {$tUsuarios} u ON d.usuario_id = u.id
    WHERE d.id = ?
", [$id]);

if (!$dispensacao) {
    die('Dispensação não encontrada.');
}

$itens = fetchAll("
    SELECT 
        di.*,
        m.nome as medicamento_nome,
        m.principio_ativo,
        m.dosagem_concentracao,
        m.unidade_medida,
        m.forma_farmaceutica,
        e.lote,
        e.data_validade
    FROM {$tItens} di
    LEFT JOIN {$tMedicamentos} m ON di.medicamento_id = m.id
    LEFT JOIN {$tEstoque} e ON di.estoque_id = e.id
    WHERE di.dispensacao_id = ?
", [$id]);

// Configurações da farmácia
try {
    $tConfig = tableName('configuracoes');
    $farmacia_nome = fetchColumn("SELECT valor FROM {$tConfig} WHERE chave = 'farmacia_nome'") ?: 'Farmácia Mão Amiga';
} catch (Exception $e) {
    $farmacia_nome = 'Farmácia Mão Amiga';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dispensação <?php echo $dispensacao['numero_dispensacao']; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            font-size: 12pt;
            padding: 20px;
            max-width: 210mm;
            margin: 0 auto;
        }
        .header { 
            text-align: center; 
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 { font-size: 18pt; margin-bottom: 5px; }
        .header p { font-size: 10pt; color: #666; }
        .info-section { 
            display: flex; 
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .info-box h3 { 
            font-size: 10pt; 
            color: #666; 
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        table { 
            width: 100%; 
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px;
            text-align: left;
        }
        th { text-align: center; }
        th { 
            background: #333; 
            color: white;
            font-weight: 600;
        }
        tr:nth-child(even) { background: #f9f9f9; }
        .receita-info {
            background: #fff3cd;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
            margin-bottom: 20px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .assinaturas {
            display: flex;
            justify-content: space-around;
            margin-top: 60px;
        }
        .assinatura {
            text-align: center;
            width: 200px;
        }
        .assinatura-linha {
            border-top: 1px solid #333;
            padding-top: 5px;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 14pt; cursor: pointer;">
            🖨️ Imprimir
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 14pt; cursor: pointer; margin-left: 10px;">
            ✖ Fechar
        </button>
    </div>
    <script>
    (function(){
        const params = new URLSearchParams(window.location.search);
        if (params.get('print') === '1') {
            setTimeout(function(){ window.print(); }, 300);
        }
    })();
    </script>

    <div class="header">
        <h1><?php echo htmlspecialchars($farmacia_nome); ?></h1>
        <p>Sistema de Gestão de Medicamentos Comunitários</p>
    </div>
    
    <h2 style="text-align: center; margin-bottom: 20px;">COMPROVANTE DE DISPENSAÇÃO</h2>
    
    <div class="info-section">
        <div class="info-box">
            <h3>Dispensação</h3>
            <strong><?php echo $dispensacao['numero_dispensacao']; ?></strong><br>
            <?php echo date('d/m/Y H:i', strtotime($dispensacao['data_dispensacao'])); ?>
        </div>
        <div class="info-box">
            <h3>Cliente</h3>
            <strong><?php echo htmlspecialchars($dispensacao['cliente_nome'] ?? 'Não identificado'); ?></strong><br>
            <?php if ($dispensacao['cliente_cpf']): ?>
                CPF: <?php echo $dispensacao['cliente_cpf']; ?>
            <?php endif; ?>
        </div>
        <div class="info-box">
            <h3>Atendente</h3>
            <?php echo htmlspecialchars($dispensacao['usuario_nome']); ?>
        </div>
    </div>
    
    <?php if ($dispensacao['receita_medica']): ?>
    <div class="receita-info">
        <strong>📋 RECEITA MÉDICA</strong><br>
        <?php if ($dispensacao['numero_receita']): ?>
            Número: <?php echo htmlspecialchars($dispensacao['numero_receita']); ?><br>
        <?php endif; ?>
        <?php if ($dispensacao['medico_responsavel']): ?>
            Médico: <?php echo htmlspecialchars($dispensacao['medico_responsavel']); ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <table>
        <thead>
            <tr>
                <th>Medicamento</th>
                <th style="text-align:center;">Qtd</th>
                <th>Apresentação</th>
                <th style="text-align:center;">Lote</th>
                <th style="text-align:center;">Validade</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($itens as $item): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($item['medicamento_nome']); ?></strong>
                        <?php if ($item['principio_ativo']): ?>
                            <br><small><?php echo htmlspecialchars($item['principio_ativo']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;"><strong><?php echo $item['quantidade']; ?></strong></td>
                    <td>
                        <?php 
                            $conc = $item['dosagem_concentracao'] ?? '';
                            if ($item['unidade_medida']) {
                                $conc .= ($item['unidade_medida'] === 'PORCENTAGEM' ? '%' : ' ' . $item['unidade_medida']);
                            }
                            echo htmlspecialchars($conc ?: '-');
                        ?>
                        <?php if ($item['forma_farmaceutica']): ?>
                            / <?php echo ucfirst($item['forma_farmaceutica']); ?>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;"><?php echo htmlspecialchars($item['lote']); ?></td>
                    <td style="text-align: center;"><?php echo date('d/m/Y', strtotime($item['data_validade'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <p><strong>Total de itens dispensados:</strong> <?php echo $dispensacao['quantidade_total']; ?> unidades</p>
    
    <?php if ($dispensacao['observacoes']): ?>
        <p style="margin-top: 15px;"><strong>Observações:</strong><br>
        <?php echo nl2br(htmlspecialchars($dispensacao['observacoes'])); ?></p>
    <?php endif; ?>
    
    <div class="assinaturas">
        <div class="assinatura">
            <div class="assinatura-linha">Cliente/Responsável</div>
        </div>
        <div class="assinatura">
            <div class="assinatura-linha">Atendente</div>
        </div>
    </div>
    
    <div class="footer">
        <p style="text-align: center; font-size: 9pt; color: #666;">
            Documento emitido em <?php echo date('d/m/Y H:i:s'); ?><br>
            <?php echo htmlspecialchars($farmacia_nome); ?> - Farmácia Comunitária Mão Amiga
        </p>
    </div>
</body>
</html>
