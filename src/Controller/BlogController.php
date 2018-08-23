<?php

namespace App\Controller;

use App\Entity\ActivityGroup;
use App\Entity\Post;
use App\Helpers\GPXEncoder;
use App\Repository\PostRepository;
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
        $activeGroup = null;
        if ($group) {
            if ($group === 'all') {
                $this->session->remove('group');
            } else {
                $this->session->set('group', $group);
            }
        }
        if ($this->session->has('group')) {
            $filter['activityGroup'] = $this->session->get('group');
            $activeGroup = $this->getEntityManager()->getRepository(ActivityGroup::class)->find($filter['activityGroup']);
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
            'group' => $this->session->get('group'),
            'groups' => $groups,
            'activeGroup' => $activeGroup,
        ]);
    }

    /**
     * @Route("/blog/{postId}")
     */
    public function view($postId)
    {
        /** @var Post $post */
        $post = $this->getPost($postId, Post::STATUS_PUBLISHED);
        if (is_null($post)) {
            return $this->redirect('/');
        }
        /** @var PostRepository $repository */
        $repository = $this->getEntityManager()->getRepository(Post::class);
        $next = $repository->findNextPost($post);
        $previous = $repository->findPreviousPost($post);

        return $this->render('views/blog/view.html.twig', [
            'post' => $post,
            'stream' => ['type' => 'activity', 'id' => $post->getActivity()->getId()],
            'activity' => $this->getStravaActivity($post->getActivity()->getId()),
            'photos' => $this->getStravaPhotos($post->getActivity()->getId()),
            'link' => ['next' => $next, 'previous' => $previous],
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

    /**
     * @Route("/data/gpx/{postId}")
     * @param integer $postId
     * @return Response
     * @throws \Strava\API\Exception
     */
    public function getGpx($postId)
    {
        $post = $this->getPost($postId, Post::STATUS_PUBLISHED);
        $activity = $this->getActivityByPost($post);

        $fileName = $post->getDate()->format('Y-m-d') . '-' . str_replace([' ', ','], '', ucwords($post->getTitle()));

        $response = new Response();
        $response->setContent(GPXEncoder::createGPX($activity['map']['polyline'], $post->getTitle()));
        $response->headers->set('Content-Type', 'application/gpx+xml');
        $response->headers->set('Content-Disposition', "attachment; filename=" . $fileName . ".gpx");

        return $response;
    }
}
