<?php
namespace Rindow\Messaging\Support;

use Interop\Lenient\Messaging\Message;

class MessageFactory
{
	public function createMessage($payload=null, array $headers=null)
	{
		return new GenericMessage($payload,$headers);
	}
}
