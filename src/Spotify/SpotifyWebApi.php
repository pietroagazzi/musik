<?php

namespace App\Spotify;

/**
 * Adds some functionality to the SpotifyWebAPI class
 */
class SpotifyWebApi extends \SpotifyWebAPI\SpotifyWebAPI
{
	public function getUserServiceId(): string
	{
		return $this->me()->id;
	}
}