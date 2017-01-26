<?php
namespace RabbitMQWrapper;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPSocketConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class RabbitMQ
 * @package RabbitMQWrapper
 */
class RabbitMQ
{
    /**
     * @var AMQPSocketConnection
     */
    private $connection;

    /**
     * RabbitMQ constructor.
     * @param string $host
     * @param int    $port
     * @param string $user
     * @param string $password
     * @param string $vhost
     */
    public function __construct($host = 'localhost', $port = 5672, $user = 'guest', $password = 'guest', $vhost = '/')
    {
        $this->connection = new AMQPSocketConnection($host, $port, $user, $password, $vhost);
    }

    /**
     * @return AMQPChannel
     */
    public function channel()
    {
        return $this->connection->channel();
    }

    /**
     * @return \PhpAmqpLib\Wire\IO\SocketIO
     */
    public function getSocket()
    {
        return $this->connection->getSocket();
    }

    /**
     * @param AMQPMessage $msg
     * @param             $exchange
     * @param             $routing_key
     * @param null        $timeout
     * @param AMQPChannel $channel
     * @return mixed
     * @internal param $ch
     */
    public function confirmedPublish(
        AMQPMessage $msg,
        $exchange,
        $routing_key,
        $timeout = null,
        AMQPChannel $channel = null
    ) {
    
        $ch = $channel ?: $this->channel();
        $response = true;
        $ch->set_ack_handler(function (AMQPMessage $msg) use (&$response) {
            $response = $response && true;
        });
        $ch->set_nack_handler(function (AMQPMessage $msg) use (&$response) {
            $response = $response && false;
        });
        $ch->set_return_listener(
            function ($replyCode, $replyText, $exchange, $routingKey, AMQPMessage $msg) use (&$response) {
                $response = $response && false;
            }
        );

        $ch->confirm_select();
        $ch->basic_publish($msg, $exchange, $routing_key, true);
        $ch->wait_for_pending_acks_returns($timeout);
        if (is_null($channel)) {
            $ch->close();
        }
        return $response;
    }
}
