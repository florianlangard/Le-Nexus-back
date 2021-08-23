<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity("email")
 * @UniqueEntity("pseudo")
 * @UniqueEntity("steamId")
 * @UniqueEntity("steamUsername")
 * @UniqueEntity("steamAvatar")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups("user_info")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Groups("user_info")
     * @Assert\Email
     * @Assert\NotBlank
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     * @Groups("user_info")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=32, unique=true)
     * @Groups("user_info")
     */
    private $pseudo;

    /**
     * @ORM\Column(type="string", length=17, unique=true)
     * @Groups("user_info")
     * @Assert\Length( min=17, max=17)
     * @Assert\Regex("/^\d+/")
     * @Assert\NotBlank
     */
    private $steamId;

    /**
     * @ORM\Column(type="string", length=64, unique=true)
     * @Groups("user_info")
     */
    private $steamUsername;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("user_info")
     */
    private $steamAvatar;

    /**
     * @ORM\Column(type="boolean")
     * @Groups("user_info")
     */
    private $visibilityState;

    /**
     * @ORM\Column(type="boolean")
     * @Groups("user_info")
     */
    private $isLogged;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity=Mood::class, inversedBy="users")
     * @Groups("user_info")
     */
    private $mood;

    /**
     * @ORM\OneToMany(targetEntity=Library::class, mappedBy="user", orphanRemoval=true)
     * @Groups("user_info")
     */
    private $libraries;

    /**
     * @ORM\OneToMany(targetEntity=Request::class, mappedBy="sender", orphanRemoval=true)
     */
    private $sentRequests;

    /**
     * @ORM\OneToMany(targetEntity=Request::class, mappedBy="target", orphanRemoval=true)
     */
    private $receivedRequests;

    /**
     * @ORM\OneToMany(targetEntity=Friendship::class, mappedBy="user", orphanRemoval=true)
     */
    private $friends;

    public function __construct()
    {
        $this->libraries = new ArrayCollection();
        $this->sentRequests = new ArrayCollection();
        $this->receivedRequests = new ArrayCollection();
        $this->friends = new ArrayCollection();
        $this->roles = ["ROLE_USER"];
        $this->createdAt = new DateTime();
    }

    public function __toString()
    {
        return strval($this->steamId);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        // $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getSteamId(): ?int
    {
        return $this->steamId;
    }

    public function setSteamId(int $steamId): self
    {
        $this->steamId = $steamId;

        return $this;
    }

    public function getSteamUsername(): ?string
    {
        return $this->steamUsername;
    }

    public function setSteamUsername(string $steamUsername): self
    {
        $this->steamUsername = $steamUsername;

        return $this;
    }

    public function getSteamAvatar(): ?string
    {
        return $this->steamAvatar;
    }

    public function setSteamAvatar(?string $steamAvatar): self
    {
        $this->steamAvatar = $steamAvatar;

        return $this;
    }

    public function getVisibilityState(): ?bool
    {
        return $this->visibilityState;
    }

    public function setVisibilityState(bool $visibilityState): self
    {
        $this->visibilityState = $visibilityState;

        return $this;
    }

    public function getIsLogged(): ?bool
    {
        return $this->isLogged;
    }

    public function setIsLogged(bool $isLogged): self
    {
        $this->isLogged = $isLogged;

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

    /**
     * Get the value of pseudo
     */ 
    public function getPseudo() :string
    {
        return $this->pseudo;
    }

    /**
     * Set the value of pseudo
     *
     * @return  self
     */ 
    public function setPseudo(string $pseudo) :self
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getMood(): ?Mood
    {
        return $this->mood;
    }

    public function setMood(?Mood $mood): self
    {
        $this->mood = $mood;

        return $this;
    }

    /**
     * @return Collection|Library[]
     */
    public function getLibraries(): Collection
    {
        return $this->libraries;
    }

    public function addLibrary(Library $library): self
    {
        if (!$this->libraries->contains($library)) {
            $this->libraries[] = $library;
            $library->setUser($this);
        }

        return $this;
    }

    public function removeLibrary(Library $library): self
    {
        if ($this->libraries->removeElement($library)) {
            // set the owning side to null (unless already changed)
            if ($library->getUser() === $this) {
                $library->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Request[]
     */
    public function getSentRequests(): Collection
    {
        return $this->sentRequests;
    }

    public function addSentRequest(Request $sentRequest): self
    {
        if (!$this->sentRequests->contains($sentRequest)) {
            $this->sentRequests[] = $sentRequest;
            $sentRequest->setSender($this);
        }

        return $this;
    }

    public function removeSentRequest(Request $sentRequest): self
    {
        if ($this->sentRequests->removeElement($sentRequest)) {
            // set the owning side to null (unless already changed)
            if ($sentRequest->getSender() === $this) {
                $sentRequest->setSender(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Request[]
     */
    public function getReceivedRequests(): Collection
    {
        return $this->receivedRequests;
    }

    public function addReceivedRequest(Request $receivedRequest): self
    {
        if (!$this->receivedRequests->contains($receivedRequest)) {
            $this->receivedRequests[] = $receivedRequest;
            $receivedRequest->setTarget($this);
        }

        return $this;
    }

    public function removeReceivedRequest(Request $receivedRequest): self
    {
        if ($this->receivedRequests->removeElement($receivedRequest)) {
            // set the owning side to null (unless already changed)
            if ($receivedRequest->getTarget() === $this) {
                $receivedRequest->setTarget(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Friendship[]
     */
    public function getFriends(): Collection
    {
        return $this->friends;
    }

    public function addFriend(Friendship $friend): self
    {
        if (!$this->friends->contains($friend)) {
            $this->friends[] = $friend;
            $friend->setUser($this);
        }

        return $this;
    }

    public function removeFriend(Friendship $friend): self
    {
        if ($this->friends->removeElement($friend)) {
            // set the owning side to null (unless already changed)
            if ($friend->getUser() === $this) {
                $friend->setUser(null);
            }
        }

        return $this;
    }
}
