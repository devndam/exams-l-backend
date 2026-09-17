@extends('emails.layout')

@section('content')
    <p>A candidate has completed an exam.</p>
    @include('emails.info-table', ['rows' => [
        ['Name', e($candidate->full_name)],
        ['Candidate ID', '<span style="font-family: monospace;">'.e($candidate->candidate_id).'</span>'],
        ['Exam', e($examName)],
        ['Score', '<strong>'.$score.' / '.$totalQuestions.'</strong> ('.$percentage.'%) <span style="color: '.($passed ? '#16a34a' : '#dc2626').'; font-weight: 600;"> — '.($passed ? 'PASS' : 'FAIL').'</span>'],
    ]])
@endsection
