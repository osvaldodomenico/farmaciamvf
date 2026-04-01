<?php
// Processar POST antes de qualquer saída para permitir redirect imediato
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once __DIR__ . '/../../config/database.php';
    $id = $_GET['id'] ?? 0;
    try {
        // Obter colunas existentes da tabela para evitar erro de coluna desconhecida
        $tMed = tableName('medicamentos');
        $cols = fetchAll("SHOW COLUMNS FROM {$tMed}");
        $colnames = array_map(fn($c) => $c['Field'], $cols);

        // Mapeamento de aliases -> coluna real se existir
        $map = [
            'nome' => 'nome',
            'principio_ativo' => 'principio_ativo',
            'dosagem_concentracao' => in_array('dosagem_concentracao', $colnames) ? 'dosagem_concentracao' : (in_array('concentracao', $colnames) ? 'concentracao' : null),
            'unidade_medida' => in_array('unidade_medida', $colnames) ? 'unidade_medida' : (in_array('unidade', $colnames) ? 'unidade' : null),
            'forma_farmaceutica' => 'forma_farmaceutica',
            'fabricante_laboratorio' => in_array('fabricante_laboratorio', $colnames) ? 'fabricante_laboratorio' : (in_array('fabricante', $colnames) ? 'fabricante' : null),
            'codigo_barras' => 'codigo_barras',
            'registro_ms' => in_array('registro_ms', $colnames) ? 'registro_ms' : (in_array('registro_anvisa', $colnames) ? 'registro_anvisa' : null),
            'estoque_minimo' => 'estoque_minimo',
            'controlado' => 'controlado',
            'temperatura_armazenamento' => in_array('temperatura_armazenamento', $colnames) ? 'temperatura_armazenamento' : (in_array('refrigerado', $colnames) ? 'refrigerado' : null),
            'observacoes' => 'observacoes'
        ];

        // Construir pares coluna=valor apenas para colunas existentes
        $values = [
            'nome' => $_POST['nome'],
            'principio_ativo' => !empty(trim($_POST['principio_ativo'])) ? $_POST['principio_ativo'] : null,
            'dosagem_concentracao' => !empty(trim($_POST['concentracao'])) ? $_POST['concentracao'] : null,
            'unidade_medida' => $_POST['unidade_medida'] ?? null ?: null,
            'forma_farmaceutica' => $_POST['forma_farmaceutica'],
            'fabricante_laboratorio' => !empty(trim($_POST['fabricante'])) ? $_POST['fabricante'] : null,
            'codigo_barras' => !empty(trim($_POST['codigo_barras'])) ? $_POST['codigo_barras'] : null,
            'registro_ms' => !empty(trim($_POST['registro_anvisa'])) ? $_POST['registro_anvisa'] : null,
            'estoque_minimo' => $_POST['estoque_minimo'] ?? 10,
            'controlado' => isset($_POST['controlado']) ? 1 : 0,
            'temperatura_armazenamento' => isset($_POST['refrigerado']) ? 'refrigerado' : 'ambiente',
            'observacoes' => !empty(trim($_POST['observacoes'])) ? $_POST['observacoes'] : null
        ];

        $setParts = [];
        $params = [];
        foreach ($values as $alias => $val) {
            $col = $map[$alias];
            if ($col) {
                $setParts[] = "{$col} = ?";
                // Ajuste para coluna boolean 'refrigerado' caso exista em vez de 'temperatura_armazenamento'
                if ($col === 'refrigerado') {
                    $params[] = isset($_POST['refrigerado']) ? 1 : 0;
                } else {
                    $params[] = $val;
                }
            }
        }
        $params[] = $id;

        $sql = "UPDATE {$tMed} SET " . implode(', ', $setParts) . " WHERE id = ?";
        execute($sql, $params);
        
        registrarLog($_SESSION['usuario_id'], 'editou_medicamento', tableName('medicamentos'), $id, $_POST['nome']);
        
        $_SESSION['success'] = 'Medicamento atualizado com sucesso!';
        header('Location: listar.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Erro ao atualizar medicamento: ' . $e->getMessage();
    }
}
// Após tratar POST, carregar header e dados para exibição do formulário
require_once __DIR__ . '/../../templates/header.php';

$id = $_GET['id'] ?? 0;
$medicamento = fetchOne("SELECT * FROM " . tableName('medicamentos') . " WHERE id = ?", [$id]);

if (!$medicamento) {
    $_SESSION['error'] = 'Medicamento não encontrado.';
    header('Location: listar.php');
    exit;
}
?>

<script>document.getElementById('page-title').textContent = 'Editar Medicamento';</script>

<div class="page-title">
    <h1><i class="bi bi-pencil"></i> Editar Medicamento</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="listar.php">Medicamentos</a></li>
            <li class="breadcrumb-item active">Editar</li>
        </ol>
    </nav>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0"><i class="bi bi-pencil-square"></i> Editar Medicamento</h5></div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nome do Medicamento *</label>
                    <input type="text" class="form-control input-uppercase" name="nome" value="<?php echo htmlspecialchars($medicamento['nome']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Princípio Ativo</label>
                    <input type="text" class="form-control input-uppercase" name="principio_ativo" value="<?php echo htmlspecialchars($medicamento['principio_ativo'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-2 mb-3">
                    <label class="form-label">Concentração</label>
                    <input type="text" class="form-control" name="concentracao" value="<?php echo htmlspecialchars($medicamento['dosagem_concentracao'] ?? ''); ?>" placeholder="Ex: 20-35">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Medida</label>
                    <select class="form-select" name="unidade_medida">
                        <option value="">Selecione...</option>
                        <?php
                        $unidades = ['MG', 'MCG', 'G', 'ML', 'UI', 'PORCENTAGEM' => '%', 'MEQ' => 'mEq'];
                        foreach ($unidades as $val => $label) {
                            $key = is_numeric($val) ? $label : $val;
                            $selected = ($medicamento['unidade_medida'] ?? '') === $key ? 'selected' : '';
                            echo "<option value='$key' $selected>$label</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Forma Farmacêutica *</label>
                    <select class="form-select" name="forma_farmaceutica" required>
                        <?php
                        $formas = ['comprimido', 'capsula', 'solucao', 'suspensao', 'pomada', 'creme', 'gel', 'xarope', 'injetavel', 'spray', 'emulsao_oral', 'gotas', 'outro'];
                        foreach ($formas as $forma) {
                            $selected = $medicamento['forma_farmaceutica'] === $forma ? 'selected' : '';
                            echo "<option value='$forma' $selected>" . ucfirst($forma) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Fabricante</label>
                    <input type="text" class="form-control input-uppercase" name="fabricante" value="<?php echo htmlspecialchars($medicamento['fabricante_laboratorio'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Código de Barras</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="codigo_barras" id="codigo_barras" value="<?php echo htmlspecialchars($medicamento['codigo_barras'] ?? ''); ?>">
                        <button class="btn btn-outline-secondary" type="button" id="btnScanBarcode">
                            <i class="bi bi-upc-scan"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Registro ANVISA</label>
                    <input type="text" class="form-control" name="registro_anvisa" value="<?php echo htmlspecialchars($medicamento['registro_ms'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Estoque Mínimo *</label>
                    <input type="number" class="form-control" name="estoque_minimo" value="<?php echo $medicamento['estoque_minimo']; ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="controlado" id="controlado" 
                            <?php echo $medicamento['controlado'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="controlado">
                            <i class="bi bi-shield-exclamation text-warning"></i> Medicamento Controlado
                        </label>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="refrigerado" id="refrigerado"
                            <?php echo ($medicamento['temperatura_armazenamento'] ?? '') === 'refrigerado' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="refrigerado">
                            <i class="bi bi-thermometer-snow text-info"></i> Requer Refrigeração
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Observações</label>
                <textarea class="form-control" name="observacoes" rows="3"><?php echo htmlspecialchars($medicamento['observacoes'] ?? ''); ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Salvar Alterações</button>
                <a href="listar.php" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<!-- HTML5-QRCode Library -->
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<!-- Modal Leitor de Código de Barras -->
<div class="modal fade" id="barcodeScannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-upc-scan"></i> Escanear Código de Barras</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="reader" style="width: 100%;"></div>
            </div>
            <div class="modal-footer justify-content-center">
                <small class="text-muted">Aponte a câmera para o código de barras</small>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let html5QrcodeScanner = null;
        const scanButton = document.getElementById('btnScanBarcode');
        const barcodeInput = document.getElementById('codigo_barras');
        const scannerModal = new bootstrap.Modal(document.getElementById('barcodeScannerModal'));
        const modalElement = document.getElementById('barcodeScannerModal');

        // Som de beep (opcional, curto e sutil)
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        function playBeep() {
            if (audioContext.state === 'suspended') audioContext.resume();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            oscillator.type = 'square';
            oscillator.frequency.value = 1500; // Hz
            gainNode.gain.value = 0.1;
            oscillator.start();
            setTimeout(() => oscillator.stop(), 100);
        }

        scanButton.addEventListener('click', function() {
            scannerModal.show();
        });

        modalElement.addEventListener('shown.bs.modal', function () {
            // Configurar formatos suportados (Prioridade para EAN/Barras)
            const formatsToSupport = [
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.EAN_8,
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.UPC_A,
                Html5QrcodeSupportedFormats.UPC_E,
                Html5QrcodeSupportedFormats.QR_CODE
            ];

            // Inicializar scanner com formatos específicos
            html5QrcodeScanner = new Html5Qrcode("reader", { formatsToSupport: formatsToSupport });
            
            // Configurações - Box mais largo e baixo para código de barras
            const config = { 
                fps: 15, // Aumentado para leitura mais fluida
                qrbox: { width: 280, height: 100 }, // Retangular
                aspectRatio: 1.0,
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                }
            };
            
            // Preferir câmera traseira
            html5QrcodeScanner.start({ facingMode: "environment" }, config, onScanSuccess, onScanFailure)
            .catch(err => {
                console.error("Erro ao iniciar scanner:", err);
                alert("Erro ao acessar a câmera. Verifique as permissões. " + err);
                scannerModal.hide();
            });
        });

        modalElement.addEventListener('hidden.bs.modal', function () {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.stop().then(() => {
                    html5QrcodeScanner.clear();
                    html5QrcodeScanner = null;
                }).catch(err => console.error("Erro ao parar scanner:", err));
            }
        });

        function onScanSuccess(decodedText, decodedResult) {
            // Tocar som
            playBeep();
            
            // Preencher input
            barcodeInput.value = decodedText;
            
            // Feedback visual
            barcodeInput.classList.add('is-valid');
            setTimeout(() => barcodeInput.classList.remove('is-valid'), 2000);
            
            // Fechar modal
            scannerModal.hide();
            
            // Opcional: Toast de sucesso
            if (window.toastr) toastr.success('Código lido com sucesso: ' + decodedText);
        }

        function onScanFailure(error) {
            // console.warn(`Code scan error = ${error}`);
        }
    });
</script>
