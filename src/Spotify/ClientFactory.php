<?php

namespace App\Spotify;


/**
 * Factory for creating spotify clients
 *
 * This factory is responsible for creating spotify clients
 * with autowired dependencies.
 *
 * @see Client
 * @see Session
 *
 * @author Pietro Agazzi <agazzi_pietro@protonmail.com>
 */
class ClientFactory
{
	/**
	 * @var array|true[] $options options for the client
	 */
	private array $options = [
		'auto_refresh' => true,
		'auto_retry' => true,
		'return_assoc' => true,
	];

	/**
	 * @param Session $session the session to use
	 * @param array|null $options options for the client
	 */
	public function __construct(
		private readonly Session $session,
		array                    $options = null
	)
	{
		// merge the options with the default ones
		if ($options) {
			$this->options = array_merge($this->options, $options);
		}
	}

	/**
	 * creates a new client
	 *
	 * @return Client the created client
	 */
	public function create(): Client
	{
		return new Client($this->options, $this->session);
	}
}