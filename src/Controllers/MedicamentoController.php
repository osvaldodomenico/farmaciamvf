<?php
// src/Controllers/MedicamentoController.php
namespace Controllers;

// Como o autoload está configurado para procurar em Models/ diretamente,
// não precisamos do namespace Models aqui, apenas o nome da classe.
// Se você estivesse usando um autoloader PSR-4 completo, seria use Models\Medicamento;
use Medicamento; // Assume que Medicamento.php está em src/Models/

class MedicamentoController {

    public function index() {
        $medicamentoModel = new Medicamento();
        $stmt = $medicamentoModel->readAll();
        $medicamentos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Passa os dados para a View
        // Define o caminho para a view
        $viewPath = BASE_PATH . '/src/Views/medicamentos/index.php';
        if (file_exists($viewPath)) {
            // Torna $medicamentos acessível na view
            include $viewPath;
        } else {
            echo "Erro: View para listar medicamentos não encontrada.";
        }
    }

    public function createForm() {
        // Simplesmente carrega o formulário de criação
        $viewPath = BASE_PATH . '/src/Views/medicamentos/create.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo "Erro: View para adicionar medicamento não encontrada.";
        }
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $medicamento = new Medicamento();
            $medicamento->nome = $_POST['nome'] ?? '';
            $medicamento->principio_ativo = $_POST['principio_ativo'] ?? '';
            $medicamento->forma_farmaceutica = $_POST['forma_farmaceutica'] ?? null;
            $medicamento->dosagem_concentracao = $_POST['dosagem_concentracao'] ?? null;
            $medicamento->unidade = $_POST['unidade'] ?? null;
            $medicamento->fabricante_laboratorio = $_POST['fabricante_laboratorio'] ?? null;
            $medicamento->codigo_barras = $_POST['codigo_barras'] ?? null;
            $medicamento->requer_receita = isset($_POST['requer_receita']) ? 1 : 0;
            $medicamento->categoria_especial = $_POST['categoria_especial'] ?? null;
            $medicamento->localizacao_estoque = $_POST['localizacao_estoque'] ?? null;
            $medicamento->estoque_minimo = !empty($_POST['estoque_minimo']) ? (int)$_POST['estoque_minimo'] : 0;

            // Validação básica (pode ser muito mais robusta)
            if (empty($medicamento->nome) || empty($medicamento->principio_ativo)) {
                // Tratar erro, talvez redirecionar de volta com mensagem
                echo "Nome e Princípio Ativo são obrigatórios.";
                // Poderia redirecionar para o formulário com uma mensagem de erro
                // header('Location: index.php?action=adicionarMedicamentoForm&error=camposObrigatorios');
                // exit;
                return;
            }

            if ($medicamento->create()) {
                // Redireciona para a lista de medicamentos com mensagem de sucesso
                // Idealmente, usaríamos sessões para mensagens flash
                $_SESSION['message'] = "Medicamento adicionado com sucesso!";
                header('Location: index.php?action=listarMedicamentos');
                exit;
            } else {
                // Tratar erro
                echo "Erro ao adicionar medicamento.";
                // header('Location: index.php?action=adicionarMedicamentoForm&error=dbError');
                // exit;
            }
        } else {
            // Se não for POST, redireciona ou mostra erro
            header('Location: index.php?action=adicionarMedicamentoForm');
            exit;
        }
    }

    // Métodos editForm, update, delete seriam adicionados aqui de forma similar
}
?>