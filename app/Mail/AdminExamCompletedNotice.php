<?php

namespace App\Mail;

use App\Models\Candidate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminExamCompletedNotice extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Candidate $candidate,
        public string $examName,
        public int $score,
        public int $totalQuestions,
    ) {}

    public function build(): self
    {
        $percentage = $this->totalQuestions > 0 ? (int) round(($this->score / $this->totalQuestions) * 100) : 0;
        $passed = $percentage >= 50;

        return $this->subject('['.config('app.name').'] Exam Completed — '.$this->candidate->full_name.' ('.$this->examName.')')
            ->view('emails.admin-exam-completed')
            ->with([
                'candidate' => $this->candidate,
                'examName' => $this->examName,
                'score' => $this->score,
                'totalQuestions' => $this->totalQuestions,
                'percentage' => $percentage,
                'passed' => $passed,
                'heading' => 'Exam Completed',
                'ctaText' => 'View Results',
                'ctaUrl' => config('exams.client_url').'/admin/results',
            ]);
    }
}
