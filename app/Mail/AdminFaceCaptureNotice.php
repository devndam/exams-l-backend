<?php

namespace App\Mail;

use App\Models\Candidate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminFaceCaptureNotice extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Candidate $candidate) {}

    public function build(): self
    {
        return $this->subject('['.config('app.name').'] New Face Capture — '.$this->candidate->full_name)
            ->view('emails.admin-face-capture')
            ->with(['candidate' => $this->candidate, 'heading' => 'New Face Capture Submitted', 'ctaText' => 'Review Face Enrollments', 'ctaUrl' => config('exams.client_url').'/admin/face-enrollments']);
    }
}
