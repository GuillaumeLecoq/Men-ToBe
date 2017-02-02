<?php
/**
 * Created by PhpStorm.
 * User: pira
 * Date: 23/01/2016
 * Time: 12:34
 */

namespace AdminBundle\Controller;

use AppBundle\Entity\Preference;
use AppBundle\Entity\PreferenceCategory;
use FrontOfficeBundle\Controller\FrontOfficeController;
use Task\FrontControllerCleanTask;
use UserBundle\Entity\User;
use AppBundle\Entity\FluxRss;
use AppBundle\Entity\RssEntity;
use AppBundle\Entity\Tag;
use Cocur\Slugify\Slugify;
use AppBundle\Entity\Article;
use AppBundle\Entity\Category;
use AppBundle\Form\ArticleType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Message;
use AppBundle\Form\MessageType;

class AdminController extends Controller {

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

        // publish article list  
        $publishArticles = $articleRepository->getArticle(
            array("step" => Article::WAITING),
            array("updatedAt" => "desc"),
            6,
            0
        );

        return $this->render('AdminBundle::home_page.html.twig', array(
                'userEvents' => $this->getDoctrine()->getRepository('AppBundle:UserEvent')->findBy(array("author" => $this->getUser()), array('date' => 'DESC')),
                'totalUser' => $userRepository->getTotalUser(),
                'totalAuthor' => $userRepository->getTotalAuthor(),
                'publishArticles' => $publishArticles,
                'totalArticles' => $articleRepository->getTotalArticleByStep(Article::WAITING),
                'fileActus' => $lastArticles,
            )
        );
    }

    /*****************************/
    /*******    ADD     *********/
    /****************************/

    public function addfluxrssAction(Request $request)
    {
        $fluxRss = new FluxRss();

        $formBuilder = $this->get('form.factory')->createBuilder('form', $fluxRss);
        $formBuilder
            ->add('name', 'text')
            ->add('url', 'text')
        ;

        $form = $formBuilder->getForm();

        if ($form->handleRequest($request)->isValid())
        {
            $em = $this->get('doctrine')->getManager('postgresql');
            $em->persist($fluxRss);
            $em->flush();

            $this->addFlash(
                'success',
                'L\'ajout du flux rss a été bien effectué.'
            );

            return $this->redirect($this->generateUrl("list_flux_rss_event"));
        }

        return $this->render('AdminBundle::create_flux_rss.html.twig', array('form' => $form->createView()));
    }


    public function addUserAction(Request $request)
    {
        $user = new User();

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
            ->add('roles', 'choice', array(
                    'choices' => array(
                        "ROLE_ADMIN" => "Admin",
                        "ROLE_VISITOR" => "Visiteur",
                        "ROLE_AUTHOR" => "Auteur"
                    ),
                    'data' => array("ROLE_VISITOR"),
                    'label' => 'Roles',
                    'expanded' => false,
                    'multiple' => true,
                    'mapped' => true,
                )
            )
            ->add('enabled', 'choice', array(
                    'choices' => array(
                        "0" => "Innactive",
                        "1" => "Active"
                    )
                )
            )
            ->getForm()
        ;

        if ($form->handleRequest($request)->isValid()) {

            /** @var $formPost add the selected role in the form */
            $formPost = $request->request->get('form');
            foreach ($formPost["roles"] as $rolePost) {
                $user->addRole($rolePost);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $this->addFlash(
                'notice',
                'Un utilisateur vient d\'être ajouter.'
            );

            return $this->redirect($this->generateUrl("list_user"));
        }

        return $this->render('AdminBundle::add_user.html.twig',
            array(
                'form' => $form->createView()
            )
        );
    }


    // update article 
    public function updateArticleAction(Request $request, $id)
    {
        $articleUpdate = $this->getDoctrine()->getRepository('AppBundle:Article')->findOneBy(array('id' => $id));

        $form = $this->get('form.factory')->create(new ArticleType(array('imageFile' => false)), $articleUpdate);

        if ($form->handleRequest($request)->isValid())
        {
            $slugify = new Slugify();
            $articleUpdate->setUpdatedAt(new \Datetime());
            $articleUpdate->setSlug($slugify->slugify($articleUpdate->getName()));

            $articleUpdate->setStep(Article::PUBLICATION);
            $this->addFlash(
                'notice',
                'Article enregistré avec succès.'
            );

            $em = $this->getDoctrine()->getManager();
            $em->persist($articleUpdate);
            $em->flush();

            return $this->redirect($this->generateUrl('list_article_validation'));
        }

        return $this->render('AdminBundle::update_article.html.twig', array(
            'form' => $form->createView(), 
            'article' => $articleUpdate
            )
        );
    }

    public function deletecategoryAction($name)
    {
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Category');

        /* TODO(Guillaume): Remove all articles with this category name */

        $category = $repository->findOneBy(array('name' => $name));
        $em = $this->getDoctrine()->getManager();
        $em->remove($category);
        $em->flush();

        return new Response("delete category " . $name);
    }

    // delete flux rss by id
    public function deletefluxrssAction($idFluxRss)
    {
        $em = $this->get('doctrine')->getManager('postgresql');
        $repo = $em->getRepository('AppBundle:FluxRss');
        $repo_entity = $em->getRepository('AppBundle:RssEntity');

        $fluxRss = $repo->findOneBy(array('id' => $idFluxRss));
        $number_of_rss = $em->getRepository('AppBundle:RssEntity')->getTotalRssEntityByFluxRss($fluxRss);
        if ($number_of_rss)
        {
            $rssEntities = $repo_entity->getRssEntityByFluxRss($fluxRss);
            foreach ($rssEntities as $entity) {
              $em->remove($entity);
            }
        }

        $em->remove($fluxRss);
        $em->flush();

        $this->addFlash(
            'warning',
            'La suppression du flux rss a été bien effectué.'
        );

        return $this->redirect($this->generateUrl("list_flux_rss_event"));
    }

    // delete flux rss by id
    public function deleteTagAction($idTag)
    {
        $em = $this->get('doctrine')->getManager();
        $repo = $em->getRepository('AppBundle:Tag');

        $tag = $repo->findOneBy(array('id' => $idTag));

        $em->remove($tag);
        $em->flush();

        $this->addFlash(
            'warning',
            'La suppression d\'un tag a été bien effectué.'
        );

        return $this->redirect($this->generateUrl("list_tag"));
    }

    public function deleteUserAction(Request $request, $idUser)
    {
        $em = $this->getDoctrine()->getManager();
        $repositoryUser = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('UserBundle:User');

        $user = $repositoryUser->findOneBy(array('id' => $idUser));

        if (!empty($user))
        {
            /*delete all article with user id*/
            $repository = $this
                ->getDoctrine()
                ->getManager()
                ->getRepository('AppBundle:Article');

            $articles = $repository->findBy(array('author' => $user->getId()));
            foreach ($articles as $article)
                $em->remove($article);

            $em->remove($user);
            $em->flush();

            $this->addFlash(
                'warning',
                'Un utilisateur vient d\'être supprimé.'
            );

            return $this->redirect($this->generateUrl("list_user_event"));
        }

        return $this->redirect($this->generateUrl("list_user_event"));
    }

    /** Method for the view of waiting article */
    public function listarticlevalidationAction($page = 1)
    {
        $paginator = $this->get('mentobe_paginator');
        /** access to article repository and category repository */
        $articleRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Article');

        /** return the total article where step is waiting */
        $totalArticleValidation = $articleRepository->getTotalArticleWaiting();

        $totalDisplay = 5;
        $last = ceil($totalArticleValidation / $totalDisplay);

        if ($last < 1)
            $last = 1;

        if ($page < 1)
            $page = 1;
        else if ($page > $last)
        {
            $page = $last;
        }

        $offset = ($page - 1) * $totalDisplay;

        /** get list of article where step is waiting by limitation for pagination */
        $articles = $articleRepository->getArticle(
            array("step" => Article::WAITING),
            array("updatedAt" => "desc"),
            $totalDisplay,
            $offset
        );

        return $this->render('AdminBundle::list_article_validation.html.twig',
            array(
                "articles" => $articles,
                'pagination' => $paginator->getPagination($last, $page, "list_article_validation_pagination")
            )
        );
    }

    /** Method for the view of publish article */
    public function listArticlePublishAction(Request $request, $page = 1)
    {
        /** access to article repository and category repository */
        $articleRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Article');

        $paginator = $this->get('mentobe_paginator');

       /** get the total of article where step is publish */
        $totalArticleValidation = $articleRepository->getTotalArticlePublish();

        $totalDisplay = 5;
        $last = ceil($totalArticleValidation / $totalDisplay);

        if ($last < 1)
            $last = 1;

        if ($page < 1)
            $page = 1;
        else if ($page > $last)
        {
            $page = $last;
        }

        $offset = ($page - 1) * $totalDisplay;

        /** get list of article where step is waiting by limitation for pagination */
        $articles = $articleRepository->getArticle(
            array("step" => Article::PUBLICATION),
            array("updatedAt" => "desc"),
            $totalDisplay,
            $offset
        );

        return $this->render('AdminBundle::list_article_publish.html.twig',
            array(
                "articles" => $articles,
                "totalPublish" => $totalArticleValidation,
                'pagination' => $paginator->getPagination($last, $page, "list_article_publish_pagination")
            )
        );
    }

    /*delete publish article*/
    public function deletePublishArticleAction(Request $request, $idArticle) {

        $repositoryArticle = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Article');

        $article = $repositoryArticle->findOneBy(array('id' => $idArticle));

        if (!empty($article))
        {
            $em = $this->getDoctrine()->getManager();
            $em->remove($article);
            $em->flush();

            $this->addFlash(
                'warning',
                'Un article vient d\'être supprimé.'
            );
        }

        return $this->redirect($this->generateUrl("list_article_publish"));
    }

    /*update publish article*/
    public function updatePublishArticleAction(Request $request, $idArticle) {
        $articleUpdate = $this->getDoctrine()->getRepository('AppBundle:Article')->findOneBy(array('id' => $idArticle));

        $form = $this->get('form.factory')->create(new ArticleType(array('imageFile' => false)), $articleUpdate);

        if ($form->handleRequest($request)->isValid())
        {
            $slugify = new Slugify();
            $articleUpdate->setSlug($slugify->slugify($articleUpdate->getName()));

            $this->addFlash(
                'notice',
                'Article modifié avec succès.'
            );

            $em = $this->getDoctrine()->getManager();
            $em->persist($articleUpdate);
            $em->flush();

            return $this->redirect($this->generateUrl('list_article_publish'));
        }

        return $this->render('AdminBundle::update_article_publish.html.twig', array(
            'form' => $form->createView(), 
            'article' => $articleUpdate
            )
        );
    }


    /** Method use for flux rss view */
    public function listfluxrssAction($page=1)
    {
        /** access to flux rss repository */
        $fluxRssRepository = $this->getDoctrine()->getManager('postgresql')->getRepository('AppBundle:FluxRss');

        /** return the total number of flux rss */
        $totalFluxRss = $fluxRssRepository->getTotalFluxRss();

        $totalDisplay = 5;
        $last = ceil($totalFluxRss / $totalDisplay);
        $paginator = $this->get('mentobe_paginator');


        if ($last < 1)
            $last = 1;

        if ($page < 1)
            $page = 1;
        else if ($page > $last)
        {
            $page = $last;
        }

        $offset = ($page - 1) * $totalDisplay;

        return $this->render('AdminBundle::list_flux_rss.html.twig',
            array(
                "fluxRss" => $fluxRssRepository->getFluxRss(null, null, $totalDisplay, $offset),
                'pagination' => $paginator->getPagination($last, $page, "list_flux_rss_pagination")
                )
            );
    }

    /** Method use for tag view */
    public function listTagAction($page=1)
    {
        /** access to flux rss repository */
        $tagRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Tag');

        /** return the total number of flux rss */
        $totalTag = $tagRepository->getTotalTag();

        $totalDisplay = 5;
        $last = ceil($totalTag / $totalDisplay);
        $paginator = $this->get('mentobe_paginator');


        if ($last < 1)
            $last = 1;
        if ($page < 1)
            $page = 1;

        else if ($page > $last)
        {
            $page = $last;
        }

        $offset = ($page - 1) * $totalDisplay;

        return $this->render('AdminBundle::list_tag.html.twig',
            array(
                "tag" => $tagRepository->getTag(array(), array("date" => "DESC"), $totalDisplay, $offset),
                'pagination' => $paginator->getPagination($last, $page, "list_tag_pagination")
                )
            );
    }

    /*** List USER ***/
    public function listUserAction(Request $request, $page = 1)
    {
        /** access to user repository */
        $userRepository = $this->getDoctrine()->getManager()->getRepository('UserBundle:User');

        $paginator = $this->get('mentobe_paginator');
        $totalUser = $userRepository->getTotalUser() - 1;
        $totalDisplay = 5;
        $last = ceil($totalUser / $totalDisplay);

        if ($last < 1)
            $last = 1;

        if ($page < 1)
            $page = 1;
        else if ($page > $last)
        {
            $page = $last;
        }

        $offset = ($page - 1) * $totalDisplay;

        $users = $userRepository->getAllUserExcept($this->get('security.context')->getToken()->getUser(), $offset, $totalDisplay);

        return $this->render('AdminBundle::list_user.html.twig',
            array(
                'users' => $users,
                'listRoles' => $this->getExistingRoles(),
                'pagination' => $paginator->getPagination($last, $page, "list_user_pagination")
            )
        );
    }

    /*****************************/
    /*******   UPDATE   *********/
    /****************************/


    public function updatefluxrssAction(Request $request, $idFluxRss)
    {
        $fluxRss = $this->getDoctrine()->getRepository('AppBundle:FluxRss', 'postgresql')->findOneBy(array('id' => $idFluxRss));

        $formBuilder = $this->get('form.factory')->createBuilder('form', $fluxRss);
        $formBuilder
            ->add('name', 'text')
            ->add('url', 'text')
        ;

        $form = $formBuilder->getForm();
        if ($form->handleRequest($request)->isValid())
        {
            $em = $this->get('doctrine')->getManager('postgresql');
            $em->persist($fluxRss);
            $em->flush();

            $this->addFlash(
                'notice',
                'La modification du flux rss a été bien effectué.'
            );

            return $this->redirect($this->generateUrl("list_flux_rss"));
        }


        !empty($fluxRss->getName()) ? $nameFluxRss = $fluxRss->getName(): $nameFluxRss = "";
        !empty($fluxRss->getUrl()) ? $urlFluxRss = $fluxRss->getUrl(): $urlFluxRss = "";

        return $this->render('AdminBundle::update_flux_rss.html.twig',
            array(
                'form' => $form->createView(),
                'nameFluxRss' => $nameFluxRss,
                'urlFluxRss' => $urlFluxRss
            )
        );
    }

    public function updateTagAction(Request $request, $idTag)
    {
        $tag = $this->getDoctrine()->getRepository('AppBundle:Tag')->findOneBy(array('id' => $idTag));

        $formBuilder = $this->get('form.factory')->createBuilder('form', $tag);
        $formBuilder
            ->add('name', 'text')
            ->add('imageUrl', 'text')
            ->add('category', 'entity', array(
                'class' => 'AppBundle:Category',
                'choice_label' => 'name'
            ));


        $form = $formBuilder->getForm();
        if ($form->handleRequest($request)->isValid())
        {
            $em = $this->get('doctrine')->getManager();
            $em->persist($tag);
            $em->flush();

            $this->addFlash(
                'notice',
                'La modification du tag a été bien effectué.'
            );

            return $this->redirect($this->generateUrl("list_tag"));
        }

        return $this->render('AdminBundle::update_tag.html.twig',
            array(
                'form' => $form->createView(),
                'tag' => $tag
            )
        );
    }

    public function updateUserAction(Request $request, $idUser)
    {
        $user = $this->getDoctrine()->getRepository('UserBundle:User')->findOneBy(array('id' => $idUser));

        $user = $this->getDoctrine()
            ->getRepository('UserBundle:User')
            ->findOneBy(array('id' => $idUser));

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
            ->add('roles', 'choice', array(
                    'choices' => array(
                        "ROLE_ADMIN" => "Admin",
                        "ROLE_VISITOR" => "Visiteur",
                        "ROLE_AUTHOR" => "Auteur"
                    ),
                    'data' => array("ROLE_VISITOR"),
                    'label' => 'Roles',
                    'expanded' => false,
                    'multiple' => true,
                    'mapped' => true,
                )
            )
            ->add('enabled', 'choice', array(
                    'choices' => array(
                        "0" => "Innactive",
                        "1" => "Active"
                    )
                )
            )
            ->getForm()
        ;

        if ($form->handleRequest($request)->isValid())
        {
            /** @var $existingRoles get the role of the user */
            $existingRoles = $this->getExistingRoles();

            foreach ($existingRoles as $existing)
            {
                /** delete all existed role */
                $user->removeRole($existing);
            }

            /** @var  $formPost get form data */
            $formPost = $request->request->get('form');
            foreach ($formPost["roles"] as $rolePost)
            {
                /** add the new role */
                $user->addRole($rolePost);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $this->addFlash(
                'info',
                'Modification : Un utilisateur vient d\'être modifié.'
            );

            return $this->redirect($this->generateUrl("list_user_event"));
        }

        return $this->render('AdminBundle::update_user.html.twig',
            array(
                'form' => $form->createView()
            )
        );
    }

    /*****************************/
    /*******    GET     *********/
    /****************************/

    public function getExistingRoles()
    {
        $roles = array();
        foreach ($this->getParameter('security.role_hierarchy.roles') as $key => $value) {
            $roles[] = $key;

            foreach ($value as $value2) {
                $roles[] = $value2;
            }
        }
        $roles = array_unique($roles);

        return $roles;
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

    /*****************************/
    /*******   OTHER    *********/
    /****************************/

    public function handleContactForm(Request $request)
    {

        $message = new Message();
        $formBuilder = $this->get('form.factory')->createBuilder('form', $message);

        $formBuilder
            ->add('name',    'text')
            ->add('email',   'text')
            ->add('subject', 'text')
            ->add('message', 'textarea')
            ->add('send', 'submit', array('label' => 'Envoyer'))
        ;

        $form = $formBuilder->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $message->setStatus(Message::UNREAD);
            $em->persist($message);
            $em->flush();
            $request->getSession()->getFlashBag()->add('notice', 'Votre message a bien été enregistré.');
            return $this->redirect($this->generateUrl('front_office_homepage'));
        }

        return $form;
    }

    public function previewArticleAction(Request $request, $id)
    {
        /** access to article repository and category repository */
        $articleRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Article');
        $categoryRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Category');
        $analyticsRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:PopArticlesAnalyticsMonth');
        $userRepository = $this->getDoctrine()->getManager()->getRepository('UserBundle:User');

        /** get the information for the looking for article */
        $article = $articleRepository->findOneBy(array('id' => $id));

        $articles = array();
        $articles[0] = $articleRepository->getNextPublishArticle($article->getUpdatedAt(), $article->getCategory());
        $articles[1] = $articleRepository->getPreviousPublishArticle($article->getUpdatedAt(), $article->getCategory());

        return $this->render('FrontOfficeBundle::article.html.twig', array(
            'article' => $article,
            'articles' => $articles,
            'menus' => $categoryRepository->findAll(),
            'popularArticles' => $articleRepository->getPopularArticle(0, 0, $analyticsRepository->getPopularArticle(4, 0, $article->getCategory()->getName())),
            'mostCommentedArticles' => $articleRepository->mostCommentedArticles(),
            'popularAuthors' => $userRepository->findById($analyticsRepository->getPopularAuthors(5, 0))
        ));
    }

    public function associationcategorytagAction()
    {

        // get all flux rss
        $fluxRss = $this->getDoctrine()->getRepository('AppBundle:FluxRss', 'postgresql')->findAll();

        if (!$fluxRss)
        {
            $this->addFlash(
            'error',
            'Veuillez ajouter au moins un flux rss'
            );
            return $this->redirect($this->generateUrl("admin_homepage"));         
        }
        
        //get default flux rss (Eurosport)
        $fluxRssDefault = $this->getDoctrine()
            ->getRepository('AppBundle:FluxRss', 'postgresql')
            ->findBy(
                array(),
                array("id" => "ASC"),
                1,
                0
            );

        // default rss id
        $defaultRssId = $fluxRssDefault[0]->getId();

        //get default entity of flux rss default
        $rssEntityDefault = $this->getDoctrine()
            ->getRepository('AppBundle:RssEntity', 'postgresql')
            ->findBy(array('fluxRss' => $fluxRssDefault[0]->getId()));

        return $this->render('AdminBundle::association_category_tag.html.twig', array('defaultRssId' => $defaultRssId, 'fluxRss' => $fluxRss, 'rssEntity' => $rssEntityDefault));
    }

    //get all rss entity for specific id flux rss
    public function ajaxrssentityAction($id)
    {
        $rssEntity = $this->getDoctrine()->getRepository('AppBundle:RssEntity', 'postgresql')->findBy(array('fluxRss' => $id));

        $html = '';

        foreach ($rssEntity as $entity) {

            $dateTime = $entity->getDate();
            $date = $dateTime->format("d-m-y h:i:s");
            $url = $this->get('router')->generate('maj_category_tag', array("id" => $entity->getId()), true);

            $html .= '<a href="'
                .$url.'" style="color: #000000;"><div class="col-sm-12"><div class="row the-notes info"><div class="col-sm-8"><h4>'
                .$entity->getName().'</h4><p>'
                .$date.'</p></div><div class="col-sm-4"><img src="'
                .$entity->getImageUrl().'" style="height: auto; width: 100%"></div></div></div></a>';
        }

        return new Response($html);
    }

    public function majcategorytagAction(Request $request, $id)
    {
        $tag = new Tag();
        $rssEntity = $this->getDoctrine()->getRepository('AppBundle:RssEntity', 'postgresql')->findOneBy(array('id' => $id));

        $formBuilder = $this->get('form.factory')->createBuilder('form', $tag);
        $formBuilder
            ->add('name', 'text')
            ->add('imageUrl', 'text')
            ->add('category', 'entity', array(
                'class' => 'AppBundle:Category',
                'choice_label' => 'name'
            ));

        $form = $formBuilder->getForm();

        if ($form->handleRequest($request)->isValid())
        {
            $em_postgresql = $this->getDoctrine()->getManager('postgresql');
            $em_mysql = $this->getDoctrine()->getManager();
            $em_mysql->persist($tag);
            $em_postgresql->remove($rssEntity);
            $em_postgresql->flush();
            $em_mysql->flush();

            return $this->redirect($this->generateUrl("admin_homepage"));
        }

        return $this->render('AdminBundle::validation_category_tag.html.twig', array('form' => $form->createView(), 'rssEntity' => $rssEntity ));
    }

    public function validationconfirmationAction($step, $id)
    {
        $article = $this->getDoctrine()
            ->getRepository('AppBundle:Article')
            ->findOneBy(array('id' => $id));

        $article->setStep($step);
        $article->setUpdatedAt(new \Datetime());

        $this->addFlash(
            'notice',
            'Votre action a été bien effectuée.'
        );

        $em = $this->getDoctrine()->getManager();
        $em->persist($article);
        $em->flush();



        return $this->redirect($this->generateUrl("list_article_validation"));
    }

    public function getTotalSearchMessage($data, $repo)
    {
        $qb = $repo->createQueryBuilder('m');
        $qb->select('COUNT(m)');
        $qb->Where('m.message LIKE :message');
        $qb->setParameter('message', '%' . $data['search'] . '%');

        $total = $qb->getQuery()->getSingleScalarResult();

        return $total;
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
            $totalMessage = $this->getTotalSearchMessage($data, $repo);
        }
        else
        {
            $totalMessage = $this->getTotalMessage($mode);
        }

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

        if ($request->isMethod('POST'))
        {
            $qb = $repo->createQueryBuilder('m');
            $qb->Where('m.message LIKE :message');
            $qb->setParameter('message', '%' . $data['search'] . '%');
            $qb->setFirstResult($limit)
                ->setMaxResults($numberMessageDisplay)
                ->orderBy('m.date', 'DESC');

            $messages = $qb->getQuery()->getResult();
            $pagination = $paginator->getPagination($last, $page, "list_inbox_pagination");
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

            $qb->setFirstResult($limit)
                ->setMaxResults($numberMessageDisplay)
                ->orderBy('m.date', 'DESC');;

            $messages = $qb->getQuery()->getResult();

            if ($mode == 'all')
                $pagination = $paginator->getPagination($last, $page, "list_inbox_pagination");
            else
                $pagination = $paginator->getPagination($last, $page, "list_inbox_read_unread_pagination", array('mode' => $mode));
        }

        return $this->render('AdminBundle::inbox.html.twig',
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

        return $this->render('AdminBundle::read_message.html.twig',
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

                $message->setStatus(Message::READ);
                
                $em = $this->getDoctrine()->getManager();
                $em->persist($message);
                $em->flush();

            $this->get('mailer')->send($mail);

            return $this->redirect($this->generateUrl('list_inbox'));
        }

        return $this->render('AdminBundle::reply_message.html.twig',
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

            return $this->redirect($this->generateUrl("list_inbox"));
        }

        return $this->redirect($this->generateUrl("list_inbox"));
    }

    /*********************************/
    /*******   EXPORT CSV    *********/
    /*********************************/

    public function exportCsvAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repositoryMessage = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('UserBundle:User');

        $users = $repositoryMessage->findAll();

        if (!empty($users))
        {
            $dumper = $this->get('csv_export');

            // Response with content
            $response = new Response($dumper->dump($users));

            $outFileName = date('Ymd-Hi') . '.' . $dumper->getFileExtension();

            $response->headers->set('Content-Type', $dumper->getContentType());
            $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"',$outFileName));

            return $response;
        }

    }
}