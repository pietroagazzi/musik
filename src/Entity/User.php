<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use SensitiveParameter;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')]
#[ORM\HasLifecycleCallbacks]
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

	#[ORM\OneToMany(mappedBy: 'user', targetEntity: Connection::class)]
	private Collection $connections;

	#[ORM\Column]
	private ?DateTimeImmutable $created_at = null;

	#[ORM\Column]
	private ?DateTimeImmutable $updated_at = null;

	#[ORM\OneToMany(mappedBy: 'user', targetEntity: Follower::class, orphanRemoval: true)]
	private Collection $followers;

	#[ORM\OneToMany(mappedBy: 'follower', targetEntity: Follower::class, orphanRemoval: true)]
	private Collection $following;

	public function __construct()
	{
		$this->emailVerificationRequests = new ArrayCollection();
		$this->connections = new ArrayCollection();
		$this->followers = new ArrayCollection();
		$this->following = new ArrayCollection();
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
	public function setPassword(#[SensitiveParameter] string $password): self
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

	/**
	 * @param EmailVerificationRequest $emailVerificationRequest
	 * @return $this
	 */
	public function addEmailVerificationRequest(EmailVerificationRequest $emailVerificationRequest): self
	{
		if (!$this->emailVerificationRequests->contains($emailVerificationRequest)) {
			$this->emailVerificationRequests->add($emailVerificationRequest);
			$emailVerificationRequest->setUser($this);
		}

		return $this;
	}

	/**
	 * @param EmailVerificationRequest $emailVerificationRequest
	 * @return $this
	 */
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

	/**
	 * @return Collection<int, Connection>
	 */
	public function getConnections(): Collection
	{
		return $this->connections;
	}

	/**
	 * @param Connection $connection
	 * @return $this
	 */
	public function addConnection(Connection $connection): self
	{
		if (!$this->connections->contains($connection)) {
			$this->connections->add($connection);
			$connection->setUser($this);
		}

		return $this;
	}

	/**
	 * @param Connection $connection
	 * @return $this
	 */
	public function removeConnection(Connection $connection): self
	{
		if ($this->connections->removeElement($connection)) {
			// set the owning side to null (unless already changed)
			if ($connection->getUser() === $this) {
				$connection->setUser(null);
			}
		}

		return $this;
	}

	/**
	 * returns true if the user has a connection to the given service
	 *
	 * @param string $serviceName
	 * @return bool
	 */
	public function hasServiceConnection(string $serviceName): bool
	{
		return $this->connections->exists(
			fn(int $key, Connection $connection) => $connection->getService() === $serviceName
		);
	}

	public function getServiceConnection(string $serviceName): ?Connection
	{
		return $this->connections->filter(
			fn(Connection $connection) => $connection->getService() === $serviceName
		)->first();
	}

	/**
	 * @return DateTimeImmutable|null
	 */
	public function getCreatedAt(): ?DateTimeImmutable
	{
		return $this->created_at;
	}

	/**
	 * @param DateTimeImmutable $created_at
	 * @return $this
	 */
	public function setCreatedAt(DateTimeImmutable $created_at): self
	{
		$this->created_at = $created_at;

		return $this;
	}

	/**
	 * @return DateTimeImmutable|null
	 */
	public function getUpdatedAt(): ?DateTimeImmutable
	{
		return $this->updated_at;
	}

	/**
	 * @param DateTimeImmutable $updated_at
	 * @return $this
	 */
	public function setUpdatedAt(DateTimeImmutable $updated_at): self
	{
		$this->updated_at = $updated_at;

		return $this;
	}

	/**
	 * executes before persisting the entity
	 *
	 * @return void
	 */
	#[ORM\PrePersist]
	public function prePersist(): void
	{
		$this->created_at = new DateTimeImmutable();
		$this->updated_at = new DateTimeImmutable();
	}

	/**
	 * executes before updating the entity
	 *
	 * @return void
	 */
	#[ORM\PreUpdate]
	public function preUpdate(): void
	{
		$this->updated_at = new DateTimeImmutable();
	}

	/**
	 * get users that follow this user
	 *
	 * @return Collection<int, Follower>
	 */
	public function getFollowers(): Collection
	{
		return $this->followers;
	}

	/**
	 * @param Follower $follower
	 * @return $this
	 */
	public function addFollower(Follower $follower): self
	{
		if (!$this->followers->contains($follower)) {
			$this->followers->add($follower);
			$follower->setUser($this);
		}

		return $this;
	}

	/**
	 * @param Follower $follower
	 * @return $this
	 */
	public function removeFollower(Follower $follower): self
	{
		if ($this->followers->removeElement($follower)) {
			// set the owning side to null (unless already changed)
			if ($follower->getUser() === $this) {
				$follower->setUser(null);
			}
		}

		return $this;
	}

	/**
	 * get users that this user is following
	 *
	 * @return Collection<int, Follower>
	 */
	public function getFollowing(): Collection
	{
		return $this->following;
	}

	/**
	 * @param Follower $following
	 * @return $this
	 */
	public function addFollowing(Follower $following): self
	{
		if (!$this->following->contains($following)) {
			$this->following->add($following);
			$following->setFollower($this);
		}

		return $this;
	}

	/**
	 * @param Follower $following
	 * @return $this
	 */
	public function removeFollowing(Follower $following): self
	{
		if ($this->following->removeElement($following)) {
			// set the owning side to null (unless already changed)
			if ($following->getFollower() === $this) {
				$following->setFollower(null);
			}
		}

		return $this;
	}
}
