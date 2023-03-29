<?php

namespace App\Controller;

use OpenAI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    #[Route('/chat', name: 'app_chat')]
    public function index(): Response
    {
        return $this->render('chat/index.html.twig', [
            'controller_name' => 'ChatController',
        ]);
    }





    #[Route('/chat/message', name: 'app_chat_message')]
    public function message(Request $request): Response
    {
        $history = $request->get('history');

        $client = OpenAI::client('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');

        $openAiResponse = $client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => array_merge(
                [
                    [
                        'role' => 'system',
                        'content' => file_get_contents(__DIR__ . '/../../system_message.txt')
                    ]
                ],
                json_decode($history)
            )
        ]);

        $message = $openAiResponse->choices[0]->message->content;

        if ($meeting = $this->checkForMeeting($message)) {

        }

        return $this->json([
            'answer' => $message
        ]);
    }


    /**
     * @param string $answer
     * @return array{summary: string, description: string, start: array{dateTime: string, timeZone: string}, end: array{dateTime: string, timeZone: string}, attendees: array{0: array{email: string}}, location: string}|null
     */
    public function checkForMeeting(string $answer): ?array
    {
        $pattern = '/Email: (?<email>.*)\nSubject: (?<subject>.*)\nLocation: (?<location>.*)\nDate: (?<date>.*)\nTime: (?<time>.*)\nDuration: (?<duration>.*)/m';

        if (preg_match($pattern, $answer, $matches) && isset($matches['email'], $matches['subject'], $matches['location'], $matches['date'], $matches['time'], $matches['duration'])) {
            $start = new \DateTime($matches['date'] . ' ' . $matches['time']);
            $end = clone $start;
            $end->modify('+' . $matches['duration']);

            return [
                'summary' => $matches['subject'],
                'description' => $matches['subject'],
                'start' => [
                    'dateTime' => $start->format('Y-m-d H:i:s'),
                    'timeZone' => 'UTC'
                ],
                'end' => [
                    'dateTime' => $end->format('Y-m-d H:i:s'),
                    'timeZone' => 'UTC'
                ],
                'attendees' => [
                    [
                        'email' => $matches['email']
                    ]
                ],
                'location' => $matches['location']
            ];
        }

        return null;
    }

    public function createMeeting(array $mettingProps)
    {
        $client = $this->getGoogleClient();
        $service = new Google_Service_Calendar($client);
        $event = new Google_Service_Calendar_Event($mettingProps);

        $service->events->insert(
            $_ENV['GOOGLE_CALENDAR_ID'],
            $event,
            array('sendNotifications' => true)
        );

    }
}
