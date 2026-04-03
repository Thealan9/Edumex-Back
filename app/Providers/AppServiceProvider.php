<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {


            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000') . '/reset-password';

            $url = $frontendUrl . '?token=' . $token . '&email=' . urlencode($notifiable->getEmailForPasswordReset());

            return (new MailMessage)
                ->subject(Lang::get('Recuperación de contraseña'))
                ->line(Lang::get('Estás recibiendo este correo porque solicitaste un restablecimiento de contraseña para tu cuenta.'))
                ->action(Lang::get('Restablecer Contraseña'), $url)
                ->line(Lang::get('Si no solicitaste un restablecimiento de contraseña, no es necesario realizar ninguna otra acción.'));
        });
    }
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */

}
