<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Admin;
use App\Models\Candidate;
use App\Support\Constants;
use App\Support\JwtService;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(private readonly JwtService $jwt) {}

    public function loginAdmin(string $email, string $password): array
    {
        $admin = Admin::query()->where('email', $email)->first();
        if (! $admin || ! Hash::check($password, $admin->password)) {
            throw new ApiException(401, 'Invalid email or password');
        }

        $token = $this->jwt->sign(['id' => $admin->id, 'role' => Constants::ROLE_ADMIN]);

        return [
            'token' => $token,
            'user' => ['id' => $admin->id, 'email' => $admin->email, 'role' => Constants::ROLE_ADMIN],
        ];
    }

    public function loginCandidate(string $candidateId): array
    {
        $candidate = Candidate::query()->with('faceEnrollment:candidate_id,status')->where('candidate_id', $candidateId)->first();
        if (! $candidate) {
            throw new ApiException(401, 'Invalid candidate ID');
        }

        $sessionToken = bin2hex(random_bytes(32));
        $candidate->update(['session_token' => $sessionToken]);

        $token = $this->jwt->sign([
            'id' => $candidate->id,
            'role' => Constants::ROLE_CANDIDATE,
            'sessionToken' => $sessionToken,
        ]);

        return [
            'token' => $token,
            'user' => [
                'id' => $candidate->id,
                'candidateId' => $candidate->candidate_id,
                'fullName' => $candidate->full_name,
                'email' => $candidate->email,
                'certificateName' => $candidate->certificate_name,
                'certificateEmail' => $candidate->certificate_email,
                'certificatePhone' => $candidate->certificate_phone,
                'role' => Constants::ROLE_CANDIDATE,
                'faceEnrollmentStatus' => $candidate->faceEnrollment->status ?? null,
            ],
        ];
    }
}
