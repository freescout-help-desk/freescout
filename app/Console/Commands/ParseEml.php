<?php

namespace App\Console\Commands;

use App\Mailbox;
use Webklex\PHPIMAP\IMAP;
use Webklex\PHPIMAP\Message;
use Illuminate\Console\Command;


class ParseEml extends Command
{
    /**
     * The name and signature of the console command.
     *
     * --mailbox Any mailbox able to connect via IMAP to its mail server.
     *
     * @var string
     */
    protected $signature = 'freescout:parse-eml {--mailbox=2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse EML file';

    /**
     * Current mailbox.
     *
     * Used to process emails sent to multiple mailboxes.
     */
    public $mailbox;

    /**
     * Used to process emails sent to multiple mailboxes.
     */
    public $mailboxes;

    public $extra_import = [];

    /**
     * Page size when requesting emails from mail server.
     */
    const PAGE_SIZE = 300;

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
        $email = file_get_contents(storage_path('logs/email.eml'));

        if (!str_contains($email, "\r\n")){
            $email = str_replace("\n", "\r\n", $email);
        }

        $raw_header = substr($email, 0, strpos($email, "\r\n\r\n"));
        $raw_body = substr($email, strlen($raw_header)+4);

        $mailbox = Mailbox::find($this->option('mailbox'));

        \Config::set('app.new_fetching_library', 'true');
        $client = \MailHelper::getMailboxClient($mailbox);
        $client->openFolder("INBOX");

        $message = Message::make(/*$this->option('uid')*/null, null, $client, $raw_header, $raw_body, [/*0 => "\\Seen"*/], IMAP::ST_UID);

        $this->line('Headers: ');
        $this->info($message->getHeader()->raw);
        $this->line('From: ');
        $this->info(json_encode($message->getFrom()[0] ?? [], JSON_UNESCAPED_UNICODE));
        $this->line('Reply-To: ');
        $this->info(json_encode($message->getReplyTo()[0] ?? [], JSON_UNESCAPED_UNICODE));
        $this->line('In-Reply-To: ');
        $this->info($message->getInReplyTo());
        $this->line('References: ');
        $this->info(json_encode(array_values(array_filter(preg_split('/[, <>]/', $message->getReferences() ?? ''))), JSON_UNESCAPED_UNICODE));
        $this->line('Date: ');
        $this->info($message->getDate());
        $this->line('Subject: ');
        $this->info($message->getSubject());
        $this->line('Text Body: ');
        $this->info($message->getTextBody());
        $this->line('HTML Body: ');
        $this->info($message->getHTMLBody(false));

        $attachments = $message->getAttachments();
        if (count($attachments)) {
            $this->line('Attachments: ');
            foreach ($attachments as $attachment) {
                $this->info($attachment->getName());
            }
        }
    }
}
