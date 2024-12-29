<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User; // For fetching users
use Illuminate\Support\Facades\Mail;
use App\Mail\PromotionalEmail; // Your Mailable

class SendEmailCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:send-promotions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send promotional emails to all users';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Fetch all users
        $users = User::all();

        // Send emails to each user
        foreach ($users as $user) {
            Mail::to($user->email)->send(new PromotionalEmail($user));
        }

        $this->info('Promotional emails have been sent to all users successfully.');
        return 0;
    }
}
