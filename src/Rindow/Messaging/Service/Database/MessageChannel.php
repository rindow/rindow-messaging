<?php
namespace Rindow\Messaging\Service\Database;

use Interop\Lenient\Messaging\PollableChannel;
use Interop\Lenient\Messaging\MessageChannel as MessageChannelInterface;

class MessageChannel implements MessageChannelInterface
{
    protected $context;
    protected $destination;

    public function __construct($context,$destination)
    {
        $this->context = $context;
        $this->destination = $destination;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function send(/*Message */$message, $timeout=null)
    {
        $this->context->send($this->destination, $message, $timeout);
    }

    public function receive($timeout=null)
    {
        return $this->context->receive($this->destination, $timeout);
    }

    public function close()
    {
        $this->context = null;
        $this->destination = null;
    }
}
