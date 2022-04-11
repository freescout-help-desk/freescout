<?php

namespace BotMan\Drivers\Facebook;

use BotMan\BotMan\Drivers\Events\GenericEvent;
use BotMan\BotMan\Drivers\HttpDriver;
use BotMan\BotMan\Interfaces\DriverEventInterface;
use BotMan\BotMan\Interfaces\VerifiesService;
use BotMan\BotMan\Messages\Attachments\Audio;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Attachments\Video;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\Drivers\Facebook\Events\MessagingDeliveries;
use BotMan\Drivers\Facebook\Events\MessagingOptins;
use BotMan\Drivers\Facebook\Events\MessagingReads;
use BotMan\Drivers\Facebook\Events\MessagingReferrals;
use BotMan\Drivers\Facebook\Exceptions\FacebookException;
use BotMan\Drivers\Facebook\Extensions\Airline\AirlineBoardingPass;
use BotMan\Drivers\Facebook\Extensions\AirlineCheckInTemplate;
use BotMan\Drivers\Facebook\Extensions\AirlineItineraryTemplate;
use BotMan\Drivers\Facebook\Extensions\AirlineUpdateTemplate;
use BotMan\Drivers\Facebook\Extensions\ButtonTemplate;
use BotMan\Drivers\Facebook\Extensions\GenericTemplate;
use BotMan\Drivers\Facebook\Extensions\ListTemplate;
use BotMan\Drivers\Facebook\Extensions\MediaTemplate;
use BotMan\Drivers\Facebook\Extensions\OpenGraphTemplate;
use BotMan\Drivers\Facebook\Extensions\ReceiptTemplate;
use BotMan\Drivers\Facebook\Extensions\User;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FacebookDriver extends HttpDriver implements VerifiesService
{
    const HANDOVER_INBOX_PAGE_ID = '263902037430900';

    const TYPE_RESPONSE = 'RESPONSE';
    const TYPE_UPDATE = 'UPDATE';
    const TYPE_MESSAGE_TAG = 'MESSAGE_TAG';

    /** @var string */
    protected $signature;

    /** @var string */
    protected $content;

    /** @var array */
    protected $messages = [];

    /** @var array */
    protected $templates = [
        AirlineBoardingPass::class,
        AirlineCheckInTemplate::class,
        AirlineItineraryTemplate::class,
        AirlineUpdateTemplate::class,
        ButtonTemplate::class,
        GenericTemplate::class,
        ListTemplate::class,
        ReceiptTemplate::class,
        MediaTemplate::class,
        OpenGraphTemplate::class,
    ];

    private $supportedAttachments = [
        Video::class,
        Audio::class,
        Image::class,
        File::class,
    ];

    /** @var DriverEventInterface */
    protected $driverEvent;

    protected $facebookProfileEndpoint = 'https://graph.facebook.com/v3.0/';

    /** @var bool If the incoming request is a FB postback */
    protected $isPostback = false;

    const DRIVER_NAME = 'Facebook';

    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        $this->payload = new ParameterBag((array) json_decode($request->getContent(), true));
        $this->event = Collection::make((array) $this->payload->get('entry', [null])[0]);
        $this->signature = $request->headers->get('X_HUB_SIGNATURE', '');
        $this->content = $request->getContent();
        $this->config = Collection::make($this->config->get('facebook', []));
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        $validSignature = empty($this->config->get('app_secret')) || $this->validateSignature();
        $messages = Collection::make($this->event->get('messaging'))->filter(function ($msg) {
            return (isset($msg['message']['text']) || isset($msg['postback']['payload'])) && ! isset($msg['message']['is_echo']);
        });

        return ! $messages->isEmpty() && $validSignature;
    }

    /**
     * @param Request $request
     * @return null|Response
     */
    public function verifyRequest(Request $request)
    {
        if ($request->get('hub_mode') === 'subscribe' && $request->get('hub_verify_token') === $this->config->get('verification')) {
            return Response::create($request->get('hub_challenge'))->send();
        }
    }

    /**
     * @return bool|DriverEventInterface
     */
    public function hasMatchingEvent()
    {
        $event = Collection::make($this->event->get('messaging'))->filter(function ($msg) {
            return Collection::make($msg)->except([
                'sender',
                'recipient',
                'timestamp',
                'message',
                'postback',
            ])->isEmpty() === false;
        })->transform(function ($msg) {
            return Collection::make($msg)->toArray();
        })->first();

        if (! is_null($event)) {
            $this->driverEvent = $this->getEventFromEventData($event);

            return $this->driverEvent;
        }

        return false;
    }

    /**
     * @param array $eventData
     * @return DriverEventInterface
     */
    protected function getEventFromEventData(array $eventData)
    {
        $name = Collection::make($eventData)->except([
            'sender',
            'recipient',
            'timestamp',
            'message',
            'postback',
        ])->keys()->first();
        switch ($name) {
            case 'referral':
                return new MessagingReferrals($eventData);
                break;
            case 'optin':
                return new MessagingOptins($eventData);
                break;
            case 'delivery':
                return new MessagingDeliveries($eventData);
                break;
            case 'read':
                return new MessagingReads($eventData);
                break;
            case 'account_linking':
                return new Events\MessagingAccountLinking($eventData);
                break;
            case 'checkout_update':
                return new Events\MessagingCheckoutUpdates($eventData);
                break;
            default:
                $event = new GenericEvent($eventData);
                $event->setName($name);

                return $event;
                break;
        }
    }

    /**
     * @return bool
     */
    protected function validateSignature()
    {
        return hash_equals($this->signature,
            'sha1='.hash_hmac('sha1', $this->content, $this->config->get('app_secret')));
    }

    /**
     * @param IncomingMessage $matchingMessage
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function markSeen(IncomingMessage $matchingMessage)
    {
        $parameters = [
            'recipient' => [
                'id' => $matchingMessage->getSender(),
            ],
            'access_token' => $this->config->get('token'),
            'sender_action' => 'mark_seen',
        ];

        return $this->http->post($this->facebookProfileEndpoint.'me/messages', [], $parameters);
    }

    /**
     * @param IncomingMessage $matchingMessage
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function types(IncomingMessage $matchingMessage)
    {
        $parameters = [
            'recipient' => [
                'id' => $matchingMessage->getSender(),
            ],
            'access_token' => $this->config->get('token'),
            'sender_action' => 'typing_on',
        ];

        return $this->http->post($this->facebookProfileEndpoint.'me/messages', [], $parameters);
    }

    /**
     * @param  IncomingMessage $message
     * @return Answer
     */
    public function getConversationAnswer(IncomingMessage $message)
    {
        $payload = $message->getPayload();
        if (isset($payload['message']['quick_reply'])) {
            return Answer::create($payload['message']['text'])->setMessage($message)->setInteractiveReply(true)->setValue($payload['message']['quick_reply']['payload']);
        } elseif (isset($payload['postback']['payload'])) {
            return Answer::create($payload['postback']['title'])->setMessage($message)->setInteractiveReply(true)->setValue($payload['postback']['payload']);
        }

        return Answer::create($message->getText())->setMessage($message);
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        if (empty($this->messages)) {
            $this->loadMessages();
        }

        return $this->messages;
    }

    /**
     * Load Facebook messages.
     */
    protected function loadMessages()
    {
        $messages = Collection::make($this->event->get('messaging'));
        $messages = $messages->transform(function ($msg) {
            $message = new IncomingMessage('', $this->getMessageSender($msg), $this->getMessageRecipient($msg), $msg);
            if (isset($msg['message']['text']) && ! isset($msg['message']['quick_reply']['payload'])) {
                $message->setText($msg['message']['text']);

                if (isset($msg['message']['nlp'])) {
                    $message->addExtras('nlp', $msg['message']['nlp']);
                }
            } elseif (isset($msg['postback']['payload'])) {
                $this->isPostback = true;

                $message->setText($msg['postback']['payload']);
            } elseif (isset($msg['message']['quick_reply']['payload'])) {
                $this->isPostback = true;

                $message->setText($msg['message']['quick_reply']['payload']);
            }

            return $message;
        })->toArray();

        if (count($messages) === 0) {
            $messages = [new IncomingMessage('', '', '')];
        }

        $this->messages = $messages;
    }

    /**
     * @return bool
     */
    public function isBot()
    {
        // Facebook bot replies don't get returned
        return false;
    }

    /**
     * Tells if the current request is a callback.
     *
     * @return bool
     */
    public function isPostback()
    {
        return $this->isPostback;
    }

    /**
     * Convert a Question object into a valid Facebook
     * quick reply response object.
     *
     * @param Question $question
     * @return array
     */
    private function convertQuestion(Question $question)
    {
        $questionData = $question->toArray();

        $replies = Collection::make($question->getButtons())
            ->map(function ($button) {
                if (isset($button['content_type']) && $button['content_type'] !== 'text') {
                    return ['content_type' => $button['content_type']];
                }

                return array_merge([
                    'content_type' => 'text',
                    'title' => $button['text'] ?? $button['title'],
                    'payload' => $button['value'] ?? $button['payload'],
                    'image_url' => $button['image_url'] ?? $button['image_url'],
                ], $button['additional'] ?? []);
            });

        return [
            'text' => $questionData['text'],
            'quick_replies' => $replies->toArray(),
        ];
    }

    /**
     * @param string|Question|IncomingMessage $message
     * @param IncomingMessage $matchingMessage
     * @param array $additionalParameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        if ($this->driverEvent) {
            $payload = $this->driverEvent->getPayload();
            if (isset($payload['optin']) && isset($payload['optin']['user_ref'])) {
                $recipient = ['user_ref' => $payload['optin']['user_ref']];
            } else {
                $recipient = ['id' => $payload['sender']['id']];
            }
        } else {
            $recipient = ['id' => $matchingMessage->getSender()];
        }
        $parameters = array_merge_recursive([
            'messaging_type' => self::TYPE_RESPONSE,
            'recipient' => $recipient,
            'message' => [
                'text' => $message,
            ],
        ], $additionalParameters);
        /*
         * If we send a Question with buttons, ignore
         * the text and append the question.
         */
        if ($message instanceof Question) {
            $parameters['message'] = $this->convertQuestion($message);
        } elseif (is_object($message) && in_array(get_class($message), $this->templates)) {
            $parameters['message'] = $message->toArray();
        } elseif ($message instanceof OutgoingMessage) {
            $attachment = $message->getAttachment();
            if (! is_null($attachment) && in_array(get_class($attachment), $this->supportedAttachments)) {
                $attachmentType = strtolower(basename(str_replace('\\', '/', get_class($attachment))));
                unset($parameters['message']['text']);
                $parameters['message']['attachment'] = [
                    'type' => $attachmentType,
                    'payload' => [
                        'is_reusable' => $attachment->getExtras('is_reusable') ?? false,
                        'url' => $attachment->getUrl(),
                    ],
                ];
            } else {
                $parameters['message']['text'] = $message->getText();
            }
        }

        $parameters['access_token'] = $this->config->get('token');

        return $parameters;
    }

    /**
     * @param mixed $payload
     * @return Response
     * @throws FacebookException
     */
    public function sendPayload($payload)
    {
        $response = $this->http->post($this->facebookProfileEndpoint.'me/messages', [], $payload);
        $this->throwExceptionIfResponseNotOk($response);

        return $response;
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return ! empty($this->config->get('token'));
    }

    /**
     * Retrieve specific User field information.
     *
     * @param array $fields
     * @param IncomingMessage $matchingMessage
     * @return User
     * @throws FacebookException
     */
    public function getUserWithFields(array $fields, IncomingMessage $matchingMessage)
    {
        $messagingDetails = $this->event->get('messaging')[0];
        // implode field array to create concatinated comma string
        $fields = implode(',', $fields);
        // WORKPLACE (Facebook for companies)
        // if community isset in sender Object, it is a request done by workplace
        if (isset($messagingDetails['sender']['community'])) {
            $fields = 'first_name,last_name,email,title,department,employee_number,primary_phone,primary_address,picture,link,locale,name,name_format,updated_time';
        }
        $userInfoData = $this->http->get($this->facebookProfileEndpoint.$matchingMessage->getSender().'?fields='.$fields.'&access_token='.$this->config->get('token'));
        $this->throwExceptionIfResponseNotOk($userInfoData);
        $userInfo = json_decode($userInfoData->getContent(), true);
        $firstName = $userInfo['first_name'] ?? null;
        $lastName = $userInfo['last_name'] ?? null;

        return new User($matchingMessage->getSender(), $firstName, $lastName, null, $userInfo);
    }

    /**
     * Retrieve User information.
     *
     * @param IncomingMessage $matchingMessage
     * @return User
     * @throws FacebookException
     */
    public function getUser(IncomingMessage $matchingMessage)
    {
        $messagingDetails = $this->event->get('messaging')[0];

        // field string available at Facebook
        $fields = 'name,first_name,last_name,profile_pic';

        // WORKPLACE (Facebook for companies)
        // if community isset in sender Object, it is a request done by workplace
        if (isset($messagingDetails['sender']['community'])) {
            $fields = 'first_name,last_name,email,title,department,employee_number,primary_phone,primary_address,picture,link,locale,name,name_format,updated_time';
        }

        $userInfoData = $this->http->get($this->facebookProfileEndpoint.$matchingMessage->getSender().'?fields='.$fields.'&access_token='.$this->config->get('token'));

        $this->throwExceptionIfResponseNotOk($userInfoData);
        $userInfo = json_decode($userInfoData->getContent(), true);

        $firstName = $userInfo['first_name'] ?? null;
        $lastName = $userInfo['last_name'] ?? null;

        return new User($matchingMessage->getSender(), $firstName, $lastName, null, $userInfo);
    }

    /**
     * Low-level method to perform driver specific API requests.
     *
     * @param string $endpoint
     * @param array $parameters
     * @param IncomingMessage $matchingMessage
     * @return Response
     */
    public function sendRequest($endpoint, array $parameters, IncomingMessage $matchingMessage)
    {
        $parameters = array_replace_recursive([
            'access_token' => $this->config->get('token'),
        ], $parameters);

        return $this->http->post($this->facebookProfileEndpoint.$endpoint, [], $parameters);
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param Response $facebookResponse
     * @return mixed
     * @throws FacebookException
     */
    protected function throwExceptionIfResponseNotOk(Response $facebookResponse)
    {
        if ($facebookResponse->getStatusCode() !== 200) {
            $responseData = json_decode($facebookResponse->getContent(), true);
            throw new FacebookException('Error sending payload: '.$responseData['error']['message']);
        }
    }

    /**
     * @param $msg
     * @return string|null
     */
    protected function getMessageSender($msg)
    {
        if (isset($msg['sender'])) {
            return $msg['sender']['id'];
        } elseif (isset($msg['optin'])) {
            return $msg['optin']['user_ref'];
        }
    }

    /**
     * @param $msg
     * @return string|null
     */
    protected function getMessageRecipient($msg)
    {
        if (isset($msg['recipient'])) {
            return $msg['recipient']['id'];
        }
    }

    /**
     * Pass a conversation to the page inbox.
     *
     * @param IncomingMessage $message
     * @param $bot
     * @return Response
     */
    public function handover(IncomingMessage $message, $bot)
    {
        return $this->http->post($this->facebookProfileEndpoint.'me/pass_thread_control?access_token='.$this->config->get('token'), [], [
            'recipient' => [
                'id' => $message->getSender(),
            ],
            'target_app_id' => self::HANDOVER_INBOX_PAGE_ID,
        ]);
    }
}
