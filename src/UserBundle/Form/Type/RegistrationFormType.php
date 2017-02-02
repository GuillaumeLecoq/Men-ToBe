<?php
/**
 * Created by PhpStorm.
 * User: pira
 * Date: 23/01/2016
 * Time: 16:21
 */

namespace UserBundle\Form\Type;

use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationFormType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('lastname')
            ->add('firstname')
            ->add('plainPassword', 'repeated', array(
                'type' => 'password',
                'options' => array('translation_domain' => 'FOSUserBundle'),
                'first_options' => array('label' => 'form.password'),
                'second_options' => array('label' => 'form.password_confirmation'),
                'invalid_message' => 'Le mot de passe ne semble pas être le même.'
                ))
            ;
    }


    public function getName()
    {
        return 'acme_user_registration';
    }
}