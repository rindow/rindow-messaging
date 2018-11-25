<?php
namespace Rindow\Messaging\Service\Database;

use Rindow\Messaging\Exception;

abstract class AbstractQueueDriver implements QueueDriver
{
    const FRAME_CLASS = 'Rindow\Messaging\Service\Database\QueueFrame';

    protected static $builderAliases = array(
        'sqlite' => 'Rindow\Messaging\Service\Database\SqlBuilder\Sqlite',
        'mysql'  => 'Rindow\Messaging\Service\Database\SqlBuilder\Mysql',
        'pgsql'  => 'Rindow\Messaging\Service\Database\SqlBuilder\Pgsql',
    );

    protected $queueMessageTable = 'rindow_queue_message';
    protected $queueNameTable    = 'rindow_queue_name';
    protected $subscribing = array();
    protected $connection;
    protected $config;
    protected $timeoutsec = 360;
    protected $timeoutmicrosec = 0;
    protected $handles = array();
    protected $sqlBuilder;

    abstract public function getConnection();
    abstract protected function getDriverName($connection);
    abstract protected function execute($sql, $params);
    abstract protected function query($sql,$params,$class=null);
    abstract protected function getLastInsertId($table,$column);

    public function __construct($connection=null,array $config=null)
    {
        if($connection)
            $this->setConnection($connection);
        if($config)
            $this->setConfig($config);
    }

    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
        if(isset($this->config['AckTimeoutSecond'])) {
            $seconds = 0;
            if(isset($this->config['AckTimeoutSecond']))
                $seconds = intval($this->config['AckTimeoutSecond']);
            $this->setAckTimeout($seconds);
        }
        if(isset($this->config['queueMessageTable']))
            $this->queueMessageTable = $this->config['queueMessageTable'];
        if(isset($this->config['queueNameTable']))
            $this->queueNameTable = $this->config['queueNameTable'];
        if(isset($this->config['sqlBuilder']))
            $this->sqlBuilder = $this->config['sqlBuilder'];
    }

    public function setAckTimeout($seconds) 
    {
        $this->timeoutsec = $seconds;
    }

    public function getSqlBuilder()
    {
        if($this->sqlBuilder)
            return $this->sqlBuilder;
        $slugName = $this->getDriverName($this->getConnection());
        if(isset(self::$builderAliases[$slugName]))
            $this->sqlBuilder = self::$builderAliases[$slugName];
        else
            throw new Exception\DomainException('sqlBuilder is not specified for driver "'.$slugName.'".');
        return $this->sqlBuilder;
    }

    public function setSqlBuilder($sqlBuilder)
    {
        $this->sqlBuilder = $sqlBuilder;
    }

    public function getQueueMessageTable()
    {
        return $this->queueMessageTable;
    }

    public function setQueueMessageTable($queueMessageTable)
    {
        $this->queueMessageTable = $queueMessageTable;
    }

    public function getQueueNameTable()
    {
        return $this->queueNameTable;
    }

    public function setQueueNameTable($queueNameTable)
    {
        $this->queueNameTable = $queueNameTable;
    }

    protected function getQueueId($queue)
    {
        if(isset($this->queueNames[$queue]))
            return $this->queueNames[$queue];
        $sql = "SELECT * FROM ".$this->queueNameTable." WHERE name = :name";
        $params = array(':name'=>$queue);
        $results = $this->query($sql, $params);
        $queue_id = null;
        foreach ($results as $result) {
            $queue_id = $result['id'];
        }
        if($queue_id) {
            $this->queueNames[$queue] = $queue_id;
            return $queue_id;
        }
        $sql = "INSERT INTO ".$this->queueNameTable." (name) VALUES (:name)";
        $params = array(':name'=>$queue);
        $this->execute($sql, $params);
        $queue_id = $this->getLastInsertId($this->queueNameTable,'id');
        if($queue_id===false) {
            throw new Exception\DomainException('Fail to get a last insert id');
        }
        $this->queueNames[$queue] = $queue_id;
        return $queue_id;
    }

    public function send($queue,$msg)
    {
        if(!is_string($msg)) {
            throw new Exception\DomainException('message must be string to send to "'.$queue.'"');
        }
        $queue_id = $this->getQueueId($queue);
        $sql = "INSERT INTO ".$this->queueMessageTable." (queue_id,body,created) VALUES (:queue_id, :body, :created)";
        $params = array(':queue_id'=>$queue_id,':body'=>$msg,':created'=>time());
        $this->execute($sql, $params);
    }

    public function subscribe($queue)
    {
        $this->subscribing[$this->getQueueId($queue)]=true;
    }

    public function unsubscribe($queue)
    {
        unset($this->subscribing[$this->getQueueId($queue)]);
    }

    public function receive($queue=null)
    {
        $frames = $this->receiveFrames(1,$queue);
        if(count($frames)>0)
            return $frames[0];
        return false;
    }

    public function receiveFrames($count,$queue=null)
    {
        if($queue) {
            $subscribing = array($this->getQueueId($queue));
        } else {
            $subscribing = array_keys($this->subscribing);
        }
        if(count($subscribing)==0) {
            throw new Exception\DomainException('nothing subscribing queue');
        }
        $table = $this->queueMessageTable;
        $handle = md5(uniqid(rand(), true));
        list($micro,$sec) = explode(' ',microtime());
        $micro = str_pad(substr($micro,2,6),6,'0');
        $now = $sec . $micro;
        $timeout = strval($sec + $this->timeoutsec) . $micro;

        $this->handles[$handle] = true;
        $sqlBuilder = $this->getSqlBuilder();
        list($sql,$params) = $sqlBuilder::getUpdateHandleStatement(
            $this,$subscribing,$handle,$timeout,$now,$count);
        try {
        $this->execute($sql,$params);
        } catch(\Rindow\Database\Exception\ExceptionInterface $e) {
            echo mb_convert_encoding($e->getMessage(),'SJIS','UTF-8');
            var_dump($params);
            throw $e;
        }

        list($sql,$params) = $sqlBuilder::getSelectByHandleStatement(
            $this,$subscribing,$handle,$timeout,$now,$count);
        $results = $this->query($sql,$params,self::FRAME_CLASS);
        $frames = array();
        foreach($results as $result) {
            $frames[] = $result;
        }
        return $frames;
    }

    public function ack($msg)
    {
        $this->ackFrames(array($msg));
    }

    public function ackFrames(array $msgs)
    {
        if(count($msgs)==0)
            return;
        if(!($msgs[0] instanceof QueueFrame))
            throw new Exception\InvalidArgumentException('messages must be array of "QueueFrame"');
        $handle = $msgs[0]->handle;
        $table = $this->queueMessageTable;
        $sql = "DELETE FROM {$table} WHERE handle = :handle";
        $params = array(':handle'=>$handle);
        $this->execute($sql,$params);
        unset($this->handles[$handle]);
    }

    public function recover()
    {
        foreach($this->handles as $handle => $flag) {
            $table = $this->queueMessageTable;
            $sql = "UPDATE {$table} SET timeout = 0 WHERE handle = :handle";
            $params = array(':handle'=>$handle);
            $this->execute($sql,$params);
        }
        $this->handles = array();
    }

    public function createSchema()
    {
        $sqlBuilder = $this->getSqlBuilder();
        if($sqlBuilder===null)
            throw new Exception\DomainException('sql builder is not resolved.');
        $sqls = $sqlBuilder::createSchema($this);
        foreach ($sqls as $sql) {
            $this->execute($sql,array());
        }
    }

    public function dropSchema()
    {
        $sqlBuilder = $this->getSqlBuilder();
        if($sqlBuilder===null)
            throw new Exception\DomainException('sql builder is not resolved.');
        $sqls = $sqlBuilder::dropSchema($this);
        foreach ($sqls as $sql) {
            $this->execute($sql,array());
        }
    }

    public function close()
    {
        if($this->connection == null)
            return;
        $this->recover();
        $this->connection = null;
    }

    public function __destruct()
    {
        $this->close();
    }
}