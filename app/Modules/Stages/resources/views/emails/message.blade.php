{{-- Message générique du module Stages (demandeur, stagiaire, personnel). Thème clair. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f4;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;color:#0f172a;font-size:15px;line-height:1.6;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f4;padding:24px 12px;">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e2e8f0;">
        <tr><td style="height:5px;background:linear-gradient(90deg,#326761,#10b981,#0ea5e9);background-color:#326761;"></td></tr>
        <tr><td style="padding:24px 28px 8px;">
          <p style="margin:0;font-size:12px;font-weight:700;letter-spacing:1.5px;color:#326761;text-transform:uppercase;">CAP-EPAC · Stages</p>
          <h1 style="margin:6px 0 0;font-size:21px;line-height:1.3;color:#0f172a;">{{ $title }}</h1>
        </td></tr>
        <tr><td style="padding:12px 28px 4px;">
          <p style="margin:0 0 14px;font-weight:600;">{{ $greeting }}</p>
          @foreach ($paragraphs as $p)
            <p style="margin:0 0 14px;color:#334155;">{{ $p }}</p>
          @endforeach
        </td></tr>

        @php $rows = array_filter($fields ?? [], fn ($v) => trim((string) $v) !== ''); @endphp
        @if (count($rows))
        <tr><td style="padding:4px 28px 8px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
            @foreach ($rows as $label => $value)
            <tr>
              <td style="padding:10px 14px;width:38%;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;vertical-align:top;border-bottom:1px solid #eef2f6;">{{ $label }}</td>
              <td style="padding:10px 14px;font-weight:600;color:#0f172a;vertical-align:top;border-bottom:1px solid #eef2f6;">{{ $value }}</td>
            </tr>
            @endforeach
          </table>
        </td></tr>
        @endif

        @if (!empty($highlight))
        <tr><td style="padding:10px 28px 4px;">
          <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:14px 16px;color:#78350f;">
            @if (!empty($highlightTitle))<p style="margin:0 0 4px;font-weight:700;">{{ $highlightTitle }}</p>@endif
            <p style="margin:0;white-space:pre-line;">{{ $highlight }}</p>
          </div>
        </td></tr>
        @endif

        @if (!empty($ctaUrl))
        <tr><td align="center" style="padding:22px 28px 6px;">
          <a href="{{ $ctaUrl }}" style="display:inline-block;background:#326761;color:#ffffff;text-decoration:none;font-weight:700;padding:13px 26px;border-radius:10px;">{{ $ctaLabel ?: 'Ouvrir' }}</a>
        </td></tr>
        @endif

        <tr><td style="padding:22px 28px 24px;">
          <p style="margin:0;font-size:12px;color:#94a3b8;border-top:1px solid #eef2f6;padding-top:14px;">
            Message envoyé automatiquement par la plateforme du CAP-EPAC. Merci de ne pas y répondre.
          </p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
