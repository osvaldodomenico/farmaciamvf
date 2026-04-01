<?php
// src/Views/medicamentos/index.php
// $medicamentos é passado pelo MedicamentoController::index()
?>

<h2>Lista de Medicamentos</h2>

<?php
if (isset($_SESSION['message'])) {
    echo "<p style='color: green;'>" . htmlspecialchars($_SESSION['message']) . "</p>";
    unset($_SESSION['message']); // Limpa a mensagem após exibir
}
?>

<a href="index.php?action=adicionarMedicamentoForm" class="btn">Adicionar Novo Medicamento</a>

<?php if (!empty($medicamentos)): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Princípio Ativo</th>
                <th>Forma Farm.</th>
                <th>Dosagem</th>
                <th>Unidade</th>
                <th>Fabricante</th>
                <th>Cód. Barras</th>
                <th>Requer Receita?</th>
                <th>Est. Mínimo</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($medicamentos as $medicamento): ?>
                <tr>
                    <td><?php echo htmlspecialchars($medicamento['id']); ?></td>
                    <td><?php echo htmlspecialchars($medicamento['nome']); ?></td>
                    <td><?php echo htmlspecialchars($medicamento['principio_ativo']); ?></td>
                    <td><?php echo htmlspecialchars($medicamento['forma_farmaceutica'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($medicamento['dosagem_concentracao'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($medicamento['unidade'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($medicamento['fabricante_laboratorio'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($medicamento['codigo_barras'] ?? '-'); ?></td>
                    <td><?php echo $medicamento['requer_receita'] ? 'Sim' : 'Não'; ?></td>
                    <td><?php echo htmlspecialchars($medicamento['estoque_minimo']); ?></td>
                    <td class="actions">
                        <a href="index.php?action=editarMedicamentoForm&id=<?php echo $medicamento['id']; ?>" class="edit">Editar</a>
                        <a href="index.php?action=deletarMedicamento&id=<?php echo $medicamento['id']; ?>" class="delete" onclick="return confirm('Tem certeza que deseja excluir este medicamento?');">Excluir</a>
                        <a href="index.php?action=verLotesMedicamento&medicamento_id=<?php echo $medicamento['id']; ?>">Lotes</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Nenhum medicamento cadastrado ainda.</p>
<?php endif; ?>