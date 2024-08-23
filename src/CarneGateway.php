<?php

class CarneGateway 
{
    private PDO $connection;

    public function __construct(Database $database)
    {
        $this->connection = $database->getConnection();
    }

    /**
     * Cria um novo carne e retorno seu id.
     */
    public function create() : int
    {
        $sql = "INSERT INTO Carne (Id) VALUES (NULL);";

        $this->connection->exec($sql);

        return (int) $this->connection->lastInsertId();
    }
}   