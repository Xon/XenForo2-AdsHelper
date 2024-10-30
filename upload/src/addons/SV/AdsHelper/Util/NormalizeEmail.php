<?php

namespace SV\AdsHelper\Util;

use function mb_strrpos;
use function mb_strtolower;
use function mb_substr;
use function preg_replace;
use function str_replace;

abstract class NormalizeEmail
{
    protected static array $providers = [
        'gmail.com'      => [
            'stripDot' => true,
        ],
        'googlemail.com' => [
            'stripDot' => true,
            'alias'    => 'gmail.com',
        ],
        'hotmail.com'    => [],
        'live.com'       => [],
        'outlook.com'    => [],
        'fastmail.com'   => [],
        'fastmail.fm'    => [],
    ];

    public static function normalize(?string $email): ?string
    {
        if ($email === '' || $email === null)
        {
            return null;
        }

        $email = mb_strtolower($email);
        $lastAtPos = mb_strrpos($email, '@');
        if ($lastAtPos === false)
        {
            return false;
        }

        $host = mb_substr($email, $lastAtPos + 1);
        $localPart = mb_substr($email, 0, $lastAtPos);

        $provider = self::$providers[$host] ?? null;
        if ($provider !== null)
        {
            $localPart = preg_replace('/\+.*$/u', '', $localPart);
            if ($provider['stripDot'] ?? false)
            {
                $localPart = str_replace('.', '', $localPart);
            }
            $host = $provider['alias'] ?? $host;
        }

        return $localPart . '@' . $host;
    }
}