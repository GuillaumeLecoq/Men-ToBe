<?php

namespace BackOfficeBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use AppBundle\Entity\Message;

class MessageEvent extends Event
{
    private $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function getSubject()
    {
        return $this->message->getSubject();
    }

    public function getDate()
    {
        return $this->message->getDate();
    }

    public function getOwner()
    {
        return $this->message->getOwner();
    }
}