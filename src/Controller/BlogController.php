<?php

namespace App\Controller;

use App\Entity\Post;
use App\Helpers\SVGEncoder;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BlogController extends AbstractController
{
    /**
     * @Route("/blog/posts")
     */
    public function posts()
    {
        $posts = $this->getUser()->getPosts();

        return $this->render('views/posts/posts.html.twig', [
            'posts' => $posts,
        ]);
    }

    /**
     * @Route("/blog/view/{postId}")
     */
    public function view($postId)
    {
        $post = $this->getEntityManager()->getRepository(Post::class)->find($postId);

        return $this->render('views/posts/view.html.twig', [
            'post' => $post,
        ]);
    }

    /**
     * @Route("/blog/add/{activityId}")
     */
    public function add(Request $request, $activityId)
    {
        $post = new Post();
        $post->setUser($this->getUser());
        $post->setStatus(Post::STATUS_DRAFT);
        $post->setActivityId($activityId);

        $form = $this->buildForm($post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();
            $this->getEntityManager()->persist($post);
            $this->getEntityManager()->flush();

            return $this->redirect('/blog/posts');
        }

        return $this->render('views/posts/add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/blog/edit/{postId}")
     */
    public function edit(Request $request, $postId)
    {
        $post = $this->getEntityManager()->getRepository(Post::class)->find($postId);

        $form = $this->buildForm($post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();

            $this->getEntityManager()->persist($post);
            $this->getEntityManager()->flush();

            return $this->redirect('/blog/view/' . $postId);
        }

        return $this->render('views/posts/edit.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    private function buildForm(&$post)
    {
        $form = $this->createFormBuilder($post)
            ->add('title', TextType::class)
            ->add('text', TextareaType::class)
            ->add('save', SubmitType::class, array('label' => 'Opslaan'))
            ->getForm();

        return $form;
    }
}
