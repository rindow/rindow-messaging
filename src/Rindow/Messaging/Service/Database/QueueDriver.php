<?php
namespace Rindow\Messaging\Service\Database;

interface QueueDriver
{
    public function getQueueMessageTable();
    public function getQueueNameTable();
    public function send($queue,$msg);
    public function setAckTimeout($seconds);
    public function subscribe($queue);
    public function unsubscribe($queue);
    public function receive($queue=null);
    public function receiveFrames($count,$queue=null);
    public function ack($msg);
    public function ackFrames(array $msgs);
    public function recover();
    public function close();
    public function createSchema();
    public function dropSchema();
}