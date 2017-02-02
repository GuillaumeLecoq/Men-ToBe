<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PopArticlesAnalytics
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Entity\PopArticlesAnalyticsRepository")
 */

class PopArticlesAnalytics
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="article_id", type="integer")
     */
    private $articleId;

    /**
     * @var integer
     *
     * @ORM\Column(name="author_id", type="integer", nullable=true)
     */
    private $authorId;

    /**
     * @var integer
     *
     * @ORM\Column(name="average_time", type="integer")
     */
    private $averageTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="unique_views", type="integer")
     */
    private $uniqueViews;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_views", type="integer")
     */
    private $totalViews;

    /**
     * @var string
     *
     * @ORM\Column(name="category", type="string", length=255)
     */
    private $category;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;


    public function __construct()
    {
        $this->date = new \DateTime();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set articleId
     *
     * @param integer $articleId
     *
     * @return PopArticlesAnalytics
     */
    public function setArticleId($articleId)
    {
        $this->articleId = $articleId;

        return $this;
    }
    
    /**
     * Get articleId
     *
     * @return integer
     */
    public function getArticleId()
    {
        return $this->articleId;
    }

    /**
     * Set averageTime
     *
     * @param integer $averageTime
     *
     * @return PopArticlesAnalytics
     */
    public function setAverageTime($averageTime)
    {
        $this->averageTime = $averageTime;

        return $this;
    }
    
    /**
     * Get averageTime
     *
     * @return integer
     */
    public function getAverageTime()
    {
        return $this->averageTime;
    }

    /**
     * Set uniqueViews
     *
     * @param integer $uniqueViews
     *
     * @return PopArticlesAnalytics
     */
    public function setUniqueViews($uniqueViews)
    {
        $this->uniqueViews = $uniqueViews;

        return $this;
    }
    
    /**
     * Get uniqueViews
     *
     * @return integer
     */
    public function getUniqueViews()
    {
        return $this->uniqueViews;
    }

    /**
     * Set totalViews
     *
     * @param integer $totalViews
     *
     * @return PopArticlesAnalytics
     */
    public function setTotalViews($totalViews)
    {
        $this->totalViews = $totalViews;

        return $this;
    }
    
    /**
     * Get totalViews
     *
     * @return integer
     */
    public function getTotalViews()
    {
        return $this->totalViews;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return PopArticlesAnalytics
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set category
     *
     * @param string $category
     *
     * @return PopArticlesAnalytics
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return PopArticlesAnalytics
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Set authorId
     *
     * @param integer $authorId
     *
     * @return PopArticlesAnalytics
     */
    public function setAuthorId($authorId)
    {
        $this->authorId = $authorId;

        return $this;
    }

    /**
     * Get authorId
     *
     * @return integer
     */
    public function getAuthorId()
    {
        return $this->authorId;
    }
}
