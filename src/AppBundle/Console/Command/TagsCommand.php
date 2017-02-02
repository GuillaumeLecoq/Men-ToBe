<?php

namespace AppBundle\Console\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use \AppBundle\Entity\Tag;

class TagsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('remove:tags')
            ->setDescription('Remove old (more than 10 days) Tags');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = new \DateTime('today');
        $date->modify('-10 day');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $qb = $em->createQueryBuilder();

        $qb->delete('AppBundle:Tag', 'tags')
            ->where('tags.date <= :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }
}



