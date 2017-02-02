<?php

namespace BackOfficeBundle\EventListener;

use AppBundle\Entity\UserEvent;
use BackOfficeBundle\BackOfficeEvents;
use BackOfficeBundle\Event\MessageEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class MessageListener implements EventSubscriberInterface
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public static function getSubscribedEvents()
    {
        return array(
            BackOfficeEvents::POST_MESSAGE => 'updateTimeLine'
        );
    }

    public function updateTimeLine(MessageEvent $event)
    {
        $em = $this->container->get('doctrine')->getManager();

        $userEvent = new UserEvent();
        $message = "Mail arrivé: " . $event->getSubject();
        $userEvent->setMessage($message);
        $userEvent->setDate(new \DateTime());
        $userEvent->setAuthor($event->getOwner());
        $em->persist($userEvent);
        $em->flush();

        $msg = array('message' => $message);
        $this->container->get('old_sound_rabbit_mq.notify_users_producer')->publish(json_encode($msg));
    }

}
