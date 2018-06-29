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

    public function __construct()
    {
        $this->session = new Session();
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
}
