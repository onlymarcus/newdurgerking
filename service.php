sudo tee /var/www/durgerking/service.php > /dev/null <<'PHP'
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

Router::any("{$_ENV['REMOTE_URI']}/telegram", function () {

    // --- DEBUG: log headers, content-type, raw body and $_POST to /tmp/telegram_debug.log
    $logFile = '/tmp/telegram_debug.log';
    $now = date('c');
    $headers = [];
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    } else {
        foreach ($_SERVER as $k => $v) {
            if (strpos($k, 'HTTP_') === 0) {
                $headers[$k] = $v;
            }
        }
    }
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $rawBody = @file_get_contents('php://input');

    $entry = [
        'time' => $now,
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? '',
        'content_type' => $contentType,
        'headers' => $headers,
        'raw_body' => $rawBody,
        'post_superglobal' => $_POST,
        'get_superglobal' => $_GET,
        'server' => [
            'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? '',
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '',
        ],
    ];

    file_put_contents($logFile, json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL . "----" . PHP_EOL, FILE_APPEND);

    // If JSON, decode it into $_POST
    if (strpos($contentType, 'application/json') !== false) {
        $json = json_decode($rawBody, true);
        if (is_array($json)) {
            $_POST = array_merge($_POST, $json);
        }
    }

    try {
        (new App())->resolve();
        Response::send(StatusCode::OK, 'Bot is working...');
    } catch (\Throwable $e) {
        $err = [
            'time' => date('c'),
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];
        file_put_contents($logFile, json_encode($err, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL . "====" . PHP_EOL, FILE_APPEND);
        Response::send(500, 'Internal Server Error');
    }
});

Router::any("{$_ENV['REMOTE_URI']}", function () {
    echo "Ready to serve...";
});
PHP
