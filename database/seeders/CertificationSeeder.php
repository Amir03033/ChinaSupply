<?php

namespace Database\Seeders;

use App\Models\Certification;
use Illuminate\Database\Seeder;

class CertificationSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'ISO9001' => 'ISO 9001 (quality management)',
            'ISO14001' => 'ISO 14001 (environmental management)',
            'ISO45001' => 'ISO 45001 (occupational health & safety)',
            'ISO13485' => 'ISO 13485 (medical devices)',
            'BSCI' => 'amfori BSCI (social compliance audit)',
            'SEDEX' => 'SEDEX / SMETA (ethical audit)',
            'FSC' => 'FSC (forest stewardship)',
            'GRS' => 'GRS (Global Recycled Standard)',
            'OEKO_TEX' => 'OEKO-TEX Standard 100',
            'GOTS' => 'GOTS (organic textiles)',
            'CE' => 'CE test report / Declaration of Conformity',
            'ROHS' => 'RoHS test report',
            'REACH' => 'REACH test report',
            'EN71' => 'EN 71 (toy safety) test report',
            'LFGB' => 'LFGB / EU food-contact test report',
            'FDA' => 'FDA registration',
            'UL' => 'UL certification',
            'BRCGS' => 'BRCGS (packaging / food safety)',
        ] as $code => $name) {
            Certification::firstOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
