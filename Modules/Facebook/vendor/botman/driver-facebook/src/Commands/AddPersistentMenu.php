<?php

namespace BotMan\Drivers\Facebook\Commands;

use BotMan\BotMan\Http\Curl;
use Illuminate\Console\Command;

class AddPersistentMenu extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'botman:facebook:AddMenu';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a persistent Facebook menu';

    /**
     * @var Curl
     */
    private $http;

    /**
     * Create a new command instance.
     *
     * @param Curl $http
     */
    public function __construct(Curl $http)
    {
        parent::__construct();
        $this->http = $http;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $payload = ['persistent_menu' => config('botman.facebook.persistent_menu')];

        if (! $payload) {
            $this->error('You need to add a Facebook menu payload data to your BotMan Facebook config in facebook.php.');
            exit;
        }

        $response = $this->http->post('https://graph.facebook.com/v3.0/me/messenger_profile?access_token='.config('botman.facebook.token'),
            [], $payload);

        $responseObject = json_decode($response->getContent());

        if ($response->getStatusCode() == 200) {
            $this->info('Facebook menu was set.');
        } else {
            $this->error('Something went wrong: '.$responseObject->error->message);
        }
    }
}
