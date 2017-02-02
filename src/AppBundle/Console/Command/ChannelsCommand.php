<?php

namespace AppBundle\Console\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Client;
use \AppBundle\Entity\Category;

class ChannelsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('channels:create')
            ->setDescription('Create channels for Rocket Chat')
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $categories = $em->getRepository('AppBundle:Category')->findAll();
        $client = new Client(['base_uri' => 'http://mentobe.fr:3000/api/v1/']);
        
        $response = $client->request('POST', 'login', [
            'json' => [
                "username" => "*******",
                "password" => "*******"
            ]
        ]);

        $creditentials = json_decode($response->getBody(), true);
        if ($creditentials['status'] != 'success')
        {
            $output->writeln("Bad creditentials");
            exit(84);
        }
            

        foreach ($categories as $category) {
            $sanitazed_category = $this->suppr_accents($category->getName());
            var_dump($sanitazed_category);

            $response = $client->request('POST', 'channels.create', [
                'headers' => [
                    'X-Auth-Token' => $creditentials['data']['authToken'],
                    'X-User-Id' => $creditentials['data']['userId'],
                ],
                'json' => [
                    "name" => $sanitazed_category
                ]
            ]);

            $json = json_decode($response->getBody(), true);
            if ($json['success'] != true)
            {
                $output->writeln("Channel can't be created");
                exit(84);
            }

            $category->setRocketchat($json['channel']['_id']);
            $em->persist($category);
            $em->flush();
            $output->writeln($category->getName() . ": ". $json['channel']['_id']);
        }
    }


    function suppr_accents($str, $encoding='utf-8')
    {
        $str = htmlentities($str, ENT_NOQUOTES, $encoding);
        $str = preg_replace('#&([A-za-z])(?:acute|grave|cedil|circ|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
        $str = preg_replace('#&[^;]+;#', '', $str);
     
        return $str;
    }
}



