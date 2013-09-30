<?php
// src/Helios/BlogBundle/Form/ImageType.php

namespace Helios\BlogBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ImageType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('file', 'file')
    ;
  }

  public function setDefaultOptions(OptionsResolverInterface $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'Helios\BlogBundle\Entity\Image'
    ));
  }

  public function getName()
  {
    return 'helios_blogbundle_imagetype';
  }
}
