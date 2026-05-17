<?php

declare(strict_types=1);

namespace App\Enum;

enum Climate: string
{
    case Oceanique = 'oceanique';
    case OceaniqueAltere = 'oceanique_altere';
    case SemiContinental = 'semi_continental';
    case Montagnard = 'montagnard';
    case Mediterraneen = 'mediterraneen';
    case MediterraneenAltere = 'mediterraneen_altere';
    case MontagnardSud = 'montagnard_sud';

    public function label(): string
    {
        return match ($this) {
            self::Oceanique => 'océanique',
            self::OceaniqueAltere => 'océanique altéré',
            self::SemiContinental => 'semi-continental',
            self::Montagnard => 'montagnard',
            self::Mediterraneen => 'méditerranéen',
            self::MediterraneenAltere => 'méditerranéen altéré',
            self::MontagnardSud => 'montagnard du sud',
        };
    }
}
