<?php declare(strict_types=1);

use ShahradElahi\DurgerKing\App;
use Utilities\Routing\Response;
use Utilities\Routing\Router;
use Utilities\Routing\Utils\StatusCode;

require_once __DIR__ . '/vendor/autoload.php';

ini_set('display_errors', '1');
error_reporting(E_ALL);

// Carregar .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// LOG MANUAL PARA DEBUG
file_put_contents("/tmp/telegram_debug.log",
    "[" . date("Y-m-d H:i:s") . "] service.php carregado\n",
    FILE_APPEND
);

// Registrar assets estáticos
Router::resource("{$_ENV['REMOTE_URI']}/public", __DIR__ . '/public');

// Rota principal: /telegram
Router::any("{$_ENV['REMOTE_URI']}/telegram", function () {

    file_put_contents("/tmp/telegram_debug.log",
        "[" . date("Y-m-d H:i:s") . "] chamada ao /telegram\n",
        FILE_APPEND
    );

    try {
        (new App())->resolve();

        Response::send(StatusCode::OK, [
            "ok" => true,
            "message" => "Bot is working..."
        ]);
    } catch (Throwable $e) {

        file_put_contents("/tmp/telegram_debug.log",
            "[" . date("Y-m-d H:i:s") . "] ERRO: " . $e->getMessage() . "\n",
            FILE_APPEND
        );

        Response::send(StatusCode::INTERNAL_SERVER_ERROR, [
            "ok" => false,
            "error" => "Server exception: " . $e->getMessage()
        ]);
    }
});

// / (raiz)
Router::any("{$_ENV['REMOTE_URI']}", function () {
    echo "Ready to serve...";
});
