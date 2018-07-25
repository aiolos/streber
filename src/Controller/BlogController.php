<?php

namespace App\Controller;

use App\Entity\ActivityGroup;
use App\Entity\Post;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        $groups = $this->getEntityManager()->getRepository(ActivityGroup::class)->findAll();
        $filter = ['status' => Post::STATUS_PUBLISHED];
        $group = $request->get('group', null);
        if ($group) {
            $filter['activityGroup'] = $group;
        }
        $total = count($repository->findBy($filter, ['date' => 'desc', 'id' => 'desc']));
        $perPage = 5;
        $maxPage = ceil($total / $perPage);
        $currentPage = max(min($maxPage, $request->get('page', 1)), 1);
        $offset = ($currentPage * $perPage) - $perPage;

        $posts = $repository->findBy($filter, ['date' => 'desc', 'id' => 'desc'], $perPage, $offset);

        return $this->render('views/blog/index.html.twig', [
            'posts' => $posts,
            'pages' => $maxPage,
            'currentPage' => $currentPage,
            'group' => $group,
            'groups' => $groups,
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
            'stream' => ['type' => 'activity', 'id' => $post->getActivity()->getId()],
            'activity' =>$this->getStravaClient()->getActivity($post->getActivity()->getId()),
            'photos' => $this->getStravaClient()->getActivityPhotos($post->getActivity()->getId()),
        ]);
    }

    /**
     * @Route("/data/stream/{streamType}/{streamId}/{results}")
     */
    public function activityStreams($streamType, $streamId, $results)
    {
        /** @var Post $post */
        $post = $this->getEntityManager()->getRepository(Post::class)->findOneBy(['activity' => $streamId, 'status' => Post::STATUS_PUBLISHED]);
        if (!is_null($post)) {
            $this->setStravaToken($post->getUser()->getStravaToken());
            $results = strlen($post->getStreamTypes()) ? $post->getStreamTypes() : $results;
        }
        return new JsonResponse($this->getStreams($streamType, $streamId, $results));
    }
}
