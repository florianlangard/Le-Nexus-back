<?php

namespace App\Entity;

use App\Repository\RequestRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;


/**
 * @ORM\Entity(repositoryClass=RequestRepository::class)
 */
class Request
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups("request_info")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     * @Groups("request_info")
     */
    private $friend;

    /**
     * @ORM\Column(type="boolean")
     * @Groups("request_info")
     */
    private $game;

    /**
     * @ORM\Column(type="datetime")
     * @Groups("request_info")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups("request_info")
     */
    private $acceptedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups("request_info")
     */
    private $declinedAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="sentRequests")
     * @ORM\JoinColumn(nullable=false)
     * @Groups("request_info")
     */
    private $sender;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="receivedRequests")
     * @ORM\JoinColumn(nullable=false)
     * @Groups("request_info")
     */
    private $target;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFriend(): ?bool
    {
        return $this->friend;
    }

    public function setFriend(bool $friend): self
    {
        $this->friend = $friend;

        return $this;
    }

    public function getGame(): ?bool
    {
        return $this->game;
    }

    public function setGame(bool $game): self
    {
        $this->game = $game;

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

    public function getAcceptedAt(): ?\DateTimeInterface
    {
        return $this->acceptedAt;
    }

    public function setAcceptedAt(?\DateTimeInterface $acceptedAt): self
    {
        $this->acceptedAt = $acceptedAt;

        return $this;
    }

    public function getDeclinedAt(): ?\DateTimeInterface
    {
        return $this->declinedAt;
    }

    public function setDeclinedAt(?\DateTimeInterface $declinedAt): self
    {
        $this->declinedAt = $declinedAt;

        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getTarget(): ?User
    {
        return $this->target;
    }

    public function setTarget(?User $target): self
    {
        $this->target = $target;

        return $this;
    }
}
