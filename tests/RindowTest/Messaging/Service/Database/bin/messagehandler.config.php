<?php
return array(
    'module_manager' => array(
        'version' => 4,
        'modules' => array(
            'Rindow\\Database\\Pdo\\Transaction\\Local\\Module' => true,
            'Rindow\\Messaging\\Service\\Database\\Module' => true,
            'RindowTest\\Messaging\\Service\\Database\\TestMessageHandlerModule' => true,
        ),
        'autorun' => 'Rindow\\Messaging\\Service\\Database\\Module',
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
            'queuename' => '/queue/fooDest',
            //'timeout' => 100,
        ),
    ),
);
