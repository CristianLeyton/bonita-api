<!DOCTYPE html>
<html>

<head>
    <title>{{ $mailSubject }}</title>
</head>

<body>
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <h2>{{ $mailSubject }}</h2>
        <div style="margin: 20px 0; padding: 20px; background-color: #f8f9fa; border-radius: 5px;">
            {!! nl2br(e($mailMessage)) !!}
        </div>
        <p style="color: #666; font-size: 12px;">
            Este correo fue enviado desde tu aplicación Laravel. Bonnita Glam
        </p>
    </div>
</body>

</html>
