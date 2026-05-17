<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\Climate;

/**
 * Mappings code INSEE → nom pour les régions et départements français.
 * Évite un appel HTTP supplémentaire à chaque import.
 *
 * Référence : https://www.insee.fr/fr/information/2114819
 */
final class RegionsAndDepartments
{
    /** @var array<string, string> code région → libellé */
    private const REGIONS = [
        '01' => 'Guadeloupe',
        '02' => 'Martinique',
        '03' => 'Guyane',
        '04' => 'La Réunion',
        '06' => 'Mayotte',
        '11' => 'Île-de-France',
        '24' => 'Centre-Val de Loire',
        '27' => 'Bourgogne-Franche-Comté',
        '28' => 'Normandie',
        '32' => 'Hauts-de-France',
        '44' => 'Grand Est',
        '52' => 'Pays de la Loire',
        '53' => 'Bretagne',
        '75' => 'Nouvelle-Aquitaine',
        '76' => 'Occitanie',
        '84' => 'Auvergne-Rhône-Alpes',
        '93' => "Provence-Alpes-Côte d'Azur",
        '94' => 'Corse',
    ];

    /** @var array<string, string> code département → libellé */
    private const DEPARTMENTS = [
        '01' => 'Ain', '02' => 'Aisne', '03' => 'Allier', '04' => 'Alpes-de-Haute-Provence',
        '05' => 'Hautes-Alpes', '06' => 'Alpes-Maritimes', '07' => 'Ardèche', '08' => 'Ardennes',
        '09' => 'Ariège', '10' => 'Aube', '11' => 'Aude', '12' => 'Aveyron',
        '13' => 'Bouches-du-Rhône', '14' => 'Calvados', '15' => 'Cantal', '16' => 'Charente',
        '17' => 'Charente-Maritime', '18' => 'Cher', '19' => 'Corrèze', '2A' => 'Corse-du-Sud',
        '2B' => 'Haute-Corse', '21' => "Côte-d'Or", '22' => "Côtes-d'Armor", '23' => 'Creuse',
        '24' => 'Dordogne', '25' => 'Doubs', '26' => 'Drôme', '27' => 'Eure',
        '28' => 'Eure-et-Loir', '29' => 'Finistère', '30' => 'Gard', '31' => 'Haute-Garonne',
        '32' => 'Gers', '33' => 'Gironde', '34' => 'Hérault', '35' => 'Ille-et-Vilaine',
        '36' => 'Indre', '37' => 'Indre-et-Loire', '38' => 'Isère', '39' => 'Jura',
        '40' => 'Landes', '41' => 'Loir-et-Cher', '42' => 'Loire', '43' => 'Haute-Loire',
        '44' => 'Loire-Atlantique', '45' => 'Loiret', '46' => 'Lot', '47' => 'Lot-et-Garonne',
        '48' => 'Lozère', '49' => 'Maine-et-Loire', '50' => 'Manche', '51' => 'Marne',
        '52' => 'Haute-Marne', '53' => 'Mayenne', '54' => 'Meurthe-et-Moselle', '55' => 'Meuse',
        '56' => 'Morbihan', '57' => 'Moselle', '58' => 'Nièvre', '59' => 'Nord',
        '60' => 'Oise', '61' => 'Orne', '62' => 'Pas-de-Calais', '63' => 'Puy-de-Dôme',
        '64' => 'Pyrénées-Atlantiques', '65' => 'Hautes-Pyrénées', '66' => 'Pyrénées-Orientales',
        '67' => 'Bas-Rhin', '68' => 'Haut-Rhin', '69' => 'Rhône', '70' => 'Haute-Saône',
        '71' => 'Saône-et-Loire', '72' => 'Sarthe', '73' => 'Savoie', '74' => 'Haute-Savoie',
        '75' => 'Paris', '76' => 'Seine-Maritime', '77' => 'Seine-et-Marne', '78' => 'Yvelines',
        '79' => 'Deux-Sèvres', '80' => 'Somme', '81' => 'Tarn', '82' => 'Tarn-et-Garonne',
        '83' => 'Var', '84' => 'Vaucluse', '85' => 'Vendée', '86' => 'Vienne',
        '87' => 'Haute-Vienne', '88' => 'Vosges', '89' => 'Yonne', '90' => 'Territoire de Belfort',
        '91' => 'Essonne', '92' => 'Hauts-de-Seine', '93' => 'Seine-Saint-Denis', '94' => 'Val-de-Marne',
        '95' => "Val-d'Oise",
        '971' => 'Guadeloupe', '972' => 'Martinique', '973' => 'Guyane', '974' => 'La Réunion',
        '976' => 'Mayotte',
    ];

    /**
     * Climat inféré depuis le code région. Approximation pour l'import :
     * les climats fins (montagnard pour communes d'altitude, méditerranéen
     * altéré pour intérieur des terres) sont rectifiés lors de l'enrichissement
     * manuel des 500 communes critiques (cf. étape 13 du brief).
     */
    private const REGION_CLIMATE = [
        '11' => Climate::SemiContinental,        // Île-de-France
        '24' => Climate::OceaniqueAltere,        // Centre-Val de Loire
        '27' => Climate::SemiContinental,        // Bourgogne-Franche-Comté
        '28' => Climate::Oceanique,              // Normandie
        '32' => Climate::OceaniqueAltere,        // Hauts-de-France
        '44' => Climate::SemiContinental,        // Grand Est
        '52' => Climate::Oceanique,              // Pays de la Loire
        '53' => Climate::Oceanique,              // Bretagne
        '75' => Climate::Oceanique,              // Nouvelle-Aquitaine
        '76' => Climate::MediterraneenAltere,    // Occitanie (approximation)
        '84' => Climate::SemiContinental,        // Auvergne-Rhône-Alpes
        '93' => Climate::Mediterraneen,          // Provence-Alpes-Côte d'Azur
        '94' => Climate::Mediterraneen,          // Corse
    ];

    public function regionName(string $code): string
    {
        return self::REGIONS[$code] ?? 'Région inconnue';
    }

    public function departmentName(string $code): string
    {
        return self::DEPARTMENTS[$code] ?? 'Département inconnu';
    }

    public function climateForRegion(string $regionCode): Climate
    {
        return self::REGION_CLIMATE[$regionCode] ?? Climate::OceaniqueAltere;
    }
}
