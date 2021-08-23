<?php

namespace App\Entity;

use App\Repository\LibraryRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=LibraryRepository::class)
 */
class Library
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     * @Groups("user_info")
     */
    private $installed;

    /**
     * @ORM\Column(type="boolean")
     * @Groups("user_info")
     */
    private $hidden;

    /**
     * @ORM\Column(type="datetime")
     * @Groups("user_info")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity=Game::class, inversedBy="libraries")
     * @ORM\JoinColumn(nullable=false)
     * @Groups("user_info")
     */
    private $game;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="libraries")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->installed = true;
        $this->hidden = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInstalled(): ?bool
    {
        return $this->installed;
    }

    public function setInstalled(bool $installed): self
    {
        $this->installed = $installed;

        return $this;
    }

    public function getHidden(): ?bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): self
    {
        $this->hidden = $hidden;

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): self
    {
        $this->game = $game;

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
}
