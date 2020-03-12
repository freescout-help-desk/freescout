<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:create-user {--role=} {--firstName=} {--lastName=} {--email=} {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $class = config(
            'auth.providers.'.config(
                'auth.guards.'.config(
                    'auth.defaults.guard'
                ).'.provider'
            ).'.model'
        );
        $user = new $class();

        $user->role = $this->option('role');
        if ($user->role ) {
            if (!in_array($user->role , User::$roles)) {
                $this->error('Invalid role');
                return false;
            }
        } else {
            $user->role = $this->ask('User role (admin/user)', 'admin');
            while (!in_array($user->role, User::$roles)) {
                $this->error('Invalid role');
                $user->role = $this->ask('Please enter valid role');
            }
        }
        $user->role = array_flip(User::$roles)[$user->role];

        $user->first_name = $this->option('firstName') ? $this->option('firstName') : $this->ask('User first name');
        $user->last_name = $this->option('lastName') ? $this->option('lastName') : $this->ask('User last name');

        $user->email = $this->option('email');
        if ($user->email) {
            if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                $this->error('Invalid email address');
                return false;
            }
        } else {
            $user->email = $this->ask('User email address');
            while (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                $this->error('Incorrect email address');
                $user->email = $this->ask('Please enter valid email address');
            }
        }

        $user->password = \Hash::make($this->option('password') ? $this->option('password') : $this->secret('User password'));

        if ($this->confirm('Do you want to create the user?', true)) {
            if ($user->isAdmin()) {
                $user->invite_state = User::INVITE_STATE_ACTIVATED;
            }

            try {
                $user->save();
            } catch (\Exception $e) {
                $this->line($e->getMessage());
                $this->error('User already exists.');
                return false;
            }
        }

        $this->info('User created with id: '.$user->id);

        return true;
    }
}
