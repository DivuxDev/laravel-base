<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bienvenido</title>
  <style>
    body { margin: 0; padding: 0; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #18181b; }
    .wrapper { max-width: 560px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 6px rgba(0,0,0,.08); }
    .header { background: #409eff; padding: 36px 40px; text-align: center; }
    .header h1 { margin: 0; color: #fff; font-size: 24px; font-weight: 700; }
    .body { padding: 36px 40px; }
    .body p { margin: 0 0 16px; line-height: 1.6; font-size: 15px; color: #3f3f46; }
    .btn { display: inline-block; margin: 8px 0 24px; padding: 12px 28px; background: #409eff; color: #fff !important; text-decoration: none; border-radius: 8px; font-size: 15px; font-weight: 600; }
    .footer { padding: 20px 40px; background: #f4f4f5; text-align: center; font-size: 12px; color: #a1a1aa; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <h1>⚡ {{ config('app.name') }}</h1>
    </div>
    <div class="body">
      <p>Hola, <strong>{{ $user->name }}</strong>.</p>
      <p>Tu cuenta ha sido creada correctamente. Ya puedes acceder a la plataforma con tu correo electrónico.</p>
      <p style="text-align:center">
        <a href="{{ config('app.url') }}" class="btn">Acceder ahora</a>
      </p>
      <p>Si no creaste esta cuenta, puedes ignorar este mensaje.</p>
    </div>
    <div class="footer">
      &copy; {{ date('Y') }} {{ config('app.name') }} · Este es un mensaje automático.
    </div>
  </div>
</body>
</html>
