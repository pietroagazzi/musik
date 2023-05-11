<?php

namespace App\Controller;

use App\Entity\{Connection, User};
use SpotifyWebAPI\SpotifyWebAPI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * controller for the musik pages
 */
class MusikController extends AbstractController
{
	/**
	 * @param UserInterface|null $user
	 * @return Response
	 */
	#[Route('', name: 'app_home')]
	public function index(
		#[CurrentUser] ?UserInterface $user
	): Response
	{
		/**
		 * @var User $user
		 */
		if ($user and $user->hasServiceConnection('spotify')) {
			$api = new SpotifyWebAPI();
			$api->setAccessToken(
				$user
					->getConnections()
					->filter(fn(Connection $connection) => $connection->getService() === 'spotify')
					->first()
					->getToken()
			);

			$me = $api->me();

			// debug
			dump($me);
		}

		return $this->render('musik/index.html.twig');
	}
}
