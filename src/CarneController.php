<?php

class CarneController 
{
    public function __construct(private CarneGateway $carneGateway,
                                private ParcelaGateway $parcelaGateway)
    {}

    public function processaRequisicao(string $method, ?string $id) : void 
    {
        switch ($method) {
            case "GET":
                $this->processaRetornoCarne($id);
                break;
            case "POST":
                $this->processaCriacaoCarne();
                break;
            default:
                http_response_code(405);
                header("Allow: GET, POST");
        }
    }

    /**
     * Processa a criação de um novo carnê, validando os dados e criando as parcelas.
     */
    public function processaCriacaoCarne() : void
    {
        $data = (array) json_decode(file_get_contents("php://input"), null);
        
        $errors = $this->retornaErrosValidacao($data);
        if (! empty($errors)) {
            http_response_code(422);
            echo json_encode($errors);
            return;
        }

        $DTO = new CriaCarneDTO;
        $DTO->map($data);

        //Gero um novo Id para o carne
        $carneId = $this->carneGateway->create();
        
        //Guarda o número da parcela que está sendo criada no momento
        $numParcelaAtual = 1;

        //O número para dividir o valor restante do carne
        $qtdParcelasRestantes = $DTO->qtdParcelas;

        //Acompanha a data de vencimento da parcela atual para utilizar a próxima parcela
        $dataVencimentoAtual = $DTO->dataPrimeiroVencimento;

        $parcelas = [];

        //Crio a parcela de entrada, se houver
        if (! empty($DTO->valorEntrada))
        {
            $parcelaEntrada = new ParcelaDTO;
            
            $parcelaEntrada->carne = $carneId;
            $parcelaEntrada->data_vencimento = $DTO->dataPrimeiroVencimento->format("Y-m-d");
            $parcelaEntrada->entrada = true;
            $parcelaEntrada->valor = $DTO->valorEntrada >= $DTO->valorTotal ? $DTO->valorTotal : $DTO->valorEntrada;
            $parcelaEntrada->numero = $numParcelaAtual;

            $parcelas[] = $parcelaEntrada;

            //Ajusto a data de vencimento da próxima parcela
            switch ($DTO->periodicidade) {
                case "mensal":
                    $dataVencimentoAtual->add(new DateInterval("P1M"));
                case "semanal":
                    $dataVencimentoAtual->add(new DateInterval("P1W"));
            }

            //Incremento a o numero da parcela atua e diminuo a quantidade de parcelas para realizar o cálculo correto
            $numParcelaAtual++;
            $qtdParcelasRestantes--;
        }
        
        $valor_restante = $DTO->valorTotal - ($DTO->valorEntrada ?? 0);
        $valor_parcela = round($valor_restante / $qtdParcelasRestantes, 2, PHP_ROUND_HALF_DOWN);
        
        //Calculo o valor da última parcela, que pode incluir ajustes devido ao arredondamento.
        $valor_ultima_parcela = round(($valor_restante - ($valor_parcela * $qtdParcelasRestantes)) + $valor_parcela, 2, PHP_ROUND_HALF_DOWN);

        //Crio as parcelas restantes incrementando o número a cada iteração
        while ($numParcelaAtual <= $DTO->qtdParcelas)
        {
            $parcela = new ParcelaDTO;

            $parcela->carne = $carneId;

            
            $parcela->data_vencimento = $dataVencimentoAtual->format('Y-m-d');
            $parcela->entrada = false;
            $parcela->valor = $numParcelaAtual === $DTO->qtdParcelas ? $valor_ultima_parcela : $valor_parcela;            
            $parcela->numero = $numParcelaAtual;
            
            $parcelas[] = $parcela;

            //Ajusto a data de vencimento da próxima parcela
            switch ($DTO->periodicidade) {
                case "mensal":
                    $dataVencimentoAtual->add(new DateInterval("P1M"));
                case "semanal":
                    $dataVencimentoAtual->add(new DateInterval("P1W"));
            }
            
            $numParcelaAtual++;
        }
        
        //Persisto as parcelas criadas no banco de dados
        foreach($parcelas as $parcela) {
            $this->parcelaGateway->create($parcela);
        }
        
        http_response_code(201);
        echo json_encode($this->retornaDadosCarne($parcelas));
    }

    /**
     * Processa o retorno das parcelas de um carnê específico com base no ID.
     */
    public function processaRetornoCarne(?string $id) : void 
    {
        $parcelas = $this->parcelaGateway->getAll($id);

        if (count($parcelas) === 0)
        {
            http_response_code(404);
            echo json_encode(['error'=> 'O Carne informado não possuí parcelas.']);
            return;
        }

        http_response_code(200);
        echo json_encode($this->retornaDadosCarne($parcelas));
    }

    /**
     * Retorna os dados agregados de um carnê, incluindo o valor total e as parcelas.
     */
    private function retornaDadosCarne(array $parcelas) : array
    {
        $total = 0;
        $valor_entrada = 0;
        foreach($parcelas as $parcela) {
            $total += $parcela->valor;

            if ($parcela->entrada) {
                $valor_entrada = $parcela->valor;
            }
        }

        return [
            "total"=>round($total, 2, PHP_ROUND_HALF_DOWN),
            "valor_entrada"=>$valor_entrada,
            "parcelas"=>array_map(fn($p) => [
                "data_vencimento"=>$p->data_vencimento,
                "valor"=>$p->valor,
                "numero"=>$p->numero,
                "entrada"=>$p->entrada
            ], $parcelas)
        ];
    }

    /**
     * Realiza a validação dos dados de entrada para a criação do carnê.
     * Retorna um array de erros se houver problemas nos dados.
     */
    private function retornaErrosValidacao(array $data) : array
    {
        $errors = [];

        $valor_total = $data["valor_total"] ?? null;

        if (empty($valor_total)) {
            $errors[] = "Informe o valor_total do carnê.";
        } else if (filter_var($valor_total, FILTER_VALIDATE_FLOAT) === false) {
            $errors[] = "O valor_total precisa ser um float.";
        } else if ($valor_total <= 0.0) {
            $errors[] = "O valor_total não pode ser menor ou igual a zero.";
        }

        $qtd_parcelas = $data["qtd_parcelas"] ?? null;
        
        if (empty($qtd_parcelas)) {
            $errors[] = "Informe a qtd_parcelas do carnê.";
        } else if (filter_var($qtd_parcelas, FILTER_VALIDATE_INT) === false) {
            $errors[] = "A qtd_parcelas precisa ser um valor int.";
        } else if ($qtd_parcelas <= 0) {
            $errors[] = "A qtd_parcelas não pode ser negativa.";
        }

        $data_primeiro_vencimento = $data["data_primeiro_vencimento"] ?? null;

        if (empty($data_primeiro_vencimento)) {
            $errors[] = "Informe a data_primeiro_vencimento do carnê.";
        } else if (DateTime::createFromFormat("Y-m-d", $data_primeiro_vencimento) === false) {
            $errors[] = "A data_primeiro_vencimento precisa estar no formato YYYY-MM-DD";
        } else if (DateTime::createFromFormat("Y-m-d", $data_primeiro_vencimento) < new DateTime()) {
            $errors[] = "A data_primeiro_vencimento precisa ser maior que a data atual.";
        }

        $periodicidade = $data["periodicidade"] ?? null;

        if (empty($periodicidade)) {
            $errors[] = "Informe a periodicidade das parcelas.";
        } else if (strtolower($periodicidade) !== "mensal" && strtolower($periodicidade) !== "semanal") {
            $errors[] = "A periodicidade precisa ser 'mensal' ou 'semanal.";
        }

        if (array_key_exists("valor_entrada", $data)) {
            $valor_entrada = $data["valor_entrada"];

            if (filter_var($valor_entrada, FILTER_VALIDATE_FLOAT) === false) {
                $errors[] = "O valor_entrada precisa ser um valor float.";
            } else if ($valor_entrada <= 0) {
                $errors[] = "O valor_entrada não pode ser menor ou igual a zero.";
            } else if ($valor_entrada > $valor_total) {
                $errors[] = "O valor_entrada não pode ser maior que o valor total.";
            }
        }

        return $errors;
    } 
}