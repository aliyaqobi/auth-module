<?php

namespace Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SendSmsVerificationNotification extends Notification
{
    use Queueable;

    private $code;

    public function __construct($code)
    {
        $this->code = $code;
    }

    public function via($notifiable)
    {
        // فعلاً فقط log میکنیم، بعداً SMS واقعی اضافه میکنیم
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'mobile' => $notifiable,
            'code' => $this->code,
            'type' => 'sms_verification',
            'message' => "Your verification code is: {$this->code}",
        ];
    }

    public function toArray($notifiable)
    {
        return [
            'mobile' => $notifiable,
            'code' => $this->code,
            'message' => "Your verification code is: {$this->code}",
        ];
    }
}
