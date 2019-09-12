<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\ActivityGroup;
use App\Entity\Post;
use App\Helpers\SVGEncoder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
        $post = $this->getPost($postId);

        return $this->render('views/posts/view.html.twig', [
            'post' => $post,
            'activity' => $post->getActivity() !== null ? $this->getStravaActivity($post->getActivity()->getId()) : ''
        ]);
    }

    /**
     * @Route("/posts/add/{activityId}")
     */
    public function add(Request $request, $activityId)
    {
        /** @var Activity $activity */
        $activity = $this->getEntityManager()->getRepository(Activity::class)->find($activityId);
        $post = new Post();
        $post->setUser($this->getUser());
        $post->setStatus(Post::STATUS_DRAFT);
        $post->setActivity($activity);

        $form = $this->buildForm($post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();
            $this->getEntityManager()->persist($post);
            $this->getEntityManager()->flush();
            $activity->setPost($post);
            $this->getEntityManager()->persist($activity);
            $this->getEntityManager()->flush();

            return $this->redirect('/posts');
        }

        return $this->render('views/posts/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/posts/edit/{postId}")
     */
    public function edit(Request $request, $postId)
    {
        $post = $this->getPost($postId);

        $form = $this->buildForm($post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();

            $this->getEntityManager()->persist($post);
            $this->getEntityManager()->flush();

            return $this->redirect('/posts/view/' . $postId);
        }

        return $this->render('views/posts/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function buildForm(&$post)
    {
        return $this->createFormBuilder($post)
            ->add('title', TextType::class)
            ->add('text', TextareaType::class, ['attr' => ['rows' => 10]])
            ->add('date', DateType::class, array('widget' => 'single_text'))
            ->add('status', ChoiceType::class, array(
                'choices'  => [
                    Post::STATUS_DRAFT => Post::STATUS_DRAFT,
                    Post::STATUS_PUBLISHED => Post::STATUS_PUBLISHED,
                    Post::STATUS_DELETED => Post::STATUS_DELETED,
                ],
            ))
            ->add('type', ChoiceType::class, [
                'choices'  => [
                    Post::TYPE_RIDE => Post::TYPE_RIDE,
                    Post::TYPE_WALK => Post::TYPE_WALK,
                    Post::TYPE_SNOWBOARD => Post::TYPE_SNOWBOARD,
                    Post::TYPE_ICESKATE => Post::TYPE_ICESKATE,
                    Post::TYPE_COMMENT => Post::TYPE_COMMENT,
                ],
            ])
            ->add('activityGroup', EntityType::class, [
                'class' => ActivityGroup::class,
                'choice_label' => 'title',
                'required' => false,
            ])
            ->add('altitude', CheckboxType::class, [
                'label'    => 'Hoogte',
                'required' => false,
            ])
            ->add('heartrate', CheckboxType::class, [
                'label'    => 'Hartslag',
                'required' => false,
            ])
            ->add('cadence', CheckboxType::class, [
                'label'    => 'Cadans',
                'required' => false,
            ])
            ->add('temperature', CheckboxType::class, [
                'label'    => 'Temperatuur',
                'required' => false,
            ])
            ->add('watts', CheckboxType::class, [
                'label'    => 'Vermogen',
                'required' => false,
            ])
            ->add('numbers', CheckboxType::class, [
                'label'    => 'Toon cijfers',
                'required' => false,
            ])
            ->add('map', CheckboxType::class, [
                'label'    => 'Toon kaart',
                'required' => false,
            ])
            ->add('save', SubmitType::class, ['label' => 'Opslaan'])
            ->getForm();
    }
}
