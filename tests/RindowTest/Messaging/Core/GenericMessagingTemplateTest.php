<?php
namespace RindowTest\Messaging\Core\GenericMessagingTemplateTest;

use PHPUnit\Framework\TestCase;
use Interop\Lenient\Messaging\Message as MessageInterface;
use Interop\Lenient\Messaging\PollableChannel as PollableChannelInterface;
use Interop\Lenient\Messaging\Converter\MessageConverter as MessageConverterInterface;
use Interop\Lenient\Messaging\Core\DestinationResolver as DestinationResolverInterface;
use Rindow\Messaging\Converter\SimpleMessagConverter;
use Rindow\Messaging\Core\GenericMessagingTemplate;
use Rindow\Messaging\Support\GenericMessage;
use Rindow\Stdlib\Entity\PropertyHydrator;

class TestLogger
{
    public $logdata = array();
    public function log($message)
    {
        $this->logdata[] = $message;
    }
}

class TestSession
{
    protected $messages = array();

    public function send($message)
    {
        array_unshift($this->messages, $message);
    }

    public function receive()
    {
        return array_shift($this->messages);
    }
}

class TestDestinationResolver implements DestinationResolverInterface
{
    public function resolveDestination($destinationName)
    {
        if(!isset($this->sessions[$destinationName]))
            $this->sessions[$destinationName] = new TestSession();
        $channel = new TestChannel($this->sessions[$destinationName]);
        return $channel;
    }
}

class TestChannel implements PollableChannelInterface
{
    protected $session;

    public function __construct($session)
    {
        $this->session = $session;
    }

    public function send(/*Message */$message, $timeout=null)
    {
        $this->session->send($message);
    }

    public function receive($timeout=null)
    {
        return $this->session->receive();
    }

    public function close()
    {
        $this->session = null;
    }
}

class TestPublic
{
	public $var1;
	public $var2;
}

class TestConverter implements MessageConverterInterface
{
	public function toMessage($payload, array $headers)
	{
		$payload .= ':send';
		$headers['converter'] = 'onHeader';
		return new GenericMessage($payload,$headers);
	}
	public function fromMessage(/*MessageInterface*/ $message, $targetClass=null)
	{
		$headers = $message->getHeaders();
		return $message->getPayload() . ':'.$headers['converter'];
	}
}

class IllegalConverter implements MessageConverterInterface
{
	public function toMessage($payload, array $headers)
	{
		return null;
	}
	public function fromMessage(/*MessageInterface*/ $message, $targetClass=null)
	{
		$headers = $message->getHeaders();
		return $message->getPayload() . ':'.$headers['converter'];
	}
}

class Test extends TestCase
{
    public static $skip = false;
    public static function setUpBeforeClass()
    {
    }

    public function setUp()
    {
        if(self::$skip) {
            $this->markTestSkipped();
            return;
        }
    }

	public function testSendMessage()
	{
		$resolver = new TestDestinationResolver();
		$templateToSend = new GenericMessagingTemplate($resolver);
		$templateToReceive = new GenericMessagingTemplate($resolver);

		$message = new GenericMessage('test-text');
		$templateToSend->send('test-destination',$message);

		$receivedMessage = $templateToReceive->receive('test-destination');

		$this->assertEquals('test-text',$receivedMessage->getPayload());
	}

	public function testConvertAndSend()
	{
        $resolver = new TestDestinationResolver();
        $templateToSend = new GenericMessagingTemplate($resolver);
        $templateToReceive = new GenericMessagingTemplate($resolver);

		$payload = new TestPublic();
		$payload->var1 = 'test-text';
		$templateToSend->convertAndSend('test-destination',$payload);
		$receivedPayload = $templateToReceive->receiveAndConvert('test-destination');
		$this->assertEquals(__NAMESPACE__.'\\TestPublic',get_class($receivedPayload));
		$this->assertEquals('test-text',$receivedPayload->var1);
		$this->assertEquals(null,$receivedPayload->var2);
	}

	public function testConvertAndPostprocessingAndSend()
	{
        $resolver = new TestDestinationResolver();
        $templateToSend = new GenericMessagingTemplate($resolver);
        $templateToReceive = new GenericMessagingTemplate($resolver);

		$payload = new TestPublic();
		$payload->var1 = 'test-text';
		$fooOption = 'bar';
		$postProcesser = function ($message) use ($fooOption) {
			$headers = $message->getHeaders();
			$headers['foo'] = $fooOption;
			$message->setHeaders($headers);
			return $message;
		};
		$templateToSend->convertAndSend('test-destination',$payload,null,$postProcesser);
		$receivedMessage = $templateToReceive->receive('test-destination');
		$headers = $receivedMessage->getHeaders();
		$payload = $templateToReceive->getMessageConverter()->fromMessage($receivedMessage);
		$this->assertEquals(__NAMESPACE__.'\\TestPublic',get_class($payload));
		$this->assertEquals('bar',$headers['foo']);
	}

    /**
     * @expectedException        Rindow\Messaging\Exception\InvalidArgumentException
     * @expectedExceptionMessage postProcessor must be callable or type of "MessagePostProcessor" or null.
     */
	public function testIllegalPostproceser()
	{
        $resolver = new TestDestinationResolver();
        $template = new GenericMessagingTemplate($resolver);
		$template->convertAndSend('test-destination','test-text',$headers=null,'illegal');
	}

    /**
     * @expectedException        Rindow\Messaging\Exception\DomainException
     * @expectedExceptionMessage message convert error in a post processor.
     */
	public function testIllegalMessageFromPostproceser()
	{
        $resolver = new TestDestinationResolver();
        $template = new GenericMessagingTemplate($resolver);
		$postProcesser = function ($message) {
			return null;
		};
		$template->convertAndSend('test-destination','test-text',$headers=null,$postProcesser);
	}
/*
	public function testDestinationResolver()
	{
		$config = $this->getConfig();
		$sessionOutbound = new Session(new QueueDriver($config));
		$sessionInbound = new Session(new QueueDriver($config));
		$destinationResolver = new TestDestinationResolver();
		$destinationResolver->registerDestination('outbound', new Destination('test-destination',$sessionOutbound));
		$destinationResolver->registerDestination('inbound', new Destination('test-destination',$sessionInbound));
		$messageTemplate = new GenericMessagingTemplate($session=null,$converter=null,$destinationResolver);

		$messageTemplate->convertAndSend('outbound','test-text');
		$receivedPayload = $messageTemplate->receiveAndConvert('inbound');
		$this->assertEquals('test-text',$receivedPayload);
	}
*/
    /**
     * expectedException        Rindow\Messaging\Exception\DomainException
     * expectedExceptionMessage destination "none" is not found.
     */
/*
	public function testDestinationResolverNotfound()
	{
		$config = $this->getConfig();
		$sessionOutbound = new Session(new QueueDriver($config));
		$sessionInbound = new Session(new QueueDriver($config));
		$destinationResolver = new TestDestinationResolver();
		$destinationResolver->registerDestination('outbound', new Destination('test-destination',$sessionOutbound));
		$destinationResolver->registerDestination('inbound', new Destination('test-destination',$sessionInbound));
		$messageTemplate = new GenericMessagingTemplate($session=null,$converter=null,$destinationResolver);

		$messageTemplate->convertAndSend('outbound','test-text');
		$receivedPayload = $messageTemplate->receiveAndConvert('none');
	}
*/
	public function testConverter()
	{
        $resolver = new TestDestinationResolver();
		$converter = new TestConverter();
		$messageTemplate = new GenericMessagingTemplate($resolver,$converter);

		$messageTemplate->convertAndSend('test-destination','test-text');
		$receivedPayload = $messageTemplate->receiveAndConvert('test-destination');
		$this->assertEquals('test-text:send:onHeader',$receivedPayload);
	}

    /**
     * @expectedException        Rindow\Messaging\Exception\DomainException
     * @expectedExceptionMessage message convertion error in the converter "RindowTest\Messaging\Core\GenericMessagingTemplateTest\IllegalConverter".
     */
	public function testIllegalConverter()
	{
        $resolver = new TestDestinationResolver();
		$converter = new IllegalConverter();
		$messageTemplate = new GenericMessagingTemplate($resolver,$converter);

		$messageTemplate->convertAndSend('test-destination','test-text');
	}
}
