<?php

namespace App\Entity;

use App\Repository\EmailVerificationRequestRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailVerificationRequestRepository::class)]
#[ORM\HasLifecycleCallbacks]
class EmailVerificationRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'emailVerificationRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $is_valid = null;

    #[ORM\Column]
    private ?DateTimeImmutable $requested_at = null;

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
     * @return bool|null
     */
    public function IsValid(): ?bool
    {
        return $this->is_valid;
    }

    public function setIsValid(bool $is_valid): self
    {
        $this->is_valid = $is_valid;

        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getRequestedAt(): ?DateTimeImmutable
    {
        return $this->requested_at;
    }

    /**
     * @param DateTimeImmutable $requested_at
     * @return $this
     */
    public function setRequestedAt(DateTimeImmutable $requested_at): self
    {
        $this->requested_at = $requested_at;

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
     * @return void
     */
    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->requested_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable();

        // set default value
        $this->is_valid ??= true;
    }

    /**
     * @return void
     */
    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updated_at = new DateTimeImmutable();

        // set default value
        $this->is_valid ??= true;
    }
}
