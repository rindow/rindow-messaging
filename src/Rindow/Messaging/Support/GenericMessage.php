<?php
namespace Rindow\Messaging\Support;

use Interop\Lenient\Messaging\Message;

class GenericMessage implements Message
{
    protected $payload;
    protected $headers=array();

    public function __construct($payload=null, array $headers=null)
    {
        $this->payload = $payload;
        if($headers===null)
            $headers = array();
        $this->headers = $headers;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setPayload($payload)
    {
        $this->payload = $payload;
        return $this;
    }
}
