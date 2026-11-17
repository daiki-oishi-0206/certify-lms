<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Q&Aスレッドの解決状態を表すEnum。
 */
enum QaThreadStatus: string
{
    case Unresolved = 'unresolved';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Unresolved => '未解決',
            self::Resolved => '解決済み',
        };
    }
}
