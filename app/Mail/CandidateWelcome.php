<?php

namespace App\Mail;

use App\Models\Candidate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CandidateWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Candidate $candidate) {}

    public function build(): self
    {
        return $this->subject('Welcome to '.config('app.name').' — Your Candidate ID')
            ->view('emails.candidate-welcome')
            ->with([
                'candidate' => $this->candidate,
                'heading' => 'Welcome, '.$this->candidate->full_name,
                'ctaText' => 'Go to Exams Portal',
                'ctaUrl' => config('exams.client_url'),
            ]);
    }
}
