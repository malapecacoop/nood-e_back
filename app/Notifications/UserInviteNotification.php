<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInviteNotification extends Notification
{
    use Queueable;

    private $url;

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Has estat convidada a ser col·laboradora a Nood-e')
            ->line('Has estat convidada a ser col·laboradora a Nood-e.')
            ->action('Acceptar la invitació', $this->url)
            ->line('Gràcies per utilitzar la nostra aplicació!');
    }

    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
