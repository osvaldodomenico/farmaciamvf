<?php
// Processar POST ANTES de incluir o header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    require_once __DIR__ . '/../../config/database.php';
    
    try {
        $dados = [
            $_POST['nome'],
            !empty(trim($_POST['principio_ativo'])) ? $_POST['principio_ativo'] : null,
            !empty(trim($_POST['concentracao'])) ? $_POST['concentracao'] : null,
            $_POST['unidade_medida'] ?? null ?: null,
            $_POST['forma_farmaceutica'],
            !empty(trim($_POST['fabricante'])) ? $_POST['fabricante'] : null,
            !empty(trim($_POST['codigo_barras'])) ? $_POST['codigo_barras'] : null,
            !empty(trim($_POST['registro_anvisa'])) ? $_POST['registro_anvisa'] : null,
            $_POST['estoque_minimo'] ?? 10,
            isset($_POST['controlado']) ? 1 : 0,
            isset($_POST['refrigerado']) ? 'refrigerado' : 'ambiente',
            !empty(trim($_POST['observacoes'])) ? $_POST['observacoes'] : null
        ];
        
        $tMedicamentos = tableName('medicamentos');
        $id = execute("INSERT INTO {$tMedicamentos} (nome, principio_ativo, dosagem_concentracao, unidade_medida, forma_farmaceutica, 
            fabricante_laboratorio, codigo_barras, registro_ms, estoque_minimo, controlado, temperatura_armazenamento, observacoes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $dados);
        
        $id = getDb()->lastInsertId();
        
        registrarLog($_SESSION['usuario_id'], 'criou_medicamento', 'medicamentos', $id, $_POST['nome']);
        
        $_SESSION['success'] = 'Medicamento cadastrado com sucesso!';
        header('Location: listar.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Erro ao cadastrar medicamento: ' . $e->getMessage();
        header('Location: adicionar.php');
        exit;
    }
}

require_once __DIR__ . '/../../templates/header.php';
?>

<script>document.getElementById('page-title').textContent = 'Novo Medicamento';</script>

<div class="page-title">
    <h1><i class="bi bi-plus-circle"></i> Novo Medicamento</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="listar.php">Medicamentos</a></li>
            <li class="breadcrumb-item active">Novo</li>
        </ol>
    </nav>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0"><i class="bi bi-file-earmark-plus"></i> Cadastrar Medicamento</h5></div>
    <div class="card-body">
        <form method="POST" id="formMedicamento">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nome do Medicamento *</label>
                    <input type="text" class="form-control input-uppercase" name="nome" required maxlength="255">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Princípio Ativo</label>
                    <input type="text" class="form-control input-uppercase" name="principio_ativo" maxlength="255">
                </div>
            </div>
            
            <div class="row">
            <div class="row">
                <div class="col-md-2 mb-3">
                    <label class="form-label">Concentração</label>
                    <input type="text" class="form-control" name="concentracao" placeholder="Ex: 20-35">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Medida</label>
                    <select class="form-select" name="unidade_medida">
                        <option value="">Selecione...</option>
                        <option value="MG">MG</option>
                        <option value="MCG">MCG</option>
                        <option value="G">G</option>
                        <option value="ML">ML</option>
                        <option value="UI">UI</option>
                        <option value="PORCENTAGEM">%</option>
                        <option value="MEQ">mEq</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Forma Farmacêutica *</label>
                    <select class="form-select" name="forma_farmaceutica" required>
                        <option value="">Selecione...</option>
                        <option value="comprimido">Comprimido</option>
                        <option value="capsula">Cápsula</option>
                        <option value="solucao">Solução</option>
                        <option value="suspensao">Suspensão</option>
                        <option value="pomada">Pomada</option>
                        <option value="creme">Creme</option>
                        <option value="gel">Gel</option>
                        <option value="xarope">Xarope</option>
                        <option value="injetavel">Injetável</option>
                        <option value="spray">Spray</option>
                        <option value="emulsao_oral">Emulsão Oral</option>
                        <option value="gotas">Gotas</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Fabricante</label>
                    <input type="text" class="form-control input-uppercase" name="fabricante" maxlength="255">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Código de Barras</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="codigo_barras" id="codigo_barras" maxlength="100">
                        <button class="btn btn-outline-secondary" type="button" id="btnScanBarcode">
                            <i class="bi bi-upc-scan"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Registro ANVISA</label>
                    <input type="text" class="form-control" name="registro_anvisa" maxlength="100">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Estoque Mínimo *</label>
                    <input type="number" class="form-control" name="estoque_minimo" value="10" required min="0">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="controlado" id="controlado">
                        <label class="form-check-label" for="controlado">
                            <i class="bi bi-shield-exclamation text-warning"></i> Medicamento Controlado
                        </label>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="refrigerado" id="refrigerado">
                        <label class="form-check-label" for="refrigerado">
                            <i class="bi bi-thermometer-snow text-info"></i> Requer Refrigeração
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Observações</label>
                <textarea class="form-control" name="observacoes" rows="3"></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Salvar Medicamento
                </button>
                <a href="listar.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancelar
                </a>
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
                Html5QrcodeSupportedFormats.UPC_E
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
