<?php
return array(
    'module_manager' => array(
        'version' => 1,
        'modules' => array(
            'Rindow\\Database\\Pdo\\Transaction\\Local\\Module' => true,
            'Rindow\\Messaging\\Service\\Database\\Module' => true,
            'RindowTest\\Messaging\\Service\\Database\\TestMessageSenderModule' => true,
        ),
        'autorun' => 'RindowTest\\Messaging\\Service\\Database\\TestMessageSenderModule',
    ),
    'cache' => array(
        'enableFileCache' => false,
    ),
    'database' => array(
        'connections' => array(
            'default' => array(
                'dsn' => 'sqlite:'.__DIR__.'/../data/db.sqlite',
            ),
        ),
    ),
    'messaging'=> array(
        'handler'=>array(
            'bulkReceive' => 2,
            'source' => '/queue/dummy',
            //'timeout' => 100,
            'subscribe' => array(
                '/queue/fooDest',
                '/queue/fooDest2',
            ),
        ),
        'sender'=>array(
            'queueName' => '/queue/fooDest',
            //'timeout' => 100,
        ),
    ),
);
