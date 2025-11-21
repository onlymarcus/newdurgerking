<?php declare(strict_types=1);

use ShahradElahi\DurgerKing\App;
use TelegramBot\Request;
use Utilities\Routing\Response;
use Utilities\Routing\Router;
use Utilities\Routing\Utils\StatusCode;

require_once __DIR__ . '/vendor/autoload.php';

ini_set('display_errors', '1');
error_reporting(E_ALL);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// DEBUG
file_put_contents("/tmp/telegram_debug.log",
    "[" . date("Y-m-d H:i:s") . "] service.php carregado\n",
    FILE_APPEND
);

Router::resource("{$_ENV['REMOTE_URI']}/public", __DIR__ . '/public');

Router::any("{$_ENV['REMOTE_URI']}/telegram", function () {

    $raw = file_get_contents("php://input");
    file_put_contents("/tmp/telegram_debug.log",
        "[" . date("Y-m-d H:i:s") . "] INPUT RAW: $raw\n",
        FILE_APPEND
    );

    $data = json_decode($raw, true);

    // ────────────────────────────────────────────────
    // 1) HANDLER DO WEBAPP → makeOrder
    // ────────────────────────────────────────────────
    if (isset($data['method']) && $data['method'] === 'makeOrder') {

        $order = json_encode($data['order_data'], JSON_PRETTY_PRINT);

        // Envia mensagem ao admin
        Request::sendMessage([
            'chat_id' => $_ENV['ADMIN_CHAT_ID'],
            'text' => "🍔 *NOVO PEDIDO RECEBIDO*\n\n$order",
            'parse_mode' => 'Markdown'
        ]);

        file_put_contents("/tmp/telegram_debug.log",
            "[" . date("Y-m-d H:i:s") . "] Pedido enviado ao admin.\n",
            FILE_APPEND
        );

        Response::send(StatusCode::OK, ['ok' => true]);
        return;
    }

    // ────────────────────────────────────────────────
    // 2) HANDLER DO WEBAPP → checkInitData
    // ────────────────────────────────────────────────
    if (isset($data['method']) && $data['method'] === 'checkInitData') {
        Response::send(StatusCode::OK, ['ok' => true]);
        return;
    }

    // ────────────────────────────────────────────────
    // 3) SE NÃO FOR WEBAPP → trata UPDATE DO BOT
    // ────────────────────────────────────────────────
    file_put_contents("/tmp/telegram_debug.log",
        "[" . date("Y-m-d H:i:s") . "] Resolvendo como UPDATE Telegram\n",
        FILE_APPEND
    );

    try {
        (new App())->resolve();

        Response::send(StatusCode::OK, [
            "ok" => true,
            "elapsed_time" => "20ms",
            "message" => "Bot is working..."
        ]);

    } catch (Throwable $e) {
        file_put_contents("/tmp/telegram_debug.log",
            "[" . date("Y-m-d H:i:s") . "] EXCEPTION: " . $e->getMessage() . "\n",
            FILE_APPEND
        );

        Response::send(StatusCode::INTERNAL_SERVER_ERROR, [
            "ok" => false,
            "error" => $e->getMessage()
        ]);
    }
});

Router::any("{$_ENV['REMOTE_URI']}", function () {
    echo "Ready to serve...";
});
