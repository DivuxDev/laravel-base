<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset your password</title>
  <style>
    body { margin: 0; padding: 0; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #18181b; }
    .wrapper { max-width: 560px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 6px rgba(0,0,0,.08); }
    .header { background: #409eff; padding: 36px 40px; text-align: center; }
    .header h1 { margin: 0; color: #fff; font-size: 24px; font-weight: 700; }
    .body { padding: 36px 40px; }
    .body p { margin: 0 0 16px; line-height: 1.6; font-size: 15px; color: #3f3f46; }
    .btn { display: inline-block; margin: 8px 0 24px; padding: 12px 28px; background: #409eff; color: #fff !important; text-decoration: none; border-radius: 8px; font-size: 15px; font-weight: 600; }
    .notice { font-size: 13px; color: #71717a; }
    .footer { padding: 20px 40px; background: #f4f4f5; text-align: center; font-size: 12px; color: #a1a1aa; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <h1>{{ config('app.name') }}</h1>
    </div>
    <div class="body">
      <p>Hello, <strong>{{ $user->name }}</strong>.</p>
      <p>We received a request to reset the password for your account. Click the button below to choose a new password.</p>
      <p style="text-align:center">
        <a href="{{ $resetUrl }}" class="btn">Reset Password</a>
      </p>
      <p class="notice">This link will expire in <strong>60 minutes</strong>. If you did not request a password reset, you can safely ignore this email — your password will remain unchanged.</p>
    </div>
    <div class="footer">
      &copy; {{ date('Y') }} {{ config('app.name') }} &middot; This is an automated message.
    </div>
  </div>
</body>
</html>
