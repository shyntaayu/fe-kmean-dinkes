<?php

declare(strict_types=1);

spl_autoload_register(function ($class) {
    require __DIR__ . "/src/$class.php";
});


require 'vendor/autoload.php';

set_error_handler("ErrorHandler::handleError");
set_exception_handler("ErrorHandler::handleException");

header("Content-type:application/json; charset=UTF-8");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods:GET,POST,OPTIONS");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers");

// $parts = explode("/", $_SERVER["REQUEST_URI"]);
// var_dump($parts);
$uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
// var_dump($uri_parts[0]);
$parts = explode("/", $uri_parts[0]);

if ($parts[3] == "daerah" || $parts[3] == "penyakit" || $parts[3] == "main" || $parts[3] == "penduduk" || $parts[3] == "jumlah") {
} else {
    http_response_code(404);
    exit;
}

$route = $parts[3] ?? null;
$id = $parts[4] ?? null;
$param = $_GET;

$database = new Database("localhost", "dinkes", "root", "");
$database->getConnection();

switch ($route) {

    case "daerah":
        $gatewayDaerah = new DaerahGateway($database);

        $controllerDaerah = new DaerahController($gatewayDaerah);
        $controllerDaerah->processRequest($_SERVER["REQUEST_METHOD"], $id);
        break;
    case "penyakit":
        $gatewayPenyakit = new PenyakitGateway($database);

        $controllerPenyakit = new PenyakitController($gatewayPenyakit);
        $controllerPenyakit->processRequest($_SERVER["REQUEST_METHOD"], $id);
        break;
    case "main":
        $gatewayMain = new MainGateway($database);

        $controllerMain = new MainController($gatewayMain);
        $controllerMain->processRequest($_SERVER["REQUEST_METHOD"], $id, $param);
        break;
    case "penduduk":
        $gatewayPenduduk = new PendudukGateway($database);

        $controllerPenduduk = new PendudukController($gatewayPenduduk);
        $controllerPenduduk->processRequest($_SERVER["REQUEST_METHOD"], $id, $param);
        break;
    case "jumlah":
        $gatewayJumlah = new JumlahGateway($database);

        $controllerJumlah = new JumlahController($gatewayJumlah);
        $controllerJumlah->processRequest($_SERVER["REQUEST_METHOD"], $id);
        break;
}
