<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LogoutUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:logout-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Logout all users';

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
        try {
            // Remove files from storage/frameworks/sessions
            $files = \File::files(storage_path('framework/sessions'));
            
            $count = 0;

            foreach ($files as $file) {
                try {
                    $deleted = \File::delete($file->getPathname());
                    if ($deleted) {
                        $count++;
                    }
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->line('Deleted sessions: '.$count);

            // Cycle remember tokens to revoke remember-me cookies and
            // mobile app auth tokens (the token is a part of their key).
            $cycled = 0;
            \App\User::chunkById(100, function ($users) use (&$cycled) {
                foreach ($users as $user) {
                    \DB::table('users')
                        ->where('id', $user->id)
                        ->update(['remember_token' => \Str::random(60)]);
                    $cycled++;
                }
            });
            $this->line('Revoked remember tokens: '.$cycled);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
