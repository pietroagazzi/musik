<?php

namespace App\Entity;

use App\Repository\ConnectionRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConnectionRepository::class)]
#[ORM\UniqueConstraint(name: 'provider_user_unique', fields: ['provider', 'user'])]
#[ORM\UniqueConstraint(name: 'provider_provider_user_unique', fields: ['provider', 'provider_user_id'])]
#[ORM\HasLifecycleCallbacks]
class Connection
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column(length: 255)]
	private ?string $provider = null;

	#[ORM\ManyToOne(inversedBy: 'connections')]
	#[ORM\JoinColumn(nullable: false)]
	private ?User $user = null;

	#[ORM\Column(length: 500)]
	private ?string $token = null;

	#[ORM\Column(length: 500)]
	private ?string $refresh = null;

	/**
	 * @var string|null The identifier of the user in the provider (e.g. spotify user id)
	 */
	#[ORM\Column(length: 255)]
	private ?string $provider_user_id = null;

	#[ORM\Column]
	private ?DateTimeImmutable $created_at = null;

	#[ORM\Column]
	private ?DateTimeImmutable $updated_at = null;

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
	public function getProvider(): ?string
	{
		return $this->provider;
	}

	/**
	 * @param string $provider
	 * @return $this
	 */
	public function setProvider(string $provider): self
	{
		$this->provider = $provider;

		return $this;
	}

	/**
	 * @return User|null
	 */
	public function getUser(): ?User
	{
		return $this->user;
	}

	/**
	 * @param User|null $user
	 * @return $this
	 */
	public function setUser(?User $user): self
	{
		$this->user = $user;

		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getToken(): ?string
	{
		return $this->token;
	}

	/**
	 * @param string $token
	 * @return $this
	 */
	public function setToken(string $token): self
	{
		$this->token = $token;

		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getRefresh(): ?string
	{
		return $this->refresh;
	}

	/**
	 * @param string $refresh
	 * @return $this
	 */
	public function setRefresh(string $refresh): self
	{
		$this->refresh = $refresh;

		return $this;
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
	 * get provider user id (e.g. spotify user id)
	 *
	 * @return string|null
	 */
	public function getProviderUserId(): ?string
	{
		return $this->provider_user_id;
	}

	/**
	 * @param string $provider_user_id
	 * @return $this
	 */
	public function setProviderUserId(string $provider_user_id): self
	{
		$this->provider_user_id = $provider_user_id;

		return $this;
	}

	/**
	 * execute before insert
	 */
	#[ORM\PrePersist]
	public function prePersist(): void
	{
		$this->created_at = new DateTimeImmutable();
		$this->updated_at = new DateTimeImmutable();
	}

	/**
	 * execute before update
	 */
	#[ORM\PreUpdate]
	public function preUpdate(): void
	{
		$this->updated_at = new DateTimeImmutable();
	}
}
