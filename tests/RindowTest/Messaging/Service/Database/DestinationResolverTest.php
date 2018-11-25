<?php
namespace RindowTest\Messaging\Service\Database\DestinationResolverTest;

use PHPUnit\Framework\TestCase;
use Rindow\Messaging\Service\Database\QueueDriver;
use Rindow\Messaging\Service\Database\DestinationResolver;
use Rindow\Messaging\Service\Database\QueueFrame;
use Rindow\Messaging\Support\MessageFactory;
use Rindow\Messaging\Core\GenericMessagingTemplate;
use Interop\Lenient\Transaction\ResourceManager;
use Rindow\Transaction\Local\TransactionManager;
use Rindow\Transaction\Support\TransactionSynchronizationRegistry;

class TestLogger
{
    public $logdata = array();
    public function log($message)
    {
        $this->logdata[] = $message;
    }
    public function debug($message)
    {
        $this->log($message);
    }
    public function error($message)
    {
        $this->log($message);
    }
}

class TestDestinationResolver extends DestinationResolver
{
//    protected function createDriver($connection, $config)
//    {
//        return new TestQueueDriver($connection);
//    }
}

class TestQueueDriver implements QueueDriver
{
    protected $connection;
    protected $logger;

    public function __construct($connection,$config)
    {
        $this->connection = $connection;
        $this->logger = $connection->logger;
    }

    public function getQueueMessageTable(){}
    public function getQueueNameTable(){}
    public function send($queue,$msg){
        $data = unserialize($msg);
        //$this->conn->connect();
        if($this->logger==null) throw new \Exception("Error Processing Request", 1);
        
        $this->logger->log('SEND:'.$queue.':'.$data['p']);
    }
    public function setAckTimeout($seconds){}
    public function subscribe($queue){
        $this->logger->log('SUBSCRIBE');
    }
    public function unsubscribe($queue){
        $this->logger->log('UNSUBSCRIBE');
    }
    public function receive($queue=null){
        //$this->logger->connect();
        $this->logger->log('RECEIVE:'.$queue);
        $frame = new QueueFrame();
        $data['h']['destination'] = '/queue/recv';
        $data['p'] = 'fooBody';
        $frame->body = serialize($data);
        return $frame;
    }
    public function receiveFrames($count,$queue=null)
    {
        $this->logger->log('RECEIVEFRAMES');
    }
    public function ack($msg)
    {
        $this->logger->log('ACK');
    }
    public function ackFrames(array $msgs)
    {
        $this->logger->log('ACKFRAMES:'.count($msgs));
    }
    public function recover()
    {
        $this->logger->log('RECOVER');
    }
    public function close()
    {
        $this->logger->log('CLOSE');
    }
    public function createSchema(){}
    public function dropSchema(){}
}

class TestDataSource
{
    public $connection;
    public $logger;
    protected $transactionManager;

    public function __construct($transactionManager=null)
    {
        $this->transactionManager = $transactionManager;
    }

    public function getTransactionManager()
    {
        return $this->transactionManager;
    }

    public function getConnection($username=null,$password=null)
    {
        if($this->connection==null) {
            $this->connection = new TestDbConnection();
            $this->connection->logger = $this->logger;
        }
        $transaction = null;
        if($this->transactionManager) {
            $transaction = $this->transactionManager->getTransaction();
        }
        if($transaction)
            $transaction->enlistResource($this->connection);
        return $this->connection;
    }
}

class TestDbConnection implements ResourceManager
{
    public $logger;
    protected $listener;
    protected $connected = false;
    protected $name;

    public function getName()
    {
        return $this->name;
    }

    public function getResourceManager()
    {
        return $this;
    }

    //public function setConnectedEventListener($listener)
    //{
    //    $this->log('setConnectedEventListener');
    //    $this->listener = $listener;
    //}
    public function setTimeout($seconds) {}
    public function isNestedTransactionAllowed()
    {
        return true;
    }
    //public function isConnected()
    //{
    //    return $this->connected;
    //}
    //public function connect()
    //{
    //    if($this->connected)
    //        return;
    //    $this->log('connect');
    //    $this->connected = true;
    //    if($this->listener)
    //        call_user_func($this->listener,$this);
    //}

    public function beginTransaction($definition=null)
    {
        //$this->connect();
        $this->logger->log('beginTransaction');
    }
    public function commit()
    {
        //$this->connect();
        $this->logger->log('commit');
    }
    public function rollback()
    {
        //$this->connect();
        $this->logger->log('rollback');
    }
    //public function createSavepoint()
    //{
    //    $this->connect();
    //    $this->log('createSavepoint');
    //    return 'SAVEPOINT';
    //}
    //public function releaseSavepoint($savepoint)
    //{
    //    $this->connect();
    //    $this->log('releaseSavepoint');
    //}
    //public function rollbackSavepoint($savepoint)
    //{
    //    $this->connect();
    //    $this->log('rollbackSavepoint');
    //}
    public function suspend()
    {
        //$this->connect();
        $this->logger->log('suspend');
        return 'txObject';
    }
    public function resume($txObject)
    {
        //$this->connect();
        $this->logger->log('resume');
    }
}

class Test extends TestCase
{
    public function testSendAndBeginAndCommit()
    {
        $logger = new TestLogger();
        $tx = new TransactionManager();
        $dataSource = new TestDataSource($tx);
        $dataSource->logger = $logger;
        $messageFactory = new MessageFactory();
        $synchronizationRegistry = new TransactionSynchronizationRegistry($tx);
        $destinationResolver = new TestDestinationResolver(
            __NAMESPACE__.'\\TestQueueDriver',
            $dataSource,$messageFactory,$synchronizationRegistry);

        $message = $messageFactory->createMessage();
        $message->setPayload('barMsg');

        $logger->log('[begin transaction]');
        $tx->begin();

        $logger->log('[resolve destination]');
        $name = '/queue/fooDest';
        $channel = $destinationResolver->resolveDestination($name);

        $logger->log('[send message]');
        $channel->send($message);

        $logger->log('[commit transaction]');
        $tx->commit();

        $this->assertEquals(array(
            '[begin transaction]',
            '[resolve destination]',
            'beginTransaction',
            '[send message]',
            '[commit transaction]',
            'SEND:/queue/fooDest:barMsg',
            'commit',
            ),$logger->logdata);
    }

    public function testSendAndReceiveAndBeginAndCommitNormal()
    {
        $logger = new TestLogger();
        $tx = new TransactionManager();
        $dataSource = new TestDataSource($tx);
        $dataSource->logger = $logger;
        $messageFactory = new MessageFactory();
        $synchronizationRegistry = new TransactionSynchronizationRegistry($tx);
        $destinationResolver = new TestDestinationResolver(
            __NAMESPACE__.'\\TestQueueDriver',
            $dataSource,$messageFactory,$synchronizationRegistry);

        $logger->log('[begin transaction]');
        $tx->begin();

        $logger->log('[resolve destination]');
        $name = '/queue/fooDest';
        $channel = $destinationResolver->resolveDestination($name);
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(1,$log);
        //$this->assertEquals('setConnectedEventListener',$log[0]);

        $logger->log('[receive message]');
        $message = $channel->receive();
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(4,$log);
        //$this->assertEquals('connect',$log[1]);
        //$this->assertEquals('beginTransaction',$log[2]);
        //$this->assertEquals('RECEIVE:/queue/fooDest',$log[3]);

        $message = $messageFactory->createMessage();
        $message->setPayload('barMsg');
        $logger->log('[send message]');
        $channel->send($message);
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(4,$log);
        //$logger->log('[commit transaction]');
        $logger->log('[commit transaction]');
        $tx->commit();

        //$log = $dataSource->connection->getLog();
        //$this->assertCount(7,$log);
        //$this->assertEquals('SEND:/queue/fooDest:barMsg',$log[4]);
        //$this->assertEquals('ACK',$log[5]);
        //$this->assertEquals('commit',$log[6]);

        $this->assertEquals(array(
            '[begin transaction]',
            '[resolve destination]',
            'beginTransaction',
            '[receive message]',
            'RECEIVE:/queue/fooDest',
            '[send message]',
            '[commit transaction]',
            'SEND:/queue/fooDest:barMsg',
            'ACK',
            'commit',
            ),$logger->logdata);
    }

    public function testSendAndBeginAndRollback()
    {
        $logger = new TestLogger();
        $tx = new TransactionManager();
        $dataSource = new TestDataSource($tx);
        $dataSource->logger = $logger;
        $messageFactory = new MessageFactory();
        $synchronizationRegistry = new TransactionSynchronizationRegistry($tx);
        $destinationResolver = new TestDestinationResolver(
            __NAMESPACE__.'\\TestQueueDriver',
            $dataSource,$messageFactory,$synchronizationRegistry);

        $message = $messageFactory->createMessage();
        $message->setPayload('barMsg');

        $logger->log('[begin transaction]');
        $tx->begin();

        $logger->log('[resolve destination]');
        $name = '/queue/fooDest';
        $channel = $destinationResolver->resolveDestination($name);
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(1,$log);
        //$this->assertEquals('setConnectedEventListener',$log[0]);

        $logger->log('[send message]');
        $channel->send($message);
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(1,$log);
        $logger->log('[rollback transaction]');
        $tx->rollback();

        //$log = $dataSource->connection->getLog();
        //$this->assertCount(1,$log);

        $this->assertEquals(array(
            '[begin transaction]',
            '[resolve destination]',
            'beginTransaction',
            '[send message]',
            '[rollback transaction]',
            'rollback',
            ),$logger->logdata);
    }

    public function testSendAndReceiveAndBeginAndRollback()
    {
        $logger = new TestLogger();
        $tx = new TransactionManager();
        $dataSource = new TestDataSource($tx);
        $dataSource->logger = $logger;
        $messageFactory = new MessageFactory();
        $synchronizationRegistry = new TransactionSynchronizationRegistry($tx);
        $destinationResolver = new TestDestinationResolver(
            __NAMESPACE__.'\\TestQueueDriver',
            $dataSource,$messageFactory,$synchronizationRegistry);

        $logger->log('[begin transaction]');
        $tx->begin();

        $logger->log('[resolve destination]');
        $name = '/queue/fooDest';
        $channel = $destinationResolver->resolveDestination($name);
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(1,$log);
        //$this->assertEquals('setConnectedEventListener',$log[0]);

        $logger->log('[receive message]');
        $message = $channel->receive();
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(4,$log);
        //$this->assertEquals('connect',$log[1]);
        //$this->assertEquals('beginTransaction',$log[2]);
        //$this->assertEquals('RECEIVE:/queue/fooDest',$log[3]);

        $message = $messageFactory->createMessage();
        $message->setPayload('barMsg');
        $logger->log('[send message]');
        $channel->send($message);
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(4,$log);

        $logger->log('[rollback transaction]');
        $tx->rollback();

        //$log = $dataSource->connection->getLog();
        //$this->assertCount(5,$log);
        //$this->assertEquals('rollback',$log[4]);
        $this->assertEquals(array(
            '[begin transaction]',
            '[resolve destination]',
            'beginTransaction',
            '[receive message]',
            'RECEIVE:/queue/fooDest',
            '[send message]',
            '[rollback transaction]',
            'rollback',
            ),$logger->logdata);
    }

    public function testSendWithoutTransaction()
    {
        $logger = new TestLogger();
        $tx = new TransactionManager();
        $dataSource = new TestDataSource($tx);
        $dataSource->logger = $logger;
        $messageFactory = new MessageFactory();
        $synchronizationRegistry = new TransactionSynchronizationRegistry($tx);
        $destinationResolver = new TestDestinationResolver(
            __NAMESPACE__.'\\TestQueueDriver',
            $dataSource,$messageFactory,$synchronizationRegistry);

        $message = $messageFactory->createMessage();
        $message->setPayload('barMsg');

        $logger->log('[resolve destination]');
        $name = '/queue/fooDest';
        $channel = $destinationResolver->resolveDestination($name);
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(0,$log);

        $logger->log('[send message]');
        $channel->send($message);
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(2,$log);
        //$this->assertEquals('connect',$log[0]);
        //$this->assertEquals('SEND:/queue/fooDest:barMsg',$log[1]);

        $this->assertEquals(array(
            '[resolve destination]',
            '[send message]',
            'SEND:/queue/fooDest:barMsg',
            ),$logger->logdata);
    }


    public function testSendAndReceiveWithoutTransaction()
    {
        $logger = new TestLogger();
        $tx = new TransactionManager();
        $dataSource = new TestDataSource($tx);
        $dataSource->logger = $logger;
        $messageFactory = new MessageFactory();
        $synchronizationRegistry = new TransactionSynchronizationRegistry($tx);
        $destinationResolver = new TestDestinationResolver(
            __NAMESPACE__.'\\TestQueueDriver',
            $dataSource,$messageFactory,$synchronizationRegistry);

        $logger->log('[resolve destination]');
        $name = '/queue/fooDest';
        $channel = $destinationResolver->resolveDestination($name);
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(0,$log);

        $logger->log('[receive message]');
        $message = $channel->receive();
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(3,$log);
        //$this->assertEquals('connect',$log[0]);
        //$this->assertEquals('RECEIVE:/queue/fooDest',$log[1]);
        //$this->assertEquals('ACK',$log[2]);

        $message = $messageFactory->createMessage();
        $message->setPayload('barMsg');
        $logger->log('[send message]');
        $channel->send($message);
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(4,$log);
        //$this->assertEquals('SEND:/queue/fooDest:barMsg',$log[3]);

        $this->assertEquals(array(
            '[resolve destination]',
            '[receive message]',
            'RECEIVE:/queue/fooDest',
            'ACK',
            '[send message]',
            'SEND:/queue/fooDest:barMsg',
            ),$logger->logdata);
    }

    public function testSendAndReceiveOtherDestinationAndBeginAndCommit()
    {
        $logger = new TestLogger();
        $tx = new TransactionManager();
        $dataSource = new TestDataSource($tx);
        $dataSource->logger = $logger;
        $messageFactory = new MessageFactory();
        $synchronizationRegistry = new TransactionSynchronizationRegistry($tx);
        $destinationResolver = new TestDestinationResolver(
            __NAMESPACE__.'\\TestQueueDriver',
            $dataSource,$messageFactory,$synchronizationRegistry);

        $logger->log('[begin transaction]');
        $tx->begin();

        $logger->log('[resolve destination]');
        $name = '/queue/fooDest';
        $channel1 = $destinationResolver->resolveDestination($name);
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(1,$log);
        //$this->assertEquals('setConnectedEventListener',$log[0]);

        $logger->log('[receive message]');
        $message = $channel1->receive();
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(4,$log);
        //$this->assertEquals('connect',$log[1]);
        //$this->assertEquals('beginTransaction',$log[2]);
        //$this->assertEquals('RECEIVE:/queue/fooDest',$log[3]);

        $logger->log('[resolve destination]');
        $name = '/queue/fooDest2';
        $channel2 = $destinationResolver->resolveDestination($name);
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(4,$log);

        $message = $messageFactory->createMessage();
        $message->setPayload('barMsg');
        $logger->log('[send message]');
        $channel2->send($message);
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(4,$log);

        $logger->log('[commit transaction]');
        $tx->commit();

        //$log = $dataSource->connection->getLog();
        //$this->assertCount(7,$log);
        //$this->assertEquals('SEND:/queue/fooDest2:barMsg',$log[4]);
        //$this->assertEquals('ACK',$log[5]);
        //$this->assertEquals('commit',$log[6]);

        $this->assertEquals(array(
            '[begin transaction]',
            '[resolve destination]',
            'beginTransaction',
            '[receive message]',
            'RECEIVE:/queue/fooDest',
            '[resolve destination]',
            '[send message]',
            '[commit transaction]',
            'SEND:/queue/fooDest2:barMsg',
            'ACK',
            'commit',
            ),$logger->logdata);
    }

    public function testSendAndReceiveAndBeginAndCommitWithSuspendTransaction()
    {
        $logger = new TestLogger();
        $tx = new TransactionManager();
        $dataSource = new TestDataSource($tx);
        $dataSource->logger = $logger;
        $messageFactory = new MessageFactory();
        $synchronizationRegistry = new TransactionSynchronizationRegistry($tx);
        $destinationResolver = new TestDestinationResolver(
            __NAMESPACE__.'\\TestQueueDriver',
            $dataSource,$messageFactory,$synchronizationRegistry);

        $logger->log('[resolve destination]');
        $name = '/queue/fooDest0';
        $channel0 = $destinationResolver->resolveDestination($name);

        $message = $messageFactory->createMessage();
        $message->setPayload('trigger');
        $logger->log('[send message]');
        $channel0->send($message);
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(2,$log);
        //$this->assertEquals('connect',$log[0]);
        //$this->assertEquals('SEND:/queue/fooDest0:trigger',$log[1]);

        $logger->log('[begin transaction]');
        $tx->begin();
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(2,$log);

        $logger->log('[resolve destination]');
        $name = '/queue/fooDest';
        $channel1 = $destinationResolver->resolveDestination($name);
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(4,$log);
        //$this->assertEquals('setConnectedEventListener',$log[2]);
        //$this->assertEquals('beginTransaction',$log[3]);

        $logger->log('[receive message]');
        $message = $channel1->receive();
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(5,$log);
        //$this->assertEquals('RECEIVE:/queue/fooDest',$log[4]);

        $logger->log('[resolve destination]');
        $name = '/queue/fooDest2';
        $channel2 = $destinationResolver->resolveDestination($name);
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(5,$log);

        $message = $messageFactory->createMessage();
        $message->setPayload('barMsg');
        $logger->log('[send message]');
        $channel2->send($message);
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(5,$log);
        $logger->log('[commit transaction]');
        $tx->commit();

        //$log = $dataSource->connection->getLog();
        //$this->assertCount(8,$log);
        //$this->assertEquals('SEND:/queue/fooDest2:barMsg',$log[5]);
        //$this->assertEquals('ACK',$log[6]);
        //$this->assertEquals('commit',$log[7]);

        $this->assertEquals(array(
            '[resolve destination]',
            '[send message]',
            'SEND:/queue/fooDest0:trigger',
            '[begin transaction]',
            '[resolve destination]',
            'beginTransaction',
            '[receive message]',
            'RECEIVE:/queue/fooDest',
            '[resolve destination]',
            '[send message]',
            '[commit transaction]',
            'SEND:/queue/fooDest2:barMsg',
            'ACK',
            'commit',
            ),$logger->logdata);
    }

    public function testSendAndReceiveWithTemplate()
    {
        $logger = new TestLogger();
        $tx = new TransactionManager();
        $dataSource = new TestDataSource($tx);
        $dataSource->logger = $logger;
        $messageFactory = new MessageFactory();
        $synchronizationRegistry = new TransactionSynchronizationRegistry($tx);
        $destinationResolver = new TestDestinationResolver(
            __NAMESPACE__.'\\TestQueueDriver',
            $dataSource,$messageFactory,$synchronizationRegistry);
        $template = new GenericMessagingTemplate($destinationResolver);

        $logger->log('[begin transaction]');
        $tx->begin();

        $logger->log('[receive and convert]');
        $name = '/queue/fooDest';
        $msg = $template->receiveAndConvert($name);
        $this->assertEquals('fooBody',$msg);
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(4,$log);
        //$this->assertEquals('setConnectedEventListener',$log[0]);
        //$this->assertEquals('connect',$log[1]);
        //$this->assertEquals('beginTransaction',$log[2]);
        //$this->assertEquals('RECEIVE:/queue/fooDest',$log[3]);

        $logger->log('[convert and send]');
        $template->convertAndSend($name,'barMsg');
        //$log = $dataSource->connection->getLog();
        //$this->assertCount(4,$log);
        $logger->log('[commit transaction]');
        $tx->commit();

        //$log = $dataSource->connection->getLog();
        //$this->assertCount(7,$log);
        //$this->assertEquals('SEND:/queue/fooDest:barMsg',$log[4]);
        //$this->assertEquals('ACK',$log[5]);
        //$this->assertEquals('commit',$log[6]);
        $this->assertEquals(array(
            '[begin transaction]',
            '[receive and convert]',
            'beginTransaction',
            'RECEIVE:/queue/fooDest',
            '[convert and send]',
            '[commit transaction]',
            'SEND:/queue/fooDest:barMsg',
            'ACK',
            'commit',
            ),$logger->logdata);
    }
}
