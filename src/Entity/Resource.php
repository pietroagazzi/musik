<?php

namespace App\Entity;

use App\Repository\ResourceRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResourceRepository::class)]
#[ORM\UniqueConstraint(fields: ['uri', 'provider'])]
#[ORM\HasLifecycleCallbacks]
final class Resource
{
	/**
	 * used to retrieve the resource info from the uri
	 *
	 * e.g. for spotify resources:
	 *  - spotify:artist:id
	 */
	private const SPOTIFY_URI = '/^(spotify):(artist|album|track):([a-zA-Z0-9]+)$/';

	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	/**
	 * the uri given by the provider representing the resource
	 *
	 * @see https://developer.spotify.com/documentation/web-api/concepts/spotify-uris-ids
	 * @var string|null
	 */
	#[ORM\Column(length: 255)]
	private ?string $uri = null;

	#[ORM\Column]
	private ?DateTimeImmutable $created_at = null;

	#[ORM\Column]
	private ?DateTimeImmutable $updated_at = null;

	#[ORM\OneToMany(mappedBy: 'resource', targetEntity: Post::class)]
	private Collection $posts;

	public function __construct()
	{
		$this->posts = new ArrayCollection();
	}

	/**
	 * get the id of the resource
	 *
	 * @return int|null
	 */
	public function getId(): ?int
	{
		return $this->id;
	}

	/**
	 * get the uri of the resource
	 *
	 * @return string|null
	 */
	public function getUri(): ?string
	{
		return $this->uri;
	}

	/**
	 * set the uri of the resource
	 *
	 * @param string $uri
	 * @return $this
	 */
	public function setUri(string $uri): self
	{
		$this->uri = $uri;

		return $this;
	}

	/**
	 * returns the provider of the resource
	 *
	 * @return string|null the provider of the resource or null if the uri
	 *  is not valid or the provider is not supported
	 */
	public function getProvider(): ?string
	{
		if (preg_match(self::SPOTIFY_URI, $this->uri, $matches)) {
			return $matches[1];
		}

		return null;
	}

	/**
	 * returns the resource identifier
	 *
	 * @return string|null the resource identifier or null if the uri is not valid
	 */
	public function getResourceIdentifier(): ?string
	{
		if (preg_match(self::SPOTIFY_URI, $this->uri, $matches)) {
			return $matches[3];
		}

		return null;
	}

	/**
	 * get the resource type
	 * - artist
	 * - album
	 * - track
	 *
	 * @return string|null the resource type or null if the uri is not valid
	 */
	public function getResourceType(): ?string
	{
		if (preg_match(self::SPOTIFY_URI, $this->uri, $matches)) {
			return $matches[2];
		}

		return null;
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
	 * executed before the entity is persisted
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
	 * executed before the entity is updated
	 *
	 * @return void
	 */
	#[ORM\PreUpdate]
	public function preUpdate(): void
	{
		$this->updated_at = new DateTimeImmutable();
	}

	/**
	 * get the posts related to the resource
	 *
	 * @return Collection<int, Post>
	 */
	public function getPosts(): Collection
	{
		return $this->posts;
	}
}
