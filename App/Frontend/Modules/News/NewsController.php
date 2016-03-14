<?php
namespace App\Frontend\Modules\News;

use \OCFram\BackController;
use \OCFram\HTTPRequest;
use \Entity\Comment;
use \FormBuilder\CommentFormBuilder;
use \OCFram\FormHandler;
use Entity\News;
use FormBuilder\NewsFormBuilder;

class NewsController extends BackController
{
    public function executeIndex(HTTPRequest $request)
    {
        $nombreNews = $this->app->config()->get('nombre_news');

        $nombreCaracteres = $this->app->config()->get('nombre_caracteres');

        // On ajoute une définition pour le titre.
        $this->page->addVar('title', 'Liste des '.$nombreNews.' dernières news');

        // On récupère le manager des news.
        $manager = $this->managers->getManagerOf('News');

        $listeNews = $manager->getList(0, $nombreNews);


        foreach ($listeNews as $news)
        {
            if (strlen($news->contenu()) > $nombreCaracteres)
            {
                $debut = substr($news->contenu(), 0, $nombreCaracteres);

                if ((strrpos($debut, ' ')==false))
                {
                    $debut = substr($debut, 0, $nombreCaracteres - 3) . '(...)';

                }

               else { $debut = substr($debut, 0, strrpos($debut, ' ')) . '...';}

                $news->setContenu($debut);
            }
        }

        // On ajoute la variable $listeNews à la vue.
        $this->page->addVar('listeNews', $listeNews);
        foreach ($listeNews as $news)
        {
            $Newsshow[$news->id()]=$this->page->getSpecificLink('News','show', array($news->id()));


        }
        $this->page->addVar('Newsshow', $Newsshow);

    }

    public function executeShow(HTTPRequest $request)
    {
        $news = $this->managers->getManagerOf('News')->getUnique($request->getData('id'));
        $auteur= $this->managers->getManagerOf('News')->getIdOfAuthorUsingId($request->getData('id'));

        if (empty($news))
        {
            $this->app->httpResponse()->redirect404();
        }

        $this->page->addVar('title', $news->titre());
        $this->page->addVar('news', $news);
        $comments=$this->managers->getManagerOf('Comments')->getListOf($news->id());
        $this->page->addVar('comments', $this->managers->getManagerOf('Comments')->getListOf($news->id()));
        $authors=$this->managers->getManagerOf('Users')->getAuthorUsingNewsComments($news->id());
        $this->page->addVar('authors',$authors);
        $this->page->addVar('auteur',$auteur);
       if ($auteur != NULL )
        {
            $Newsshowauthoruser [$news->auteur()]=$this->page->getSpecificLink('News','showauthoruser', array($auteur->id()));

        }
        else {
            $Newsshowauthoruser [$news->auteur()]= NULL;
        }
        $this->page->addVar('Newsshowauthoruser', $Newsshowauthoruser);


        $NewsinsertComment[$news->id()]=$this->page->getSpecificLink('News','insertComment', array($news->id()));
        $this->page->addVar('NewsinsertComment', $NewsinsertComment);



if ($comments != NULL)
{
        foreach ( $comments as $com) {
            $NewsupdateComment[$com->id()]=$this->page->getSpecificLink('News','updateComment', array($com->id()));
            $NewsdeleteComment[$com->id()]=$this->page->getSpecificLink('News','deleteComment', array($com->id()));

           if ($authors != NULL )
           {
            foreach($authors as $auth)
            {
                if ($auth->login()==$com['auteur']){

                    $Newsshowuser [$com['auteur']] = $this->page->getSpecificLink('News','updateComment', array($auth->id()));
            }
                else $Newsshowuser [$com['auteur']] = NULL;

        }

           }
            $Newsshowuser [$com['auteur']] = NULL;
    }
    $this->page->addVar('Newsshowuser', $Newsshowuser);
    $this->page->addVar('NewsupdateComment', $NewsupdateComment);
        $this->page->addVar('NewsdeleteComment', $NewsdeleteComment);


    }
    }

    public function executeInsertComment(HTTPRequest $request)
    {
        // Si le formulaire a été envoyé.

        if ($request->method() == 'POST')
        {
            if ($this->app->user()->isUser() == true || $this->app->user()->isAuthenticated() == true )
            {
                $comment = new Comment([
                    'news' => $request->getData('news'),
                    'auteur' => $this->app->user()->getAttribute('log'),
                    'contenu' => $request->postData('contenu'),
                    'email' => $this->app->user()->getAttribute('mail'),

                ]);

            }
            else
            {
            $comment = new Comment([
                'news' => $request->getData('news'),
                'auteur' => $request->postData('auteur'),
                'contenu' => $request->postData('contenu'),
                'email' => $request->postData('email'),

            ]);
        }}
        else
        {
            $comment = new Comment;
        }

        $formBuilder = new CommentFormBuilder($comment);
        if ($this->app->user()->isUser() == true || $this->app->user()->isAuthenticated() == true )
        {
            $formBuilder->buildUser();
        }
        else {
            $formBuilder->build();
            }
        $form = $formBuilder->form();

        $formHandler = new FormHandler($form, $this->managers->getManagerOf('Comments'), $request);

        if ($formHandler->process())
        {
            //$this->sendmail($request->getData('news'));

            $this->app->user()->setFlash('Le commentaire a bien été ajouté, merci !');

            $this->app->httpResponse()->redirect('news-'.$request->getData('news').'.html');
        }

        $this->page->addVar('comment', $comment);
        $this->page->addVar('form', $form->createView());
        $this->page->addVar('title', 'Ajout d\'un commentaire');

    }
/*Ajout user */
    public function executeInsert(HTTPRequest $request)
    {
        $this->processForm($request);

        $this->page->addVar('title', 'Ajout d\'une news');





    }

    public function executeMynews(HTTPRequest $request)
    {
        $this->page->addVar('title', 'Mes news');
        $manager = $this->managers->getManagerOf('News');

        $listeNews = $manager->getListByAuthor($this->app()->user()->getAttribute('log'));
        $this->page->addVar('listeNews', $manager->getListByAuthor($this->app()->user()->getAttribute('log')));
        $listeCom=$this->managers->getManagerOf('Comments')->getListByAuthor($this->app()->user()->getAttribute('log'));
        $this->page->addVar('listeCom',$this->managers->getManagerOf('Comments')->getListByAuthor($this->app()->user()->getAttribute('log')));
        $listeComnews=$this->managers->getManagerOf('Comments')->getListByCommentAuthor($this->app()->user()->getAttribute('log'));
        $this->page->addVar('listeComnews',$this->managers->getManagerOf('Comments')->getListByCommentAuthor($this->app()->user()->getAttribute('log')));

        $this->page->addVar('log',$this->app()->user()->getAttribute('log'));


        if ($listeNews != NULL )
        {
        foreach ($listeNews as $news)
        {
            $Newsupdate[$news->id()]=$this->page->getSpecificLink('News','update', array($news->id()));
            $Newsdelete[$news->id()]=$this->page->getSpecificLink('News','delete', array($news->id()));

        }
        $this->page->addVar('Newsupdate', $Newsupdate);
        $this->page->addVar('Newsdelete', $Newsdelete);
    }
        if ($listeCom != NULL )
    {
        foreach ($listeCom as $com)
        {
            $NewsupdateComment[$com->id()]=$this->page->getSpecificLink('News','updateComment', array($com->id()));
            $NewsdeleteComment[$com->id()]=$this->page->getSpecificLink('News','deleteComment', array($com->id()));

        }
        $this->page->addVar('NewsupdateComment', $NewsupdateComment);
        $this->page->addVar('NewsdeleteComment', $NewsdeleteComment);
    }
        if ($listeComnews != NULL )
        {
            foreach ($listeComnews as $comnews)
            {
                $Newsshow[$comnews['nid']]=$this->page->getSpecificLink('News','show', array([$comnews['nid']]));

            }

            $this->page->addVar('Newsshow', $Newsshow);
        }

    }
    public function executeDeleteComment(HTTPRequest $request)
    {

        if ($this->managers->getManagerOf('Comments')->get($request->getData('id')) ==false )
    {
        $this->app->httpResponse()->redirect404();
    }
    else {
       if ( $this->app->user()->getAttribute('log')==$this->managers->getManagerOf('Comments')->get($request->getData('id'))->auteur())
       {
        $this->managers->getManagerOf('Comments')->delete($request->getData('id'));

        $this->app->user()->setFlash('Le commentaire a bien été supprimé !');

        $this->app->httpResponse()->redirect('.');
        }
        else
        {
            $this->app->httpResponse()->redirect404();
        }
    }
    }

    public function executeUpdateComment(HTTPRequest $request)
    {        if ($this->managers->getManagerOf('Comments')->get($request->getData('id')) ==false )
        {
            $this->app->httpResponse()->redirect404();
        }
    else {
        $this->page->addVar('title', 'Modification d\'un commentaire');

        if ($this->app->user()->getAttribute('log') == $this->managers->getManagerOf('Comments')->get($request->getData('id'))->auteur()) {
            if ($request->method() == 'POST') {
                $comment = new Comment([
                    'id' => $request->getData('id'),
                    'auteur' => $this->app->user()->getAttribute('log'),
                    'contenu' => $request->postData('contenu')
                ]);
            } else {
                $comment = $this->managers->getManagerOf('Comments')->get($request->getData('id'));
            }

            $formBuilder = new CommentFormBuilder($comment);
            $formBuilder->buildUser();

            $form = $formBuilder->form();

            $formHandler = new FormHandler($form, $this->managers->getManagerOf('Comments'), $request);

            if ($formHandler->process()) {
                $this->app->user()->setFlash('Le commentaire a bien été modifié');

                $this->app->httpResponse()->redirect('.');
            }

            $this->page->addVar('form', $form->createView());
        } else
        {
            $this->app->httpResponse()->redirect404();

        }
    }}
   public function executeUpdate(HTTPRequest $request)
   {
       if ($this->managers->getManagerOf('News')->getUnique($request->getData('id')  ) === NULL)

       {                $this->app->httpResponse()->redirect404(); }
       else
       {
           if ($this->app->user()->getAttribute('log') == $this->managers->getManagerOf('News')->getUnique($request->getData('id'))->auteur()) {


               $this->processForm($request);

               $this->page->addVar('title', 'Modification d\'une news');
           }
           else
           {
               $this->app->httpResponse()->redirect404();
           }



       }}

    public function executeDelete(HTTPRequest $request)
    {          if ($this->managers->getManagerOf('News')->getUnique($request->getData('id'))!= NULL)
    {
        if ($this->app->user()->getAttribute('log') == $this->managers->getManagerOf('News')->getUnique($request->getData('id'))->auteur()) {
        $newsId = $request->getData('id');

        $this->managers->getManagerOf('News')->delete($newsId);
        $this->managers->getManagerOf('Comments')->deleteFromNews($newsId);

        $this->app->user()->setFlash('La news a bien été supprimée !');

        $this->app->httpResponse()->redirect('.');
    }
        else {
            $this->app->httpResponse()->redirect404();

        }
    }
    else {
        $this->app->httpResponse()->redirect404();

    }

    }
    public function processForm(HTTPRequest $request)
    {
        if ($request->method() == 'POST')
        {
            $news = new News([
                'auteur' => $this->app->user()->getAttribute('log'),
                'titre' => $request->postData('titre'),
                'contenu' => $request->postData('contenu')
            ]);

            if ($request->getExists('id') )
            {
                $news->setId($request->getData('id'));
            }
        }
        else
        {
            // L'identifiant de la news est transmis si on veut la modifier
            if ($request->getExists('id'))
            {

                    $news = $this->managers->getManagerOf('News')->getUnique($request->getData('id'));


            }
            else
            {
                $news = new News;
            }
        }

        $formBuilder = new NewsFormBuilder($news);
        $formBuilder->Userbuild();

        $form = $formBuilder->form();

        $formHandler = new FormHandler($form, $this->managers->getManagerOf('News'), $request);

        if ($formHandler->process())
        {
            $this->managers->getManagerOf('News')->addnewsUser($this->app->user()->getAttribute('id'));
            $this->app->user()->setFlash($news->isNew() ? 'La news a bien été ajoutée !' : 'La news a bien été modifiée !');

            $this->app->httpResponse()->redirect('/./');
        }

        $this->page->addVar('form', $form->createView());
    }

    public function executeShowuser(HTTPRequest $request)
    {
        if ($request->method() == 'POST')
    {
        $auteur =  $request->postData('auteur');

    }
    else
    {
        $auteur=$this->managers->getManagerOf('Users')->get($request->getData('id'));


    }
        if ($auteur != NULL )
        {
        $ListCom=$this->managers->getManagerOf('Comments')->getListByAuthor($auteur->login());
        $listenews = $this->managers->getManagerOf('News')->getListByAuthor($auteur->login());

        $this->page->addVar('listnews', $listenews);
        $this->page->addVar('listcom', $ListCom);

        $this->page->addVar('auteur',$auteur);


    }}
    public function executeShowauthoruser(HTTPRequest $request)
    {
        if ($request->method() == 'POST')
        {
            $auteur =  $request->postData('auteur');

        }
        else
        {

            $auteur = $this->managers->getManagerOf('Users')->get($request->getData('id'));
        }
        $ListCom=$this->managers->getManagerOf('Comments')->getListByAuthor($auteur->login());


        $listenews = $this->managers->getManagerOf('News')->getListByAuthor($auteur->login());

        $this->page->addVar('listnews', $listenews);
        $this->page->addVar('listcom', $ListCom);
        $this->page->addVar('auteur',$auteur);
    }
    public function sendmail($id)
    {
     $listcomment= $this->managers->getManagerOf('Comments')->getListOf($id);

        if ($listcomment != NULL )
        {
            foreach ($listcomment as $com)
            {
                if ($com->email() !=NULL)
                {   $mail[] = $com->email();
                    $mail=array_unique($mail);
                }
            }

        foreach($mail as $email)
        {
        $to      = $email;
     $subject = 'Une nouvelle personne a aussi commenté la news';
     $message = 'Bonjour ! Une nouvelle personne a aussi commenté la news! Réagissez vite ';

            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
            $headers .= 'Content-type: text/plain; charset=iso-8859-1'."\r\n";
            $headers .= 'From: MOI <comment@example.com>'. "\r\n";
            $headers.= 'X-Mailer: PHP/'.phpversion();

      mail($to, $subject, $message,$headers);


   }}}

}