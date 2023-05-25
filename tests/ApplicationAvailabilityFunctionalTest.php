<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApplicationAvailabilityFunctionalTest extends WebTestCase
{
	/**
	 * @dataProvider musikControllerProvider
	 */
	public function testPageIsSuccessful($url): void
	{
		$client = static::createClient();
		$client->request('GET', $url);

		self::assertResponseIsSuccessful();
	}

	public function musikControllerProvider(): array
	{
		return [
			[''],
			['/login'],
			['/register'],
			['/reset'],
		];
	}
}