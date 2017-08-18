<?php

namespace Remp\MailerModule\Hermes;

use Ramsey\Uuid\Uuid;
use Tomaj\Hermes\MessageInterface;

class HermesMessage implements MessageInterface
{

    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $payload;

    /**
     * @var string
     */
    private $messageId;

    /**
     * @var string
     */
    private $created;

    /**
     * @var string
     */
    private $process;

    /**
     * @param string $type
     * @param array|null $payload
     * @param string|null $messageId
     * @param string|null $created
     * @param integer|boolean $process
     */
    public function __construct($type, array $payload = null, $messageId = null, $created = null, $process = false)
    {
        $this->messageId = $messageId;
        if (!$messageId) {
            $this->messageId = Uuid::uuid4()->toString();
        }
        $this->created = $created;
        if (!$created) {
            $this->created = microtime();
        }
        $this->type = $type;
        $this->payload = $payload;

        $this->process = $process;
        if (!$process) {
            list($usec, $sec) = explode(' ', $this->created);
            $this->process = $sec;
        }
    }

    /**
     * Message identifier.
     *
     * This identifier should be unique all the time.
     * Recommendation is to use UUIDv4 (Included Message implementation
     * generating UUIDv4 identifiers)
     *
     * @return string
     */
    public function getId()
    {
        return $this->messageId;
    }

    /**
     * Message creation date - micro timestamp
     *
     * @return string
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Date when message has to be processed - timestamp
     *
     * @return string
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * Message type
     *
     * Based on this field, message will be dispatched and will be sent to
     * appropriate handler.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Payload data.
     *
     * This data can be used for anything that you would like to send to handler.
     * Warning! This data has to be serializable to string. Don't put there php resources
     * like database connection resources, file handlers etc..
     *
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }
}
