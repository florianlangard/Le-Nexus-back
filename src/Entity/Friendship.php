<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\FriendshipRepository;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=FriendshipRepository::class)
 */
class Friendship
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups("user_info")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups("user_info")
     */
    private $lastPlayed;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups("user_info")
     */
    private $timesPlayed;

    /**
     * @ORM\Column(type="datetime")
     * @Groups("user_info")
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="friends")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $friend;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastPlayed(): ?\DateTimeInterface
    {
        return $this->lastPlayed;
    }

    public function setLastPlayed(?\DateTimeInterface $lastPlayed): self
    {
        $this->lastPlayed = $lastPlayed;

        return $this;
    }

    public function getTimesPlayed(): ?int
    {
        return $this->timesPlayed;
    }

    public function setTimesPlayed(?int $timesPlayed): self
    {
        $this->timesPlayed = $timesPlayed;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getFriend(): ?User
    {
        return $this->friend;
    }

    public function setFriend(?User $friend): self
    {
        $this->friend = $friend;

        return $this;
    }
}
