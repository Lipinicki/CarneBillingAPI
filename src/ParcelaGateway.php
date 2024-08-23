<?php

class ParcelaGateway 
{
    private PDO $connection;

    public function __construct(Database $database)
    {
        $this->connection = $database->getConnection();
    }

    /**
     * Retorna todas as parcelas relacionadas a um carne.
     */
    public function getAll(string $carneId) : array
    {
        $sql = "SELECT *
                FROM Parcela
                WHERE Carne_Id=:carne;";

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(":carne", $carneId, PDO::PARAM_INT);
        $statement->execute();

        $data = [];

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $parcela = new ParcelaDTO;
            $parcela->carne = $row["Carne_Id"];
            $parcela->data_vencimento = $row["Data_Vencimento"];
            $parcela->entrada = $row["Entrada"];
            $parcela->numero = $row["Numero"];
            $parcela->valor = $row["Valor"];

            $data[] = $parcela;
        }

        return $data;
    }

    /**
     * Cria uma nova parcela com os dados do DTO.
     */
    public function create(ParcelaDTO $parcelaDTO) : void
    {
        $sql = "INSERT INTO Parcela (Carne_Id, Data_Vencimento, Entrada, Numero, Valor) 
                VALUES (:carne_id, :data_vencimento, :entrada, :numero, :valor);";

        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue("carne_id", $parcelaDTO->carne, PDO::PARAM_INT);
        $stmt->bindValue(":data_vencimento", $parcelaDTO->data_vencimento, PDO::PARAM_STR);
        $stmt->bindValue(":entrada", $parcelaDTO->entrada, PDO::PARAM_BOOL);
        $stmt->bindValue(":numero", $parcelaDTO->numero, PDO::PARAM_INT);
        $stmt->bindValue(":valor", $parcelaDTO->valor, PDO::PARAM_STR);

        $stmt->execute();
    }
}