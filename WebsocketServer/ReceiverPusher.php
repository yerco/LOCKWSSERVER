<?php

namespace WebsocketServer;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;
use Monolog\Logger;


// Web Application Messaging Protocol (check autobahn.js)
class ReceiverPusher implements WampServerInterface
{
    private $logger;

    public function __construct(Logger $logger = null) {
        $this->logger = $logger;
    }

    /**
     * A lookup of all the topics clients have subscribed to
     */
    protected $subscribedTopics = array(
        "gateway_record"    => "gateway JSON Packet"
    );

    public function onOpen(ConnectionInterface $conn) {
        if ($this->logger) {
            $this->logger->info(
                "onOpen",
                array(
                    'onOpen time, browser connection' =>
                        gmdate("Y-m-d\TH:i:s\Z", time()),
                    'remoteAddress' => $conn->remoteAddress
                )
            );
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // TODO: Implement onClose() method.
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        // TODO: Implement onError() method.
    }

    public function onCall(ConnectionInterface $conn, $id, $topic, array $params) {
        // In this application if clients send data it's because the user hacked around in console
        $conn->callError($id, $topic, 'You are not allowed to make calls')->close();
    }

    public function onSubscribe(ConnectionInterface $conn, $topic) {
        //var_dump($topic->getId()); // this case `gateway_record`
        $this->subscribedTopics[$topic->getId()] = $topic;
        if ($this->logger) {
            $this->logger->info(
                "onSubscribed: ",
                array(
                    "topic ID" => $topic->getId()
                )
            );
        }
    }

    public function onUnSubscribe(ConnectionInterface $conn, $topic) {
        // TODO: Implement onUnSubscribe() method.
    }

    public function onPublish(
        ConnectionInterface $conn,
        $topic,
        $event,
        array $exclude,
        array $eligible
    ) {
        // In this application if clients send data it's because the user hacked around in console
        $conn->close();
    }

    public function onNewData($entry) {
        $data = json_decode($entry, true);

        if ($this->logger) {
            $this->logger->info(
                "Event at ".
                time(),
                array(
                    'Human time' =>
                        gmdate("Y-m-d\TH:i:s\Z", time())
                )
            );
        }

        // If the lookup topic object isn't set there is no one to publish to
        if (!array_key_exists($data['category'], $this->subscribedTopics)) {
            //echo "\n\n\nwn regresa\n";
            return;
        }

        $topic = $this->subscribedTopics[$data['category']];
        if ($this->logger) {
           $this->logger->info(
               "Topic",
               array(
                   "topic" => $topic,
                   "Push server reception time" =>
                       gmdate("Y-m-d\TH:i:s\Z", time())
               )
           );
        }
        // re-send the data to all the clients
        try {
            if ($this->logger) {
                $this->logger->info(
                    "Broadcasting",
                    array(
                        "Data sent:" => $data
                    )
                );
            }
            $topic->broadcast($data);
        }
        catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->info(
                    "Broadcast stopped",
                    array(
                        "Halt time" =>
                            gmdate("Y-m-d\TH:i:s\Z", time()),
                        "message" => $e->getMessage()
                    )
                );
            }
        }
    }
}