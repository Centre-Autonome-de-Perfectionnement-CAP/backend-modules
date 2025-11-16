<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Modules\Inscription\Jobs\SendPendingStudentMailJob;
use Illuminate\Http\Request;

class MailController
{
    public function sendMail(Request $request)
    {
        $students = $request->input('students', []);
        
        foreach ($students as $studentData) {
            SendPendingStudentMailJob::dispatch($studentData);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Mails en cours d\'envoi'
        ]);
    }
}
