<?php
namespace RindowTest\Messaging\Service\Database;

use Rindow\Messaging\MessageHandler;

class TestMessageSender
{
    protected $messagingTemplate;
    protected $queueName;

    public function setMessagingTemplate($messagingTemplate)
    {
        $this->messagingTemplate = $messagingTemplate;
    }

    public function setQueueName($queueName)
    {
        $this->queueName = $queueName;
    }

    public function run()
    {
        $options = getopt('i' , array('init') );
        if(isset($options['i']) || isset($options['init'])) {
            $this->initialize();
            return;
        }
        $this->messagingTemplate->convertAndSend($this->queueName,'Foo');
    }

    public function initialize()
    {
        $driver = $this->messagingTemplate->getDestinationResolver()->getCurrentContext()->getDriver();
        $driver->dropSchema();
        $driver->createSchema();
    }
}
