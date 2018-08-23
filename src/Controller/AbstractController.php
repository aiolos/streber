<?php

namespace App\Controller;

use App\Entity\Post;
use Pest;
use Psr\Log\LoggerInterface;
use Strava\API\Client;
use Strava\API\Exception;
use Strava\API\OAuth;
use Strava\API\Service\REST;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractController extends Controller
{
    protected $session;
    protected $client;
    protected $cache;
    protected $logger;
    private $entityManager;
    protected $stravaToken;

    public function __construct(LoggerInterface $logger)
    {
        $this->session = new Session();
        $this->cache = new FilesystemCache();
        $this->logger = $logger;
    }

    protected function getEntityManager()
    {
        if (is_null($this->entityManager)) {
            $this->entityManager = $this->getDoctrine()->getManager();
        }
        return $this->entityManager;
    }

    protected function getOAuth()
    {
        $options = [
            'clientId'     => getenv('CLIENT_ID'),
            'clientSecret' => getenv('CLIENT_SECRET'),
            'redirectUri'  => getenv('REDIRECT_URI'),
        ];
        return new OAuth($options);
    }

    protected function getStravaClient()
    {
        if (is_null($this->client)) {
            $adapter = new Pest('https://www.strava.com/api/v3');
            $service = new REST($this->getStravaToken(), $adapter);
            $this->client = new Client($service);
        }
        return $this->client;
    }

    protected function getStravaToken()
    {
        if ($this->stravaToken === null) {
            $this->stravaToken = $this->getUser()->getStravaToken();
        }
        return $this->stravaToken;
    }

    protected function setStravaToken($token)
    {
        $this->stravaToken = $token;
    }

    protected function getStreams($type, $id, $streamResults = null)
    {
        /**
         * allowed types for getStreams*()-method are (comma seperated):
         * - time:  integer seconds
         * - latlng:  floats [latitude, longitude]
         * - distance:  float meters
         * - altitude:  float meters
         * - velocity_smooth:  float meters per second
         * - heartrate:  integer BPM
         * - cadence:  integer RPM
         * - watts:  integer watts
         * - temp:  integer degrees Celsius
         * - moving:  boolean
         * - grade_smooth:  float percent
         */
        if ($streamResults === null) {
            $streamResults = 'altitude,heartrate,velocity_smooth,cadence,temp';
        }

        if ($this->cache->has('strava.streams.' . $type . '.' . $id . '.' . $streamResults)) {
            $this->logger->info('cache hit for streams');
            return $this->cache->get('strava.streams.' . $type . '.' . $id . '.' . $streamResults);
        }

        if ($type === 'activity') {
            $results = $this->getStravaClient()->getStreamsActivity($id, $streamResults, 'medium', 'distance');
        } elseif ($type === 'segment') {
            $results = $this->getStravaClient()->getStreamsSegment($id, $streamResults, 'medium', 'distance');
        } elseif ($type === 'segmenteffort') {
            $results = $this->getStravaClient()->getStreamsEffort($id, $streamResults, 'medium', 'distance');
        } else {
            throw new \Exception('Invalid stream type');
        }

        foreach ($results as $index => $result) {
            /** Calculate speed to km/h */
            if ($result['type'] == 'velocity_smooth') {
                $result['data'] = array_map(function ($element) {
                    return $element * 3.6;
                },
                    $result['data']);
            }

            if ($result['type'] == 'distance') {
                $xData = array_map(
                    function ($value) {
                        return $value / 1000;
                    },
                    $result['data']
                );
                continue;
            }

            $units = $this->mapTitle($result['type']);

            $streams[] = [
                'data' => $result['data'],
                'name' => $units['name'],
                'index' => $index,
                'unit' => $units['unit'],
                'valueDecimals' => $units['decimals'],
                'type' => $units['type'],
            ];
        }

        $data = [
            'xData' => $xData,
            'datasets' => $streams,
        ];

        $this->cache->set('strava.streams.' . $type . '.' . $id . '.' . $streamResults, $data);

        return $data;
    }

    private function mapTitle($title)
    {
        $titles = [
            'distance' => ['name' => 'Afstand', 'unit' => 'm', 'decimals' => 0, 'type' => 'line'],
            'heartrate' => ['name' => 'Hartslag', 'unit' => 'bpm', 'decimals' => 0, 'type' => 'line'],
            'altitude' => ['name' => 'Hoogte', 'unit' => 'm', 'decimals' => 0, 'type' => 'area'],
            'velocity_smooth' => ['name' => 'Snelheid', 'unit' => 'km/h', 'decimals' => 1, 'type' => 'line'],
            'cadence' => ['name' => 'Cadans', 'unit' => 'rpm', 'decimals' => 0, 'type' => 'line'],
            'temp' => ['name' => 'Temperatuur', 'unit' => '°C', 'decimals' => 0, 'type' => 'line'],
        ];

        return $titles[$title];
    }

    /**
     * @param Post $post
     * @return array
     * @throws \Strava\API\Exception
     */
    protected function getActivityByPost(Post $post): array
    {
        return $this->getStravaActivity($post->getActivity()->getId());
    }

    /**
     * @param int $postId
     * @param null $status
     * @return Post
     */
    protected function getPost(int $postId, $status = null): Post
    {
        $filter = ['id' => $postId];
        if (!is_null($status)) {
            $filter['status'] = $status;
        }
        /** @var Post $post */
        $post = $this->getEntityManager()->getRepository(Post::class)->findOneBy($filter);

        if (!is_null($post)) {
            $this->setStravaToken($post->getUser()->getStravaToken());
        } else {
            throw new NotFoundHttpException('Post cannot be found');
        }

        return $post;
    }

    protected function getStravaActivity(int $activityId)
    {
        if (!$this->cache->has('strava.activity.' . $activityId)) {
            $this->logger->info('Cache miss for activity ' . $activityId);
            $activity = $this->getStravaClient()->getActivity($activityId);
            $this->cache->set('strava.activity.' . $activityId, $activity);
        }

        return $this->cache->get('strava.activity.' . $activityId);
    }

    protected function getStravaPhotos(int $activityId)
    {
        if (!$this->cache->has('strava.photos.' . $activityId)) {
            $this->logger->info('Cache miss for photos of activity ' . $activityId);
            $activityPhotos = $this->getStravaClient()->getActivityPhotos($activityId);
            $this->cache->set('strava.photos.' . $activityId, $activityPhotos);
        }

        return $this->cache->get('strava.photos.' . $activityId);
    }
}
