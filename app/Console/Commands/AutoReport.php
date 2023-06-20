<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\SLASetting;
use App\Conversation;
use Dompdf\Dompdf;
use Dompdf\Options;
use \PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mail;
use Carbon\Carbon;

class AutoReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canidesk:auto-reporting';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'this command is used to send email automatically';

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
    public function handle(Request $request)
    {

        $today = Carbon::today();
        $fourDaysAgo = Carbon::today()->subDays(4);
        $sevenDaysAgo = Carbon::today()->subDays(7);
        $thirtyDaysAgo = Carbon::today()->subDays(30);
        $prev = false;
        $date_field = 'conversations.created_at';
        $date_field_to = '';

        

        $settings=SLASetting::orderBy('id', 'desc')->first();
        $emails=explode(',', $settings->to_email);
        $this->info($settings->frequency);
        $this->info($settings->schedule);
    //    dd('done');
        $this->info($settings);
        $tickets = Conversation::query();
        $tickets = $tickets->with('user', 'conversationCustomField.custom_field', 'conversationCategory','conversationPriority');
        if(!empty($settings->frequency) && $settings->frequency === 'Monthly'){
            $this->info(Carbon::now()->subDays(30)->startOfMonth()->endOfDay());
            $tickets = $tickets->whereBetween('created_at',[Carbon::now()->subDays(30)->startOfMonth()->startOfDay(),Carbon::now()->subDays(30)->endOfMonth()->endOfDay()]);

        }
        else if(!empty($settings->frequency) && $settings->frequency === 'Weekly'){
            $this->info(Carbon::now()->subDays(7)->startOfWeek()->endOfDay());
            $tickets = $tickets->whereBetween('created_at',[Carbon::now()->subDays(7)->startOfWeek()->startOfDay(),Carbon::now()->subDays(7)->endOfWeek()->endOfDay()]);
        }
        else if(!empty($settings->frequency) && $settings->frequency === 'Daily'){
            $this->info(Carbon::now()->subDays(1)->endOfDay());
            $tickets = $tickets->whereBetween('created_at',[Carbon::now()->subDays(1)->startOfDay(),Carbon::now()->subDays(1)->endOfDay()]);

        }
        $tickets = $tickets->get();
        $dompdf = new Dompdf();
        // (Optional) Set Dompdf options
        $options = new Options();
        $options->set('defaultFont', 'Arial'); // Set the default font
        // You can customize other options if needed

        $dompdf->setOptions($options);

        // Load the Blade view with the table data
        $html = view('sla.report-email', compact('tickets'));
        // return $html;
        // Load the HTML content
        $dompdf->load_html($html);

        // Render the PDF
        $dompdf->render();

        // Output the generated PDF to the browser
        // $dompdf->save('report.pdf');

        $output = $dompdf->output();
        file_put_contents('storage/slaReport/report.pdf', $output);
        $data = array('name'=>"Example");


        foreach($emails as $email){
            // $this->info($email);
        $mail = Mail::send('mail', $data, function($message) use ($email, $settings){
        $message->to($email, 'Canidesk User')->subject
            ($settings->frequency.' Report')->attach('storage/slaReport/report.pdf');
        $message->from('rajeshcanaris@gmail.com','[ Canidesk Report ]');
        
        });
      }
    }
}
