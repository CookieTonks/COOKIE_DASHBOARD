@php use App\Support\Format; @endphp
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:24px 12px;background:#f5f3fb;font-family:Poppins,Arial,sans-serif;color:#1a1535;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr><td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:540px;background:#ffffff;border-radius:22px;">
                <tr><td align="center" style="background:#ffffff;border-radius:22px 22px 0 0;padding:24px 28px;">
                    <img src="{{ $message->embed(public_path('images/email-logo.png')) }}" alt="{{ config('mail.from.name') }}" width="280" style="display:block;width:280px;max-width:100%;height:auto;border:0;">
                </td></tr>
                <tr><td style="background:#785edc;padding:16px 28px;color:#ffffff;font-size:18px;font-weight:bold;">
                    Recordatorio de pago
                </td></tr>
                <tr><td style="padding:28px;font-size:15px;line-height:1.6;">
                    <p style="margin:0 0 16px;">Hola, {{ $due->plan->client->name }}:</p>
                    <p style="margin:0 0 20px;">Te recordamos que tienes un pago próximo a vencer.</p>
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f3fb;border-radius:14px;">
                        <tr><td style="padding:16px 18px 4px;color:#6b5f7c;font-size:13px;">Concepto</td></tr>
                        <tr><td style="padding:0 18px 10px;font-weight:bold;">{{ $due->plan->concept }}</td></tr>
                        <tr><td style="padding:0 18px 4px;color:#6b5f7c;font-size:13px;">Monto</td></tr>
                        <tr><td style="padding:0 18px 10px;font-weight:bold;">{{ Format::money($due->amount, $due->currency) }}</td></tr>
                        <tr><td style="padding:0 18px 4px;color:#6b5f7c;font-size:13px;">Fecha de pago</td></tr>
                        <tr><td style="padding:0 18px 16px;font-weight:bold;">{{ $due->due_date->translatedFormat('l j \d\e F \d\e Y') }}</td></tr>
                    </table>
                    <p style="margin:20px 0 0;">Si ya realizaste tu pago, por favor ignora este mensaje. Gracias.</p>
                    <p style="margin:20px 0 0;">Saludos,<br><strong>{{ config('mail.from.name') }}</strong></p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
