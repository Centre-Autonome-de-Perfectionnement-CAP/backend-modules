<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Modules\Inscription\Jobs\SendPendingStudentMailJob;
<<<<<<< HEAD
use App\Modules\Inscription\Models\PendingStudent;
=======
>>>>>>> f355611 (draft)
use Illuminate\Http\Request;

class MailController
{
    public function sendMail(Request $request)
    {
        $students = $request->input('students', []);
<<<<<<< HEAD
        $errors = [];
        $success = [];
        
        foreach ($students as $studentData) {
            try {
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
                        
                        $success[] = [
                            'id' => $studentId,
                            'nom' => $pendingStudent->personalInformation->last_name ?? '',
                            'prenoms' => $pendingStudent->personalInformation->first_names ?? '',
                        ];
                    }
                }
            } catch (\Exception $e) {
                $studentId = $studentData['id'] ?? $studentData['studentId'] ?? 'inconnu';
                $errors[] = [
                    'id' => $studentId,
                    'message' => $e->getMessage()
                ];
            }
        }
        
        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Certains mails n\'ont pas pu être envoyés',
                'errors' => $errors,
                'success' => $success
            ], 422);
=======
        
        foreach ($students as $studentData) {
            SendPendingStudentMailJob::dispatch($studentData);
>>>>>>> f355611 (draft)
        }
        
        return response()->json([
            'success' => true,
<<<<<<< HEAD
            'message' => 'Mails en cours d\'envoi',
            'success' => $success
=======
            'message' => 'Mails en cours d\'envoi'
>>>>>>> f355611 (draft)
        ]);
    }
}
