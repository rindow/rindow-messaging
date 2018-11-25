<?php
namespace Rindow\Messaging\Support;

use Interop\Lenient\Messaging\Message;
use Interop\Lenient\Messaging\Converter\MessageConverter;
use Interop\Lenient\Messaging\Core\DestinationResolver;
use Interop\Lenient\Messaging\Core\MessageOperations;
use Interop\Lenient\Messaging\Core\DestinationResolvingOperations;
use Rindow\Messaging\Exception;

abstract class AbstractMessagingTemplate implements MessageOperations,DestinationResolvingOperations
{
    protected $defaultDestinationName;
    protected $messageConverter;
    protected $destinationResolver;

    abstract protected function doReceive($destination);
    abstract protected function doSend($destination, Message $message);
    abstract protected function doSendAndReceive($destination, Message $requestMessage);

    public function setDestinationResolver(/*DestinationResolver*/ $destinationResolver)
    {
        $this->destinationResolver = $destinationResolver;
    }

    public function getDestinationResolver()
    {
        return $this->destinationResolver;
    }
    
    protected function registerDestination($destinationName,$session)
    {
        $destinationResolver = $this->getDestinationResolver();
        if($destinationResolver===null)
            throw new Exception\DomainException('destination resolver is not specifed.');
        return $destinationResolver->registerDestination($destinationName,$session);
    }

    protected function resolveDestination($destinationName)
    {
        $destinationResolver = $this->getDestinationResolver();
        if($destinationResolver===null)
            throw new Exception\DomainException('destination resolver is not specifed.');
        if($destinationName===null)
            $destinationName = $this->defaultDestinationName;
        return $destinationResolver->resolveDestination($destinationName);
    }

    public function setMessageConverter(/*MessageConverter*/ $messageConverter)
    {
        $this->messageConverter = $messageConverter;
    }

    public function getMessageConverter()
    {
        return $this->messageConverter;
    }

    public function setDefaultDestinationName($destinationName)
    {
        $this->defaultDestinationName = $destinationName;
    }

    public function send($destinationName, /*Message*/ $message)
    {
        $destination = $this->resolveDestination($destinationName);
        $this->doSend($destination, $message);
    }

    public function convertAndSend($destinationName, $payload, array $headers=null, $postProcessor=null)
    {
        if($postProcessor !==null && !is_callable($postProcessor) &&
            !($postProcessor instanceof MessagePostProcessor) ) {
            throw new Exception\InvalidArgumentException('postProcessor must be callable or type of "MessagePostProcessor" or null.');
        }
        if($headers)
            $headers = $this->processHeadersToSend($headers);
        else
            $headers = array();
        $destination = $this->resolveDestination($destinationName);
        $message = $this->getMessageConverter()->toMessage($payload,$headers);
        $message = $this->invokePostProcessing($postProcessor,$message);
        return $this->doSend($destination,$message);
    }

    protected function processHeadersToSend(array $headers)
    {
        return $headers;
    }

    public function receive($destinationName=null)
    {
        $destination = $this->resolveDestination($destinationName);
        return $this->doReceive($destination);
    }

    public function receiveAndConvert($destinationName=null,$targetClass=null)
    {
        $destination = $this->resolveDestination($destinationName);
        $message = $this->doReceive($destination);
        if(!$message)
            return null;
        return $this->getMessageConverter()->fromMessage($message,$targetClass);
    }

    public function sendAndReceive($destinationName, /*Message*/ $requestMessage)
    {
        $destination = $this->resolveDestination($destinationName);
        return $this->doSendAndReceive($destination, $requestMessage);
    }

    public function convertSendAndReceive(
        $destinationName, 
        $request, 
        array $headers=null,
        $targetClass=null, 
        $postProcessor=null)
    {
        if($postProcessor !==null && !is_callable($postProcessor) &&
            !($postProcessor instanceof MessagePostProcessor) ) {
            throw new Exception\InvalidArgumentException('postProcessor must be null or callable or type "MessagePostProcessor"');
        }
        if($headers)
            $headers = $this->processHeadersToSend($headers);
        else
            $headers = array();
        $destination = $this->resolveDestination($destinationName);
        $message = $this->getMessageConverter()->toMessage($request,$headers);
        $message = $this->invokePostProcessing($postProcessor,$message);
        $message = $this->doSendAndReceive($destination, $message);
        return $this->getMessageConverter()->fromMessage($message,$targetClass);
    }

    protected function invokePostProcessing($postProcessor,$message)
    {
        if(!$message instanceof Message)
            throw new Exception\DomainException('message convertion error in the converter "'.get_class($this->getMessageConverter()).'".');
        if($postProcessor) {
            if(is_callable($postProcessor)) {
                $message = call_user_func($postProcessor,$message);
            } else {
                $message = $postProcessor->postProcessMessage($message);
            }
            if(!$message instanceof Message)
                throw new Exception\DomainException('message convert error in a post processor.');
        }
        return $message;
    }
}
