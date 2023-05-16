<?php

namespace App\Entity;

use App\Repository\ConnectionRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConnectionRepository::class)]
#[ORM\UniqueConstraint(name: 'service_user_unique', fields: ['service', 'user'])]
#[ORM\UniqueConstraint(name: 'service_user_service_id_unique', fields: ['service', 'user_service_id'])]
#[ORM\HasLifecycleCallbacks]
class Connection
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column(length: 255)]
	private ?string $service = null;

	#[ORM\ManyToOne(inversedBy: 'connections')]
	#[ORM\JoinColumn(nullable: false)]
	private ?User $user = null;

	#[ORM\Column(length: 500)]
	private ?string $token = null;

	#[ORM\Column(length: 500)]
	private ?string $refresh = null;

	#[ORM\Column]
	private ?DateTimeImmutable $created_at = null;

	#[ORM\Column]
	private ?DateTimeImmutable $updated_at = null;

	/**
	 * @var string|null The identifier of the user in the service (e.g. spotify user id)
	 */
	#[ORM\Column(length: 255)]
	private ?string $user_service_id = null;

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
	public function getService(): ?string
	{
		return $this->service;
	}

	/**
	 * @param string $service
	 * @return $this
	 */
	public function setService(string $service): self
	{
		$this->service = $service;

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

	public function getUserServiceId(): ?string
	{
		return $this->user_service_id;
	}

	public function setUserServiceId(string $user_service_id): self
	{
		$this->user_service_id = $user_service_id;

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
