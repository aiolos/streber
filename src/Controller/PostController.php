<?php

namespace App\Controller;

use App\Entity\Post;
use App\Helpers\SVGEncoder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PostController extends AbstractController
{
    /**
     * @Route("/posts")
     */
    public function posts()
    {
        $posts = $this->getUser()->getPosts();

        return $this->render('views/posts/posts.html.twig', [
            'posts' => $posts,
        ]);
    }

    /**
     * @Route("/posts/view/{postId}")
     */
    public function view($postId)
    {
        $post = $this->getEntityManager()->getRepository(Post::class)->find($postId);

        return $this->render('views/posts/view.html.twig', [
            'post' => $post,
            'activity' =>$this->getStravaClient()->getActivity($post->getActivityId())
        ]);
    }

    /**
     * @Route("/posts/add/{activityId}")
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

            return $this->redirect('/posts');
        }

        return $this->render('views/posts/add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/posts/edit/{postId}")
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

            return $this->redirect('/posts/view/' . $postId);
        }

        return $this->render('views/posts/edit.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    private function buildForm(&$post)
    {
        $form = $this->createFormBuilder($post)
            ->add('title', TextType::class)
            ->add('text', TextareaType::class, ['attr' => ['rows' => 10]])
            ->add('date', DateType::class, array('widget' => 'single_text'))
            ->add('status', ChoiceType::class, array(
                'choices'  => array(
                    Post::STATUS_DRAFT => Post::STATUS_DRAFT,
                    Post::STATUS_PUBLISHED => Post::STATUS_PUBLISHED,
                    Post::STATUS_DELETED => Post::STATUS_DELETED,
                ),
            ))
            ->add('save', SubmitType::class, array('label' => 'Opslaan'))
            ->getForm();

        return $form;
    }
}
