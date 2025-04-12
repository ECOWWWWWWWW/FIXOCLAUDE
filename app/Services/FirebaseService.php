<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService
{
    protected $messaging;
    
    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(config('firebase.credentials.file'));
            
        $this->messaging = $factory->createMessaging();
    }
    
    /**
     * Send notification to a specific device
     */
    public function sendNotification($token, $title, $body, $data = [])
    {
        $notification = Notification::create($title, $body);
        
        $message = CloudMessage::withTarget('token', $token)
            ->withNotification($notification)
            ->withData($data);
            
        return $this->messaging->send($message);
    }
    
    /**
     * Send notification to multiple devices
     */
    public function sendMulticastNotification($tokens, $title, $body, $data = [])
    {
        $notification = Notification::create($title, $body);
        
        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($data);
            
        return $this->messaging->sendMulticast($message, $tokens);
    }
}