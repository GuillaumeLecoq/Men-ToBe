<?php
/**
 * Created by PhpStorm.
 * User: pira
 * Date: 27/02/2016
 * Time: 18:34
 **/


namespace UserBundle\Event;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\UserEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use GuzzleHttp\Client;
use UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

/**
 * Redirection aprés enregistrement d'un utilisateur
 */
class RegistrationConfirmListener implements EventSubscriberInterface
{
    private $router;
    private $container;

    public function __construct(UrlGeneratorInterface $router,Container $container)
    {
        $this->router = $router;
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::REGISTRATION_SUCCESS => 'onRegistrationConfirm'
        );
    }

    public function onRegistrationConfirm(\FOS\UserBundle\Event\FormEvent $event)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
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

        $user = $event->getForm()->getData();
        // Create user in Rocket Chat
        $response = $client->request('POST', 'users.create', [
            'headers' => [
                'X-Auth-Token' => $creditentials['data']['authToken'],
                'X-User-Id' => $creditentials['data']['userId'],
            ],
            'json' => [
                "name" => $user->getFirstname() . ' ' . $user->getLastname(),
                "email" => $user->getEmail(),
                "password" => $user->getPlainPassword(),
                "username" => $user->getUsername()
            ]
        ]);

        $json = json_decode($response->getBody(), true);
        if ($json['success'] != true)
        {
            $output->writeln("Account can't be created");
            exit(84);
        }

        $userId = $json['user']['_id'];
        // Add link between account and all categories from website
        foreach ($categories as $category) {
            $response = $client->request('POST', 'channels.invite', [
                'headers' => [
                    'X-Auth-Token' => $creditentials['data']['authToken'],
                    'X-User-Id' => $creditentials['data']['userId'],
                ],
                'json' => [
                    "roomId" => $category->getRocketchat(),
                    "userId" => $userId
                ]
            ]);

            $json = json_decode($response->getBody(), true);
            if ($json['success'] != true)
            {
                $output->writeln("Association between account " . $userId .  " and " . $category->getRocketchat() .  " can't be created");
                exit(84);
            }
        }

        $url = $this->router->generate('back_office_homepage');

        $event->setResponse(new RedirectResponse($url));
    }
}
