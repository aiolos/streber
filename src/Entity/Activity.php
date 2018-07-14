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
     * @ORM\Column(type="integer", unique=true)
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     *  @ORM\OneToOne(targetEntity="App\Entity\Post", inversedBy="activity")
     */
    private $post;

    /**
     *  @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="activities")
     */
    private $user;

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
}
