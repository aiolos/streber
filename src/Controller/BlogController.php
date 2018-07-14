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

class BlogController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function posts()
    {
        $posts = $this->getEntityManager()->getRepository(Post::class)->findBy(['status' => Post::STATUS_PUBLISHED], ['date' => 'desc', 'id' => 'desc']);

        return $this->render('views/blog/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    /**
     * @Route("/blog/{postId}")
     */
    public function view($postId)
    {
        /** @var Post $post */
        $post = $this->getEntityManager()->getRepository(Post::class)->find($postId);
        $this->setStravaToken($post->getUser()->getStravaToken());

        return $this->render('views/blog/view.html.twig', [
            'post' => $post,
            'activity' =>$this->getStravaClient()->getActivity($post->getActivity()->getId()),
            'streams' => $this->getStreams('activity', $post->getActivity()->getId(), 'altitude'),
            'photos' => $this->getStravaClient()->getActivityPhotos($post->getActivity()->getId()),
        ]);
    }
}
