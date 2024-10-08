# CarneBillingAPI

## Projeto
**CarneBillingAPI** é uma API REST desenvolvida em PHP para criar e gerenciar carnês de cobrança, facilitando o controle de parcelas e pagamentos. A API utiliza um banco de dados MySQL e foi projetada para ser simples de configurar e utilizar.

## Requisitos
- **WampServer** (recomendado) ou qualquer servidor web compatível com PHP.
- PHP 8.3.
- MySQL.

## Passos para Instalação e Execução

1. **Clone o Repositório**:
   ```bash
   git clone https://github.com/Lipinicki/CarneBillingAPI.git
   ```

2. **Configure o Banco de Dados**:
   - Execute o script `cria_db.sql` no seu banco de dados MySQL para criar as tabelas necessárias.
   - Atualize as informações de conexão no arquivo `app.ini` com as credenciais do seu banco de dados.

3. **Configure o Servidor Web**:
   - Use o WampServer para hospedar a aplicação.
   - Coloque o projeto na pasta `www` do WampServer.
   - Verifique se o módulo de reescrita (`mod_rewrite`) está ativado no Apache.

4. **Acesse a API**:
   - Inicie o WampServer e acesse a API via navegador ou ferramenta de testes, como o Postman.
   - A URL de acesso será algo como `http://localhost/CarneBillingAPI/carne`.

## API Endpoints
- **GET** `/carne/{id}`: Retorna as parcelas de um carnê específico.
- **POST** `/carne`: Cria um novo carnê.

## Exemplos de Requisição
### POST /carne
```json
{
    "valor_total": 100.00,
    "qtd_parcelas": 12,
    "data_primeiro_vencimento": "2024-08-01",
    "periodicidade": "mensal"
}
```

### POST /carne (Valor de entrada)
```json
{
    "valor_total": 0.30,
    "qtd_parcelas": 2,
    "data_primeiro_vencimento": "2024-08-01",
    "periodicidade": "semanal",
    "valor_entrada": 0.10
}
```

## Licença
Este projeto está licenciado sob a licença MIT. Consulte o arquivo `LICENSE.md` para mais detalhes.
