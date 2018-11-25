<?php
namespace Rindow\Messaging\Core;

use Interop\Lenient\Messaging\Converter\MessageConverter;
use Interop\Lenient\Messaging\Message;
use Rindow\Messaging\Support\AbstractMessagingTemplate;
use Rindow\Messaging\Converter\SimpleMessageConverter;
use Rindow\Messaging\Exception;

class GenericMessagingTemplate extends AbstractMessagingTemplate
{
    protected $destinationResolver;
    protected $pubSubDomain;

    public function __construct(
        /*DestinationResolver*/ $destinationResolver=null,
        /*MessageConverter*/ $messageConverter=null)
    {
        if($messageConverter) {
            $this->setMessageConverter($messageConverter);
        }
        if($destinationResolver) {
            $this->setDestinationResolver($destinationResolver);
        }
    }

    public function setMessageConverter($messageConverter)
    {
        $this->messageConverter = $messageConverter;
    }

    public function getMessageConverter()
    {
        if($this->messageConverter===null)
            $this->messageConverter = new SimpleMessageConverter();
        return $this->messageConverter;
    }

    protected function doReceive($destination)
    {
        return $destination->receive();
    }

    protected function doSend($destination, Message $message)
    {
        $destination->send($message);
    }
    
    protected function doSendAndReceive($destination, Message $requestMessage)
    {
        return $destination->sendAndReceive($destination,$message);
    }
}