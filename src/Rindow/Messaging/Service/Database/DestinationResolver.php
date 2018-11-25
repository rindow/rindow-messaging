<?php
namespace Rindow\Messaging\Service\Database;

use Interop\Lenient\Messaging\Core\DestinationResolver as DestinationResolverInterface;
use Rindow\Messaging\Exception;

class DestinationResolver implements DestinationResolverInterface
{
    protected $queueDriverClass;
    protected $dataSource;
    protected $messageFactory;
    protected $synchronizationRegistry;
    protected $noTransactionContext;

    public function __construct(
        $queueDriverClass=null,
        $dataSource=null,
        $messageFactory=null,
        $synchronizationRegistry=null)
    {
        if($queueDriverClass)
            $this->setQueueDriverClass($queueDriverClass);
        if($dataSource)
            $this->setDataSource($dataSource);
        if($messageFactory)
            $this->setMessageFactory($messageFactory);
        if($synchronizationRegistry)
            $this->setSynchronizationRegistry($synchronizationRegistry);
    }

    public function setQueueDriverClass($queueDriverClass)
    {
        $this->queueDriverClass = $queueDriverClass;
    }

    public function setDataSource($dataSource)
    {
        $this->dataSource = $dataSource;
    }

    public function setMessageFactory($messageFactory)
    {
        $this->messageFactory = $messageFactory;
    }

    public function setSynchronizationRegistry($synchronizationRegistry)
    {
        $this->synchronizationRegistry = $synchronizationRegistry;
    }

    public function resolveDestination($name)
    {
        $messageChannel = new MessageChannel($this->getCurrentContext(),$name);
        return $messageChannel;
    }

    public function getCurrentContext()
    {
        $transacted=false;
        if($this->synchronizationRegistry) {
            $transaction = $this->synchronizationRegistry->getTransactionKey();
            if($transaction) {
                $transacted=true;
            }
        }
        if(!$transacted) {
            if($this->noTransactionContext==null)
                $this->noTransactionContext = $this->createContext($transacted=false);
            return $this->noTransactionContext;
        }
        $context = $this->synchronizationRegistry->getResource($this);
        if($context)
            return $context;
        $context = $this->createContext($transacted=true);
        $this->synchronizationRegistry->putResource($this,$context);
        $this->synchronizationRegistry->registerInterposedSynchronization($context);
        return $context;
    }

    protected function createContext($transacted)
    {
        if(isset($this->config['driver']) && is_array($this->config['driver']))
            $config = $this->config['driver'];
        else
            $config = null;
        $connection = $this->dataSource->getConnection();
        $driver = $this->createDriver($connection, $config);
        $context = new MessageContext($driver,$transacted, $this->messageFactory);
        return $context;
    }

    protected function createDriver($connection, $config)
    {
        if($this->queueDriverClass==null)
            throw new Exception\DomainException('the queue driver name is not specified.');
        $class = $this->queueDriverClass;
        return new $class($connection, $config);
    }
}
