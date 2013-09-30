<?php

// src/Helios/BlogBundle/Controller/BlogController.php

namespace Helios\BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Form;
use JMS\SecurityExtraBundle\Annotation\Secure;

use Helios\BlogBundle\Entity\Article;
use Helios\BlogBundle\Entity\Categorie;
use Helios\BlogBundle\Entity\Commentaire;
use Helios\BlogBundle\Entity\ArticleCompetence;

use Helios\BlogBundle\Form\ArticleType;
use Helios\BlogBundle\Form\ArticleEditType;
use Helios\BlogBundle\Form\CommentaireType;

use Helios\BlogBundle\Bigbrother\BigbrotherEvents;
use Helios\BlogBundle\Bigbrother\MessagePostEvent;

class BlogController extends Controller
{

    /*
     * Affiche la page d'accueil avec le nombre d'articles par page
     * Pour changer le nombre d'articles par page, on modifie le fichier parametres.yml
     */
  public function indexAction($page)
  {
    // On récupère le nombre d'article par page depuis un paramètre du conteneur
    // cf app/config/parameters.yml
    $nbParPage = $this->container->getParameter('heliosblog.nombre_par_page');

    // On récupère les articles de la page courante
    $articles = $this->getDoctrine()
                     ->getManager()
                     ->getRepository('HeliosBlogBundle:Article')
                     ->getArticles($nbParPage, $page);

    // On passe le tout à la vue
    return $this->render('HeliosBlogBundle:Blog:index.html.twig', array(
      'articles' => $articles,
      'page'     => $page,
      'nb_page'  => ceil(count($articles) / $nbParPage) ?: 1
    ));
  }

    /*
     * Affiche un article
     */
  public function voirAction(Article $article, Form $form = null)
  {
    // On récupère l'EntityManager
    $em = $this->getDoctrine()->getManager();

    // On récupère la liste des commentaires
    $commentaires = $em->getRepository('HeliosBlogBundle:Commentaire')
                       ->getByArticle($article->getId());

    if (null === $form) {
      $form = $this->getCommentaireForm($article);
    }

    return $this->render('HeliosBlogBundle:Blog:voir.html.twig', array(
      'article'      => $article,
      'form'         => $form->createView(),
      'commentaires' => $commentaires
    ));
  }


    /*
     * Ajoute un article
     */
  /**
   * @Secure(roles="ROLE_AUTEUR")
   */
  public function ajouterAction()
  {
    $article = new Article;
    if ($this->getUser()) {
      // On définit le User par défaut dans le formulaire (utilisateur courant)
      $article->setUser($this->getUser());
    }

    // On crée le formulaire grâce à l'ArticleType
    $form = $this->createForm(new ArticleType(), $article);

    // On récupère la requête
    $request = $this->getRequest();

    // On vérifie qu'elle est de type POST
    if ($request->getMethod() == 'POST') {
      // On fait le lien Requête <-> Formulaire
      $form->bind($request);

      // On vérifie que les valeurs rentrées sont correctes
      if ($form->isValid()) {
        // --- Début de notre fonctionnalité BigBrother ---
        // On crée l'évènement
        $event = new MessagePostEvent($article->getContenu(), $this->getUser());

        // On déclenche l'évènement
        $this->get('event_dispatcher')
             ->dispatch(BigbrotherEvents::onMessagePost, $event);

        // On récupère ce qui a été modifié par le ou les listener(s), ici le message
        $article->setContenu($event->getMessage());
        // --- Fin de notre fonctionnalité BigBrother ---

        $article->getArticleCompetences()->clear();

        // On enregistre l'objet $article dans la base de données
        $em = $this->getDoctrine()->getManager();
        $em->persist($article);
        $em->flush();

        foreach ($form->get('articleCompetences')->getData() as $ac) {
          $ac->setArticle($article);
          $em->persist($ac);
        }
        $em->flush();

        // On définit un message flash
        $this->get('session')->getFlashBag()->add('info', 'Article bien ajouté');

        // On redirige vers la page de visualisation de l'article nouvellement créé
        return $this->redirect($this->generateUrl('heliosblog_voir', array('slug' => $article->getSlug())));
      }
    }

    // À ce stade :
    // - soit la requête est de type GET, donc le visiteur vient d'arriver sur la page et veut voir le formulaire
    // - soit la requête est de type POST, mais le formulaire n'est pas valide, donc on l'affiche de nouveau

    return $this->render('HeliosBlogBundle:Blog:ajouter.html.twig', array(
      'form' => $form->createView(),
    ));
  }


    /*
     * Modifie un article, à condition d'être un auteur
     */
  /**
   * @Secure(roles="ROLE_AUTEUR")
   */
  public function modifierAction(Article $article)
  {
    $listeAc = array();
    foreach ($article->getArticleCompetences() as $ac) {
      $listeAc[] = $ac;
    }

    // On utilise le ArticleEditType
    $form = $this->createForm(new ArticleEditType(), $article);

    $request = $this->getRequest();

    if ($request->getMethod() == 'POST') {
      $form->bind($request);

      if ($form->isValid()) {

        $article->getArticleCompetences()->clear();

        // On enregistre l'article
        $em = $this->getDoctrine()->getManager();
        $em->persist($article);
        $em->flush();

        foreach ($form->get('articleCompetences')->getData() as $ac) {
          $ac->setArticle($article);
          $em->persist($ac);
        }
        // Et on supprime les articleCompetences qui existaient au début mais plus maintenant
        foreach ($listeAc as $originalAc) {
          foreach ($form->get('articleCompetences')->getData() as $ac) {
            // Si $originalAc existe dans le formulaire, on sort de la boucle car pas besoin de la supprimer
            if ($originalAc == $ac) {
              continue 2;
            }
          }
          // $originalAc n'existe plus dans le formulaire, on la supprime
          $em->remove($originalAc);
        }
        $em->flush();

        // On définit un message flash
        $this->get('session')->getFlashBag()->add('info', 'Article bien modifié');

        return $this->redirect($this->generateUrl('heliosblog_voir', array('slug' => $article->getSlug())));
      }
    }

    return $this->render('HeliosBlogBundle:Blog:modifier.html.twig', array(
      'form'    => $form->createView(),
      'article' => $article
    ));
  }


    /*
     * Supprime un article, à condition d'être un admin
     */
  /**
   * @Secure(roles="ROLE_ADMIN")
   */
  public function supprimerAction(Article $article)
  {
    // On crée un formulaire vide, qui ne contiendra que le champ CSRF
    // Cela permet de protéger la suppression d'article contre cette faille
    $form = $this->createFormBuilder()->getForm();

    $request = $this->getRequest();
    if ($request->getMethod() == 'POST') {
      $form->bind($request);

      if ($form->isValid()) { // Ici, isValid ne vérifie donc que le CSRF
        // On supprime l'article
        $em = $this->getDoctrine()->getManager();
        $em->remove($article);
        $em->flush();

        // On définit un message flash
        $this->get('session')->getFlashBag()->add('info', 'Article bien supprimé');

        // Puis on redirige vers l'accueil
        return $this->redirect($this->generateUrl('heliosblog_accueil'));
      }
    }

    // Si la requête est en GET, on affiche une page de confirmation avant de supprimer
    return $this->render('HeliosBlogBundle:Blog:supprimer.html.twig', array(
      'article' => $article,
      'form'    => $form->createView()
    ));
  }


    /*
     * Ajoute un commentaire à un article
     */
  public function ajouterCommentaireAction(Article $article)
  {
    $commentaire = new Commentaire;
    $commentaire->setArticle($article);
    $commentaire->setIp($this->getRequest()->server->get('REMOTE_ADDR'));

    $form = $this->getCommentaireForm($article, $commentaire);

    $request = $this->getRequest();

    // Avec la route que l'on a, nous sommes forcément en POST ici, pas besoin de le retester
    $form->bind($request);
    if ($form->isValid()) {
      $em = $this->getDoctrine()->getManager();
      $em->persist($commentaire);
      $em->flush();

      $this->get('session')->getFlashBag()->add('info', 'Commentaire bien enregistré !');

      // On redirige vers la page de l'article, avec une ancre vers le nouveau commentaire
      return $this->redirect($this->generateUrl('heliosblog_voir', array('slug' => $article->getSlug())).'#comment'.$commentaire->getId());
    }

    $this->get('session')->getFlashBag()->add('error', 'Votre formulaire contient des erreurs');

    // On réaffiche le formulaire sans redirection (sinon on perd les informations du formulaire)
    return $this->forward('HeliosBlogBundle:Blog:voir', array(
      'article' => $article,
      'form'    => $form
    ));
  }


    /*
     * Supprime un commentaire d'un article, à condition d'être un admin
     */
  /**
   * @Secure(roles="ROLE_ADMIN")
   */
  public function supprimerCommentaireAction(Commentaire $commentaire)
  {
    // On crée un formulaire vide, qui ne contiendra que le champ CSRF
    // Cela permet de protéger la suppression d'article contre cette faille
    $form = $this->createFormBuilder()->getForm();

    $request = $this->getRequest();
    if ($request->getMethod() == 'POST') {
      $form->bind($request);

      if ($form->isValid()) { // Ici, isValid ne vérifie donc que le CSRF
        // On supprime l'article
        $em = $this->getDoctrine()->getManager();
        $em->remove($commentaire);
        $em->flush();

        // On définit un message flash
        $this->get('session')->getFlashBag()->add('info', 'Commentaire bien supprimé');

        // Puis on redirige vers l'accueil
        return $this->redirect($this->generateUrl('heliosblog_voir', array('slug' => $commentaire->getArticle()->getSlug())));
      }
    }

    // Si la requête est en GET, on affiche une page de confirmation avant de supprimer
    return $this->render('HeliosBlogBundle:Blog:supprimerCommentaire.html.twig', array(
      'commentaire' => $commentaire,
      'form'        => $form->createView()
    ));
  }


    /*
     * Menu de gauche qui affiche les derniers articles postés
     */
  public function menuAction($nombre)
  {
    $repository = $this->getDoctrine()->getManager()->getRepository('HeliosBlogBundle:Article');

    $liste = $repository->findBy(
      array(),                 // Pas de critère
      array('date' => 'desc'), // On tri par date décroissante
      $nombre,                 // On sélectionne $nombre articles
      0                        // A partir du premier
    );

    return $this->render('HeliosBlogBundle:Blog:menu.html.twig', array(
      'liste_articles' => $liste // C'est ici tout l'intérêt : le contrôleur passe les variables nécessaires au template !
    ));
  }

  public function traductionAction($name)
  {
    return $this->render('HeliosBlogBundle:Blog:traduction.html.twig', array(
      'name' => $name
    ));
  }

  public function feedAction()
  {
    $articles = $this->getDoctrine()
                     ->getManager()
                     ->getRepository('HeliosBlogBundle:Article')
                     ->getArticles(10, 1);

    $lastArticle = current($articles->getIterator());

    return $this->render('HeliosBlogBundle:Blog:feed.xml.twig', array(
      'articles'  => $articles,
      'buildDate' => $lastArticle->getDate()
    ));
  }

  // Méthodes protégées :

  /**
   * Retourne le formulaire d'ajout d'un commentaire
   * @param Article $article
   * @return Form
   */
  protected function getCommentaireForm(Article $article, Commentaire $commentaire = null)
  {
    if (null === $commentaire) {
      $commentaire = new Commentaire;
    }

    // Si l'utilisateur courant est identifié, on l'ajoute au commentaire
    if (null !== $this->getUser()) {
        $commentaire->setUser($this->getUser());
    }

    return $this->createForm(new CommentaireType(), $commentaire);
  }
}
