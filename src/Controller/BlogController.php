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
        $group = $request->get('group', null);

        return $this->getPosts($request, $group);
    }

    /**
     * @Route("/reis/{slug}")
     */
    public function filter(Request $request, $slug = null)
    {
        /** @var ActivityGroup $activeGroup */
        $activeGroup = $this->getEntityManager()->getRepository(ActivityGroup::class)->findOneBy(['slug' => $slug]);
        $group = $activeGroup->getId();

        return $this->getPosts($request, $group);
    }

    private function getPosts(Request $request, $group)
    {
        $repository = $this->getEntityManager()->getRepository(Post::class);
        $groups = $this->getGroups();
        $filter = ['status' => Post::STATUS_PUBLISHED];
        if ($group) {
            if ($group === 'all') {
                $this->session->remove('group');
            } else {
                $this->session->set('group', $group);
            }
        }
        if ($this->session->has('group')) {
            $filter['activityGroup'] = $this->session->get('group');
        }

        $total = count($repository->findBy($filter, ['date' => 'desc', 'id' => 'desc']));
        $perPage = 4;
        $maxPage = ceil($total / $perPage);
        $currentPage = max(min($maxPage, $request->get('page', 1)), 1);
        $offset = (int) ($currentPage * $perPage) - $perPage;

        $posts = $repository->findBy($filter, ['date' => 'desc', 'id' => 'desc'], $perPage, $offset);

        return $this->render('views/blog/index.html.twig', [
            'posts' => $posts,
            'pages' => $maxPage,
            'currentPage' => $currentPage,
            'group' => $this->session->get('group'),
            'groups' => $groups,
            'activeGroup' => $this->getActiveGroup(),
        ]);
    }

    /**
     * @Route("/blog/{postId}")
     */
    public function view($postId)
    {
        /** @var Post $post */
        $post = $this->getPost($postId, Post::STATUS_PUBLISHED);

        return $this->viewPost($post);
    }

    /**
     * @Route("/reis/{groupSlug}/{postSlug}")
     */
    public function viewBySlug($groupSlug, $slug)
    {
        /** @var Post $post */
        $post = $this->getPostBySlug($slug, Post::STATUS_PUBLISHED);

        return $this->viewPost($post);
    }

    private function viewPost(Post $post = null)
    {
        if (is_null($post)) {
            return $this->redirect('/');
        }
        /** @var PostRepository $repository */
        $repository = $this->getEntityManager()->getRepository(Post::class);
        $next = $repository->findNextPost($post);
        $previous = $repository->findPreviousPost($post);
        if ($post->getActivity() === null) {
            throw new \Exception("No activity found");
        }

        return $this->render('views/blog/view.html.twig', [
            'post' => $post,
            'stream' => ['type' => 'activity', 'id' => $post->getActivity()->getId()],
            'activity' => $this->getActivity($post->getActivity()->getId())->getResponse(),
            'photos' => $post->getActivity()->getPhotos(),
            'link' => ['next' => $next, 'previous' => $previous],
            'group' => $this->session->get('group'),
            'groups' => $this->getGroups(),
            'activeGroup' => $this->getActiveGroup(),
        ]);
    }

    /**
     * @Route("/blog/map/{postId}")
     */
    public function map($postId)
    {
        $post = $this->getPost($postId, Post::STATUS_PUBLISHED);
        if ($post->getActivity() === null) {
            throw new \Exception("No activity found");
        }

        return $this->render('views/blog/map.html.twig', [
            'activity' => $post->getActivity()->getResponse(),
        ]);
    }

    /**
     * @Route("/data/stream/{streamType}/{streamId}/{results}")
     */
    public function activityStreams($streamType, $streamId, $results)
    {
        /** @var Post|null $post */
        $post = $this->getEntityManager()->getRepository(Post::class)->findOneBy(['activity' => $streamId, 'status' => Post::STATUS_PUBLISHED]);
        if (!is_null($post)) {
            $user = $post->getUser();
            if ($user === null) {
                throw new \Exception('User not found for post');
            }
            $this->setStravaToken($user->getStravaToken());
            $results = strlen($post->getStreamTypes()) !== 0 ? $post->getStreamTypes() : $results;
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

        $fileName = $post->getDate()->format('Y-m-d') . '-' . str_replace([' ', ','], '', ucwords((string) $post->getTitle()));

        $response = new Response();
        $response->setContent(GPXEncoder::createGPX($activity['map']['polyline'], (string) $post->getTitle()));
        $response->headers->set('Content-Type', 'application/gpx+xml');
        $response->headers->set('Content-Disposition', "attachment; filename=" . $fileName . ".gpx");

        return $response;
    }

    /**
     * @Route("/over")
     */
    public function about()
    {
        return $this->render('views/blog/about.html.twig', [
            'group' => $this->session->get('group'),
            'groups' => $this->getGroups(),
            'activeGroup' => $this->getActiveGroup(),
        ]);
    }

    private function getGroups()
    {
        return $this->getEntityManager()->getRepository(ActivityGroup::class)->findAll();
    }

    private function getActiveGroup()
    {
        $activeGroup = null;
        if ($this->session->has('group')) {
            $activeGroup = $this->getEntityManager()->getRepository(ActivityGroup::class)->find($this->session->get('group'));
        }

        return $activeGroup;
    }
}
