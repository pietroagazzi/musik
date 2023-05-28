<?php

namespace App\Entity;

use App\Repository\PostRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Post
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	/* the user that made this post */
	#[ORM\ManyToOne(inversedBy: 'posts')]
	#[ORM\JoinColumn(nullable: false)]
	private ?User $user = null;

	/* the resource that this post is about */
	#[ORM\ManyToOne(inversedBy: 'posts')]
	#[ORM\JoinColumn(nullable: false)]
	private ?Resource $resource = null;

	/* if exists, the comment of this post */
	#[ORM\Column(type: Types::TEXT, nullable: true)]
	private ?string $comment = null;

	#[ORM\Column]
	private ?DateTimeImmutable $created_at = null;

	#[ORM\Column]
	private ?DateTimeImmutable $updated_at = null;

	public function getId(): ?int
	{
		return $this->id;
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

	public function getResource(): ?Resource
	{
		return $this->resource;
	}

	public function setResource(?Resource $resource): Post
	{
		$this->resource = $resource;

		return $this;
	}

	public function getComment(): ?string
	{
		return $this->comment;
	}

	public function setComment(?string $comment): self
	{
		$this->comment = $comment;

		return $this;
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
}
