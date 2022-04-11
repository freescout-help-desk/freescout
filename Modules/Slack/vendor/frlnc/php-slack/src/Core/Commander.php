<?php namespace Frlnc\Slack\Core;

use InvalidArgumentException;
use Frlnc\Slack\Contracts\Http\Interactor;

class Commander {

    /**
     * The default command headers.
     *
     * @var array
     */
    protected static $defaultHeaders = [];

    /**
     * The commands.
     *
     * @var array
     */
    protected static $commands = [
        'api.test' => [
            'endpoint' => '/api.test',
            'token'    => false
        ],
        'auth.test' => [
            'endpoint' => '/auth.test',
            'token'    => true
        ],
        'channels.archive' => [
            'token'    => true,
            'endpoint' => '/channels.archive'
        ],
        'channels.create' => [
            'token'    => true,
            'endpoint' => '/channels.create'
        ],
        'channels.history' => [
            'token'    => true,
            'endpoint' => '/channels.history'
        ],
        'channels.info' => [
            'token'    => true,
            'endpoint' => '/channels.info'
        ],
        'channels.invite' => [
            'token'    => true,
            'endpoint' => '/channels.invite'
        ],
        'channels.join' => [
            'token'    => true,
            'endpoint' => '/channels.join'
        ],
        'channels.kick' => [
            'token'    => true,
            'endpoint' => '/channels.kick'
        ],
        'channels.leave' => [
            'token'    => true,
            'endpoint' => '/channels.leave'
        ],
        'channels.list' => [
            'token'    => true,
            'endpoint' => '/channels.list'
        ],
        'channels.mark' => [
            'token'    => true,
            'endpoint' => '/channels.mark'
        ],
        'channels.rename' => [
            'token'    => true,
            'endpoint' => '/channels.rename'
        ],
        'channels.setPurpose' => [
            'token'    => true,
            'endpoint' => '/channels.setPurpose',
            'format'   => [
                'purpose'
            ]
        ],
        'channels.setTopic' => [
            'token'    => true,
            'endpoint' => '/channels.setTopic',
            'format'   => [
                'topic'
            ]
        ],
        'channels.unarchive' => [
            'token'    => true,
            'endpoint' => '/channels.unarchive'
        ],
        'chat.delete' => [
            'token'    => true,
            'endpoint' => '/chat.delete'
        ],
        'chat.postMessage' => [
            'token'    => true,
            'endpoint' => '/chat.postMessage',
            'format'   => [
                'text',
                'username'
            ]
        ],
        'chat.update' => [
            'token'    => true,
            'endpoint' => '/chat.update',
            'format'   => [
                'text'
            ]
        ],
        'dnd.endDnd' => [
            'token'    => true,
            'endpoint' => '/dnd.endDnd'
        ],
        'dnd.endSnooze' => [
            'token'    => true,
            'endpoint' => '/dnd.endSnooze'
        ],
        'dnd.info' => [
            'token'    => true,
            'endpoint' => '/dnd.info'
        ],
        'dnd.setSnooze' => [
            'token'    => true,
            'endpoint' => '/dnd.setSnooze'
        ],
        'dnd.teamInfo' => [
            'token'    => true,
            'endpoint' => '/dnd.teamInfo'
        ],
        'emoji.list' => [
            'token'    => true,
            'endpoint' => '/emoji.list'
        ],
        'files.comments.add' => [
            'token'    => true,
            'endpoint' => '/files.comments.add'
        ],
        'files.comments.delete' => [
            'token'    => true,
            'endpoint' => '/files.comments.delete'
        ],
        'files.comments.edit' => [
            'token'    => true,
            'endpoint' => '/files.comments.edit'
        ],
        'files.delete' => [
            'token'    => true,
            'endpoint' => '/files.delete'
        ],
        'files.info' => [
            'token'    => true,
            'endpoint' => '/files.info'
        ],
        'files.list' => [
            'token'    => true,
            'endpoint' => '/files.list'
        ],
        'files.revokePublicURL' => [
            'token'    => true,
            'endpoint' => '/files.revokePublicURL'
        ],
        'files.sharedPublcURL' => [
            'token'    => true,
            'endpoint' => '/files.sharedPublcURL'
        ],
        'files.upload' => [
            'token'    => true,
            'endpoint' => '/files.upload',
            'post'     => true,
            'headers'  => [
                'Content-Type' => 'multipart/form-data'
            ],
            'format'   => [
                'filename',
                'title',
                'initial_comment'
            ]
        ],
        'groups.archive' => [
            'token'    => true,
            'endpoint' => '/groups.archive'
        ],
        'groups.close' => [
            'token'    => true,
            'endpoint' => '/groups.close'
        ],
        'groups.create' => [
            'token'    => true,
            'endpoint' => '/groups.create',
            'format'   => [
                'name'
            ]
        ],
        'groups.createChild' => [
            'token'    => true,
            'endpoint' => '/groups.createChild'
        ],
        'groups.history' => [
            'token'    => true,
            'endpoint' => '/groups.history'
        ],
        'groups.info' => [
            'token'    => true,
            'endpoint' => '/groups.info'
        ],
        'groups.invite' => [
            'token'    => true,
            'endpoint' => '/groups.invite'
        ],
        'groups.kick' => [
            'token'    => true,
            'endpoint' => '/groups.kick'
        ],
        'groups.leave' => [
            'token'    => true,
            'endpoint' => '/groups.leave'
        ],
        'groups.list' => [
            'token'    => true,
            'endpoint' => '/groups.list'
        ],
        'groups.mark' => [
            'token'    => true,
            'endpoint' => '/groups.mark'
        ],
        'groups.open' => [
            'token'    => true,
            'endpoint' => '/groups.open'
        ],
        'groups.rename' => [
            'token'    => true,
            'endpoint' => '/groups.rename'
        ],
        'groups.setPurpose' => [
            'token'    => true,
            'endpoint' => '/groups.setPurpose',
            'format'   => [
                'purpose'
            ]
        ],
        'groups.setTopic' => [
            'token'    => true,
            'endpoint' => '/groups.setTopic',
            'format'   => [
                'topic'
            ]
        ],
        'groups.unarchive' => [
            'token'    => true,
            'endpoint' => '/groups.unarchive'
        ],
        'im.close' => [
            'token'    => true,
            'endpoint' => '/im.close'
        ],
        'im.history' => [
            'token'    => true,
            'endpoint' => '/im.history'
        ],
        'im.list' => [
            'token'    => true,
            'endpoint' => '/im.list'
        ],
        'im.mark' => [
            'token'    => true,
            'endpoint' => '/im.mark'
        ],
        'im.open' => [
            'token'    => true,
            'endpoint' => '/im.open'
        ],
        'mpim.close' => [
            'token'    => true,
            'endpoint' => '/mpim.close'
        ],
        'mpmim.history' => [
            'token'    => true,
            'endpoint' => '/mpmim.history'
        ],
        'mpim.list' => [
            'token'    => true,
            'endpoint' => '/mpim.list'
        ],
        'mpim.mark' => [
            'token'    => true,
            'endpoint' => '/mpim.mark'
        ],
        'mpim.open' => [
            'token'    => true,
            'endpoint' => '/mpim.open'
        ],
        'oauth.access' => [
            'token'    => false,
            'endpoint' => '/oauth.access'
        ],
        'pins.add' => [
            'token'    => true,
            'endpoint' => '/pins.add'
        ],
        'pins.list' => [
            'token'    => true,
            'endpoint' => '/pins.list'
        ],
        'pins.remove' => [
            'token'    => true,
            'endpoint' => '/pins.remove'
        ],
        'reactions.add' => [
            'token'    => true,
            'endpoint' => '/reactions.add'
        ],
        'reactions.get' => [
            'token'    => true,
            'endpoint' => '/reactions.get'
        ],
        'reactions.list' => [
            'token'    => true,
            'endpoint' => '/reactions.list'
        ],
        'reactions.remove' => [
            'token'    => true,
            'endpoint' => '/reactions.remove'
        ],
        'rtm.start' => [
            'token'    => true,
            'endpoint' => '/rtm.start'
        ],
        'search.all' => [
            'token'    => true,
            'endpoint' => '/search.all'
        ],
        'search.files' => [
            'token'    => true,
            'endpoint' => '/search.files'
        ],
        'search.messages' => [
            'token'    => true,
            'endpoint' => '/search.messages'
        ],
        'stars.add' => [
            'token'    => true,
            'endpoint' => '/stars.add'
        ],
        'stars.list' => [
            'token'    => true,
            'endpoint' => '/stars.list'
        ],
        'stars.remove' => [
            'token'    => true,
            'endpoint' => '/stars.remove'
        ],
        'team.accessLogs' => [
            'token'    => true,
            'endpoint' => '/team.accessLogs'
        ],
        'team.info' => [
            'token'    => true,
            'endpoint' => '/team.info'
        ],
        'team.integrationLogs' => [
            'token'    => true,
            'endpoint' => '/team.integrationLogs'
        ],
        'usergroups.create' => [
            'token'    => true,
            'endpoint' => '/usergroups.create'
        ],
        'usergroups.disable' => [
            'token'    => true,
            'endpoint' => '/usergroups.disable'
        ],
        'usergroups.enable' => [
            'token'    => true,
            'endpoint' => '/usergroups.enable'
        ],
        'usergroups.list' => [
            'token'    => true,
            'endpoint' => '/usergroups.list'
        ],
        'usergroups.update' => [
            'token'    => true,
            'endpoint' => '/usergroups.update'
        ],
        'usergroups.users.list' => [
            'token'    => true,
            'endpoint' => '/usergroups.users.list'
        ],
        'usergroups.users.update' => [
            'token'    => true,
            'endpoint' => '/usergroups.users.update'
        ],
        'users.getPresence' => [
            'token'    => true,
            'endpoint' => '/users.getPresence'
        ],
        'users.info' => [
            'token'    => true,
            'endpoint' => '/users.info'
        ],
        'users.list' => [
            'token'    => true,
            'endpoint' => '/users.list'
        ],
        'users.setActive' => [
            'token'    => true,
            'endpoint' => '/users.setActive'
        ],
        'users.setPresence' => [
            'token'    => true,
            'endpoint' => '/users.setPresence'
        ],
        'users.admin.invite' => [
            'token'    => true,
            'endpoint' => '/users.admin.invite'
        ]
    ];

    /**
     * The base URL.
     *
     * @var string
     */
    protected static $baseUrl = 'https://slack.com/api';

    /**
     * The API token.
     *
     * @var string
     */
    protected $token;

    /**
     * The Http interactor.
     *
     * @var \Frlnc\Slack\Contracts\Http\Interactor
     */
    protected $interactor;

    /**
     * @param string $token
     * @param \Frlnc\Slack\Contracts\Http\Interactor $interactor
     */
    public function __construct($token, Interactor $interactor)
    {
        $this->token = $token;
        $this->interactor = $interactor;
    }

    /**
     * Executes a command.
     *
     * @param  string $command
     * @param  array $parameters
     * @return \Frlnc\Slack\Contracts\Http\Response
     */
    public function execute($command, array $parameters = [])
    {
        if (!isset(self::$commands[$command]))
            throw new InvalidArgumentException("The command '{$command}' is not currently supported");

        $command = self::$commands[$command];

        if ($command['token'])
            $parameters = array_merge($parameters, ['token' => $this->token]);

        if (isset($command['format']))
            foreach ($command['format'] as $format)
                if (isset($parameters[$format]))
                    $parameters[$format] = self::format($parameters[$format]);

        $headers = [];
        if (isset($command['headers']))
            $headers = $command['headers'];

        $url = self::$baseUrl . $command['endpoint'];

        if (isset($command['post']) && $command['post'])
            return $this->interactor->post($url, [], $parameters, $headers);

        return $this->interactor->get($url, $parameters, $headers);
    }

    /**
     * Sets the token.
     *
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Formats a string for Slack.
     *
     * @param  string $string
     * @return string
     */
    public static function format($string)
    {
        $string = str_replace('&', '&amp;', $string);
        $string = str_replace('<', '&lt;', $string);
        $string = str_replace('>', '&gt;', $string);

        return $string;
    }

}
