<?php
/**
 * Created by PhpStorm.
 * User: pira
 * Date: 24/01/2016
 * Time: 17:03
 */

namespace UserBundle\Component\Authentication\Handler;

use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Router;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{

    protected $router;
    protected $security;

    public function __construct(Router $router, SecurityContext $security)
    {
        $this->router = $router;
        $this->security = $security;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        if ($this->security->isGranted('ROLE_ADMIN'))
            $response = new RedirectResponse($this->router->generate('admin_homepage'));
        elseif ($this->security->isGranted('ROLE_AUTHOR') || $this->security->isGranted('ROLE_VISITOR'))
            $response = new RedirectResponse($this->router->generate('back_office_homepage'));
        else
        {
            // redirect the user to where they were before the login process begun.
            $referer_url = $request->headers->get('referer');
            $response = new RedirectResponse($referer_url);
        }

        return $response;
    }

}