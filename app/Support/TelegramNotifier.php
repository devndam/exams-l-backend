<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramNotifier
{
    public function notifyError(Throwable $e, ?Request $request = null): void
    {
        $botToken = config('exams.telegram.bot_token');
        $chatId = config('exams.telegram.chat_id');

        if (! $botToken || ! $chatId) {
            return;
        }

        $stackLines = array_slice(explode("\n", (string) $e->getTraceAsString()), 0, 6);

        $lines = [
            '<b>'.e(config('app.name')).' — Unhandled Error</b>',
            'Message: '.e($e->getMessage()),
        ];

        if ($request) {
            $lines[] = 'Route: '.e($request->method().' '.$request->path());
        }

        $lines[] = "Stack:\n".e(implode("\n", $stackLines));

        try {
            Http::asForm()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => implode("\n", $lines),
                'parse_mode' => 'HTML',
            ]);
        } catch (Throwable $notifyError) {
            Log::warning('Failed to send Telegram error notification', ['error' => $notifyError->getMessage()]);
        }
    }
}
