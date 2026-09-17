<div style="background: #f4f5f7; padding: 32px 16px; font-family: -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb;">
        <div style="background: #11191f; padding: 20px 32px;">
            <span style="color: #ffffff; font-size: 18px; font-weight: 700; letter-spacing: 0.2px;">{{ config('app.name') }}</span>
        </div>
        <div style="padding: 32px;">
            <h2 style="margin: 0 0 16px; color: #11191f; font-size: 20px;">{{ $heading }}</h2>
            <div style="color: #333333; font-size: 15px; line-height: 1.6;">
                @yield('content')
            </div>
            @isset($ctaUrl)
                <p style="margin: 28px 0 4px;">
                    <a href="{{ $ctaUrl }}"
                       style="display: inline-block; padding: 12px 24px; background: #11191f; color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 600;">
                        {{ $ctaText }}
                    </a>
                </p>
            @endisset
        </div>
        <div style="padding: 20px 32px; background: #fafafa; border-top: 1px solid #e5e7eb;">
            <p style="margin: 0; color: #9ca3af; font-size: 12px; line-height: 1.5;">
                This is an automated message from {{ config('app.name') }}. Please do not reply directly to this email.
            </p>
        </div>
    </div>
</div>
