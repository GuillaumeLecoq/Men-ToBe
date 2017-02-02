<?php

namespace BackOfficeBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use AppBundle\Entity\Article;

class ArticleEvent extends Event
{
    private $article;

    public function __construct(Article $article)
    {
        $this->article = $article;
    }

    public function getName()
    {
        return $this->article->getName();
    }

    public function getAuthor()
    {
        return $this->article->getAuthor();
    }

    public function getAuthorId()
    {
        return $this->article->getAuthor()->getId();
    }

    public function getAuthorName()
    {
        return $this->article->getAuthor()->getLastname() . " " . $this->article->getAuthor()->getFirstname();
    }

    public function getResume()
    {
        return $this->article->getResume();
    }

    public function getDate()
    {
        return $this->article->getDate();
    }

    public function getContent()
    {
        return $this->article->getContent();
    }

    public function getStep()
    {
        return $this->article->getStep();
    }
}