<?php

declare(strict_types=1);

spl_autoload_register(function ($class) {
    require __DIR__ . "/src/$class.php";
});

//Registro as funções de tratamento de exceção
set_error_handler("ErrorHandler::handleError");
set_exception_handler("ErrorHandler::handleException");

//Defino o tipo de conteúdo da resposta da API como JSON
header("Content-type: application/json; charset=UTF-8");

$parts = explode("/", $_SERVER['REQUEST_URI']);

if ($parts[2] != "carne") {
    http_response_code(404);
    exit;
}

$id = $parts[3] ?? null;

$config = parse_ini_file("app.ini");

$database = new Database(
    $config["db_host"], 
    $config["db_name"], 
    $config["db_user"], 
    $config["db_password"]
);

$parcelaGateway = new ParcelaGateway($database);
$carneGateway = new CarneGateway($database);

$carneController = new CarneController($carneGateway, $parcelaGateway);
$carneController->processaRequisicao($_SERVER['REQUEST_METHOD'] ,$id);