<?php
/**
 * Created by PhpStorm.
 * User: pira
 * Date: 10/02/2016
 * Time: 16:44
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RssEntity
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RssEntityRepository")
 */
class RssEntity
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
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\FluxRss")
     */
    private $fluxRss;

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

    /**
     * @var string
     *
     * @ORM\Column(name="image_url", type="string", length=255)
     */
    private $imageUrl;

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
     * Set name
     *
     * @param string $name
     *
     * @return RssEntity
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
     * Set date
     *
     * @param \DateTime $date
     *
     * @return RssEntity
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
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
     * Set imageUrl
     *
     * @param string $imageUrl
     *
     * @return RssEntity
     */
    public function setImageUrl($imageUrl)
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    /**
     * Get imageUrl
     *
     * @return string
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    /**
     * Set fluxRss
     *
     * @param \AppBundle\Entity\FluxRss $fluxRss
     *
     * @return RssEntity
     */
    public function setFluxRss(\AppBundle\Entity\FluxRss $fluxRss = null)
    {
        $this->fluxRss = $fluxRss;

        return $this;
    }

    /**
     * Get fluxRss
     *
     * @return \AppBundle\Entity\FluxRss
     */
    public function getFluxRss()
    {
        return $this->fluxRss;
    }
}
