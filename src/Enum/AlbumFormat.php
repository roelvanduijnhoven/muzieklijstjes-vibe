<?php

namespace App\Enum;

enum AlbumFormat: string
{
    case CD = 'CD';
    case DVD = 'DVD';
    case VIDEO = 'VIDEO';
    case LP = 'LP'; // Added LP as it might be in "materiaal" if it's char(3) and legacy data might have it, or "LP" might be represented differently. Legacy schema says char(3).
    case UNKNOWN = 'UNK';

    public function label(): string
    {
        return match($this) {
            self::CD => 'CD',
            self::DVD => 'DVD',
            self::VIDEO => 'Video',
            self::LP => 'LP',
            self::UNKNOWN => 'Unknown',
        };
    }

    public static function fromLegacyCode(string $code): ?self
    {
        $code = strtoupper(trim($code));
        return match($code) {
            'CD' => self::CD,
            'DVD' => self::DVD,
            'VID' => self::VIDEO, // Assuming 'VID' or similar for video if char(3)
            'LP' => self::LP,
            default => null, 
        };
    }
}

