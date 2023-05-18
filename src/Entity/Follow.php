<?php

namespace App\Entity;

use App\Repository\FollowRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FollowRepository::class)]
#[ORM\UniqueConstraint(fields: ['followed', 'follower'])]
#[ORM\HasLifecycleCallbacks]
class Follow
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
	private ?User $followed = null;

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
	 * get user who is followed
	 *
	 * @return User|null
	 */
	public function getFollowed(): ?User
	{
		return $this->followed;
	}

	/**
	 * set user who is followed
	 *
	 * @param User|null $followed
	 * @return $this
	 */
	public function setFollowed(?User $followed): self
	{
		$this->followed = $followed;

		return $this;
	}

	/**
	 * get user who follows
	 *
	 * @return User|null
	 */
	public function getFollower(): ?User
	{
		return $this->follower;
	}

	/**
	 * set user who follows
	 *
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
