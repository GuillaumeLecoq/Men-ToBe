<?php

namespace BackOfficeBundle\EventListener;

use AppBundle\Entity\UserEvent;
use BackOfficeBundle\BackOfficeEvents;
use BackOfficeBundle\Event\ArticleEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class ArticleListener implements EventSubscriberInterface
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public static function getSubscribedEvents()
    {
        return array(
            BackOfficeEvents::POST_ARTICLE => 'updateTimeLine'
        );
    }

    public function updateTimeLine(ArticleEvent $event)
    {
        $em = $this->container->get('doctrine')->getManager();

        $userEvent = new UserEvent();
        $message = "Création de l'article: " . $event->getName();
        $userEvent->setMessage($message);
        $userEvent->setDate(new \DateTime());
        $userEvent->setAuthor($event->getAuthor());
        $em->persist($userEvent);
        $em->flush();

        $msg = array('user_id' => $event->getAuthorId(), 'message' => $message);
        $this->container->get('old_sound_rabbit_mq.notify_users_producer')->publish(json_encode($msg));
    }

}
