<?php

final class CriaCarneDTO {
    public float $valorTotal;
    public int $qtdParcelas;
    public DateTime $dataPrimeiroVencimento;
    public string $periodicidade;
    public ?float $valorEntrada;

    /**
     * Faz o mapeamento dos dados da requisição para o objeto DTO.
     */
    public function map(array $data) : void 
    {
        $this->valorTotal = $data["valor_total"];
        $this->qtdParcelas = $data["qtd_parcelas"];
        $this->dataPrimeiroVencimento = DateTime::createFromFormat("Y-m-d", $data["data_primeiro_vencimento"]);
        $this->periodicidade = $data["periodicidade"];
        $this->valorEntrada = $data["valor_entrada"] ?? null;
    }
}