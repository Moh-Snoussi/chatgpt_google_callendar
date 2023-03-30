<?php

namespace App\Controller;

use App\Service\GoogleService;
use Exception;
use JsonException;
use OpenAI;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{

	public function __construct(
		private readonly GoogleService   $calendarService,
		private readonly LoggerInterface $logger
	)
	{
	}

	#[Route( '/chat', name: 'app_chat' )]
	public function index(): Response
	{
		return $this->render( 'chat/index.html.twig' );
	}


	/**
	 * Responds to the chat message
	 * By requesting the OpenAI API
	 * If the OpenAI API returns a meeting configuration,
	 * the CalendarService is used to create the meeting,
	 *
	 * Chat messages are not stored,
	 * this means that the conversation will be lost after the page is refreshed
	 * To store the conversation, you can use a database
	 *
	 * @throws JsonException
	 * @throws Exception
	 */
	#[Route( '/chat/message', name: 'app_chat_message', methods: [ 'POST' ] )]
	public function message( Request $request ): Response
	{
		// All message history are delivered in a post request,
		// better alternatives is to store it in a database, that will allow you to view the history of the conversation,
		// we will save the history in the logs in var/log/*.log
		$history = json_decode( $request->get( 'history' ), true, 512, JSON_THROW_ON_ERROR );

		// OpenAI API key can be generated here: https://platform.openai.com/account/api-keys
		// OPENAI_API_KEY is set in .env or .env.local (env.local has priority)
		$client = OpenAI::client( $_ENV[ 'OPENAI_API_KEY' ] );

		$openAiResponse = $client->chat()->create( [
													   /**
														* gpt-3.5-turbo is the latest model
														* available models can be found at:
														* https://platform.openai.com/docs/guides/chat
														*/
													   'model'    => 'gpt-3.5-turbo',
													   'messages' => array_merge(
														   [
															   [
																   'role'    => 'system',
																   /**
																	* This is a system message, it is used to give the AI some context
																	* You can change the content of the file to change the context.
																	* IMPORTANT: changes to this file can lead to unexpected results
																	*/
																   'content' => $this->getSystemContent(),
															   ]
														   ],
														   $history
													   ),
													   /**
														* More parameters can be found here:
														* https://platform.openai.com/docs/api-reference/chat/create
														* and here: https://github.com/openai-php/client
														*/
												   ] );

		$answer = $openAiResponse->choices[ 0 ]->message->content;

		if ( $meeting = $this->calendarService->processMessage( $answer ) )
		{
			// the $meeting variable contains the link to the meeting,
			// or in case of error, the error message
			$answer .= PHP_EOL . $meeting;
		}

		$this->logger->info( 'Chat [user][agent]: ', [ $history[ count( $history ) - 1 ][ 'content' ] ], [ $answer ] );

		return $this->json( [
								'answer' => $answer
							] );
	}

	/**
	 * Returns the content of the system_message.txt file
	 * with the variables replaced with the values from the .env file
	 */
	private function getSystemContent(): string
	{
		$content = file_get_contents( __DIR__ . '/../../system_message.txt' );

		return str_replace( [
								'__CHAT_BOT_NAME__',
								'__COMPANY__',
								'__SERVICES__',
								'__SUPPORT_EMAIL__',
								'__AVAILABILITY__',
								'__LOCATION__',
								'__NOW_DATE_TIME__',
							], [
								$_ENV[ 'GPT_SYS_MESSAGE_CHAT_BOT_NAME' ],
								$_ENV[ 'GPT_SYS_MESSAGE_COMPANY' ],
								$_ENV[ 'GPT_SYS_MESSAGE_SERVICES' ],
								$_ENV[ 'GPT_SYS_MESSAGE_SUPPORT_EMAIL' ],
								$_ENV[ 'GPT_SYS_MESSAGE_AVAILABILITY' ],
								$_ENV[ 'GPT_SYS_MESSAGE_LOCATION' ],
								date( 'l, F j, Y, g:i a' )
							],
			$content );
	}

}
