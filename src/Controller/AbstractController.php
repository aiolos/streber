<?php

namespace App\Controller;

use Pest;
use Strava\API\Client;
use Strava\API\Exception;
use Strava\API\OAuth;
use Strava\API\Service\REST;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;

class AbstractController extends Controller
{
    protected $session;
    protected $client;
    private $entityManager;

    public function __construct()
    {
        $this->session = new Session();
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
            $service = new REST($this->getUser()->getStravaToken(), $adapter);
            $this->client = new Client($service);
        }
        return $this->client;
    }

    protected function getStreams($type, $id)
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
        if ($type === 'activity') {
            $results = $this->getStravaClient()->getStreamsActivity($id, 'altitude,heartrate,velocity_smooth,cadence,temp', 'medium', 'distance');
        } elseif ($type === 'segment') {
            $results = $this->getStravaClient()->getStreamsSegment($id, 'altitude,heartrate,velocity_smooth,cadence,temp', 'medium', 'distance');
        } elseif ($type === 'segmenteffort') {
            $results = $this->getStravaClient()->getStreamsEffort($id, 'altitude,heartrate,velocity_smooth,cadence,temp', 'medium', 'distance');
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
            $streams[$result['type']] = [
                'data' => "[" . implode(",", $result['data']) . "]",
                'title' => $this->mapTitle($result['type']),
                'index' => $index
            ];
        }

        return $streams;
    }

    private function mapTitle($title)
    {
        $titles = [
            'distance' => 'Afstand',
            'heartrate' => 'Hartslag',
            'altitude' => 'Hoogte',
            'velocity_smooth' => 'Snelheid',
            'cadence' => 'Cadans',
            'temp' => 'Temperatuur',
        ];

        return $titles[$title];
    }
}
