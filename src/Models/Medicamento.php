<?php
// src/Models/Medicamento.php

require_once 'Database.php';

class Medicamento {
    private $conn;
    private $table_name;

    // Propriedades do Medicamento
    public $id;
    public $nome;
    public $principio_ativo;
    public $forma_farmaceutica;
    public $dosagem_concentracao;
    public $unidade;
    public $fabricante_laboratorio;
    public $codigo_barras;
    public $requer_receita;
    public $categoria_especial;
    public $localizacao_estoque;
    public $estoque_minimo;
    public $criado_em;
    public $atualizado_em;

    public function __construct() {
        if (!Database::validatePrefix()) {
            die('Prefixo de tabela inválido');
        }
        $this->table_name = Database::tableName('medicamentos');
        $this->conn = Database::getConnection();
    }

    // Criar novo medicamento
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET nome=:nome, principio_ativo=:principio_ativo, forma_farmaceutica=:forma_farmaceutica,
                      dosagem_concentracao=:dosagem_concentracao, unidade=:unidade,
                      fabricante_laboratorio=:fabricante_laboratorio, codigo_barras=:codigo_barras,
                      requer_receita=:requer_receita, categoria_especial=:categoria_especial,
                      localizacao_estoque=:localizacao_estoque, estoque_minimo=:estoque_minimo";

        $stmt = $this->conn->prepare($query);

        // Limpar dados (sanitize)
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->principio_ativo = htmlspecialchars(strip_tags($this->principio_ativo));
        // ... fazer o mesmo para todos os campos de string ...
        $this->requer_receita = $this->requer_receita ? 1 : 0;
        $this->estoque_minimo = (int)$this->estoque_minimo;


        // Bind dos valores
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":principio_ativo", $this->principio_ativo);
        $stmt->bindParam(":forma_farmaceutica", $this->forma_farmaceutica);
        $stmt->bindParam(":dosagem_concentracao", $this->dosagem_concentracao);
        $stmt->bindParam(":unidade", $this->unidade);
        $stmt->bindParam(":fabricante_laboratorio", $this->fabricante_laboratorio);
        $stmt->bindParam(":codigo_barras", $this->codigo_barras);
        $stmt->bindParam(":requer_receita", $this->requer_receita, PDO::PARAM_BOOL);
        $stmt->bindParam(":categoria_especial", $this->categoria_especial);
        $stmt->bindParam(":localizacao_estoque", $this->localizacao_estoque);
        $stmt->bindParam(":estoque_minimo", $this->estoque_minimo, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        // Imprimir erro se algo der errado (para debug)
        // printf("Erro: %s.\n", $stmt->error);
        return false;
    }

    // Ler todos os medicamentos
    public function readAll($limit = null, $offset = 0) {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY nome ASC";
        if ($limit !== null) {
            $query .= " LIMIT :limit OFFSET :offset";
        }
        $stmt = $this->conn->prepare($query);
        if ($limit !== null) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt; // Retorna o statement para ser processado (fetchAll)
    }

    // Ler um medicamento pelo ID
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->nome = $row['nome'];
            $this->principio_ativo = $row['principio_ativo'];
            $this->forma_farmaceutica = $row['forma_farmaceutica'];
            $this->dosagem_concentracao = $row['dosagem_concentracao'];
            $this->unidade = $row['unidade'];
            $this->fabricante_laboratorio = $row['fabricante_laboratorio'];
            $this->codigo_barras = $row['codigo_barras'];
            $this->requer_receita = $row['requer_receita'];
            $this->categoria_especial = $row['categoria_especial'];
            $this->localizacao_estoque = $row['localizacao_estoque'];
            $this->estoque_minimo = $row['estoque_minimo'];
            $this->criado_em = $row['criado_em'];
            $this->atualizado_em = $row['atualizado_em'];
            return true;
        }
        return false;
    }

    // Atualizar medicamento
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET nome = :nome, principio_ativo = :principio_ativo, forma_farmaceutica = :forma_farmaceutica,
                      dosagem_concentracao = :dosagem_concentracao, unidade = :unidade,
                      fabricante_laboratorio = :fabricante_laboratorio, codigo_barras = :codigo_barras,
                      requer_receita = :requer_receita, categoria_especial = :categoria_especial,
                      localizacao_estoque = :localizacao_estoque, estoque_minimo = :estoque_minimo
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Limpar dados
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        // ... fazer o mesmo para todos os campos de string ...
        $this->requer_receita = $this->requer_receita ? 1 : 0;
        $this->estoque_minimo = (int)$this->estoque_minimo;
        $this->id = (int)$this->id;

        // Bind dos valores
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":principio_ativo", $this->principio_ativo);
        $stmt->bindParam(":forma_farmaceutica", $this->forma_farmaceutica);
        $stmt->bindParam(":dosagem_concentracao", $this->dosagem_concentracao);
        $stmt->bindParam(":unidade", $this->unidade);
        $stmt->bindParam(":fabricante_laboratorio", $this->fabricante_laboratorio);
        $stmt->bindParam(":codigo_barras", $this->codigo_barras);
        $stmt->bindParam(":requer_receita", $this->requer_receita, PDO::PARAM_BOOL);
        $stmt->bindParam(":categoria_especial", $this->categoria_especial);
        $stmt->bindParam(":localizacao_estoque", $this->localizacao_estoque);
        $stmt->bindParam(":estoque_minimo", $this->estoque_minimo, PDO::PARAM_INT);
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Deletar medicamento
    public function delete() {
        // Antes de deletar, verificar se há lotes associados.
        // Idealmente, a deleção de medicamentos só seria permitida se não houver histórico de movimentação
        // ou se o medicamento for "arquivado" em vez de deletado fisicamente.
        // Por simplicidade, vamos permitir a deleção, mas o ON DELETE CASCADE na tabela lotes cuidará disso.

        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $this->id = (int)htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Buscar medicamentos
    public function search($keywords) {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE nome LIKE :keywords OR principio_ativo LIKE :keywords OR codigo_barras LIKE :keywords
                  ORDER BY nome ASC";

        $stmt = $this->conn->prepare($query);

        $keywords = htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%"; // Adiciona wildcards para a busca LIKE

        $stmt->bindParam(':keywords', $keywords);
        $stmt->execute();
        return $stmt;
    }
}
?>
