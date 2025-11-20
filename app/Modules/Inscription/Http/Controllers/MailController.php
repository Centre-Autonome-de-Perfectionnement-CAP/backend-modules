<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Modules\Inscription\Jobs\SendPendingStudentMailJob;
use App\Modules\Inscription\Models\PendingStudent;
use Illuminate\Http\Request;

class MailController
{
    public function sendMail(Request $request)
    {
        $students = $request->input('students', []);
        
        foreach ($students as $studentData) {
            SendPendingStudentMailJob::dispatch($studentData);
            
            $studentId = $studentData['id'] ?? $studentData['studentId'] ?? null;
            if ($studentId) {
                $pendingStudent = PendingStudent::find($studentId);
                if ($pendingStudent) {
                    // Détecter automatiquement le type de mail
                    $hasCuca = !empty($studentData['opinionCuca']);
                    $hasCuo = !empty($studentData['opinionCuo']);
                    
                    if ($hasCuca) {
                        $pendingStudent->increment('mail_cuca_count');
                        $pendingStudent->update(['mail_cuca_sent' => true]);
                    }
                    if ($hasCuo) {
                        $pendingStudent->increment('mail_cuo_count');
                        $pendingStudent->update(['mail_cuo_sent' => true]);
                    }
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Mails en cours d\'envoi'
        ]);
    }
}
