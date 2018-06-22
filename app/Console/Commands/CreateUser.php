<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:create-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new user';

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
            'auth.providers.' . config(
                'auth.guards.' . config(
                    'auth.defaults.guard'
                ) . '.provider'
            ) . '.model'
        );
        $user = new $class;
        $fillables = $user->getFillable();
        foreach($fillables as $key => $fillable) {
            if ($fillable == 'password') {
                $user->password = \Hash::make($this->secret(($key+1) . "/" . count($fillables) . " User $fillable"));
            } elseif ($fillable == 'role') {
                $user->$fillable = $this->ask(($key+1) . "/" . count($fillables) . " User $fillable (admin/user)", 'admin');
                while (!in_array($user->$fillable, User::$roles)) {
                    $this->error("Incorrect role");
                    $user->$fillable = $this->ask("Please enter valid role");
                }
            } else {
                $user->$fillable = $this->ask(($key+1) . "/" . count($fillables) . " User $fillable");

                if ($fillable == 'email') {
                    while (!filter_var($user->$fillable, FILTER_VALIDATE_EMAIL)) {
                        $this->error("Incorrect email address");
                        $user->$fillable = $this->ask("Please enter valid email address");
                    }
                }
            }
        }
        if ($this->confirm("Do you want to create the user?", true)) {
            if ($user->isAdmin()) {
                $user->invite_state = User::INVITE_STATE_ACTIVATED;
            }
            $user->save();
            $this->info("User created (id: {$user->id})");
        }
        return true;
    }
}
