<table style="border-collapse: collapse; width: 100%; margin: 16px 0;">
    @foreach ($rows as [$label, $value])
        <tr>
            <td style="padding: 8px 12px; font-weight: 600; color: #555; white-space: nowrap;">{{ $label }}</td>
            <td style="padding: 8px 12px;">{!! $value !!}</td>
        </tr>
    @endforeach
</table>
