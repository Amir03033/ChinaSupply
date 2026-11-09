<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CommunicationChannel: string implements HasLabel
{
    case Email = 'email';
    case WeChat = 'wechat';
    case WhatsApp = 'whatsapp';
    case Phone = 'phone';
    case VideoCall = 'video_call';
    case MarketplaceChat = 'marketplace_chat';
    case Meeting = 'meeting';
    case Other = 'other';

    public function getLabel(): string
    {
        return __('enums.channel.'.$this->value);
    }
}
