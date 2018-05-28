<?php

namespace WebsocketServer;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

// Web Application Messaging Protocol (check autobahn.js)
class ReceiverPusher implements WampServerInterface
{
    public function __construct() {

    }

    /**
     * A lookup of all the topics clients have subscribed to
     */
    protected $subscribedTopics = array(
        "gateway_record"    => "algo"
    );

    public function onOpen(ConnectionInterface $conn) {
        // TODO: Implement onOpen() method.
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

        // If the lookup topic object isn't set there is no one to publish to
        if (!array_key_exists($data['category'], $this->subscribedTopics)) {
            //echo "\n\n\nwn regresa\n";
            return;
        }

        $topic = $this->subscribedTopics[$data['category']];
        // re-send the data to all the clients
        $topic->broadcast($data);
    }
}