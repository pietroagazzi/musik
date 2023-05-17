<?php

namespace App\Spotify;

use SpotifyWebAPI\SpotifyWebAPI;
use SpotifyWebAPI\SpotifyWebAPIException;

/**
 * Adds some functionality to the SpotifyWebAPI class
 *
 * Simplifies the integration between the SpotifyWebAPI
 * client and the session
 *
 * @see Session
 * @see SpotifyWebAPI
 *
 * @author Pietro Agazzi <agazzi_pietro@protonmail.com>
 */
class Client extends SpotifyWebAPI
{
	/**
	 * returns true if the client has an access token
	 *
	 * @return bool
	 */
	public function isUsable(): bool
	{
		return (bool)$this->accessToken;
	}

	/**
	 * set the access token and store it in the session
	 *
	 * @param $accessToken
	 * @return Client
	 */
	public function setAccessToken($accessToken): Client
	{
		if (isset($this->session)) {
			$this->session->setAccessToken($accessToken);
		}

		return parent::setAccessToken($accessToken);
	}

	/**
	 * set the refresh token and store it in the session
	 *
	 * @param $refreshToken
	 * @return $this
	 */
	public function setRefreshToken($refreshToken): Client
	{
		if (isset($this->session)) {
			$this->session->setRefreshToken($refreshToken);
		}

		return $this;
	}

	/**
	 * returns the user id of the current user
	 *
	 * @return string
	 * @see https://developer.spotify.com/documentation/web-api/concepts/spotify-uris-ids
	 */
	public function getUserId(): string
	{
		return $this->me()->id;
	}

	/**
	 * adds support for the 403 Forbidden error at the parent sendRequest method
	 *
	 * @throws SpotifyWebAPIException 403 Forbidden If the request is understood
	 * @inheritDoc
	 */
	public function sendRequest($method, $uri, $parameters = [], $headers = []): array
	{
		try {
			$response = parent::sendRequest($method, $uri, $parameters, $headers);
		} catch (SpotifyWebAPIException $e) {
			match ($e->getCode()) {
				/**
				 * 403 Forbidden
				 * The request is understood, use this exception to disconnect the user from the provider
				 */
				403 => throw new SpotifyWebAPIException('Bad OAuth request', $e->getCode(), $e),
				default => throw $e,
			};
		}

		return $response;
	}
}