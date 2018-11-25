<?php
namespace Rindow\Messaging\Service\Database;

use Rindow\Messaging\Exception;
use PDO;

class GenericQueueDriver extends AbstractQueueDriver
{
    public function getConnection()
    {
        if(!$this->connection)
            throw new Exception\DomainException('connection is not specified');
        return $this->connection;
    }

    protected function getDriverName($connection)
    {
        return $connection->getDriverName();
    }

    protected function execute($sql, $params)
    {
        $this->getConnection()->executeUpdate($sql, $params);
    }

    protected function query($sql,$params,$className=null)
    {
        if($className===null)
            $fetchMode = null;
        else
            $fetchMode = PDO::FETCH_CLASS;
        $resultList = $this->getConnection()->executeQuery($sql,$params,$fetchMode,$className);
        return $resultList;
    }

    protected function getLastInsertId($table,$column)
    {
        return $this->getConnection()->getLastInsertId($table,$column);
    }
}
