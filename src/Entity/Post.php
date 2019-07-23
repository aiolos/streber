<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Table(name="posts")
 * @ORM\Entity(repositoryClass="App\Repository\PostRepository")
 */
class Post implements \Serializable
{
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_DELETED = 'deleted';

    const TYPE_RIDE = 'Ride';
    const TYPE_WALK = 'Walk';
    const TYPE_SNOWBOARD = 'Snowboard';
    const TYPE_ICESKATE = 'IceSkate';
    const TYPE_COMMENT = 'Comment';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=64, unique=true, nullable=true)
     */
    private $slug;

    /**
     * @ORM\Column(type="boolean", length=255)
     */
    private $altitude;

    /**
     * @ORM\Column(type="boolean", length=255)
     */
    private $heartrate;

    /**
     * @ORM\Column(type="boolean", length=255)
     */
    private $cadence;

    /**
     * @ORM\Column(type="boolean", length=255)
     */
    private $temperature;

    /**
     * @ORM\Column(type="boolean", length=255)
     */
    private $watts;

    /**
     * @ORM\Column(type="text")
     */
    private $text;

    /**
     * @ORM\Column(type="date")
     */
    private $date;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     *  @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="posts")
     */
    private $user;

    /**
     *  @ORM\ManyToOne(targetEntity="App\Entity\ActivityGroup", inversedBy="posts")
     */
    private $activityGroup;

    /**
     *  @ORM\OneToOne(targetEntity="App\Entity\Activity", inversedBy="post")
     */
    private $activity;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $status;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id)
    {
        $this->id = $id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug)
    {
        $this->slug = $slug;
    }

    public function getText()
    {
        return $this->text;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user)
    {
        $this->user = $user;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date)
    {
        $this->date = $date;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getAltitude()
    {
        return $this->altitude;
    }

    public function setAltitude(bool $altitude)
    {
        $this->altitude = $altitude;
    }

    public function getHeartrate()
    {
        return $this->heartrate;
    }

    public function setHeartrate(bool $heartrate)
    {
        $this->heartrate = $heartrate;
    }

    public function getCadence()
    {
        return $this->cadence;
    }

    public function setCadence(bool $cadence)
    {
        $this->cadence = $cadence;
    }

    public function getTemperature()
    {
        return $this->temperature;
    }

    public function setTemperature(bool $temperature)
    {
        $this->temperature = $temperature;
    }

    public function getWatts()
    {
        return $this->watts;
    }

    public function setWatts(bool $watts)
    {
        $this->watts = $watts;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function setActivity(?Activity $activity)
    {
        $this->activity = $activity;
    }

    public function getActivityGroup(): ?ActivityGroup
    {
        return $this->activityGroup;
    }

    public function setActivityGroup(?ActivityGroup $activityGroup)
    {
        $this->activityGroup = $activityGroup;
    }

    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->title,
            $this->activity,
            $this->text,
            $this->type,
            $this->slug,
        ));
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->title,
            $this->activity,
            $this->text,
            $this->type,
            $this->slug,
            ) = unserialize($serialized, ['allowed_classes' => false]);
    }

    public static function getStatusName($status)
    {
        $statuses = [
            self::STATUS_DRAFT => 'concept',
            self::STATUS_PUBLISHED => 'gepubliceerd',
            self::STATUS_DELETED => 'verwijderd',
        ];

        return $statuses[$status];
    }

    public function getStreamTypes(): string
    {
        $streamTypes = [];
        $this->getAltitude() ? $streamTypes[] = 'altitude' : null;
        $this->getCadence() ? $streamTypes[] = 'cadence' : null;
        $this->getHeartrate() ? $streamTypes[] = 'heartrate' : null;
        $this->getTemperature() ? $streamTypes[] = 'temp' : null;
        $this->getWatts() ? $streamTypes[] = 'watts' : null;

        return implode(',', $streamTypes);
    }
}
