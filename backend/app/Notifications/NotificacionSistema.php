<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NotificacionSistema extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $titulo,
        public readonly string $mensaje,
        public readonly string $url,
        public readonly string $tipo = 'info',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titulo' => $this->titulo,
            'mensaje' => $this->mensaje,
            'url' => $this->url,
            'tipo' => $this->tipo,
        ];
    }
}
