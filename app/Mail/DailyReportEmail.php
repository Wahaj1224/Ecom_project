<?php
namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DailyReportEmail extends Mailable
{
    use SerializesModels;

    public $reportData;

    public function __construct($reportData)
    {
        $this->reportData = $reportData;
    }

    public function build()
    {
        return $this->view('emails.daily_report')
                    ->with('reportData', $this->reportData);
    }
}
