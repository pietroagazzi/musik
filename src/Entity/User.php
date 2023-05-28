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

/**
 * Defines the properties of the User entity to represent the application users.
 *
 * @see https://symfony.com/doc/current/security.html#the-user
 * @see https://symfony.com/doc/current/doctrine.html#creating-an-entity-class.
 *
 * @author Pietro Agazzi <agazzi_pietro@protonmail.com>
 */
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

	/**
	 * @var string[]
	 */
	#[ORM\Column]
	private array $roles = [];

	/**
	 * @var string|null The hashed password
	 */
	#[ORM\Column(nullable: false)]
	private ?string $password = null;

	#[ORM\Column(length: 30, unique: true)]
	#[Assert\NotBlank(message: 'Username cannot be blank.')]
	#[Assert\Length(
		min: 4,
		max: 18,
		minMessage: 'Your username should be at least {{ limit }} characters',
		maxMessage: 'Your username should be at most {{ limit }} characters'
	)]
	#[Assert\Regex(
		pattern: '/^[a-zA-Z0-9_]+$/',
		message: 'Your username can only contain letters, numbers and underscores'
	)]
	private ?string $username = null;

	#[ORM\Column(type: 'boolean')]
	private bool $isVerified = false;

	#[ORM\OneToMany(
		mappedBy: 'user',
		targetEntity: EmailVerificationRequest::class,
		cascade: ['persist', 'remove'],
		orphanRemoval: true
	)]
	private Collection $emailVerificationRequests;

	#[ORM\OneToMany(mappedBy: 'user', targetEntity: Connection::class, cascade: ['persist', 'remove'])]
	private Collection $connections;

	#[ORM\Column]
	private ?DateTimeImmutable $created_at = null;

	#[ORM\Column]
	private ?DateTimeImmutable $updated_at = null;

	#[ORM\OneToMany(mappedBy: 'followed', targetEntity: Follow::class, cascade: ['persist', 'remove'])]
	private Collection $followers;

	#[ORM\OneToMany(mappedBy: 'follower', targetEntity: Follow::class, cascade: ['persist', 'remove'])]
	private Collection $following;

	#[ORM\OneToMany(mappedBy: 'user', targetEntity: Post::class, cascade: ['persist', 'remove'])]
	private Collection $posts;

	public function __construct()
	{
		// initialize properties
		$this->emailVerificationRequests = new ArrayCollection();
		$this->connections = new ArrayCollection();
		$this->followers = new ArrayCollection();
		$this->following = new ArrayCollection();
		$this->posts = new ArrayCollection();
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
	 * @inheritDoc
	 * @see UserInterface
	 */
	public function getUserIdentifier(): string
	{
		return (string)$this->email;
	}

	/**
	 * @return string[]
	 * @see UserInterface
	 */
	public function getRoles(): array
	{
		$roles = $this->roles;
		// guarantee every user at least has ROLE_USER
		$roles[] = 'ROLE_USER';

		return array_unique($roles);
	}

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

	public function setPassword(#[SensitiveParameter] string $password): self
	{
		$this->password = $password;

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function eraseCredentials(): void
	{
	}

	public function isVerified(): bool
	{
		return $this->isVerified;
	}

	public function setIsVerified(bool $isVerified): self
	{
		$this->isVerified = $isVerified;

		return $this;
	}

	/**
	 * @return Collection<EmailVerificationRequest> the email verification requests of this user
	 */
	public function getEmailVerificationRequests(): Collection
	{
		return $this->emailVerificationRequests;
	}

	/**
	 * @return Collection<int, Connection>
	 */
	public function getConnections(): Collection
	{
		return $this->connections;
	}

	/**
	 * adds a connection to this user
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
	 * returns true if the user has a connection to the given provider
	 *
	 * @param string $providerName the name of the provider (e.g. spotify)
	 */
	public function hasConnection(string $providerName): bool
	{
		return $this->getConnection($providerName) !== false;
	}

	/**
	 * returns the connection to the given provider or false if the user has no connection to the given provider
	 *
	 * @param string $providerName the name of the provider (e.g. spotify)
	 */
	public function getConnection(string $providerName): Connection|false
	{
		return $this->connections->filter(
			fn(Connection $connection) => $connection->getProvider() === $providerName
		)->first();
	}

	public function getCreatedAt(): ?DateTimeImmutable
	{
		return $this->created_at;
	}

	public function setCreatedAt(DateTimeImmutable $created_at): self
	{
		$this->created_at = $created_at;

		return $this;
	}

	public function getUpdatedAt(): ?DateTimeImmutable
	{
		return $this->updated_at;
	}

	public function setUpdatedAt(DateTimeImmutable $updated_at): self
	{
		$this->updated_at = $updated_at;

		return $this;
	}

	/**
	 * get users that follow this user
	 *
	 * @return Collection<int, User> the users that follow this user
	 */
	public function getFollowers(): Collection
	{
		return $this->followers->map(fn(Follow $follow) => $follow->getFollower());
	}

	/**
	 * get users that this user is following
	 *
	 * @return Collection<int, User> the users that this user is following
	 */
	public function getFollowing(): Collection
	{
		return $this->following->map(fn(Follow $follow) => $follow->getFollowed());
	}

	/**
	 * returns true if the user follows the given user
	 *
	 * @param User $user the user to check if this user follows
	 */
	public function following(User $user): bool
	{
		return $user->followedBy($this);
	}

	/**
	 * returns true if the given user follows this user
	 */
	public function followedBy(User $user): bool
	{
		return $this->followers->filter(
				fn(Follow $follower) => $follower->getFollower() === $user
			)->count() > 0;
	}

	/**
	 * returns a collection of posts that this user has posted
	 *
	 * @return Collection<int, Post> the posts that this user has posted
	 */
	public function getPosts(): Collection
	{
		return $this->posts;
	}

	/**
	 * adds a new post created by this user
	 */
	public function addPost(Post $post): self
	{
		if (!$this->posts->contains($post)) {
			$this->posts->add($post);
			$post->setUser($this);
		}

		return $this;
	}

	/**
	 * executes before persisting the entity
	 */
	#[ORM\PrePersist]
	public function prePersist(): void
	{
		$this->created_at = new DateTimeImmutable();
		$this->updated_at = new DateTimeImmutable();
	}

	/**
	 * executes before updating the entity
	 */
	#[ORM\PreUpdate]
	public function preUpdate(): void
	{
		$this->updated_at = new DateTimeImmutable();
	}

	/**
	 * returns the username of the user
	 */
	public function __toString(): string
	{
		return $this->getUsername();
	}

	public function getUsername(): ?string
	{
		return $this->username;
	}

	public function setUsername(string $username): self
	{
		$this->username = $username;

		return $this;
	}

	/**
	 * @return array{int, string, string, bool, DateTimeImmutable|null, DateTimeImmutable|null}
	 */
	public function __serialize(): array
	{
		return [
			$this->id,
			$this->username,
			$this->password,
			$this->isVerified,
			$this->created_at,
			$this->updated_at,
		];
	}

	/**
	 * @param array{int, string, string, bool, DateTimeImmutable|null, DateTimeImmutable|null} $data
	 */
	public function __unserialize(array $data): void
	{
		[
			$this->id,
			$this->username,
			$this->password,
			$this->isVerified,
			$this->created_at,
			$this->updated_at,
		] = $data;
	}
}
