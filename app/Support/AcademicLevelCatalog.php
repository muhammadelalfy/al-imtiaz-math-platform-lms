<?php

namespace App\Support;

final class AcademicLevelCatalog
{
    /** @var array<int, string> */
    private const GRADES = [
        'الأول الإعدادي',
        'الثاني الإعدادي',
        'الثالث الإعدادي',
        'الأول الثانوي',
        'الثاني الثانوي',
        'الثالث الثانوي رياضيات',
        'الثالث الثانوي إحصاء',
    ];

    /** @var array<int, string> */
    private const GROUP_NAMES = [
        'المجموعة الأولى',
        'المجموعة الثانية',
        'المجموعة الثالثة',
    ];

    /** @return array<int, string> */
    public static function grades(): array
    {
        return self::GRADES;
    }

    /** @return array<int, string> */
    public static function groupNames(): array
    {
        return self::GROUP_NAMES;
    }

    public static function gradeForSeed(int $sequence): string
    {
        return self::GRADES[$sequence % count(self::GRADES)];
    }

    public static function groupForSeed(int $sequence): string
    {
        return self::GROUP_NAMES[intdiv($sequence, count(self::GRADES)) % count(self::GROUP_NAMES)];
    }
}
