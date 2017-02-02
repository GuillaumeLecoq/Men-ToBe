<?php

namespace Mobile\Service;

use AppBundle\Entity\Article;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class ArticleConsumer implements ConsumerInterface
{
	private $container;

	public function __construct(Container $container) {
        $this->container = $container;
    }

    public function execute(AMQPMessage $msg)
    {
    	$em = $this->container->get('doctrine.orm.entity_manager');
        // retrieve id article from queue
        $article_id = unserialize($msg->body);
        // get article from id
        $article = $em->getRepository('AppBundle:Article')->find($article_id);
        $doc = new \DOMDocument();
        $doc->loadHTML($article->getContent());
        $purify_content = "";
        foreach($doc->getElementsByTagName('p') as $paragraph) {
            // remove all tags in paragraph
            $purify_content .= '<p>' . strip_tags($paragraph->textContent) . '</p>';
        } 

        $article->setMobileContent($purify_content);
        $em->persist($article);
        $em->flush();

        return true;
    }
}