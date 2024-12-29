<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\DailyReportEmail;  // Assume you have a DailyReportEmail Mailable class
use App\Models\Order;  // Example model to generate a report from orders
use Carbon\Carbon;

class GenerateDailyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:generate-daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a daily report and send it via email';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Get the date for today
        $today = Carbon::today();

        // Example: Fetch daily sales report from orders
        $salesReport = Order::whereDate('created_at', $today)->get();

        // You can format the data or generate a summary
        $reportData = [
            'total_orders' => $salesReport->count(),
            'total_sales' => $salesReport->sum('total_price'),
            'date' => $today->toFormattedDateString(),
        ];

        // Send email with the report
        Mail::to('admin@example.com')->send(new DailyReportEmail($reportData));

        $this->info('Daily report has been sent!');
    }
}
