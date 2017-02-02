<?php
/**
 * Created by PhpStorm.
 * User: pira
 * Date: 24/02/2016
 * Time: 15:53
 */

namespace AppBundle\Console\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \AppBundle\Entity\RssEntity;
use \AppBundle\Entity\FluxRss;


class RssCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('update:rss')
            ->setDescription('Update Rss Feed')
        ;
    }


    public function returnImage($node)
    {
        $imageLink = $this->getContainer()->get('templating.helper.assets')->getUrl('bundles/frontoffice/img/logo.png');

        if ($node->getElementsByTagName('enclosure')->item(0) != null)
        {
            $imageLink = $node->getElementsByTagName('enclosure')->item(0)->getAttribute('url');
        }

        return $imageLink;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager('postgresql');

        $fluxRss = $em->getRepository('AppBundle:FluxRss')->findAll();

        //delete all rss entity
        $qb = $em->createQueryBuilder();
        $qb->delete('AppBundle:RssEntity')
            ->getQuery()
            ->execute();

        foreach($fluxRss as $flux) {

            $url = $flux->getUrl();
            $file_headers = @get_headers($url);
            if (strpos($file_headers[0], '200') !== false) {
                $rss = new \DOMDocument();
                $rss->load($url);
                $limit = 1;

                foreach ($rss->getElementsByTagName('item') as $node) {

                    if ($limit > 5)
                        break;

                    $rssEntity = new RssEntity();
                    $rssEntity->setName($node->getElementsByTagName('title')->item(0)->nodeValue);
                    $rssEntity->setImageUrl($this->returnImage($node));
                    $rssEntity->setFluxRss($flux);
                    $em->persist($rssEntity);
                    $em->flush();

                    $limit = $limit + 1;
                }
            }
        }
    }
}



