<?php
namespace RindowTest\Messaging\Service\Database;

class TestMessageHandlerModule
{
    public function getConfig()
    {
        return array(
            'container' => array(
                'components' => array(
                    'RindowTest\\Messaging\\Service\\Database\\TestMessageHandler' => array(
                        'properties' => array(
                            'messagingTemplate' => array('ref'=>'RindowTest\\Messaging\\Service\\Database\\HandlerMessageTemplate'),
                        ),
                    ),
                    'RindowTest\\Messaging\\Service\\Database\\HandlerMessageTemplate' => array(
                        'class' => 'Rindow\\Messaging\\Core\\GenericMessagingTemplate',
                        'properties' => array(
                            'destinationResolver' => array('ref'=>'Rindow\\Messaging\\Service\\Database\\DefaultDestinationResolver'),
                        ),
                    ),
                    'Rindow\\Messaging\\Service\\Database\\DefaultMessageHandlerApplication' => array(
                        'properties' => array(
                            'handler' => array('ref'=>'RindowTest\\Messaging\\Service\\Database\\TestMessageHandler'),
                            'config' => array('config'=>'messaging::handler'),
                        ),
                    ),
                ),
            ),
        );
    }
}
