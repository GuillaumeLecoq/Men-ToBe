<?php

namespace Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Paginator
{

    private $router;

    public function __construct(UrlGeneratorInterface $router) {
        $this->router = $router;
    }


    public function getPagination($last, $page, $route_name, $params = array())
    {
        $pagination = "";

        if ($last != 1) {

            $pagination .= '
                <nav style="text-align: center">
                    <ul class="pagination">';

            if ($page > 1)
            {
                $url = $this->router->generate($route_name, array_merge(array("page" => $page - 1), $params));
                $pagination .= '
                <nav style="text-align: center">
                    <ul class="pagination">
                        <li>
                            <a href="'.$url.'" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>';
                for ($i = $page - 4; $i < $page; $i++)
                {
                    if ($i > 0)
                    {
                        $url = $this->router->generate($route_name, array_merge(array("page" => $i), $params));
                        $pagination .= '<li><a href="'.$url.'">'.$i.'</a></li>';
                    }
                }
            }

            $url = $this->router->generate($route_name, array_merge(array("page" => $page), $params));
            $pagination .= '<li class="active"><a href="'.$url.'">'.$page.'</a></li>';
            for($i = $page + 1; $i <= $last; $i++)
            {
                $url = $this->router->generate($route_name, array_merge(array("page" => $i), $params));
                $pagination .= '<li><a href="'.$url.'">'.$i.'</a></li>';
                if ($i >= $page + 4)
                    break;
            }


            if ($page != $last)
            {
                $next = $page + 1;
                $url = $this->router->generate($route_name, array_merge(array("page" => $next), $params));
                $pagination .=  '<li>
                                    <a href="'.$url.'" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>';
            }

            $pagination .= '</ul></nav>';
        }

        return $pagination;
    }
}
