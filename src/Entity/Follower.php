<?php

namespace App\Entity;

use App\Repository\FollowerRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FollowerRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Follower
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	/**
	 * user who is followed
	 *
	 * @var User|null
	 */
	#[ORM\ManyToOne(inversedBy: 'followers')]
	#[ORM\JoinColumn(nullable: false)]
	private ?User $user = null;

	/**
	 * user who follows
	 *
	 * @var User|null
	 */
	#[ORM\ManyToOne(inversedBy: 'following')]
	#[ORM\JoinColumn(nullable: false)]
	private ?User $follower = null;

	#[ORM\Column]
	private ?DateTimeImmutable $created_at = null;

	/**
	 * @return int|null
	 */
	public function getId(): ?int
	{
		return $this->id;
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
	 * @return User|null
	 */
	public function getFollower(): ?User
	{
		return $this->follower;
	}

	/**
	 * @param User|null $follower
	 * @return $this
	 */
	public function setFollower(?User $follower): self
	{
		$this->follower = $follower;

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
	 * @return void
	 */
	#[ORM\PrePersist]
	public function setCreatedAtValue(): void
	{
		$this->created_at = new DateTimeImmutable();
	}
}
