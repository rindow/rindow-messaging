<?php
namespace Rindow\Messaging\Service\Database;

class Module
{
    public function getConfig()
    {
        return array(
            'container' => array(
                /*
                * Must set aliases for your database by the module manager.
                *
                * 'aliases' => array(
                *    'Rindow\\Messaging\\Service\\Database\\DefaultDataSource' => 'Your Database Interface',
                *    'Rindow\\Messaging\\Service\\Database\\DefaultTransactionManager' => 'Your Transation Manager Interface',
                *    'Rindow\\Messaging\\Service\\Database\\DefaultTransactionSynchronizationRegistry' => 'Your Transaction Synchronization Registry',
                *),
                */
                'components' => array(
                    'Rindow\\Messaging\\Service\\Database\\DefaultDestinationResolver' => array(
                        'class' => 'Rindow\\Messaging\\Service\\Database\\DestinationResolver',
                        'properties' => array(
                            /* Must set aliases for your database by the module manager.
                            * 'queueDriverClass' => array('value'=>'Your queue driver name'),
                            */
                            'queueDriverClass' => array('value'=>'Rindow\\Messaging\\Service\\Database\\GenericQueueDriver'),
                            'dataSource' => array('ref'=>'Rindow\\Messaging\\Service\\Database\\DefaultDataSource'),
                            'messageFactory' => array('ref'=>'Rindow\\Messaging\\DefaultMessageFactory'),
                            'synchronizationRegistry' => array('ref'=>'Rindow\\Messaging\\Service\\Database\\DefaultTransactionSynchronizationRegistry'),
                        ),
                        'proxy' => 'disable',
                    ),
                    'Rindow\\Messaging\\DefaultMessageFactory'=>array(
                        'class'=>'Rindow\\Messaging\\Support\\MessageFactory',
                        'proxy' => 'disable',
                    ),
                    'Rindow\\Messaging\\Service\\Database\\DefaultMessageHandlerApplication' => array(
                        'class' => 'Rindow\\Messaging\\Service\\Database\\MessageHandlerApplication',
                        'properties' => array(
                            'destinationResolver' => array('ref'=>'Rindow\\Messaging\\Service\\Database\\DefaultDestinationResolver'),
                            'transactionBoundary' => array('ref'=>'Rindow\\Messaging\\Service\\Database\\DefaultTransactionBoundary'),
                        ),
                    ),
                    'Rindow\\Messaging\\Service\\Database\\DefaultTransactionBoundary' => array(
                        'class' => 'Rindow\\Transaction\\Support\\TransactionBoundary',
                        'properties' => array(
                            'transactionManager' => array('ref'=>'Rindow\\Messaging\\Service\\Database\\DefaultTransactionManager'),
                        ),
                        'proxy' => 'disable',
                    ),
                    'Rindow\\Messaging\\Service\\Database\\DefaultGenericMessagingTemplate' => array(
                        'class' => 'Rindow\\Messaging\\Core\\GenericMessagingTemplate',
                        'properties' => array(
                            'destinationResolver' => array('ref'=>'Rindow\\Messaging\\Service\\Database\\DefaultDestinationResolver'),
                        ),
                    ),
                ),
            ),
        );
    }

    public function invoke($moduleManager)
    {
        $app = $moduleManager->getServiceLocator()
            ->get('Rindow\\Messaging\\Service\\Database\\DefaultMessageHandlerApplication');
        try {
            $app->run();
            //$app->getDestinationResolver()->getResourceManager()->close();
        } catch(\Exception $e) {
            //$app->getDestinationResolver()->getResourceManager()->close();
            throw $e;
        }
    }
}
