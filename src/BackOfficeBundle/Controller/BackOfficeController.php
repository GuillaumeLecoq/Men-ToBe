<?php

namespace BackOfficeBundle\Controller;

use Cocur\Slugify\Slugify;
use AppBundle\Entity\Article;
use AppBundle\Form\ArticleType;
use BackOfficeBundle\Event\ArticleEvent;
use BackOfficeBundle\Event\MessageEvent;
use BackOfficeBundle\BackOfficeEvents;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\Message;
use AppBundle\Form\UserMessageType;
use AppBundle\Form\MessageType;

class BackOfficeController extends Controller
{

    public function indexAction()
    {
        //repository article and user
        $userRepository = $this->getDoctrine()->getManager()->getRepository('UserBundle:User');
        $articleRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Article');

        /** get last 3 publish articles */
        $lastArticles = $articleRepository->findBy(
            array("step" => Article::PUBLICATION),
            array("updatedAt" => "desc"),
            3,
            0
        );

        return $this->render('BackOfficeBundle::home_page.html.twig', array(
                'userEvents' => $this->getDoctrine()->getRepository('AppBundle:UserEvent')->findBy(array("author" => $this->getUser()), array('date' => 'DESC')),
                'totalUser' => $userRepository->getTotalUser(),
                'totalAuthor' => $userRepository->getTotalAuthor(),
                'totalArticles' => $articleRepository->getTotalArticlePublish(),
                'fileActus' => $lastArticles,
                'userTotalAmount' => $this->getUser()->getTotalAmount(),
            )
        );
    }

    /** Tutorial Article Controller */
    public function tutoArticleAction()
    {
        return $this->render('BackOfficeBundle::tuto.html.twig');
    }



    /*******************/
    /******* Create *****/
    /*******************/

    public function createArticleAction(Request $request, $step = 1, $category = "", $tag = "")
    {
        /** access to tag repository */
        $tagRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Tag');

        if ($step == 2) {
            $article = new Article();
            $form = $this->get('form.factory')->create(new ArticleType(array('imageFile' => true)), $article);
            $article->setAuthor($this->getUser());
            $slugify = new Slugify();

            if ($form->handleRequest($request)->isValid())
            {
                if ($request->request->get('save_draft') == "draft") {
                    $article->setStep(Article::BROUILLON);
                    $this->addFlash(
                        'notice',
                        'Article enregistré en brouillon.'
                    );
                }
                else {
                    $article->setStep(Article::WAITING);
                    $this->addFlash(
                        'notice',
                        'Article crée avec succès, un administrateur traitera votre demande dans les plus bref délais.'
                    );
                }

                $article->setSlug($slugify->slugify($article->getName()));
                $em = $this->getDoctrine()->getManager();
                $em->persist($article);
                $em->flush();

                $event = new ArticleEvent($article);
                $this->get('event_dispatcher')->dispatch(BackOfficeEvents::POST_ARTICLE, $event);

                // TODO(Guillaume): create new producer to send to rabbitMQ
                $this->get('old_sound_rabbit_mq.send_article_producer')->publish(serialize(array("id" => $article->getId())));

                return $this->redirect($this->generateUrl('back_office_list_article'));
            }

            return $this->render('BackOfficeBundle::create_article_validation.html.twig',
                array(
                    'form' => $form->createView(),
                    "tag" => $tag,
                    "tagName" => $slugify->slugify($tagRepository->find($tag)->getName()),
                    "step" => $step,
                    "category" => $category
                )
            );
        }
        else{
            $categories = $this->getDoctrine()
                ->getRepository('AppBundle:Category')
                ->findAll();

            if (!empty($category)) {
                $categoryTagDefault = $this->getDoctrine()
                    ->getRepository('AppBundle:Tag')
                    ->findBy(array("category" => $category));
            }
            else{
                $categoryTagDefault = $this->getDoctrine()
                    ->getRepository('AppBundle:Tag')
                    ->findBy(array("category" => $categories[0]->getId()));
            }

            return $this->render('BackOfficeBundle::create_article.html.twig',
                array(
                    'categories' => $categories,
                    "categoryTagDefault" => $categoryTagDefault,
                    "tag" => $tag,
                    "step" => $step,
                    "category" => $category
                )
            );

        }

    }

    /*******************/
    /******* List ******/
    /*******************/

    public function listArticleAction(Request $request, $page = 1)
    {
        $articleRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Article');

        $paginator = $this->get('mentobe_paginator');
        $totalArticleList = $articleRepository->getTotalArticleByAuthor($this->getUser());
        $numberFluxRssDisplay = 5;
        $last = ceil($totalArticleList / $numberFluxRssDisplay);


        if ($last < 1)
            $last = 1;

        if ($page < 1)
            $page = 1;
        else if ($page > $last)
        {
            $page = $last;
        }

        $offset = ($page - 1) * $numberFluxRssDisplay;

        /** get all article create by the user */
        $articles = $articleRepository->getArticle(
            array('author' => $this->getUser()),
            array('updatedAt' => 'desc'),
            $numberFluxRssDisplay,
            $offset
        );

        return $this->render('BackOfficeBundle::list-article.html.twig',
            array(
                'list_articles' => $articles,
                'pagination' => $paginator->getPagination($last, $page, "back_office_list_article_pagination")
            )
        );
    }

    public function updateArticleAction(Request $request, $id)
    {
        $articleUpdate = $this->getDoctrine()->getRepository('AppBundle:Article')->findOneBy(array('id' => $id));

        $old_articles = array();
        $em = $this->getDoctrine()->getManager();

        $repo_log = $this->getDoctrine()->getRepository('Gedmo\Loggable\Entity\LogEntry');
        $logs = $repo_log->getLogEntries($articleUpdate);
        $nb_logs = count($logs);

        if ($nb_logs > 0)
        {
            foreach ($logs as $log) {
                $articleUpdate->setUpdatedAt($log->getLoggedAt());
                $repo_log->revert($articleUpdate, $log->getVersion());
                $old_articles[$log->getVersion()] = clone $articleUpdate;
            }
            // DESC query: recent version is on top
            $repo_log->revert($articleUpdate, $logs[0]->getVersion());
        }

        $form = $this->get('form.factory')->create(new ArticleType(array('imageFile' => false)), $articleUpdate);

        if ($form->handleRequest($request)->isValid())
        {
            if ($nb_logs > 4)
                $em->remove(end($logs));

            $slugify = new Slugify();
            $articleUpdate->setAuthor($this->getUser());
            $articleUpdate->setUpdatedAt(new \Datetime());
            $articleUpdate->setSlug($slugify->slugify($articleUpdate->getName()));

            if ($request->request->get('save_draft') == "draft") {
                $articleUpdate->setStep(Article::BROUILLON);
                $this->addFlash(
                    'notice',
                    'Article enregistré en brouillon.'
                );
            }
            else {
                $articleUpdate->setStep(Article::WAITING);
                $this->addFlash(
                    'notice',
                    'Article modifié avec succès, un administrateur traitera votre demande dans les plus bref délais.'
                );
            }

            
            $em->persist($articleUpdate);
            $em->flush();

            return $this->redirect($this->generateUrl('back_office_list_article'));
        }

        return $this->render('BackOfficeBundle::update_article.html.twig', 
            array('form' => $form->createView(), 'article' => $articleUpdate, 'logs' => $old_articles));
    }

    public function revisionPreviousAction(Request $request, $id, $version)
    {
        $em = $this->getDoctrine()->getManager();
        $articleUpdate = $this->getDoctrine()
            ->getRepository('AppBundle:Article')
            ->findOneBy(array('id' => $id));

        $repo_log = $this->getDoctrine()->getRepository('Gedmo\Loggable\Entity\LogEntry');
        $version_to_del = $repo_log->findOneBy(array('version' => $version));
        $repo_log->revert($articleUpdate, $version);
        $articleUpdate->setUpdatedAt(new \Datetime());
        $em->remove($version_to_del);
        $em->persist($articleUpdate);
        $em->flush();

        $this->addFlash(
            'notice',
            'Retour à la version précèdente effectué'
        );

        return $this->redirect($this->generateUrl('back_office_update_article', array('id' => $id)));        
    }

    public function revisionDeleteAction(Request $request, $id, $version)
    {
        $em = $this->getDoctrine()->getManager();
        $articleUpdate = $this->getDoctrine()
            ->getRepository('AppBundle:Article')
            ->findOneBy(array('id' => $id));

        $repo_log = $this->getDoctrine()->getRepository('Gedmo\Loggable\Entity\LogEntry');
        $version_to_del = $repo_log->findOneBy(array('version' => $version));

        $em->remove($version_to_del);
        $em->flush();

        $this->addFlash(
            'notice',
            'Révision de l\'article supprimé'
        );

        return $this->redirect($this->generateUrl('back_office_update_article', array('id' => $id))); 
    }

    /*******************/
    /******* DELETE *****/
    /*******************/
    public function deleteArticleAction($id)
    {
        /** access to article repository */
        $articleRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Article');

        /** delete article by using is id */
        $articleRepository->deleteArticleById($id);

        $this->addFlash(
            'warning',
            'Article supprimé avec succès.'
        );

        return $this->redirect($this->generateUrl('back_office_list_article'));
    }

    /*******************/
    /******* OTHER *****/
    /*******************/
    public function profilAboutAction(Request $request)
    {
        return $this->render('BackOfficeBundle::profil_about.html.twig',
            array(
                'user' => $this->getUser(),
            )
        );
    }

    public function profilInformationsAction(Request $request)
    {
        /** @var get user $user */
        $user = $this->getUser();

        $form = $this->createFormBuilder($user, array(
            'validation_groups' => array('my_registration'),
            ))
            ->add('username', 'text', array('required' => true))
            ->add('firstname', 'text', array('required' => true))
            ->add('lastname', 'text', array('required' => true))
            ->add('email', 'email', array('required' => true))
            ->add('plainPassword', 'repeated', array(
                    'type' => 'password',
                    'required' => true,
                )
            )
            ->add('imageFile', 'file', array('required' => false))
            ->add('city', 'text', array('required' => false))
            ->getForm()
        ;

        if ($form->handleRequest($request)->isValid())
        {
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirect($this->generateUrl('back_office_profil_about'));
        }

        return $this->render('BackOfficeBundle::profil_informations.html.twig',
            array(
                'form' => $form->createView(),
                'user' => $user,
            )
        );
    }


    public function profilPreferenceAction(Request $request)
    {
        /** @var get user $user */
        $user = $this->getUser();

        $form = $this->createFormBuilder($user)
            ->add('categories', 'entity', array(
                'class'    => 'AppBundle:Category',
                'property' => 'name',
                'multiple' => true,
                'required' => false,
            ))
            ->getForm()
        ;

        if ($form->handleRequest($request)->isValid())
        {
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirect($this->generateUrl('back_office_profil_about'));
        }

        return $this->render('BackOfficeBundle::profil_preference.html.twig',
            array(
                'form' => $form->createView(),
                'user' => $user,
            )
        );
    }

    public function profilPaypalAction(Request $request)
    {
        /** @var get user $user */
        $user = $this->getUser();

        $form = $this->createFormBuilder($user)
            ->add('emailPaypal', 'text', array('required' => true))
            ->add('frequency', 'choice', array(
                'choices'  => array(
                    'Tous les 2 mois' => "2 mois",
                    'Tous les 3 mois' => "3 mois",
                    'Tous les 4 mois' => "4 mois",
                ),
                'choices_as_values' => true,
            ))
            ->add('threshold', 'text', array('required' => true))
            ->getForm()
        ;

        if ($form->handleRequest($request)->isValid())
        {
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirect($this->generateUrl('back_office_profil_about'));
        }

        return $this->render('BackOfficeBundle::profil_paypal.html.twig',
            array(
                'form' => $form->createView(),
                'user' => $user,
            )
        );
    }

    public function ajaxcategorytagAction($id)
    {
        $tags = $this->getDoctrine()
            ->getRepository('AppBundle:Tag')
            ->findBy(array('category' => $id));

        $html = '';

        foreach ($tags as $key => $tag) {

            if ($key == 0) {

                $html .= '<div class="col-sm-12 tag-element" data-content="'
                    . $tag->getCategory()->getId() . '" data-id="'
                    . $tag->getId() . '"><div class="row the-notes info"><div class="col-sm-8"><h4>'
                    . $tag->getName() . '</h4></div><div class="col-sm-4"><img src="'
                    . $tag->getImageUrl() . '" style="height: auto; width: 100%"></div></div></div>';
            }
            else {
                $html .= '<div class="col-sm-12 tag-element" data-content="'
                    . $tag->getCategory()->getId() . '" data-id="'
                    . $tag->getId() . '"><div class="row the-notes info"><div class="col-sm-8"><h4>'
                    . $tag->getName() . '</h4></div><div class="col-sm-4"><img src="'
                    . $tag->getImageUrl() . '" style="height: auto; width: 100%"></div></div></div>';
            }
        }

        return new Response($html);
    }

    public function becomeauthorAction(Request $request)
    {
        $errorMessage = "";

        if ($request->isMethod('POST'))
        {

            //if true is agree
            $checkAccept = $request->request->get('accept');
            if ($checkAccept == "ok") {
                $loggedInUser = $this->get('security.context')->getToken()->getUser();
                $loggedInUser->removeRole('ROLE_VISITOR');
                $loggedInUser->addRole('ROLE_AUTHOR');

                $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
                    $loggedInUser,
                    null,
                    'main',
                    $loggedInUser->getRoles()
                );

                $this->container->get('security.context')->setToken($token);

                $userManager = $this->get('fos_user.user_manager');
                $userManager->updateUser($loggedInUser);

                return $this->redirect($this->generateUrl('back_office_homepage'));
            }
            else{
                $errorMessage = "Veuillez accepter les termes d'utilisation de l'espace auteur.";
                return $this->render('BackOfficeBundle::become_author.html.twig', array('errorMessage' => $errorMessage));
            }
        }

        return $this->render('BackOfficeBundle::become_author.html.twig', array('errorMessage' => $errorMessage));
    }

    public function getTotalMessage($mode = 'all')
    {
        $repo = $this->getDoctrine()
            ->getRepository('AppBundle:Message');

        $qb = $repo->createQueryBuilder('m');
        $qb->select('COUNT(m)');

        if ($mode == 'read')
        {
            $qb->where('m.status = :status');
            $qb->setParameter('status', Message::READ);
        }
        else if ($mode == 'unread')
        {
            $qb->where('m.status = :status');
            $qb->setParameter('status', Message::UNREAD);
        }

        $total = $qb->getQuery()->getSingleScalarResult();

        return $total;
    }

    public function get_nb_unread_message()
    {
        $repo = $this->getDoctrine()
            ->getRepository('AppBundle:Message');

        $qb = $repo->createQueryBuilder('m');
        $qb->select('COUNT(m)')
            ->where('m.status = :status')
            ->setParameter('status', Message::UNREAD);

        $total = $qb->getQuery()->getSingleScalarResult();

        return $total;
    }

    public function get_nb_read_message()
    {
        $repo = $this->getDoctrine()
            ->getRepository('AppBundle:Message');

        $qb = $repo->createQueryBuilder('m');
        $qb->select('COUNT(m)')
            ->where('m.status = :status')
            ->setParameter('status', Message::READ);

        $total = $qb->getQuery()->getSingleScalarResult();

        return $total;
    }

    public function getTotalSearchMessage($data, $repo)
    {
        $qb = $repo->createQueryBuilder('m');
        $qb->select('COUNT(m)');
        $qb->Where('m.message LIKE :message');
        $qb->setParameter('message', '%' . $data . '%');

        $total = $qb->getQuery()->getSingleScalarResult();

        return $total;
    }

    public function chatAction(Request $request)
    {
        return $this->render('BackOfficeBundle::chat.html.twig');
    }

    public function listInboxAction(Request $request, $page = 1, $mode = "all")
    {
        $data = array();
        $form = $this->createFormBuilder($data)->add('search', 'text')->getForm();
        $repo = $this->getDoctrine()->getRepository('AppBundle:Message');

        $totalMessage = 0;
        if ($request->isMethod('POST')) 
        {
            $form->handleRequest($request);
            $data = $form->getData();
            $totalMessage = $this->getTotalSearchMessage($data['search'], $repo);
        }
        elseif (!empty($request->query->get('search')))
            $totalMessage = $this->getTotalSearchMessage($request->query->get('search'), $repo);
        else
            $totalMessage = $this->getTotalMessage($mode);

        $paginator = $this->get('mentobe_paginator');
        $numberMessageDisplay = 5;
        $last = ceil($totalMessage / $numberMessageDisplay);

        if ($last < 1)
            $last = 1;

        if ($page < 1)
            $page = 1;
        else if ($page > $last)
        {
            $page = $last;
        }

        $limit = ($page - 1) * $numberMessageDisplay;

        if ($request->isMethod('POST') || !empty($request->query->get('search')))
        {
            $word_to_search = empty($data['search']) ?  $request->query->get('search') : $data['search'];
            $qb = $repo->createQueryBuilder('m');
            $qb->Where('m.message LIKE :message');
            $qb->setParameter('message', '%' . $word_to_search . '%');
            $qb->orderBy('m.date', 'DESC');
            $qb->setFirstResult($limit)
                ->setMaxResults($numberMessageDisplay);

            $messages = $qb->getQuery()->getResult();
            $pagination = $paginator->getPagination($last, $page, "user_list_inbox_pagination", array('search' => $word_to_search));
        }
        else
        {
            $qb = $repo->createQueryBuilder('m');
            if ($mode == 'read')
            {
                $qb->where('m.status = :status');
                $qb->setParameter('status', Message::READ);
            }
            else if ($mode == 'unread')
            {
                $qb->where('m.status = :status');
                $qb->setParameter('status', Message::UNREAD);
            }

            $qb->orderBy('m.date', 'DESC');
            $qb->setFirstResult($limit)
                ->setMaxResults($numberMessageDisplay);

            $messages = $qb->getQuery()->getResult();

            if ($mode == 'all')
                $pagination = $paginator->getPagination($last, $page, "user_list_inbox_pagination");
            else
                $pagination = $paginator->getPagination($last, $page, "user_list_inbox_read_unread_pagination", array('mode' => $mode));
        }

        return $this->render('BackOfficeBundle::inbox.html.twig',
            array(
                'messages'      => $messages,
                'pagination'    => $pagination,
                'nb_messages'   => $this->getTotalMessage(),
                'nb_unread'     => $this->get_nb_unread_message(),
                'nb_read'       => $this->get_nb_read_message(),
                'form'          => $form->createView() 
            )
        );
    }

    public function readMessageAction(Request $request, $idMessage)
    {
        $em = $this->getDoctrine()->getManager();
        $message = $this->getDoctrine()
            ->getRepository('AppBundle:Message')
            ->findOneBy(array('id' => $idMessage));

        if ($message->getStatus() == Message::UNREAD)
        {
            $message->setStatus(Message::READ);
            $em->persist($message);
            $em->flush();
        }

        return $this->render('BackOfficeBundle::read_message.html.twig',
            array(
                'message'       => $message,
                'nb_messages'   => $this->getTotalMessage(),
                'nb_unread'     => $this->get_nb_unread_message(),
                'nb_read'       => $this->get_nb_read_message()
            )
        );
    }

    public function replyMessageAction(Request $request, $idMessage)
    {
        $em = $this->getDoctrine()->getManager();
        $message = $this->getDoctrine()
            ->getRepository('AppBundle:Message')
            ->findOneBy(array('id' => $idMessage));

        $form = $this->get('form.factory')->create(new MessageType(), $message);

        if ($form->handleRequest($request)->isValid())
        {
            $mail = \Swift_Message::newInstance()
                ->setSubject($message->getSubject())
                ->setFrom('contact@mentobe.fr')
                ->setTo($message->getEmail())
                ->setBody($message->getMessage(),
                    'text/html'
                );

            $this->get('mailer')->send($mail);

            return $this->redirect($this->generateUrl('user_list_inbox'));
        }

        return $this->render('BackOfficeBundle::reply_message.html.twig',
            array(
                'message'       => $message,
                'nb_messages'   => $this->getTotalMessage(),
                'nb_unread'     => $this->get_nb_unread_message(),
                'nb_read'       => $this->get_nb_read_message(),
                'form'          => $form->createView()
            )
        );
    }

    public function deleteMessageAction(Request $request, $idMessage)
    {
        $em = $this->getDoctrine()->getManager();
        $repositoryMessage = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Message');

        $message = $repositoryMessage->findOneBy(array('id' => $idMessage));

        if (!empty($message))
        {
            $em->remove($message);
            $em->flush();

            return $this->redirect($this->generateUrl("user_list_inbox"));
        }

        return $this->redirect($this->generateUrl("user_list_inbox"));
    }

    public function newMessageAction(Request $request)
    {

        $message = new Message();
        $form = $this->get('form.factory')->create(new UserMessageType(), $message);

        $form->handleRequest($request);

        if ($form->isValid()) 
        {
            $em = $this->getDoctrine()->getManager();
            $message->setName($this->getUser()->getLastname() . " " . $this->getUser()->getFirstname());
            $message->setEmail($this->getUser()->getEmail());
            $message->setStatus(Message::UNREAD);
            $message->setOwner($this->getUser());
            $event = new MessageEvent($message);
            $this->get('event_dispatcher')->dispatch(BackOfficeEvents::POST_MESSAGE, $event);
            $em->persist($message);
            $em->flush();

            return $this->redirect($this->generateUrl("user_list_inbox"));
        }

        return $this->render('BackOfficeBundle::new_message.html.twig',
            array(
                'nb_messages'   => $this->getTotalMessage(),
                'nb_unread'     => $this->get_nb_unread_message(),
                'nb_read'       => $this->get_nb_read_message(),
                'form'          => $form->createView()
            )
        ); 
    }
}
