<?php declare(strict_types=1);

use ShahradElahi\DurgerKing\App;
use Utilities\Routing\Response;
use Utilities\Routing\Router;
use Utilities\Routing\Utils\StatusCode;

require_once __DIR__ . '/vendor/autoload.php';

ini_set('display_errors', '1');
error_reporting(E_ALL);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

Router::resource("{$_ENV['REMOTE_URI']}/public", __DIR__ . '/public');

/*
|--------------------------------------------------------------------------
| CORREÇÃO ESSENCIAL PARA WEBAPP TELEGRAM
| Aceitar JSON enviado pelo front (Cafe.apiRequest)
|--------------------------------------------------------------------------
*/
Router::any("{$_ENV['REMOTE_URI']}/telegram", function () {

    // Se o Telegram WebApp enviou JSON no corpo, decodificamos e movemos para $_POST
    if (
        isset($_SERVER['CONTENT_TYPE']) &&
        strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
    ) {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);

        if (is_array($json)) {
            // agora o App()->resolve() vai conseguir ler order_data, comment, etc.
            $_POST = array_merge($_POST, $json);
        }
    }

    // processamento normal do DurgerKing
    (new App())->resolve();

    // resposta para evitar timeout / mostrar OK
    Response::send(StatusCode::OK, 'Bot is working...');
});


Router::any("{$_ENV['REMOTE_URI']}", function () {
    echo "Ready to serve...";
});
