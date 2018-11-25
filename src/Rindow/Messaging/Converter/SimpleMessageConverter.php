<?php
namespace Rindow\Messaging\Converter;

use Interop\Lenient\Messaging\Converter\MessageConverter;
use Rindow\Messaging\Message;
use Rindow\Messaging\Support\MessageFactory;
use Rindow\Messaging\Exception;
use Rindow\Stdlib\Entity\Hydrator;
use Rindow\Stdlib\Entity\PropertyHydrator;


class SimpleMessageConverter implements MessageConverter
{
    const CONTENT_TRANSFER_ENCODING = 'content-transfer-encoding';
    const QUOTED_PRINTABLE = 'quoted-printable';
    const BASE64           = 'base64';
    const CONTENT_TYPE     = 'content-type';
    const APPLICATION_JSON = 'application/json';
    const APPLICATION_PHP_SERIALIZE = 'application/php-serialize';
    const TARGET_CLASS     = 'target-class';
    const JSON_DISABLE  = false;
    const JSON_ENABLE   = true;
    const JSON_ASSOC    = 'assoc';

    protected $jsonMode = false;
    protected $hydrator;
    protected $messageFactory;
    protected $isDefaultHydrator = true;

    public function setJsonMode($jsonMode)
    {
        $this->jsonMode = $jsonMode;
    }

    public function getJsonMode()
    {
        return $this->jsonMode;
    }

    public function setHydrator(Hydrator $hydrator)
    {
        $this->hydrator = $hydrator;
        $this->isDefaultHydrator = false;
    }

    public function getHydrator()
    {
        if($this->hydrator==null)
            $this->hydrator = new PropertyHydrator();
        return $this->hydrator;
    }

    public function setMessageFactory($messageFactory)
    {
        $this->messageFactory = $messageFactory;
    }

    public function getMessageFactory()
    {
        if($this->messageFactory==null)
            $this->messageFactory = new MessageFactory();
        return $this->messageFactory;
    }

    public function toMessage($body, array $headers)
    {
        $org = $headers;
        $headers = array();
        foreach ($org as $key => $value) {
            $headers[strtolower($key)] = $value;
        }
        $contentType = null;
        if(isset($headers[self::CONTENT_TYPE])) {
            $contentType = strtolower($headers[self::CONTENT_TYPE]);
        }
        if(self::APPLICATION_JSON === $contentType || $this->jsonMode) {
            if(is_object($body)) {
                $className = get_class($body);
                if($className!=='stdClass') {
                    $headers[self::TARGET_CLASS] = $className;
                    if(!$this->isDefaultHydrator) {
                        $body = (object)$this->getHydrator()->extract($body);
                    }
                }
            }
            $body = json_encode($body);
            if($body===false) {
                throw new Exception\DomainException('json encode error.');
            }
            $contentType = $headers[self::CONTENT_TYPE] = self::APPLICATION_JSON;
        } else if(self::APPLICATION_PHP_SERIALIZE == $contentType
            || is_object($body) || is_array($body)     ) {
            $body = serialize($body);
            $contentType = $headers[self::CONTENT_TYPE] = self::APPLICATION_PHP_SERIALIZE;
        } else {
            $body = $body;
        }
        $encoding = null;
        if(isset($headers[self::CONTENT_TRANSFER_ENCODING])) {
            $encoding = strtolower($headers[self::CONTENT_TRANSFER_ENCODING]);
        } else {
            if(self::APPLICATION_JSON !== $contentType)
                $encoding = self::QUOTED_PRINTABLE;
        }
        if(self::BASE64===$encoding) {
            $body = base64_encode($body);
        } else if(self::QUOTED_PRINTABLE===$encoding
            || self::APPLICATION_PHP_SERIALIZE === $contentType) {
            $body = quoted_printable_encode($body);
            $headers[self::CONTENT_TRANSFER_ENCODING] = self::QUOTED_PRINTABLE;
        } else if(null!==$encoding) {
            throw new Exception\DomainException('unknown encoding format: '.$encoding);
        }
        $message = $this->getMessageFactory()->createMessage();
        $message->setPayload($body);
        $message->setHeaders($headers);
        return $message;
    }

    public function fromMessage(/*Message*/ $message, $targetClass=null)
    {
        $headers = $message->getHeaders();
        $body = $message->getPayload();
        if(isset($headers[self::CONTENT_TRANSFER_ENCODING])) {
            $encoding = strtolower($headers[self::CONTENT_TRANSFER_ENCODING]);
            if(self::BASE64===$encoding)
                $body = base64_decode($body);
            else if(self::QUOTED_PRINTABLE===$encoding)
                $body = quoted_printable_decode($body);
            else if(null!==$encoding)
                throw new Exception\DomainException('unknown encoding format: '.$encoding);
        }

        if(!isset($headers[self::CONTENT_TYPE]))
            return $body;
        $contentType = strtolower($headers[self::CONTENT_TYPE]);
        if($targetClass===null) {
            if(isset($headers[self::TARGET_CLASS]))
                $targetClass = $headers[self::TARGET_CLASS];
        }
        if(self::APPLICATION_JSON === $contentType) {
            if(self::JSON_ASSOC === $this->jsonMode || $targetClass) {
                $body = json_decode($body,true);
            }
            else {
                $body = json_decode($body);
            }
        } else if(self::APPLICATION_PHP_SERIALIZE === $contentType) {
            $body = unserialize($body);
        }
        if($targetClass) {
            $body = $this->getHydrator()->hydrate($body,new $targetClass());
        }
        return $body;
    }
}
