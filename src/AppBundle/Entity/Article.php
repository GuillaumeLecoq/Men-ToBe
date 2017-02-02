<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Article
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ArticleRepository")
 * @Gedmo\Loggable
 * @Vich\Uploadable
 */
class Article
{

    const BROUILLON = 0;
    const WAITING = 1;
    const PLAGIE = 2;
    const GRAMMAR = 3;
    const PUBLICATION = 4;
    const SUPPRESSION = 5;
    const INAPPROPRIATEWORD = 6;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Category")
     */
    private $category;

    /**
     * @var string
     *
     * @ORM\Column(name="tag", type="string", length=500)
     */
    private $tag;

    /**
     * @var string
     * @Gedmo\Versioned
     * @ORM\Column(name="slug", type="string", length=255)
     */
    private $slug;

    /**
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     */
    private $author;


    /**
     * @var string
     * @Gedmo\Versioned
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var \DateTime
     * @Gedmo\Versioned
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * @var string
     * @Gedmo\Versioned
     * @ORM\Column(name="resume", type="text")
     */
    private $resume;

    /**
     * @var string
     * @Gedmo\Versioned
     * @ORM\Column(name="content", type="text")
     */
    private $content;

    /**
     * @var string
     * @Gedmo\Versioned
     * @ORM\Column(name="mobile_content", type="text", nullable=true)
     */
    private $mobile_content;

    /**
     * @var string
     * @Gedmo\Versioned
     * @ORM\Column(name="link_image", type="string", length=255, nullable=true)
     */
    private $linkImage;

    /**
     * @var string
     * @Gedmo\Versioned
     * @ORM\Column(name="auteur_image", type="string", length=255, nullable=true)
     */
    private $auteurImage;


    /**
     * @var string
     *
     * @ORM\Column(name="link_pub", type="string", length=255, nullable=true)
     */
    private $linkPub;

    /**
     * @var integer
     * @Gedmo\Versioned
     * @ORM\Column(name="step", type="smallint", nullable=true)
     */
    private $step;

    /**
     * @var integer
     *
     * @ORM\Column(name="view", type="integer", nullable=true)
     */
    private $view;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     * @Assert\File(
     *     maxSize="1M",
     *     mimeTypes={"image/png", "image/jpeg"}
     * )
     * @Vich\UploadableField(mapping="article_image", fileNameProperty="imageName")
     *
     * @var File
     */
    private $imageFile;

    /**
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $imageName;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $updatedAt;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->date = new \Datetime();
        $this->view = 0;
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
     * Set slug
     *
     * @param string $slug
     *
     * @return Article
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set theme
     *
     * @param string $theme
     *
     * @return Article
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Get theme
     *
     * @return string
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Set subTheme
     *
     * @param string $subTheme
     *
     * @return Article
     */
    public function setSubTheme($subTheme)
    {
        $this->subTheme = $subTheme;

        return $this;
    }

    /**
     * Get subTheme
     *
     * @return string
     */
    public function getSubTheme()
    {
        return $this->subTheme;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Article
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
     * @return Article
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
     * Set resume
     *
     * @param string $resume
     *
     * @return Article
     */
    public function setResume($resume)
    {
        $this->resume = $resume;

        return $this;
    }

    /**
     * Get resume
     *
     * @return string
     */
    public function getResume()
    {
        return $this->resume;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return Article
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set step
     *
     * @param integer $step
     *
     * @return Article
     */
    public function setStep($step)
    {
        $this->step = $step;

        return $this;
    }

    /**
     * Get step
     *
     * @return integer
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * Set linkPub
     *
     * @param string $linkPub
     *
     * @return Article
     */
    public function setLinkPub($linkPub)
    {
        $this->linkPub = $linkPub;

        return $this;
    }

    /**
     * Get linkPub
     *
     * @return string
     */
    public function getLinkPub()
    {
        return $this->linkPub;
    }

    /**
     * Set linkImage
     *
     * @param string $linkImage
     *
     * @return Article
     */
    public function setLinkImage($linkImage)
    {
        $this->linkImage = $linkImage;

        return $this;
    }

    /**
     * Get linkImage
     *
     * @return string
     */
    public function getLinkImage()
    {
        return $this->linkImage;
    }

    /**
     * Set category
     *
     * @param \AppBundle\Entity\Category $category
     *
     * @return Article
     */
    public function setCategory(\AppBundle\Entity\Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \AppBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set author
     *
     * @param \UserBundle\Entity\User $author
     *
     * @return Article
     */
    public function setAuthor(\UserBundle\Entity\User $author = null)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return \UserBundle\Entity\User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set view
     *
     * @param integer $view
     *
     * @return Article
     */
    public function setView()
    {
        $this->view = $this->view + 1;

        return $this;
    }

    /**
     * Get view
     *
     * @return integer
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Set tag
     *
     * @param string $tag
     *
     * @return Article
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Get tag
     *
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $image
     *
     * @return Article
     */
    public function setImageFile(File $image = null)
    {
        $this->imageFile = $image;

        if ($image) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTime('now');
        }

        return $this;
    }



    /**
     * Set imageName
     *
     * @param string $imageName
     *
     * @return Article
     */
    public function setImageName($imageName)
    {
        $this->imageName = $imageName;

        return $this;
    }

    /**
     * Get imageName
     *
     * @return string
     */
    public function getImageName()
    {
        return $this->imageName;
    }

    /**
     * @return File
     */
    public function getImageFile()
    {
        return $this->imageFile;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return Article
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set auteurImage
     *
     * @param string $auteurImage
     *
     * @return Article
     */
    public function setAuteurImage($auteurImage)
    {
        $this->auteurImage = $auteurImage;

        return $this;
    }

    /**
     * Get auteurImage
     *
     * @return string
     */
    public function getAuteurImage()
    {
        return $this->auteurImage;
    }

    /**
     * Set mobileContent
     *
     * @param string $mobileContent
     *
     * @return Article
     */
    public function setMobileContent($mobileContent)
    {
        $this->mobile_content = $mobileContent;

        return $this;
    }

    /**
     * Get mobileContent
     *
     * @return string
     */
    public function getMobileContent()
    {
        return $this->mobile_content;
    }


    /** return human timing date */
    public static function getHumanTiming($time)
    {
        $time = time() - $time;
        $time = ($time<1)? 1 : $time;
        $tokens = array (
            31536000 => 'année',
            2592000 => 'mois',
            604800 => 'semaine',
            86400 => 'jour',
            3600 => 'heure',
            60 => 'minute',
            1 => 'seconde'
        );

        foreach ($tokens as $unit => $text) {
            if ($time < $unit) continue;
            $numberOfUnits = floor($time / $unit);
            return  $numberOfUnits.' '.$text.(($numberOfUnits>1 && $text != "mois" )?'s':'');
        }
    }
}
