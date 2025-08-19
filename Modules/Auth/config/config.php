<?php

return [
    'name' => 'Auth',
    'verification_length' => env('VERIFICATION_LENGTH', 5),
    'max_send_mail' => env('MAX_SEND_MAIL', 4)
];
