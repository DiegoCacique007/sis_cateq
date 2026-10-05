<?php

namespace App\Enums;

enum ChatbotRuleResult: string
{
    case Allowed = 'ALLOWED';
    case Denied = 'DENIED';
    case MissingContext = 'MISSING_CONTEXT';
    case Ambiguous = 'AMBIGUOUS';
    case Unsupported = 'UNSUPPORTED';
    case Escalate = 'ESCALATE';
}
