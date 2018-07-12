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
        $entityManager = $this->getDoctrine()->getManager();
        $post = $entityManager->getRepository(Post::class)->find($postId);

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

        $form = $this->createFormBuilder($post)
            ->add('title', TextType::class)
            ->add('text', TextareaType::class)
            ->add('save', SubmitType::class, array('label' => 'Opslaan'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirect('/blog/posts');
        }

        return $this->render('views/posts/add.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
