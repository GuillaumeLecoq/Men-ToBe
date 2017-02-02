<?php

namespace ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\DateTime;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AppBundle\Entity\Article;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Request\ParamFetcherInterface;
use UserBundle\Form\Type\UserCategoriesFormType;

class ApiController extends Controller
{
    /**
     * @ApiDoc(
     *  description="Get list of all categories",
     *  headers={
     *      {
     *          "name"="Authorization:Bearer",
     *          "description"="Token to get an authorization from OAUTH2"
     *      }
     *  }
     * )
     */
    public function categoryAction()
    {
        $categories = $this->getDoctrine()->getRepository('AppBundle:Category')->findAll();

        $listCategory = array();
        foreach ($categories as $key => $category) {
            $listCategory[$key]["id"] = $category->getId();
            $listCategory[$key]["name"] = $category->getName();
            $listCategory[$key]["totalArticle"] = $this->getTotalArticleByCategory($category->getId());
        }

        return new JsonResponse($listCategory);
    }

    /**
     * @ApiDoc(
     *  description="Return Last 'nb' articles from category 'id'",
     *  headers={
     *      {
     *          "name"="Authorization:Bearer",
     *          "description"="Token to get an authorization from OAUTH2"
     *      }
     *  },
     *  requirements={
     *      {
     *          "name"="id",
     *          "dataType"="integer",
     *          "requirement"="\d+",
     *          "description"="id of a category"
     *      },
     *      {
     *          "name" = "offset",
     *          "dataType"="integer",
     *          "requirement"="\d+",
     *          "description"="get results from offset"
     *      },
     *      {
     *          "name" = "nb",
     *          "dataType"="integer",
     *          "requirement"="\d+",
     *          "description"="number of article to get"
     *      }
     *  }
     * )
     */
    public function categoryarticleAction($id, $offset, $nb)
    {
        /** get the category */
        $category = $this->getDoctrine()->getRepository('AppBundle:Category')->findOneBy(array("id" => $id));

        /** access to article repository */
        $articleRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Article');

        /** get articles for the select category */
        $articles = $articleRepository->getArticle(
            array('category' => $category, 'step' => Article::PUBLICATION),
            array('date' => 'desc'),
            $nb,
            $offset
        );


        $url = $this->container->get('router')->getContext()->getBaseUrl();
        $helper = $this->container->get('vich_uploader.templating.helper.uploader_helper');

        $listarticle = array();
        foreach ($articles as $key => $article) {
            $author = ucfirst($article->getAuthor()->getFirstName()) . ' ' . ucfirst($article->getAuthor()->getLastName());

            $path = $helper->asset($article, 'imageFile');

            $listarticle[$key]["id"] = $article->getId();
            $listarticle[$key]["name"] = $article->getName();
            $listarticle[$key]["resume"] = strip_tags($article->getResume());
            $listarticle[$key]["content"] = strip_tags($article->getContent());
            $listarticle[$key]["resume"] = strip_tags($listarticle[$key]["resume"]);
            $listarticle[$key]["content"] = strip_tags($listarticle[$key]["content"]);
            $listarticle[$key]["linkImage"] = $url.$path;
            $listarticle[$key]["date"] = $article->getDate()->format("d-m-Y");
            $listarticle[$key]["author"] = $author;
            $listarticle[$key]['categoryId'] = $article->getCategory()->getId();
        }

        return new JsonResponse($listarticle);
    }

    /**
     * @ApiDoc(
     *  description="Get a article with id equal to 'id'",
     *  headers={
     *      {
     *          "name"="Authorization:Bearer",
     *          "description"="Token to get an authorization from OAUTH2"
     *      }
     *  },
     *  requirements={
     *      {
     *          "name"="id",
     *          "dataType"="integer",
     *          "requirement"="\d+",
     *          "description"="id of this article"
     *      }
     *  }
     * )
     */
    public function articleAction($id)
    {
        /** access to article repository */
        $articleRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Article');

        /** get the detail of id article */
        $article = $articleRepository->find($id);

        $url = $this->container->get('router')->getContext()->getBaseUrl();
        $helper = $this->container->get('vich_uploader.templating.helper.uploader_helper');

        $path = $helper->asset($article, 'imageFile');

        $articleArray = array();
        $author = ucfirst($article->getAuthor()->getFirstName()) . ' ' . ucfirst($article->getAuthor()->getLastName());
        $articleArray["id"] = $article->getId();
        $articleArray["name"] = $article->getName();
        $articleArray["resume"] = strip_tags($article->getResume());
        $articleArray["content"] = strip_tags($article->getContent());
        $articleArray["resume"] = strip_tags($articleArray["resume"]);
        $articleArray["content"] = strip_tags($articleArray["content"]);
        $articleArray["linkImage"] = $url.$path;
        $articleArray["date"] = $article->getDate()->format("d-m-Y");
        $articleArray["author"] = $author;
        $articleArray['categoryId'] = $article->getCategory()->getId();

        return new JsonResponse($articleArray);
    }

    /**
     * @ApiDoc(
     *  description="Get 'nb' lastest article with specific offset",
     *  headers={
     *      {
     *          "name"="Authorization:Bearer",
     *          "description"="Token to get an authorization from OAUTH2"
     *      }
     *  },
     *  requirements={
     *      {
     *          "name"="nb",
     *          "dataType"="integer",
     *          "requirement"="\d+",
     *          "description"="number of articles to get"
     *      },
     *      {
     *          "name" = "offset",
     *          "dataType"="integer",
     *          "requirement"="\d+",
     *          "description"="get results from a specific offset"
     *      }
     *  }
     * )
     */
    public function articlelastAction($offset, $nb)
    {
        /** access to article repository */
        $articleRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Article');

        $articles = $articleRepository->getArticle(
            array('step' => Article::PUBLICATION),
            array('date' => 'desc'),
            $nb,
            $offset
        );

        $articleArray = array();

        $url = $this->container->get('router')->getContext()->getBaseUrl();
        $helper = $this->container->get('vich_uploader.templating.helper.uploader_helper');

        foreach ($articles as $key => $article) {

            $path = $helper->asset($article, 'imageFile');

            $author = ucfirst($article->getAuthor()->getFirstName()) . ' ' . ucfirst($article->getAuthor()->getLastName());
            $articleArray[$key]["id"] = $article->getId();
            $articleArray[$key]["name"] = $article->getName();
            $articleArray[$key]["resume"] = strip_tags($article->getResume());
            $articleArray[$key]["content"] = strip_tags($article->getContent());
            $articleArray[$key]["resume"] = strip_tags($articleArray[$key]["resume"]);
            $articleArray[$key]["content"] = strip_tags($articleArray[$key]["content"]);
            $articleArray[$key]["linkImage"] = $url.$path;
            $articleArray[$key]["date"] = $article->getDate()->format("d-m-Y");
            $articleArray[$key]["author"] = $author;
            $articleArray[$key]['categoryId'] = $article->getCategory()->getId();
        }

        return new JsonResponse($articleArray);
    }

    /** return the total article on a specific category */
    public function getTotalArticleByCategory($idCategory)
    {
        /** access to article repository */
        $articleRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Article');
        return $articleRepository->getTotalArticleByCategory($idCategory);
    }

    /**
     * @ApiDoc(
     *  description="Get all categories from a specific user",
     *  headers={
     *      {
     *          "name"="Authorization:Bearer",
     *          "description"="Token to get an authorization from OAUTH2"
     *      }
     *  }
     * )
     */
    public function getUserCategoriesAction()
    {
        $this->denyAccessUnlessGranted('ROLE_USER', null, 'Vous n\'avez pas les droits !');
        $user = $this->get('security.context')->getToken()->getUser();

        $names = array();
        $categories = $user->getCategories();
        foreach ($categories as $categorie) {
            $names[] = $categorie->getName();
        }

        return new JsonResponse($names);
    }


    protected function getForm($user = null, $routeName = null)
    {
        $options = array();
        $em = $this->getDoctrine()->getManager();

        if (null !== $routeName)
            $options['action'] = $this->generateUrl($routeName);
        if (null === $user)
            $user = new User();

        if (empty($options))
            return $this->createForm(new UserCategoriesFormType($em), $user, array('csrf_protection' => false, 'method' => 'PUT'));

        return $this->createForm(new UserCategoriesFormType($em), $user, array('method' => 'PUT'));
    }

    /**
     * @ApiDoc(
     *  description="Update all categories for a specific user",
     *  headers={
     *      {
     *          "name"="Authorization:Bearer",
     *          "description"="Token to get an authorization from OAUTH2"
     *      }
     *  },
     *  input="UserBundle\Form\Type\UserCategoriesFormType",
     *  statusCodes={
     *         204="Returned when successful: update existing resources",
     *         400="Bad Request or nothing is sent to server",
     *     }
     * )
     */

    public function editUserCategoriesAction(Request $request)
    {
        // Look for convention: space, new line, variable name, etc...
        $this->denyAccessUnlessGranted('ROLE_USER', null, 'Vous n\'avez pas les droits !');
        $user = $this->get('security.context')->getToken()->getUser();
        $repository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Category');

        if($request->getMethod() == 'PUT') // really usefull ? already mentionned in routing.yml
        {
            if (0 === strpos($this->getRequest()->headers->get('Content-Type'), 'application/json')) {

                $data = $this->getRequest()->getContent();
                $json_array = json_decode($data, True);
                // test if it's valid json
                $json_msg_error = json_last_error_msg();

                if (!preg_match("#No error#i", $json_msg_error))
                {
                    return $this->badRequest("Error: " . $json_msg_error);
                }

                $em = $this->getDoctrine()->getManager();

                if (empty($json_array["categories"]))
                {
                    return $this->badRequest("Error: No name is indicated as category");
                }

                $new_categories = $this->convertArrayToArrayObjects($json_array);
                // delete all categories in user
                $user->resetCategories();
                // get all categories in repository
                $existing_categories = $repository->findAll();
                // get all categories name
                $categories_name = [];
                foreach ($existing_categories as $category) {
                    $categories_name[] = $category->getName();
                }

                foreach ($new_categories as $category) {
                    $name = $category->getName();

                    if (in_array($name, $categories_name))
                        $user->addCategory($category);
                    else
                        return $this->badRequest("Error: No existing category named " . $name);
                }

                $em->persist($user);
                $em->flush();
                $response = new Response();
                $response->setStatusCode(204);

                return $response;
            }
            else
            {
                return $this->badRequest("Error: not json content header");
            }
        }
    }

    private function convertArrayToArrayObjects($json_array)
    {
        $objects = [];

        foreach ($json_array["categories"] as $one_array) {
            foreach ($one_array as $name => $value) {
                $repository = $this->getDoctrine()->getManager()->getRepository('AppBundle:Category');
                $current = $repository->findOneBy(
                    array("name" => $value)
                );

                $objects[] = $current;
            }
        }

        return $objects;
    }

    private function binary_search(array $a, $first, $last, $key, $compare) {
        $lo = $first; 
        $hi = $last - 1;
 
        while ($lo <= $hi) {
            $mid = (int)(($hi - $lo) / 2) + $lo;
            $cmp = call_user_func($compare, $a[$mid], $key);
 
            if ($cmp < 0) {
                $lo = $mid + 1;
            } elseif ($cmp > 0) {
                $hi = $mid - 1;
            } else {
                return $mid;
            }
        }
        return -($lo + 1);
    }

    private function cmp($a, $b) {
        return ($a < $b) ? -1 : (($a > $b) ? 1 : 0);
    }

    private function badRequest($msg)
    {
        $view = View::create($msg, 400);
        $view->setFormat('json');

        return $this->get('fos_rest.view_handler')->handle($view);
    }

    public function getFormAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_USER', null, 'Vous n\'avez pas les droits !');
        $user = $this->get('security.context')->getToken()->getUser();
        $form = $this->getForm($user);

        $view = View::create($form);
        $view->setFormat('json');
        
        return $this->get('fos_rest.view_handler')->handle($view);
    }
}
