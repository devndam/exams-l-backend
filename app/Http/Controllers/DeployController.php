<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

class DeployController extends Controller
{
    /**
     * Run post-deploy artisan tasks (migrations, cache rebuild).
     *
     * Exists because the cPanel plan has no SSH access, so CI cannot run
     * `php artisan migrate` remotely — it calls this endpoint instead, once
     * the new code has been uploaded over FTP.
     */
    public function handle(Request $request): Response
    {
        $token = (string) config('deploy.token');


        if ($token === '' || ! hash_equals($token, (string) $request->bearerToken())) {
            abort(403);
        }

        Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = Artisan::output();

        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
        Artisan::call('event:cache');
        Artisan::call('queue:restart');

        return $this->success(['migrate' => trim($migrateOutput)], 'Deployed');
    }
}
