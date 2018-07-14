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
    public function posts(Request $request)
    {
        $repository = $this->getEntityManager()->getRepository(Post::class);
        $total = count($repository->findBy(['status' => Post::STATUS_PUBLISHED], ['date' => 'desc', 'id' => 'desc']));
        $perPage = 5;
        $maxPage = ceil($total / $perPage);
        $currentPage = max(min($maxPage, $request->get('page', 1)), 1);
        $offset = ($currentPage * $perPage) - $perPage;

        $posts = $repository->findBy(['status' => Post::STATUS_PUBLISHED], ['date' => 'desc', 'id' => 'desc'], $perPage, $offset);

        return $this->render('views/blog/index.html.twig', [
            'posts' => $posts,
            'pages' => $maxPage,
            'currentPage' => $currentPage,
        ]);
    }

    /**
     * @Route("/blog/{postId}")
     */
    public function view($postId)
    {
        /** @var Post $post */
        $post = $this->getEntityManager()->getRepository(Post::class)->findOneBy(['id' => $postId, 'status' => Post::STATUS_PUBLISHED]);
        if (is_null($post)) {
            return $this->redirect('/');
        }
        $this->setStravaToken($post->getUser()->getStravaToken());

        return $this->render('views/blog/view.html.twig', [
            'post' => $post,
            'activity' =>$this->getStravaClient()->getActivity($post->getActivity()->getId()),
            'streams' => $this->getStreams('activity', $post->getActivity()->getId(), 'altitude'),
            'photos' => $this->getStravaClient()->getActivityPhotos($post->getActivity()->getId()),
        ]);
    }
}
