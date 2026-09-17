@extends('emails.layout')

@section('content')
    <p>A candidate has submitted a face capture and is awaiting your review.</p>
    @include('emails.info-table', ['rows' => [
        ['Name', e($candidate->full_name)],
        ['Candidate ID', '<span style="font-family: monospace;">'.e($candidate->candidate_id).'</span>'],
        ['Email', e($candidate->email)],
    ]])
@endsection
