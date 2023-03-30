<?php

namespace App\Controller;

use App\Service\GoogleService;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * This is conditional Controller that can be
 * activated by setting the ALLOW_CALENDAR_OWNER_LOGIN variable to true in the .env.local file.
 *
 * When activated, it will allow the user to login with Google and fetch credentials that will be used later to interact the calendar.
 * the logged-in user will be the calendar owner, and will remove all other calendar owners.
 * only one user can be the calendar owner at a time.
 */
#[Route( '/connect/google' )]
class GoogleLoginController extends AbstractController
{
	private bool $allowCalendarOwner;

	public function __construct(
		private readonly GoogleService $googleService,
	)
	{
		$this->allowCalendarOwner = (bool)$_ENV[ 'ALLOW_CALENDAR_OWNER_LOGIN' ] && strtolower( trim( $_ENV[ 'ALLOW_CALENDAR_OWNER_LOGIN' ] ) ) !== 'false';
	}

	/**
	 * Browsing to this route will redirect to the Google login page.
	 * the logged in user will be the calendar owner.
	 * Make sure that the user have access to the calendar.
	 */
	#[Route( '/execute', name: 'connect_google' )]
	public function connectAction(): Response
	{
		if ( !$this->allowCalendarOwner )
		{
			return $this->redirectToRoute( 'app_chat' );
		}

		return new RedirectResponse(
			$this->googleService->getAuthUrl()
		);
	}

	/**
	 * A helper route that will display a link to the Google login page and the redirect URI.
	 */
	#[Route( '/', name: 'connect_view_google' )]
	public function viewAction(): Response
	{
		return $this->render( 'google_login.html.twig', [
			'allowCalendarOwner' => $this->allowCalendarOwner,
		] );
	}

	/**
	 * This route is called by Google after the user logged in.
	 * if this changes then the  GoogleService::redirectUri must be changed as well.
	 *
	 * @throws JsonException
	 */
	#[Route( '/authorized_redirect_uri', name: 'google_authorized_redirect_uri' )]
	public function connectCheckAction( Request $request ): Response
	{
		if ( !$this->allowCalendarOwner )
		{
			return $this->redirectToRoute( 'app_chat' );
		}


		$code = (string)$request->get( 'code' );

		$this->googleService->saveAuthFromCode( $code );

		return $this->redirectToRoute( 'app_chat' );
	}
}

// on the right side you will find the client ID and client secret -> copy them to the .env file
// GOOGLE_CLIENT_ID=...
// GOOGLE_CLIENT_SECRET=...
