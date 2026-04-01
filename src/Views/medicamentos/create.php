<h2>Adicionar Novo Medicamento</h2>

<?php
if (isset($_GET['error'])) {
    $errorMessage = '';
    switch ($_GET['error']) {
        case 'camposObrigatorios':
            $errorMessage = 'Por favor, preencha todos os campos obrigatórios.';
            break;
        case 'dbError':
            $errorMessage = 'Ocorreu um erro ao salvar no banco de dados. Tente novamente.';
            break;
        default:
            $errorMessage = 'Ocorreu um erro desconhecido.';
            break;
    }
    echo "<p style='color: red;'>" . htmlspecialchars($errorMessage) . "</p>";
}
?>

<form action="index.php?action=salvarMedicamento" method="POST">
    <div class="form-group">
        <label for="nome">Nome do Medicamento: *</label>
        <input type="text" id="nome" name="nome" required>
    </div>
    <div class="form-group">
        <label for="principio_ativo">Princípio Ativo: *</label>
        <input type="text" id="principio_ativo" name="principio_ativo" required>
    </div>
    <div class="form-group">
        <label for="forma_farmaceutica">Forma Farmacêutica:</label>
        <input type="text" id="forma_farmaceutica" name="forma_farmaceutica" placeholder="Ex: Comprimido, Xarope">
    </div>
    <div class="form-group">
        <label for="dosagem_concentracao">Dosagem/Concentração:</label>
        <input type="text" id="dosagem_concentracao" name="dosagem_concentracao" placeholder="Ex: 500mg, 10mg/ml">
    </div>
    <div class="form-group">
        <label for="unidade">Unidade:</label>
        <input type="text" id="unidade" name="unidade" placeholder="Ex: Caixa com 20, Frasco 100ml">
    </div>
    <div class="form-group">
        <label for="fabricante_laboratorio">Fabricante/Laboratório:</label>
        <input type="text" id="fabricante_laboratorio" name="fabricante_laboratorio">
    </div>
    <div class="form-group">
        <label for="codigo_barras">Código de Barras:</label>
        <input type="text" id="codigo_barras" name="codigo_barras">
    </div>
    <div class="form-group">
        <label for="estoque_minimo">Estoque Mínimo:</label>
        <input type="number" id="estoque_minimo" name="estoque_minimo" value="0" min="0">
    </div>
    <div class="form-group">
        <label for="localizacao_estoque">Localização no Estoque:</label>
        <input type="text" id="localizacao_estoque" name="localizacao_estoque" placeholder="Ex: Prateleira A, Gaveta 3">
    </div>
    <div class="form-group">
        <label for="categoria_especial">Categoria Especial:</label>
        <input type="text" id="categoria_especial" name="categoria_especial" placeholder="Ex: Controlado, Termolábil">
    </div>
    <div class="form-group">
        <input type="checkbox" id="requer_receita" name="requer_receita" value="1">
        <label for="requer_receita">Requer Receita Médica</label>
    </div>

    <button type="submit" class="btn">Salvar Medicamento</button>
    <a href="index.php?action=listarMedicamentos" style="margin-left: 10px;">Cancelar</a>
</form>