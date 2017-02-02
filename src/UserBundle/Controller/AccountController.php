<?php

namespace UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UserBundle\Entity\User;

class AccountController extends Controller
{
	public function resetPasswordAction(Request $request)
	{
		$form_data = array();

	    $form = $this->createFormBuilder($form_data)
	        ->add('email', 'email')
	        ->getForm();

    	$form->handleRequest($request);

	    if ($form->isValid()) {
	        $form_data = $form->getData();

	        $em = $this->getDoctrine()->getManager();
	        $user = $this->getDoctrine()
	            ->getRepository('UserBundle:User')
	            ->findOneBy(array('email' => $form_data['email']));

            return $this->render('UserBundle::reset_password.html.twig',
                array(
                    'form'          => $form->createView()
                )
            );
	    }

        return $this->render('UserBundle::reset_password.html.twig',
            array(
                'form'          => $form->createView()
            )
        );
    }
}