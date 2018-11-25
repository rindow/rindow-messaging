<?php
namespace RindowTest\Messaging\Converter\SimpleMessageConverterTest;

use PHPUnit\Framework\TestCase;
use Rindow\Messaging\Converter\SimpleMessageConverter;
use Rindow\Messaging\Support\GenericMessage;
use Rindow\Stdlib\Entity\PropertyHydrator;


class TestPublic
{
	public $var1;
	public $var2;
}

class Test extends TestCase
{
    public function setUp()
    {
    }

	public function testStringToMessage()
	{
		$converter = new SimpleMessageConverter();
		$message = $converter->toMessage('string test', $properties=array(), new GenericMessage());
		$this->assertEquals('Rindow\Messaging\Support\GenericMessage',get_class($message));
        $this->assertEquals('string test',$message->getPayload());
        $results = array(
    		'content-transfer-encoding' => 'quoted-printable'
    	);
        $this->assertEquals($results,$message->getHeaders());

		$message = $converter->toMessage('string test', $properties=array('foo'=>'bar'),	new GenericMessage());
		$this->assertEquals('Rindow\Messaging\Support\GenericMessage',get_class($message));
        $this->assertEquals('string test',$message->getPayload());
        $results = array(
            'foo' => 'bar',
    		'content-transfer-encoding' => 'quoted-printable'
    	);
        $this->assertEquals($results,$message->getHeaders());
	}

	public function testArrayToMessage()
	{
		$converter = new SimpleMessageConverter();
		$message = $converter->toMessage(array('var1'=>'val1','var2'=>'val2'), $properties=array(),new GenericMessage());
		$this->assertEquals('Rindow\Messaging\Support\GenericMessage',get_class($message));
        $this->assertEquals(serialize(array('var1'=>'val1','var2'=>'val2')),$message->getPayload());
        $results = array(
            'content-type' => 'application/php-serialize',
    		'content-transfer-encoding' => 'quoted-printable'
    	);
        $this->assertEquals($results,$message->getHeaders());

		$message = $converter->toMessage(array('var1'=>'val1','var2'=>'val2'), $properties=array('foo'=>'bar'),new GenericMessage());
		$this->assertEquals('Rindow\Messaging\Support\GenericMessage',get_class($message));
        $this->assertEquals(serialize(array('var1'=>'val1','var2'=>'val2')),$message->getPayload());
        $results = array(
            'foo' => 'bar',
            'content-type' => 'application/php-serialize',
    		'content-transfer-encoding' => 'quoted-printable'
    	);
        $this->assertEquals($results,$message->getHeaders());
	}

	public function testObjectToMessage()
	{
		$converter = new SimpleMessageConverter();
		$message = $converter->toMessage((object)array('var1'=>'val1','var2'=>'val2'), $properties=array(),new GenericMessage());
		$this->assertEquals('Rindow\Messaging\Support\GenericMessage',get_class($message));
        $this->assertEquals(serialize((object)array('var1'=>'val1','var2'=>'val2')),$message->getPayload());
        $results = array(
            'content-type' => 'application/php-serialize',
    		'content-transfer-encoding' => 'quoted-printable'
    	);
        $this->assertEquals($results,$message->getHeaders());

		$message = $converter->toMessage((object)array('var1'=>'val1','var2'=>'val2'), $properties=array('foo'=>'bar'),new GenericMessage());
		$this->assertEquals('Rindow\Messaging\Support\GenericMessage',get_class($message));
        $this->assertEquals(serialize((object)array('var1'=>'val1','var2'=>'val2')),$message->getPayload());
        $results = array(
            'foo' => 'bar',
            'content-type' => 'application/php-serialize',
    		'content-transfer-encoding' => 'quoted-printable'
    	);
        $this->assertEquals($results,$message->getHeaders());
	}


	public function testStringToMessageOnJsonMode()
	{
		$converter = new SimpleMessageConverter();
		$converter->setJsonMode(true);
		$message = $converter->toMessage('string test', $properties=array(),new GenericMessage());
		$this->assertEquals('Rindow\Messaging\Support\GenericMessage',get_class($message));
        $this->assertEquals('"string test"',$message->getPayload());
        $results = array(
    		'content-type' => 'application/json',
    	);
        $this->assertEquals($results,$message->getHeaders());

		$message = $converter->toMessage('string test', $properties=array('foo'=>'bar'),new GenericMessage());
		$this->assertEquals('Rindow\Messaging\Support\GenericMessage',get_class($message));
        $this->assertEquals('"string test"',$message->getPayload());
        $results = array(
            'foo' => 'bar',
    		'content-type' => 'application/json',
    	);
        $this->assertEquals($results,$message->getHeaders());
	}

	public function testArrayToMessageOnJsonMode()
	{
		$converter = new SimpleMessageConverter();
		$converter->setJsonMode(true);
		$message = $converter->toMessage(array('var1'=>'val1','var2'=>'val2'), $properties=array(),new GenericMessage());
		$this->assertEquals('Rindow\Messaging\Support\GenericMessage',get_class($message));
        $this->assertEquals('{"var1":"val1","var2":"val2"}',$message->getPayload());
        $results = array(
    		'content-type' => 'application/json',
    	);
        $this->assertEquals($results,$message->getHeaders());

		$message = $converter->toMessage(array('var1'=>'val1','var2'=>'val2'), $properties=array('foo'=>'bar'),new GenericMessage());
		$this->assertEquals('Rindow\Messaging\Support\GenericMessage',get_class($message));
        $this->assertEquals('{"var1":"val1","var2":"val2"}',$message->getPayload());
        $results = array(
            'foo' => 'bar',
    		'content-type' => 'application/json',
    	);
        $this->assertEquals($results,$message->getHeaders());
	}

	public function testObjectToMessageOnJsonMode()
	{
		$converter = new SimpleMessageConverter();
		$converter->setJsonMode(true);
		$message = $converter->toMessage((object)array('var1'=>'val1','var2'=>'val2'), $properties=array(),new GenericMessage());
		$this->assertEquals('Rindow\Messaging\Support\GenericMessage',get_class($message));
        $this->assertEquals('{"var1":"val1","var2":"val2"}',$message->getPayload());
        $this->assertEquals(1,count($message->getHeaders()));
        $results = array(
    		'content-type' => 'application/json',
    	);
        $this->assertEquals($results,$message->getHeaders());

		$message = $converter->toMessage((object)array('var1'=>'val1','var2'=>'val2'), $properties=array('foo'=>'bar'),new GenericMessage());
		$this->assertEquals('Rindow\Messaging\Support\GenericMessage',get_class($message));
        $this->assertEquals('{"var1":"val1","var2":"val2"}',$message->getPayload());
        $results = array(
            'foo' => 'bar',
    		'content-type' => 'application/json',
    	);
        $this->assertEquals($results,$message->getHeaders());
	}


	public function testNullStringToMessage()
	{
		$converter = new SimpleMessageConverter();
		$converter->setJsonMode(true);
		$message = $converter->toMessage('string '."\0".' test', $properties=array(),new GenericMessage());
		$this->assertEquals('Rindow\Messaging\Support\GenericMessage',get_class($message));
        $this->assertEquals('"string \u0000 test"',$message->getPayload());
        $results = array(
    		'content-type' => 'application/json',
    	);
        $this->assertEquals($results,$message->getHeaders());

		$converter->setJsonMode(false);
		$message = $converter->toMessage('string '."\0".' test', $properties=array(),new GenericMessage());
		$this->assertEquals('Rindow\Messaging\Support\GenericMessage',get_class($message));
        $this->assertEquals('string =00 test',$message->getPayload());
        $results = array(
    		'content-transfer-encoding' => 'quoted-printable'
    	);
        $this->assertEquals($results,$message->getHeaders());
	}

	public function testNullStringToMessageInArray()
	{
		$converter = new SimpleMessageConverter();
		$converter->setJsonMode(true);
		$message = $converter->toMessage(array('var1'=>'val'."\0".'1'), $properties=array(),new GenericMessage());
		$this->assertEquals('Rindow\Messaging\Support\GenericMessage',get_class($message));
        $this->assertEquals('{"var1":"val\u00001"}',$message->getPayload());
        $results = array(
    		'content-type' => 'application/json',
    	);
        $this->assertEquals($results,$message->getHeaders());

		$converter->setJsonMode(false);
		$message = $converter->toMessage(array('var1'=>'val'."\0".'1'), $properties=array(),new GenericMessage());
		$this->assertEquals('Rindow\Messaging\Support\GenericMessage',get_class($message));
        $this->assertEquals(quoted_printable_encode(serialize(array('var1'=>'val'."\0".'1'))),$message->getPayload());
        $results = array(
            'content-type' => 'application/php-serialize',
    		'content-transfer-encoding' => 'quoted-printable'
    	);
        $this->assertEquals($results,$message->getHeaders());
	}

	public function testStringToMessageOnJsonModeByHeader()
	{
		$converter = new SimpleMessageConverter();
		$properties=array('content-type' => 'application/json');
		$message = $converter->toMessage('string test', $properties,new GenericMessage());
		$this->assertEquals('Rindow\Messaging\Support\GenericMessage',get_class($message));
        $this->assertEquals('"string test"',$message->getPayload());
        $results = array(
    		'content-type' => 'application/json',
    	);
        $this->assertEquals($results,$message->getHeaders());
	}

	public function testArrayToMessageOnJsonModeByHeader()
	{
		$converter = new SimpleMessageConverter();
		$properties=array('content-type' => 'application/json');
		$message = $converter->toMessage(array('var1'=>'val1','var2'=>'val2'), $properties,new GenericMessage());
		$this->assertEquals('Rindow\Messaging\Support\GenericMessage',get_class($message));
        $this->assertEquals('{"var1":"val1","var2":"val2"}',$message->getPayload());
        $results = array(
    		'content-type' => 'application/json',
    	);
        $this->assertEquals($results,$message->getHeaders());
	}

	public function testObjectToMessageOnJsonModeByHeader()
	{
		$converter = new SimpleMessageConverter();
		$properties=array('content-type' => 'application/json');
		$message = $converter->toMessage((object)array('var1'=>'val1','var2'=>'val2'), $properties,new GenericMessage());
		$this->assertEquals('Rindow\Messaging\Support\GenericMessage',get_class($message));
        $this->assertEquals('{"var1":"val1","var2":"val2"}',$message->getPayload());
        $this->assertEquals(1,count($message->getHeaders()));
        $results = array(
    		'content-type' => 'application/json',
    	);
        $this->assertEquals($results,$message->getHeaders());
	}

	public function testStringFromMessage()
	{
		$converter = new SimpleMessageConverter();
		$message = $converter->toMessage('string test', $properties=array(),new GenericMessage());
		$body = $converter->fromMessage($message);
        $this->assertEquals('string test',$body);
	}

	public function testArrayFromMessage()
	{
		$converter = new SimpleMessageConverter();
		$message = $converter->toMessage(array('var1'=>'val1','var2'=>'val2'), $properties=array(),new GenericMessage());
		$body = $converter->fromMessage($message);
        $this->assertEquals(array('var1'=>'val1','var2'=>'val2'),$body);
	}

	public function testObjectFromMessage()
	{
		$converter = new SimpleMessageConverter();
		$test = new TestPublic();
		$test->var1 = 'val1';
		$test->var2 = 'val2';
		$message = $converter->toMessage($test, $properties=array(),new GenericMessage());
		$body = $converter->fromMessage($message);
		$this->assertEquals(__NAMESPACE__.'\\TestPublic',get_class($body));
        $this->assertEquals('val1',$body->var1);
        $this->assertEquals('val2',$body->var2);
	}

	public function testStringFromMessageOnJsonMode()
	{
		$converter = new SimpleMessageConverter();
		$converter->setJsonMode(true);
		$message = $converter->toMessage('string test', $properties=array(),new GenericMessage());
		$body = $converter->fromMessage($message);
        $this->assertEquals('string test',$body);
	}

	public function testArrayFromMessageOnJsonMode()
	{
		$converter = new SimpleMessageConverter();
		$converter->setJsonMode(true);
		$message = $converter->toMessage(array('var1'=>'val1','var2'=>'val2'), $properties=array(),new GenericMessage());
		$body = $converter->fromMessage($message);
        $this->assertEquals((object)array('var1'=>'val1','var2'=>'val2'),$body);

		$converter->setJsonMode(SimpleMessageConverter::JSON_ASSOC);
		$body = $converter->fromMessage($message);
        $this->assertEquals(array('var1'=>'val1','var2'=>'val2'),$body);
	}

	public function testObjectFromMessageOnJsonMode()
	{
		$converter = new SimpleMessageConverter();
		$converter->setJsonMode(true);
		$test = new TestPublic();
		$test->var1 = 'val1';
		$test->var2 = 'val2';
		$message = $converter->toMessage($test, $properties=array(),new GenericMessage());
		$headers = $message->getHeaders();
		unset($headers['target-class']);
		$message->setHeaders($headers);
		$body = $converter->fromMessage($message);
        $this->assertEquals((object)array('var1'=>'val1','var2'=>'val2'),$body);
		$converter->setJsonMode(SimpleMessageConverter::JSON_ASSOC);
		$body = $converter->fromMessage($message);
        $this->assertEquals(array('var1'=>'val1','var2'=>'val2'),$body);
	}

	public function testObjectFromMessageOnJsonModeWithHydrator()
	{
		$converter = new SimpleMessageConverter();
		$converter->setJsonMode(true);
		$converter->setHydrator(new PropertyHydrator());
		$test = new TestPublic();
		$test->var1 = 'val1';
		$test->var2 = 'val2';
		$message = $converter->toMessage($test, $properties=array(),new GenericMessage());
		$body = $converter->fromMessage($message);
		$this->assertEquals(__NAMESPACE__.'\\TestPublic',get_class($body));
        $this->assertEquals('val1',$body->var1);
        $this->assertEquals('val2',$body->var2);

		$converter->setJsonMode(SimpleMessageConverter::JSON_ASSOC);
		$body = $converter->fromMessage($message);
		$this->assertEquals(__NAMESPACE__.'\\TestPublic',get_class($body));
        $this->assertEquals('val1',$body->var1);
        $this->assertEquals('val2',$body->var2);
	}

	public function testNullStringFromMessage()
	{
		$converter = new SimpleMessageConverter();
		$converter->setJsonMode(true);
		$message = $converter->toMessage('string '."\0".' test', $properties=array(),new GenericMessage());
		$body = $converter->fromMessage($message);
		$this->assertEquals('string '."\0".' test',$body);

		$converter->setJsonMode(false);
		$message = $converter->toMessage('string '."\0".' test', $properties=array(),new GenericMessage());
		$body = $converter->fromMessage($message);
		$this->assertEquals('string '."\0".' test',$body);
	}


	public function testBase64()
	{
		$converter = new SimpleMessageConverter();
		$properties = array('content-transfer-encoding' => 'base64');
		$message = $converter->toMessage('string '."\0".' test', $properties,new GenericMessage());
		$this->assertEquals('string '."\0".' test',base64_decode($message->getPayload()));
		$body = $converter->fromMessage($message);
		$this->assertEquals('string '."\0".' test',$body);
	}

    /**
     * @expectedException        Rindow\Messaging\Exception\DomainException
     * @expectedExceptionMessage unknown encoding format: none
     */
	public function testUnknownEncoding()
	{
		$converter = new SimpleMessageConverter();
		$properties = array('content-transfer-encoding' => 'none');
		$message = $converter->toMessage('string '."\0".' test', $properties,new GenericMessage());
		$this->assertEquals('string '."\0".' test',base64_decode($message->getPayload()));
		$body = $converter->fromMessage($message);
		$this->assertEquals('string '."\0".' test',$body);
	}
}
