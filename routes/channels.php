<?php

use App\Models\ExamSession;
use App\Support\AuthContext;
use Illuminate\Support\Facades\Broadcast;

// Admins watch a candidate's live proctoring feed on this channel — see
// app/Events/FaceMonitoringEventOccurred.php, AudioMonitoringEventOccurred.php,
// SessionTerminated.php. The owning candidate also subscribes (client-side only,
// no server dispatch) to whisper live attention/gaze status straight to any admin
// watching — that signal is ephemeral and never persisted, so it skips the
// REST + broadcast round trip that face/audio monitoring results go through.
Broadcast::channel('session.{sessionId}', function ($user, int $sessionId) {
    $auth = app(AuthContext::class);

    if ($auth->isAdmin()) {
        return true;
    }

    return $auth->isCandidate()
        && ExamSession::query()->whereKey($sessionId)->where('candidate_id', $auth->id)->exists();
});
