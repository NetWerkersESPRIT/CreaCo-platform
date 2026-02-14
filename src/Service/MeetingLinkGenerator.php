<?php

namespace App\Service;

class MeetingLinkGenerator
{
    public function generateJitsiLink(): string
    {
        $random = bin2hex(random_bytes(6));
        return 'https://meet.jit.si/' . $random;
    }
}
