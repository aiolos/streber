<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Table(name="activities")
 * @ORM\Entity(repositoryClass="App\Repository\ActivityRepository")
 */
class Activity implements \Serializable
{
    /**
     * @ORM\Id
     * @ORM\Column(type="bigint", unique=true)
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @ORM\Column(type="array", nullable=true)
     * @var array|null
     */
    private $response;

    /**
     * @ORM\Column(type="array", nullable=true)
     * @var array|null
     */
    private $photos;

    /**
     *  @ORM\OneToOne(targetEntity="App\Entity\Post", inversedBy="activity")
     */
    private $post;

    /**
     *  @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="activities")
     */
    private $user;

    /**
     *  @ORM\ManyToOne(targetEntity="App\Entity\ActivityMap", inversedBy="activities")
     */
    private $activityMap;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post)
    {
        $this->post = $post;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user)
    {
        $this->user = $user;
    }

    public function setActivityMap(?ActivityMap $activityMap)
    {
        $this->activityMap = $activityMap;
    }

    public function getActivityMap(): ?ActivityMap
    {
        return $this->activityMap;
    }

    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->name,
        ));
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->name,
        ) = unserialize($serialized, ['allowed_classes' => false]);
    }

    public function hasResponse()
    {
        return $this->response !== false && $this->response !== null;
    }

    public function getResponse(): ?array
    {
        return $this->response;
    }

    public function setResponse(array $response): void
    {
        $this->response = $response;
    }

    public function hasPhotos()
    {
        return $this->photos !== false && $this->photos !== null;
    }

    public function getPhotos(): ?array
    {
        return $this->photos;
    }

    public function setPhotos(array $photos): void
    {
        $this->photos = $photos;
    }
}
