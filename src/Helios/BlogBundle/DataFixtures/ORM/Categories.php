<?php
// src/Helios/BlogBundle/DataFixtures/ORM/Categories.php

namespace Helios\BlogBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Helios\BlogBundle\Entity\Categorie;

class Categories implements FixtureInterface
{
  public function load(ObjectManager $manager)
  {
    // Liste des noms de catégorie à ajouter
    $noms = array('Categorie1', 'Categorie2', 'Categorie3', 'Categorie4', 'Categorie5');

    foreach ($noms as $i => $nom) {
      // On crée la catégorie
      $liste_categories[$i] = new Categorie();
      $liste_categories[$i]->setNom($nom);

      // On la persiste
      $manager->persist($liste_categories[$i]);
    }

    // On déclenche l'enregistrement
    $manager->flush();
  }
}
