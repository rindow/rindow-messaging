<?php
namespace Rindow\Messaging\Service\Database;

use Interop\Lenient\Transaction\Synchronization;

class MessageContext implements Synchronization
{
    protected $driver;
    protected $sendMessages = array();
    protected $receiveMessages = array();
    protected $transacted = false;
    protected $messageFactory;

    public function __construct($driver, $transacted, $messageFactory)
    {
        $this->driver = $driver;
        $this->transacted = $transacted;
        $this->messageFactory = $messageFactory;
    }

    public function setDriver($driver)
    {
        $this->driver = $driver;
    }

    public function getDriver()
    {
        return $this->driver;
    }

    public function close()
    {
        $this->sendMessages = array();
        $this->receiveMessages = array();
    }

    public function send($destination, /*Message*/ $message, $timeout=null)
    {
        $data = $this->makeMessageData($message, $destination);
        if($this->transacted)
            array_push($this->sendMessages, $data);
        else {
            $this->getDriver()->send($destination,serialize($data));
        }
    }

    public function beforeCompletion()
    {
        if(count($this->sendMessages) || count($this->receiveMessages))
            $driver = $this->getDriver();
        foreach($this->sendMessages as $data) {
            $destination = $data['h']['destination'];
            $driver->send($destination,serialize($data));
        }
        foreach($this->receiveMessages as $frame) {
            $driver->ack($frame);
        }
    }

    public function afterCompletion($success)
    {
        $this->sendMessages = array();
        $this->receiveMessages = array();
    }

    protected function makeMessageData($message, $destination)
    {
        $data = array();
        $data['h'] = $message->getHeaders();
        $data['h']['destination'] = $destination;
        unset($data['h']['message-id']);
        unset($data['h']['subscribe']);
        unset($data['h']['handle']);
        unset($data['h']['created']);
        $data['p'] = $message->getPayload();
        return $data;
    }

    public function receive($destination,$timeout=null)
    {
        $frame = $this->getDriver()->receive($destination);
        if($frame===false)
            return null;
        if($this->transacted)
            array_push($this->receiveMessages, $frame);
        else
            $this->getDriver()->ack($frame);
        return $this->makeMessageFromFrame($frame);
    }

    public function bulkReceive($count,$destination=null,$timeout=null)
    {
        $frames = $this->getDriver()->receiveFrames($count,$destination);
        if(count($frames)==0)
            return null;
        if($this->transacted)
            array_push($this->receiveMessages, $frames[0]);
        else
            $this->getDriver()->ackFrames($frames);
        $messages = array();
        foreach ($frames as $frame) {
            $messages[] = $this->makeMessageFromFrame($frame);
        }
        return $messages;
    }

    protected function makeMessageFromFrame($frame)
    {
        $data = unserialize($frame->body);
        $data['h']['message-id'] = $frame->id;
        $data['h']['subscription'] = $frame->queue_id;
        $data['h']['handle'] = $frame->handle;
        $data['h']['created'] = $frame->created;
        $message = $this->messageFactory->createMessage();
        $message->setHeaders($data['h']);
        $message->setPayload($data['p']);
        return $message;
    }

    public function subscribe($queue)
    {
        $this->getDriver()->subscribe($queue);
    }

    public function unsubscribe($queue)
    {
        $this->getDriver()->unsubscribe($queue);
    }
}
