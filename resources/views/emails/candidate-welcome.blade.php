@extends('emails.layout')

@section('content')
    <p>Your candidate profile has been created with {{ config('app.name') }}. Below are your registration details for reference.</p>
    @include('emails.info-table', ['rows' => [
        ['Candidate ID', '<span style="font-family: monospace;">'.e($candidate->candidate_id).'</span>'],
        ['Email', e($candidate->email)],
    ]])
    <p><strong>Next step:</strong> Log in to the exams portal using your Candidate ID above to view your assigned exams and get started.</p>
@endsection
