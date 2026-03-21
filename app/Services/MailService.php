<?php

namespace App\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class MailService
{
    /**
     * Send a mailable to one or more recipients.
     *
     * Usage:
     *   app(MailService::class)->send(new WelcomeEmail($user), $user->email);
     *   app(MailService::class)->send(new InvoiceEmail($invoice), ['a@b.com', 'c@d.com']);
     *
     * @param  string|array<int,string>  $to
     */
    public function send(Mailable $mailable, string|array $to): void
    {
        Mail::to($to)->send($mailable);
    }

    /**
     * Queue a mailable instead of sending synchronously.
     *
     * @param  string|array<int,string>  $to
     */
    public function queue(Mailable $mailable, string|array $to): void
    {
        Mail::to($to)->queue($mailable);
    }
}
