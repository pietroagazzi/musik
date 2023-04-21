<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\Email(message: 'The email "{{ value }}" is not a valid email.')]
    #[Assert\NotBlank(message: 'Email cannot be blank.')]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string|null The hashed password
     */
    #[ORM\Column(nullable: false)]
    private ?string $password = null;

    #[ORM\Column(length: 30, unique: true)]
    #[Assert\NotBlank(message: 'Username cannot be blank.')]
    #[Assert\Length(min: 4, max: 18, minMessage: 'Your username should be at least {{ limit }} characters', maxMessage: 'Your username should be at most {{ limit }} characters')]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_]+$/', message: 'Your username can only contain letters, numbers and underscores')]
    private ?string $username = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: EmailVerificationRequest::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $emailVerificationRequests;

    public function __construct()
    {
        $this->emailVerificationRequests = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return $this
     */
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
        return (string)$this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param array $roles
     * @return $this
     */
    public function setRoles(array $roles): self
    {
        // ignore the ROLE_USER role, it's added automatically
        // $roles = array_filter($roles, fn($role) => $role !== 'ROLE_USER');

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

    /**
     * @param string $password
     * @return $this
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return $this
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return bool
     */
    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    /**
     * @param bool $isVerified
     * @return $this
     */
    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    /**
     * @return Collection<int, EmailVerificationRequest>
     */
    public function getEmailVerificationRequests(): Collection
    {
        return $this->emailVerificationRequests;
    }

    public function addEmailVerificationRequest(EmailVerificationRequest $emailVerificationRequest): self
    {
        if (!$this->emailVerificationRequests->contains($emailVerificationRequest)) {
            $this->emailVerificationRequests->add($emailVerificationRequest);
            $emailVerificationRequest->setUser($this);
        }

        return $this;
    }

    public function removeEmailVerificationRequest(EmailVerificationRequest $emailVerificationRequest): self
    {
        if ($this->emailVerificationRequests->removeElement($emailVerificationRequest)) {
            // set the owning side to null (unless already changed)
            if ($emailVerificationRequest->getUser() === $this) {
                $emailVerificationRequest->setUser(null);
            }
        }

        return $this;
    }
}
