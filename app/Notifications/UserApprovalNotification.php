<?php

namespace App\Notifications;

use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $status;
    protected $message;

    /**
     * Create a new notification instance.
     */
    public function __construct($status)
    {
        $this->status = $status;
        
        // Set appropriate messages based on status
        switch ($status) {
            case 'approved':
                $this->message = 'Your account has been approved! You can now access all features.';
                break;
            case 'rejected':
                $this->message = 'Your account registration has been rejected. Please contact support for more information.';
                break;
            case 'verification_requested':
                $this->message = 'Additional verification is required for your account. Please check your email for instructions.';
                break;
            default:
                $this->message = 'Your account status has been updated.';
        }
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Use email and database for in-app notifications
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Account Status Update')
            ->greeting('Hello ' . $notifiable->firstname . '!')
            ->line($this->message)
            ->action('Visit Dashboard', url('/dashboard'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'time' => now()->toDateTimeString()
        ];
    }

    /**
     * Send the Firebase push notification after the database notification is stored.
     */
    public function afterCommit()
    {
        return true;
    }
    
    /**
     * Send firebase notification
     */
    public function toFirebase($notifiable)
    {
        if ($notifiable->firebase_token) {
            $firebaseService = app(FirebaseService::class);
            
            return $firebaseService->sendNotification(
                $notifiable->firebase_token,
                'Account Status Update',
                $this->message,
                [
                    'status' => $this->status,
                    'user_id' => $notifiable->id
                ]
            );
        }
    }
}