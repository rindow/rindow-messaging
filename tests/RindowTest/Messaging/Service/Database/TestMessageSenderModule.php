<?php
namespace RindowTest\Messaging\Service\Database;

class TestMessageSenderModule
{
    public function getConfig()
    {
        return array(
            'container' => array(
                'components' => array(
                    'RindowTest\\Messaging\\Service\\Database\\TestMessageSender' => array(
                        'properties' => array(
                            'messagingTemplate' => array('ref'=>'RindowTest\\Messaging\\Service\\Database\\SenderMessageTemplate'),
                            'queueName' => array('config'=>'messaging::sender::queueName'),
                        ),
                    ),
                    'RindowTest\\Messaging\\Service\\Database\\SenderMessageTemplate' => array(
                        'class' => 'Rindow\\Messaging\\Core\\GenericMessagingTemplate',
                        'properties' => array(
                            'destinationResolver' => array('ref'=>'Rindow\\Messaging\\Service\\Database\\DefaultDestinationResolver'),
                        ),
                    ),
                    'RindowTest\\Messaging\\Service\\Database\\MessageSenderAdvisor' => array(
                        'class' => 'Rindow\\Transaction\\Support\\TransactionAdvisor',
                        'properties' => array(
                            'transactionManager' => array('ref'=>'Rindow\\Messaging\\Service\\Database\\DefaultTransactionManager'),
                        ),
                        'proxy' => 'disable',
                    ),
                ),
            ),
            'aop' => array(
                'intercept_to' => array(
                    'RindowTest\\Messaging\\Service\\Database\\TestMessageSender'=>true,
                ),
                'aspects' => array(
                    'RindowTest\\Messaging\\Service\\Database\\MessageSenderAdvisor' => array(
                        'advices' => array(
                            array(
                                'type' => 'around',
                                'pointcut' => 'execution(RindowTest\\Messaging\\Service\\Database\\TestMessageSender::run())',
                                'method' => 'required',
                            ),
                        ),
                    ),
                ),
            ),
        );
    }

    public function invoke($moduleManager)
    {
        $app = $moduleManager->getServiceLocator()
            ->get('RindowTest\\Messaging\\Service\\Database\\TestMessageSender');
        $app->run();
    }
}
