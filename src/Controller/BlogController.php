<?php

namespace App\Controller;

use App\Entity\ActivityGroup;
use App\Entity\Post;
use App\Helpers\PolylineEncoder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use phpGPX\Models\GpxFile;
use phpGPX\Models\Link;
use phpGPX\Models\Metadata;
use phpGPX\Models\Point;
use phpGPX\Models\Segment;
use phpGPX\Models\Track;

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
        $post = $this->getPost($postId);
        if (is_null($post)) {
            return $this->redirect('/');
        }

        return $this->render('views/blog/view.html.twig', [
            'post' => $post,
            'stream' => ['type' => 'activity', 'id' => $post->getActivity()->getId()],
            'activity' => $this->getStravaClient()->getActivity($post->getActivity()->getId()),
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

    /**
     * @Route("/data/gpx/{postId}")
     * @param integer $postId
     * @return Response
     * @throws \Strava\API\Exception
     */
    public function getGpx($postId)
    {
        $post = $this->getPost($postId);
        $activity = $this->getActivityByPost($post);

        $link = new Link();
        $link->href = "https://spiritus-santos.nl";
        $link->text = 'Blijven Trappen';
        $gpx_file = new GpxFile();
        $gpx_file->metadata = new Metadata();
        $gpx_file->metadata->time = new \DateTime();
        $gpx_file->metadata->description = "This file is generated from a blog on https://spiritus-santos.nl";
        $gpx_file->metadata->links[] = $link;
        $track = new Track();
        $track->name = sprintf($post->getTitle());
        $track->type = 'RIDE';
        $track->source = sprintf("Garmin Edge 1000");
        $segment = new Segment();

        $coordinates = PolylineEncoder::decodeValue($activity['map']['polyline']);
        array_map(function ($element) use ($segment) {
            $point = new Point(Point::TRACKPOINT);
            $point->latitude = $element['x'];
            $point->longitude = $element['y'];

            $segment->points[] = $point;
        }, $coordinates);
        $track->segments[] = $segment;
        $track->recalculateStats();
        $gpx_file->tracks[] = $track;

        $fileName = $post->getDate()->format('Y-m-d') . '-' . str_replace([' ', ','], '', ucwords($post->getTitle()));

        $response = new Response();
        $response->setContent($gpx_file->toXML()->saveXML());
        $response->headers->set('Content-Type', 'application/gpx+xml');
        $response->headers->set('Content-Disposition', "attachment; filename=" . $fileName . ".gpx");

        return $response;
    }

    /**
     * @param Post $post
     * @return array
     * @throws \Strava\API\Exception
     */
    private function getActivityByPost(Post $post): array
    {
        return $this->getStravaClient()->getActivity($post->getActivity()->getId());
    }

    /**
     * @param $postId
     * @return Post
     */
    private function getPost(int $postId): Post
    {
        /** @var Post $post */
        $post = $this->getEntityManager()->getRepository(Post::class)->findOneBy(['id' => $postId, 'status' => Post::STATUS_PUBLISHED]);

        if (!is_null($post)) {
            $this->setStravaToken($post->getUser()->getStravaToken());
        }

        return $post;
    }
}
