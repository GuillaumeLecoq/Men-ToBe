<?php

namespace FrontOfficeBundle\Controller;

use AppBundle\Entity\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Article;
use AppBundle\Entity\Message;
use blackknight467\StarRatingBundle\Form\RatingType;

class FrontOfficeController extends Controller
{
    public function privacyAction()
    {
        $categoryRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Category');
        
        return $this->render('FrontOfficeBundle::privacy.html.twig', array(
                'menus' => $categoryRepository->findAll()
            )
        );
    }

    public function presentationAction()
    {
      $categoryRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Category');
    return $this->render('FrontOfficeBundle::presentation.html.twig', array(
            'menus' => $categoryRepository->findAll()
            )
        );
    }

    // Method use for the contact page
    public function contactAction(Request $request)
    {
        $categoryRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Category');

        $message = new Message();
        $form = $this->get('form.factory')->createBuilder('form', $message)
          ->add('name',    'text')
          ->add('email',   'text')
          ->add('subject', 'text')
          ->add('message', 'textarea')
          ->add('send', 'submit', array('label' => 'Envoyer'))
          ->getForm()
        ;

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $mail = \Swift_Message::newInstance()
                ->setSubject($message->getSubject())
                ->setFrom('contact@mentobe.fr')
                ->setTo('mentobe.fr@gmail.com')
                ->setBody($message->getMessage(),
                    'text/html'
            );

            $this->get('mailer')->send($mail);
            $message->setStatus(Message::UNREAD);
            $em->persist($message);
            $em->flush();

            $this->addFlash(
                'notice',
                'Votre message a bien été envoyé.'
            );

            return $this->redirect($request->getUri());
        }

        return $this->render('FrontOfficeBundle::contact.html.twig',
            array(
                'menus' => $categoryRepository->findAll(),
                'form' => $form->createView()
            )
        );
    }


    /** Method for home page */
    public function indexAction(Request $request)
    {
        /** access to article repository and category repository */
        $articleRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Article');
        $categoryRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Category');
        $analyticsRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:PopArticlesAnalyticsMonth');
        $userRepository = $this->getDoctrine()->getManager()->getRepository('UserBundle:User');

        /** get last 4 publish articles */
        $lastArticles = $articleRepository->findBy(
            array("step" => Article::PUBLICATION),
            array("updatedAt" => "desc"),
            11,
            0
        );

        // return the list of existing categories
        $categories = $categoryRepository->findAll();
        $lastArticlesByCategory = array();

        // return the last news on each categories
        foreach($categories as $category) {

            $lastArticlesByCategory[] = $articleRepository->getArticle(
                    array("category" => $category, "step" => Article::PUBLICATION),
                    array("updatedAt" => "desc"),
                    6,
                    0
                );
        }

        // Array contain the relation of day and category to display
        $discoverCategoryByDay = array(
            1 => 2,
            2 => 3,
            3 => 4,
            4 => 5,
            5 => 6,
            6 => 7,
            7 => 7
        );

        // list of article on discovering category
        $discoverCategoryArticles = $articleRepository->getArticle(
            array("category" => $discoverCategoryByDay[date('N')], "step" => Article::PUBLICATION),
            array("updatedAt" => "desc"),
            3,
            0
        );

        //discover category
        $discoverCategory = $categoryRepository->find(array("id" => $discoverCategoryByDay[date('N')]));

        return $this->render('FrontOfficeBundle::index.html.twig',
               array(
                   'lastArticles' => $lastArticles,
                   'menus' => $categories,
                   'popularArticles' => $articleRepository->getPopularArticle($analyticsRepository->getPopularArticle(4, 0)),
                   'lastArticlesByCategory' => $lastArticlesByCategory,
                   'mostCommentedArticles' => $articleRepository->mostCommentedArticles(),
                   'popularAuthors' => $userRepository->findById($analyticsRepository->getPopularAuthors(5, 0)),
                   'discoverCategory' => $discoverCategory,
                   'discoverCategoryArticles' => $discoverCategoryArticles
               )
        );
    }

    /** Function who display the article depending on desired data */
    public function searchAction(Request $request)
    {
        /** access to article and category repository */
        $articleRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Article');
        $categoryRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Category');
        $analyticsRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:PopArticlesAnalytics');

        /** expected data on article */
        $searchValue = $request->request->get('input-search');

        return $this->render('FrontOfficeBundle::list.html.twig', array(
            "menus" => $categoryRepository->findAll(),
            "articles" => $articleRepository->getSearchOnArticle($searchValue),
            "categoryName" => "Recherche",
            "searchWorld" => $searchValue,
            'popularArticles' => $articleRepository->getPopularArticle($analyticsRepository->getPopularArticle(4, 0)),
            'mostCommentedArticles' => $articleRepository->mostCommentedArticles(),
        ));
    }


    //Method use for preferences
    public function categoryPreferencesAction(Request $request, $page = 1) {
        /** access to article repository and category repository */
        $articleRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Article');
        $categoryRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Category');
        $analyticsRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:PopArticlesAnalyticsMonth');
        $userRepository = $this->getDoctrine()->getManager()->getRepository('UserBundle:User');
        $paginator = $this->get('mentobe_paginator');

        /** multiple category */
        $categoryName ="Préférences";
        $categories = $this->getUser()->getCategories();

        /** get total number for this category */
        $totalArticle = $articleRepository->getTotalArticleByCategory($categories);

        $totalDisplay = 10;
        $last = ceil($totalArticle / $totalDisplay);

        if ($last < 1)
            $last = 1;

        if ($page < 1)
            $page = 1;
        else if ($page > $last)
        {
            $page = $last;
        }

        $offset = ($page - 1) * $totalDisplay;

        /** get article depending on pagination */
        $articles = $articleRepository->getArticleByCategories($categories, Article::PUBLICATION, array("name" => "updatedAt", "pos" => "desc"), $totalDisplay, $offset);

        $pagination = $paginator->getPagination($last, $page, "category_preferences_pagination");

        return $this->render('FrontOfficeBundle::list.html.twig', array(
            "menus" => $categoryRepository->findAll(),
            "articles" => $articles,
            "categoryName" => $categoryName,
            'popularArticles' => $articleRepository->getPopularArticle($analyticsRepository->getPopularArticle(4, 0)),
            'pagination' => $pagination,
            'mostCommentedArticles' => $articleRepository->mostCommentedArticles()
        ));
    }


    /** Method who display the view of a category */
    public function categoryAction(Request $request, $idCategory, $selectCategory, $page = 1)
    {

        /** access to article repository and category repository */
        $articleRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Article');
        $categoryRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Category');
        $analyticsRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:PopArticlesAnalyticsMonth');
        $userRepository = $this->getDoctrine()->getManager()->getRepository('UserBundle:User');
        $paginator = $this->get('mentobe_paginator');

        /** @var  $lastArticles, $popularArticleOnSelectedCategory initialize the variables to use only if a category exist */
        $lastArticles = array();
        $popularArticleOnSelectedCategory = array();

        /** found the category by is name */
        $categories = $categoryRepository->findOneBy(array("id" => $idCategory));
        $categoryName = $categories->getName();

        /** get last 4 publish articles */
        $lastArticles = $articleRepository->findBy(
            array("step" => Article::PUBLICATION, "category" => $categories),
            array("date" => "desc"),
            4,
            0
        );

        $popularArticleOnSelectedCategory = $articleRepository->getPopularArticleByCategory($categories);

        /** get total number for this category */
        $totalArticle = $articleRepository->getTotalArticleByCategory($categories);

        $totalDisplay = 10;
        $last = ceil($totalArticle / $totalDisplay);

        if ($last < 1)
            $last = 1;

        if ($page < 1)
            $page = 1;
        else if ($page > $last)
        {
            $page = $last;
        }

        $offset = ($page - 1) * $totalDisplay;

        /** get article depending on pagination */
        $articles = $articleRepository->getArticleByCategories($categories, Article::PUBLICATION, array("name" => "updatedAt", "pos" => "desc"), $totalDisplay, $offset);

        $pagination = $paginator->getPagination($last, $page, "categorie_article_pagination", array('idCategory' => $idCategory, 'selectCategory' => $categoryName));

        return $this->render('FrontOfficeBundle::list.html.twig', array(
            "menus" => $categoryRepository->findAll(),
            "articles" => $articles,
            "categoryName" => $categoryName,
            'popularArticles' => $articleRepository->getPopularArticle($analyticsRepository->getPopularArticle(4, 0)),
            'pagination' => $pagination,
            'lastArticles' => $lastArticles,
            'mostCommentedArticles' => $articleRepository->mostCommentedArticles(),
            'popularAuthors' => $userRepository->findById($analyticsRepository->getPopularAuthors(5, 0))
        ));
    }

    public function articleAction(Request $request, $idCategory, $selectCategory, $slug)
    {
        /** access to article repository and category repository */
        $articleRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Article');
        $categoryRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Category');
        $analyticsRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:PopArticlesAnalyticsMonth');
        $userRepository = $this->getDoctrine()->getManager()->getRepository('UserBundle:User');

        /** get the information for the looking for article */
        $article = $articleRepository->findOneBy(array('slug' => $slug, 'step' => Article::PUBLICATION));

        $articles = array();
        $articles[0] = $articleRepository->getNextPublishArticle($article->getUpdatedAt(), $article->getCategory());
        $articles[1] = $articleRepository->getPreviousPublishArticle($article->getUpdatedAt(), $article->getCategory());

        return $this->render('FrontOfficeBundle::article.html.twig', array(
            'article' => $article,
            'articles' => $articles,
            'menus' => $categoryRepository->findAll(),
            'popularArticles' => $articleRepository->getPopularArticle($analyticsRepository->getPopularArticle(4, 0)),
            'mostCommentedArticles' => $articleRepository->mostCommentedArticles(),
            'popularAuthors' => $userRepository->findById($analyticsRepository->getPopularAuthors(5, 0))
        ));
    }

}
