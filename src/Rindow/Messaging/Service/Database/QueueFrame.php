<?php
namespace Rindow\Messaging\Service\Database;

class QueueFrame
{
	public $id;
	public $queue_id;
	public $handle;
	public $timeout;
	public $created;
    public $body;
}
