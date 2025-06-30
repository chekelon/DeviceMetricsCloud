<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $body;
    protected $data;
    protected $fcmToken; // Para el token del dispositivo

    /**
     * Create a new notification instance.
     *
     * @param string $title
     * @param string $body
     * @param array $data
     * @param string|null $fcmToken Opcional, si quieres enviar a un token específico
     */
    public function __construct( string $title, string $body, array $data = [], ?string $fcmToken = null)
    {
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
        $this->fcmToken = $fcmToken;
    }
    

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['fcm'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toFCM(object $notifiable)
    {
        $token = $this->fcmToken ?? $notifiable->fcm_token ?? null;

        if(!$token){
            Log::warning("No FCM token found for notifiable or provided in constructor.", ['notifiable_id' => $notifiable->id ?? 'N/A']);
        }

        // Crea la notificación de Firebase
        $firebaseNotification = FirebaseNotification::create(
            $this->title,
            $this->body
        );

        // Crea el mensaje de Cloud Messaging
        $message = CloudMessage::withTarget('token', $token)
            ->withNotification($firebaseNotification)
            ->withData($this->data);

        return $message;

    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
        ];
    }
}
