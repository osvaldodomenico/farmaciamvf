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
$tDoacoes = tableName('doacoes');
$tDoadores = tableName('doadores');
$tUsuarios = tableName('usuarios');
$doacao = fetchOne("
    SELECT 
        d.*,
        do.nome_completo as doador_nome,
        do.cpf_cnpj as doador_documento,
        do.tipo as doador_tipo,
        do.telefone as doador_telefone,
        u.nome_completo as usuario_nome
    FROM {$tDoacoes} d
    LEFT JOIN {$tDoadores} do ON d.doador_id = do.id
    LEFT JOIN {$tUsuarios} u ON d.usuario_id = u.id
    WHERE d.id = ?
", [$id]);

if (!$doacao) {
    die('Doação não encontrada.');
}

$tDoacoesItens = tableName('itens_doacao');
$tMedicamentos = tableName('medicamentos');
$itens = fetchAll("
    SELECT 
        di.*,
        m.nome as medicamento_nome,
        m.principio_ativo,
        m.dosagem_concentracao,
        m.unidade_medida,
        m.forma_farmaceutica
    FROM {$tDoacoesItens} di
    LEFT JOIN {$tMedicamentos} m ON di.medicamento_id = m.id
    WHERE di.doacao_id = ?
", [$id]);

$total_quantidade = array_sum(array_column($itens, 'quantidade'));

// Configurações da farmácia
try {
    $tConfiguracoes = tableName('configuracoes');
    $farmacia_nome = fetchColumn("SELECT valor FROM {$tConfiguracoes} WHERE chave = 'farmacia_nome'") ?: 'Farmácia Mão Amiga';
} catch (Exception $e) {
    $farmacia_nome = 'Farmácia Mão Amiga';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Doação <?php echo $doacao['numero_doacao']; ?></title>
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
            background: #e8f5e9;
            border-radius: 5px;
            border-left: 4px solid #4caf50;
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
        th { 
            background: #4caf50; 
            color: white;
            font-weight: 600;
        }
        tr:nth-child(even) { background: #f9f9f9; }
        .agradecimento {
            background: #fff8e1;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 20px;
        }
        .agradecimento h3 { color: #ff9800; margin-bottom: 10px; }
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

    <div class="header">
        <h1><?php echo htmlspecialchars($farmacia_nome); ?></h1>
        <p>Sistema de Gestão de Medicamentos Comunitários</p>
    </div>
    
    <h2 style="text-align: center; margin-bottom: 20px; color: #4caf50;">❤️ COMPROVANTE DE DOAÇÃO</h2>
    
    <div class="info-section">
        <div class="info-box">
            <h3>Doação</h3>
            <strong><?php echo $doacao['numero_doacao']; ?></strong><br>
            <?php echo date('d/m/Y H:i', strtotime($doacao['data_recebimento'])); ?>
        </div>
        <div class="info-box">
            <h3>Doador</h3>
            <strong><?php echo htmlspecialchars($doacao['doador_nome'] ?? 'Anônimo'); ?></strong><br>
            <?php if ($doacao['doador_documento']): ?>
                <?php echo $doacao['doador_tipo'] === 'pessoa_juridica' ? 'CNPJ' : 'CPF'; ?>: 
                <?php echo $doacao['doador_documento']; ?>
            <?php endif; ?>
        </div>
        <div class="info-box">
            <h3>Recebido por</h3>
            <?php echo htmlspecialchars($doacao['usuario_nome']); ?>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Medicamento</th>
                <th>Apresentação</th>
                <th>Lote</th>
                <th>Validade</th>
                <th style="text-align: center;">Quantidade</th>
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
                    <td><?php echo htmlspecialchars($item['lote']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($item['data_validade'])); ?></td>
                    <td style="text-align: center;"><strong><?php echo $item['quantidade']; ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <p><strong>Total de itens recebidos:</strong> <?php echo count($itens); ?> tipos / <?php echo $total_quantidade; ?> unidades</p>
    
    <?php if ($doacao['valor_estimado'] > 0): ?>
        <p><strong>Valor estimado:</strong> R$ <?php echo number_format($doacao['valor_estimado'], 2, ',', '.'); ?></p>
    <?php endif; ?>
    
    <?php if ($doacao['observacoes']): ?>
        <p style="margin-top: 15px;"><strong>Observações:</strong><br>
        <?php echo nl2br(htmlspecialchars($doacao['observacoes'])); ?></p>
    <?php endif; ?>
    
    <div class="agradecimento">
        <h3>🙏 MUITO OBRIGADO!</h3>
        <p>Sua doação ajudará muitas pessoas que precisam de medicamentos.<br>
        A generosidade faz a diferença na vida de quem mais precisa.</p>
    </div>
    
    <div class="assinaturas">
        <div class="assinatura">
            <div class="assinatura-linha">Doador ou Representante</div>
        </div>
        <div class="assinatura">
            <div class="assinatura-linha">Responsável pelo Recebimento</div>
        </div>
    </div>
    
    <div class="footer">
        <p style="text-align: center; font-size: 9pt; color: #666;">
            Documento emitido em <?php echo date('d/m/Y H:i:s'); ?><br>
            <?php echo htmlspecialchars($farmacia_nome); ?> - Farmácia Comunitária
        </p>
    </div>
</body>
</html>
