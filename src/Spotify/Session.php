<?php

namespace App\Spotify;

use SplObserver;
use SplSubject;

/**
 * Observer subject for spotify token refresh
 * @see https://www.php.net/manual/en/class.splobserver.php
 * @see https://en.wikipedia.org/wiki/Observer_pattern
 */
class Session extends \SpotifyWebAPI\Session implements SplSubject
{
	private array $observers = [];

	public function attach(SplObserver $observer): void
	{
		$this->observers[] = $observer;
	}

	public function detach(SplObserver $observer): void
	{
		$index = array_search($observer, $this->observers, true);

		if ($index !== false) {
			unset($this->observers[$index]);
		}
	}

	public function refreshAccessToken($refreshToken = null): bool
	{
		$success = parent::refreshAccessToken($refreshToken);
		$this->notify();
		return $success;
	}

	public function notify(): void
	{
		foreach ($this->observers as $observer) {
			$observer->update($this);
		}
	}
}