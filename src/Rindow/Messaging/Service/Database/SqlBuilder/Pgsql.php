<?php
namespace Rindow\Messaging\Service\Database\SqlBuilder;

use Rindow\Messaging\Service\Database\QueueDriver;

class Pgsql
{
    public static function createSchema(QueueDriver $adapter)
    {
        $queueMessageTable = $adapter->getQueueMessageTable();
        $queueNameTable = $adapter->getQueueNameTable();
        $sqls[] = "CREATE TABLE IF NOT EXISTS {$queueMessageTable} (".
                " id BIGSERIAL PRIMARY KEY,".
                " queue_id INTEGER NOT NULL,".
                " handle VARCHAR(32),".
                " timeout BIGINT NOT NULL DEFAULT 0,".
                " created BIGINT NOT NULL,".
                " body VARCHAR(8192)".
                ")";
        $sqls[] = "CREATE INDEX handle   ON {$queueMessageTable} (handle)";
        $sqls[] = "CREATE INDEX timeout  ON {$queueMessageTable} (timeout)";
        $sqls[] = "CREATE TABLE IF NOT EXISTS {$queueNameTable} (".
                " id SERIAL PRIMARY KEY,".
                " name VARCHAR(255) NOT NULL UNIQUE)";
        return $sqls;
    }

    public static function dropSchema(QueueDriver $adapter)
    {
        $queueMessageTable = $adapter->getQueueMessageTable();
        $queueNameTable = $adapter->getQueueNameTable();
        $sqls[] = "DROP TABLE IF EXISTS {$queueMessageTable}";
        $sqls[] = "DROP TABLE IF EXISTS {$queueNameTable}";
        return $sqls;
    }
/*
    public static function getUpdateHandleStatement(
        QueueDriver $adapter,
        $subscribing,
        $handle,
        $timeout,
        $now,
        $count)
    {
        $table = $adapter->getQueueMessageTable();
        $subscribQueue = implode(',', $subscribing);
        $sql = "UPDATE {$table} AS target INNER JOIN " .
               "(SELECT w.id FROM {$table} AS w WHERE queue_id IN ({$subscribQueue}) AND timeout < :now ORDER BY id LIMIT ".intval($count)." ) AS source " .
               "ON source.id = target.id SET handle = :handle, timeout = :timeout";
        $params = array(':now'=>$now, ':handle'=>$handle, ':timeout'=>$timeout);

        return array($sql,$params);
    }
*/
    public static function getUpdateHandleStatement(
        QueueDriver $adapter,
        $subscribing,
        $handle,
        $timeout,
        $now,
        $count)
    {
        $table = $adapter->getQueueMessageTable();
        $subscribQueue = implode(',', $subscribing);
        $sql = "UPDATE {$table} SET handle = :handle, timeout = :timeout WHERE id IN ".
                "(SELECT id FROM {$table} WHERE queue_id IN ({$subscribQueue}) AND timeout < :now ORDER BY id LIMIT ".intval($count).")";
        $params = array(':handle'=>$handle,':timeout'=>$timeout,':now'=>$now);
        return array($sql,$params);
    }

    public static function getSelectByHandleStatement(
        QueueDriver $adapter,
        $subscribing,
        $handle,
        $timeout,
        $now,
        $count)
    {
        $table = $adapter->getQueueMessageTable();
        $sql = "SELECT * FROM {$table} WHERE handle = :handle ORDER BY id";
        $params = array(':handle'=>$handle);
        return array($sql,$params);
    }
}