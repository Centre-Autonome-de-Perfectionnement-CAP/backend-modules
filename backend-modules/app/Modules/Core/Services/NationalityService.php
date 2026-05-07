<?php

namespace App\Modules\Core\Services;

class NationalityService
{
    private static $nationalities = [
        // Afrique de l'Ouest
        'Bénin' => 'Béninoise',
        'Benin' => 'Béninoise',
        'Burkina Faso' => 'Burkinabè',
        'Cap-Vert' => 'Cap-verdienne',
        'Côte d\'Ivoire' => 'Ivoirienne',
        'Gambie' => 'Gambienne',
        'Ghana' => 'Ghanéenne',
        'Guinée' => 'Guinéenne',
        'Guinée-Bissau' => 'Bissau-guinéenne',
        'Liberia' => 'Libérienne',
        'Mali' => 'Malienne',
        'Mauritanie' => 'Mauritanienne',
        'Niger' => 'Nigérienne',
        'Nigeria' => 'Nigériane',
        'Sénégal' => 'Sénégalaise',
        'Sierra Leone' => 'Sierra-léonaise',
        'Togo' => 'Togolaise',
        
        // Afrique Centrale
        'Cameroun' => 'Camerounaise',
        'Centrafrique' => 'Centrafricaine',
        'Congo' => 'Congolaise',
        'Gabon' => 'Gabonaise',
        'Guinée équatoriale' => 'Équato-guinéenne',
        'RDC' => 'Congolaise',
        'République démocratique du Congo' => 'Congolaise',
        'Tchad' => 'Tchadienne',
        
        // Afrique de l'Est
        'Burundi' => 'Burundaise',
        'Comores' => 'Comorienne',
        'Djibouti' => 'Djiboutienne',
        'Érythrée' => 'Érythréenne',
        'Éthiopie' => 'Éthiopienne',
        'Kenya' => 'Kényane',
        'Madagascar' => 'Malgache',
        'Maurice' => 'Mauricienne',
        'Ouganda' => 'Ougandaise',
        'Rwanda' => 'Rwandaise',
        'Seychelles' => 'Seychelloise',
        'Somalie' => 'Somalienne',
        'Soudan' => 'Soudanaise',
        'Soudan du Sud' => 'Sud-soudanaise',
        'Tanzanie' => 'Tanzanienne',
        
        // Afrique Australe
        'Afrique du Sud' => 'Sud-africaine',
        'Angola' => 'Angolaise',
        'Botswana' => 'Botswanaise',
        'Lesotho' => 'Lesothane',
        'Malawi' => 'Malawienne',
        'Mozambique' => 'Mozambicaine',
        'Namibie' => 'Namibienne',
        'Swaziland' => 'Swazie',
        'Zambie' => 'Zambienne',
        'Zimbabwe' => 'Zimbabwéenne',
        
        // Afrique du Nord
        'Algérie' => 'Algérienne',
        'Égypte' => 'Égyptienne',
        'Libye' => 'Libyenne',
        'Maroc' => 'Marocaine',
        'Tunisie' => 'Tunisienne',
    ];

    public static function getNationality(string $country): string
    {
        return self::$nationalities[$country] ?? $country;
    }
}
