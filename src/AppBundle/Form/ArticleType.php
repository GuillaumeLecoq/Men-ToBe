<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ArticleType extends AbstractType
{
    public function __construct($options = null){
        $this->options = $options;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('tag',    'hidden', array('required' => true))
            ->add('category',    'entity_hidden', array('class' => 'AppBundle\Entity\Category'))
            ->add('name',     'text', array('required' => true))
            ->add('resume',   'genemu_tinymce',   array('required' => true))
            ->add('content',   'textarea', array('required' => true))
            ->add('imageFile', 'file', array('required' => $this->options['imageFile']))
            ->add('linkImage', 'text',            array('required' => false))
            ->add('auteurImage',     'text', array('required' => false))

        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Article'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'appbundle_article';
    }
}
