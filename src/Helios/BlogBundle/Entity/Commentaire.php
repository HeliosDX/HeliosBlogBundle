<?php
// src/Helios/Blog/Bundle/Entity/Commentaire.php

namespace Helios\BlogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Helios\BlogBundle\Akismet\Akismet;
use Helios\BlogBundle\Validator\AntiFlood;

/**
 * @ORM\Table(name="commentaire")
 * @ORM\Entity(repositoryClass="Helios\BlogBundle\Entity\CommentaireRepository")
 * @Akismet
 */
class Commentaire
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="auteur", type="string", length=255, nullable=true)
     * Je mets cette colonne à nullable=true car maintenant on a aussi l'attribut $user
     */
    private $auteur;

    /**
     * @ORM\Column(name="contenu", type="text")
     * @Assert\NotBlank()
     * @AntiFlood(secondes="45")
     */
    private $contenu;

    /**
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(name="ip", type="string", length=255)
     */
    private $ip;

    /**
     * @ORM\ManyToOne(targetEntity="Helios\BlogBundle\Entity\Article", inversedBy="commentaires")
     * @ORM\JoinColumn(nullable=false)
     */
    private $article;

    /**
     * @ORM\ManyToOne(targetEntity="Helios\UserBundle\Entity\User")
     */
    private $user;

    public function __construct()
    {
        $this->date = new \Datetime();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $auteur
     * @return Commentaire
     */
    public function setAuteur($auteur)
    {
        $this->auteur = $auteur;
        return $this;
    }

    /**
     * @return string
     */
    public function getAuteur()
    {
        return $this->auteur;
    }

    /**
     * @param text $contenu
     * @return Commentaire
     */
    public function setContenu($contenu)
    {
        $this->contenu = $contenu;
        return $this;
    }

    /**
     * @return text
     */
    public function getContenu()
    {
        return $this->contenu;
    }

    /**
     * @param datetime $date
     * @return Commentaire
     */
    public function setDate(\Datetime $date)
    {
        $this->date = $date;
        return $this;
    }

    public function getIp()
    {
        return $this->ip;
    }

    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return datetime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param Helios\BlogBundle\Entity\Article $article
     * @return Commentaire
     */
    public function setArticle(\Helios\BlogBundle\Entity\Article $article)
    {
        $this->article = $article;
        return $this;
    }

    /**
     * @return Helios\BlogBundle\Entity\Article
     */
    public function getArticle()
    {
        return $this->article;
    }

    /**
     * Set user
     *
     * @param \Helios\UserBundle\Entity\User $user
     * @return Commentaire
     */
    public function setUser(\Helios\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Helios\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }
}