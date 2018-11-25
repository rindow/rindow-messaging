<?php
namespace Rindow\Messaging\Service\Database;

use Rindow\Messaging\Exception;

class MessageHandlerApplication
{
    protected $handler;
    protected $subscribes = array();
    protected $bulkCount = 1;
    protected $transactionBoundary;

    public function setHandler(/* MessageHandler */$handler)
    {
        $this->handler = $handler;
    }

    public function setDestinationResolver($destinationResolver)
    {
        $this->destinationResolver = $destinationResolver;
    }

    public function setTransactionBoundary($transactionBoundary)
    {
        $this->transactionBoundary = $transactionBoundary;
    }

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function run()
    {
        if(!$this->handler)
            throw new Exception\DomainException('The message handler is not specified.');

        if(isset($this->config['bulkReceive']))
            $this->bulkCount = $this->config['bulkReceive'];
        if(isset($this->config['source']))
            $this->subscribes = array($this->config['source']);
        if(isset($this->config['subscribe']))
            $this->subscribes = array_merge($this->subscribes,$this->config['subscribe']);

        while($this->transactionBoundary->required(array($this,'transactional'))) {
            ;
        }
    }

    public function transactional()
    {
        $channel = $this->destinationResolver
                                ->resolveDestination('dummy');
        $context = $channel->getContext();
        if(count($this->subscribes)) {
            $this->subscribe($context);
        }
        $messages = $context->bulkReceive($this->bulkCount);
        if($messages==null)
            return false;
        foreach($messages as $message) {
            $this->handler->handleMessage($message);
        }
        return true;
    }

    protected function subscribe($context)
    {
        foreach($this->subscribes as $name) {
            $context->subscribe($name);
        }
    }
}
