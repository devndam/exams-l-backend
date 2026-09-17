<?php

namespace App\Support;

use App\Mail\AdminExamCompletedNotice;
use App\Mail\AdminFaceCaptureNotice;
use App\Mail\CandidateWelcome;
use App\Models\Admin;
use App\Models\Candidate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificationMailer
{
    public function notifyCandidateWelcome(Candidate $candidate): void
    {
        $this->safeSend(fn () => Mail::to($candidate->email)->send(new CandidateWelcome($candidate)),
            "Failed to send welcome email to candidate {$candidate->candidate_id}");
    }

    public function notifyAdminFaceCapture(Candidate $candidate): void
    {
        $emails = $this->adminEmails();
        if (! $emails) {
            return;
        }

        $this->safeSend(fn () => Mail::to($emails)->send(new AdminFaceCaptureNotice($candidate)),
            'Failed to notify admins about face capture');
    }

    public function notifyAdminExamCompleted(Candidate $candidate, string $examName, int $score, int $totalQuestions): void
    {
        $emails = $this->adminEmails();
        if (! $emails) {
            return;
        }

        $this->safeSend(fn () => Mail::to($emails)->send(new AdminExamCompletedNotice($candidate, $examName, $score, $totalQuestions)),
            'Failed to notify admins about exam completion');
    }

    /** @return array<int, string> */
    private function adminEmails(): array
    {
        return Admin::query()->pluck('email')->all();
    }

    private function safeSend(callable $send, string $failureMessage): void
    {
        try {
            $send();
        } catch (Throwable $e) {
            Log::error($failureMessage.': '.$e->getMessage(), ['exception' => $e]);
        }
    }
}
