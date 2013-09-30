<?php

namespace Helios\BlogBundle\Form;

use Helios\BlogBundle\Entity\ArticleCompetence;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ArticleCompetenceType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('competence', 'entity', array(
        'class'   => 'HeliosBlogBundle:Competence'
      ))
      ->add('niveau', 'choice', array(
        'choices' => ArticleCompetence::getNiveaux()
      ))
    ;
  }

  public function setDefaultOptions(OptionsResolverInterface $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'Helios\BlogBundle\Entity\ArticleCompetence'
    ));
  }

  public function getName()
  {
    return 'helios_blogbundle_articlecompetencetype';
  }
}
