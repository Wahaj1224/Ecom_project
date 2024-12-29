<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\support\Facades\Mail;
use App\Mail\welcomeemail;

class mailcontroller extends Controller
{
    //
    // function sendEmail()
    // {
    //     $to='daniaarshad19@gmail.com';
    //     $msg='dmmy msg';
    //     $subject='Test Email';
    //     Mail::to($to)->send(new welcomeemail($msg,$subject));
    // }


    function SendEmail(Request $request){
        $to="daniaarshad19@gmail.com";
        $msg=$request->message;;
        $subject="subject of email";

        
        try {
            Mail::to($to)->send(new WelcomeEmail($msg, $subject));
            return "Email sent successfully.";
        } catch (\Exception $e) {
            return "Failed to send email: " . $e->getMessage();
        }
        

        // Mail::to($to)->send(new WelcomeEmail($msg,$subject));
    }
   
}
