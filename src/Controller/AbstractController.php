<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Post;
use Doctrine\Common\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Strava\API\Client;
use Strava\API\Exception;
use Strava\API\OAuth;
use Strava\API\Service\REST;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Cache\CacheInterface;

abstract class AbstractController extends Controller
{
    protected $session;
    protected $client;
    protected $cache;
    protected $logger;
    private $entityManager;
    protected $stravaToken;

    public function __construct(LoggerInterface $logger, CacheInterface $cache)
    {
        $this->session = new Session();
        $this->cache = $cache;
        $this->logger = $logger;
    }

    protected function getEntityManager(): ObjectManager
    {
        if (is_null($this->entityManager)) {
            $this->entityManager = $this->getDoctrine()->getManager();
        }
        return $this->entityManager;
    }

    protected function getOAuth(): OAuth
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
            $adapter = new \GuzzleHttp\Client(['base_uri' => 'https://www.strava.com/api/v3/']);
            $service = new REST($this->getStravaToken(), $adapter);
            $this->client = new Client($service);
        }
        return $this->client;
    }

    protected function getStravaToken()
    {
        if ($this->stravaToken === null) {
            if ($this->getUser()->isTokenExpired()) {
                $user = $this->getUser();
                $token = $this->getOAuth()->getAccessToken('refresh_token', ['refresh_token' => $user->getRefreshToken()]);
                $user->setStravaToken($token->getToken());
                $user->setRefreshToken($token->getRefreshToken());
                $user->setTokenExpires($token->getExpires());
                $this->getEntityManager()->persist($user);
                $this->getEntityManager()->flush();
            }
            $this->stravaToken = $this->getUser()->getStravaToken();
        }
        return $this->stravaToken;
    }

    protected function setStravaToken($token)
    {
        $this->stravaToken = $token;
    }

    protected function getStreams($type, $id, $streamResults = null): array
    {
        /**
         * allowed types for getStreams*()-method are (comma separated):
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

        return $this->cache->get(
            'strava.streams.' . $type . '.' . $id . '.' . $streamResults,
            function () use ($type, $id, $streamResults) {
                $this->logger->info('cache miss for streams');

                if ($type === 'activity') {
                    $results = $this->getStravaClient()->getStreamsActivity($id, $streamResults, 'medium', 'distance');
                } elseif ($type === 'segment') {
                    $results = $this->getStravaClient()->getStreamsSegment($id, $streamResults, 'medium', 'distance');
                } elseif ($type === 'segmenteffort') {
                    $results = $this->getStravaClient()->getStreamsEffort($id, $streamResults, 'medium', 'distance');
                } else {
                    throw new \Exception('Invalid stream type');
                }

                $xData = [];
                $streams = [];
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

                return $data;
            }
        );
    }

    private function mapTitle($title): array
    {
        $titles = [
            'distance' => ['name' => 'Afstand', 'unit' => 'm', 'decimals' => 0, 'type' => 'line'],
            'heartrate' => ['name' => 'Hartslag', 'unit' => 'bpm', 'decimals' => 0, 'type' => 'line'],
            'altitude' => ['name' => 'Hoogte', 'unit' => 'm', 'decimals' => 0, 'type' => 'area'],
            'velocity_smooth' => ['name' => 'Snelheid', 'unit' => 'km/h', 'decimals' => 1, 'type' => 'line'],
            'cadence' => ['name' => 'Cadans', 'unit' => 'rpm', 'decimals' => 0, 'type' => 'line'],
            'temp' => ['name' => 'Temperatuur', 'unit' => '°C', 'decimals' => 0, 'type' => 'line'],
            'watts' => ['name' => 'Vermogen', 'unit' => 'W', 'decimals' => 0, 'type' => 'line'],
        ];

        return $titles[$title];
    }

    /**
     * @throws \Exception
     */
    protected function getActivityByPost(Post $post): ?array
    {
        if ($post->getActivity() === null) {
            throw new \Exception('Activity not found for post');
        }
        $activity = $post->getActivity()->getResponse();

        return $activity;
    }

    /**
     * @param int $postId
     * @param string|null $status
     * @return Post
     */
    protected function getPost(int $postId, $status = null): Post
    {
        $filter = ['id' => $postId];
        if (!is_null($status)) {
            $filter['status'] = $status;
        }
        /** @var Post|null $post */
        $post = $this->getEntityManager()->getRepository(Post::class)->findOneBy($filter);

        if (is_null($post) || is_null($post->getUser())) {
            throw new NotFoundHttpException('Post cannot be found');
        }
        $this->setStravaToken($post->getUser()->getStravaToken());

        return $post;
    }

    /**
     * @param string $slug
     * @param string|null $status
     * @return Post
     */
    protected function getPostBySlug(string $slug, $status = null): Post
    {
        $filter = ['slug' => $slug];
        if (!is_null($status)) {
            $filter['status'] = $status;
        }
        /** @var Post|null $post */
        $post = $this->getEntityManager()->getRepository(Post::class)->findOneBy($filter);

        if (is_null($post) || is_null($post->getUser())) {
            throw new NotFoundHttpException('Post cannot be found');
        }
        $this->setStravaToken($post->getUser()->getStravaToken());

        return $post;
    }

    protected function getStravaActivity(int $activityId)
    {
        return $this->cache->get('strava.activity.' . $activityId, function () use ($activityId) {
            $this->logger->info('Cache miss for activity ' . $activityId);
            return $this->getStravaClient()->getActivity($activityId);
        });
    }

    protected function getActivity(int $activityId, bool $forceReload = false): Activity
    {
        /** @var Activity $activityEntity */
        $activityEntity = $this->getEntityManager()->getRepository(Activity::class)->find($activityId);
        if ($activityEntity === null || !$activityEntity->hasResponse() || !$activityEntity->hasPhotos() || $forceReload) {
            $activity = $this->getStravaActivity($activityId);
            $photos = $this->getStravaPhotos($activityId);

            if ($activityEntity === null) {
                $activityEntity = new Activity();
                $activityEntity->setId($activityId);
                $activityEntity->setUser($this->getUser());
                $activityEntity->setName($activity['name']);
            }
            $activityEntity->setResponse($activity);
            $activityEntity->setPhotos($photos);
            $this->getEntityManager()->persist($activityEntity);
            $this->getEntityManager()->flush();
        }

        return $activityEntity;
    }

    protected function getStravaPhotos(int $activityId)
    {
        return $this->cache->get('strava.photos.' . $activityId, function () use ($activityId) {
            $this->logger->info('Cache miss for photos of activity ' . $activityId);
            return $this->getStravaClient()->getActivityPhotos($activityId);
        });
    }

    protected function clearCache(): bool
    {
        return $this->cache->clear();
    }
}
