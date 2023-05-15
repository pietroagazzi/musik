<?php

namespace App\Spotify;

use SplObserver;
use SplSubject;
use SpotifyWebAPI\Session as SpotifySession;

class Session extends SpotifySession implements SplSubject
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