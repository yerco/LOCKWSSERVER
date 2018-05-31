<?php

namespace WebsocketServer;

use React;
use React\EventLoop\Factory;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Wamp\WampServer;
use ZMQ;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require __DIR__ . '/../vendor/autoload.php';

class PushServer
{
    private $loop;
    private $logger;

    public function __construct(Logger $logger = null) {
        $this->loop = Factory::create();
        $this->logger = $logger;
    }

    public function core() {
        $pusher = new ReceiverPusher($this->logger);

        // Listen for the web server to make a ZeroMQ push after an ajax request
        $context = new React\ZMQ\Context($this->loop);
        $pull = $context->getSocket(ZMQ::SOCKET_PULL);
        // Binding to 127.0.0.1 means the only client that can connect is itself
        /* DEVELOPMENT */
        //$pull->bind('tcp://127.0.0.1:5555');
        /* PRODUCTION */
        //$pull->bind('tcp://188.166.11.160:5555');
        /* DOCKER */
        //$pull->bind('tcp://lock8dockerized_default:5556');
        //$pull->bind('tcp://127.0.0.1:5556');
        $pull->bind('tcp://0.0.0.0:5556');
        $pull->on('message', array($pusher, 'onNewData'));

        if ($this->logger) {
            $this->logger->info(
                "PUSHSERVER started at: ".
                time(),
                array(
                    'Human time' =>
                        gmdate("Y-m-d\TH:i:s\Z", time())
                )
            );
        }

        // Set up our WebSocket server for clients wanting real-time updates
        $webSock = new React\Socket\Server('0.0.0.0:8028', $this->loop); //
        // Binding to 0.0.0//.0 means remotes can connect
        $webServer = new IoServer(
            new HttpServer(
                new WsServer(
                    new WampServer(
                        $pusher
                    )
                )
            ),
            $webSock
        );
        $this->loop->run();
    }

}

// create a log channel
try {
    $log = new Logger('name');
    $log->pushHandler(new StreamHandler('websocket_server.log',
        Logger::DEBUG));
}
catch (\Exception $e) {
    echo "Logger creation problem: " . $e->getMessage();
}

$push_server = new PushServer($log);
$push_server->core();


