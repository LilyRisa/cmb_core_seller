<?php

namespace CMBcoreSeller\Integrations\Messaging\DTO;

enum MessageDirection: string
{
    case Inbound = 'inbound';   // buyer → shop
    case Outbound = 'outbound'; // shop → buyer
}
