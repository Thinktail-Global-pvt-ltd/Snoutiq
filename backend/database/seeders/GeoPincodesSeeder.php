<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeoPincodesSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['pincode'=>'122001','label'=>'Old Gurgaon / Civil Lines','lat'=>28.464,'lon'=>77.020,'city'=>'Gurugram','state'=>'Haryana','active'=>true],
            ['pincode'=>'122002','label'=>'DLF Phase 1–3 / MG Road','lat'=>28.478,'lon'=>77.090,'city'=>'Gurugram','state'=>'Haryana','active'=>true],
            ['pincode'=>'122003','label'=>'Sector 14–17 / IFFCO Chowk','lat'=>28.471,'lon'=>77.033,'city'=>'Gurugram','state'=>'Haryana','active'=>true],
            ['pincode'=>'122004','label'=>'Sector 31–45 / Huda City Ctr','lat'=>28.448,'lon'=>77.052,'city'=>'Gurugram','state'=>'Haryana','active'=>true],
            ['pincode'=>'122005','label'=>'Rajiv Chowk / NH48','lat'=>28.438,'lon'=>77.021,'city'=>'Gurugram','state'=>'Haryana','active'=>true],
            ['pincode'=>'122006','label'=>'Palam Vihar / Sec 22-23','lat'=>28.511,'lon'=>76.995,'city'=>'Gurugram','state'=>'Haryana','active'=>true],
            ['pincode'=>'122007','label'=>'Sohna Road / Badshahpur','lat'=>28.389,'lon'=>77.044,'city'=>'Gurugram','state'=>'Haryana','active'=>true],
            ['pincode'=>'122008','label'=>'Sec 56–57 / Golf Course Rd','lat'=>28.437,'lon'=>77.101,'city'=>'Gurugram','state'=>'Haryana','active'=>true],
            ['pincode'=>'122009','label'=>'Udyog Vihar / Cyber Hub','lat'=>28.503,'lon'=>77.092,'city'=>'Gurugram','state'=>'Haryana','active'=>true],
            ['pincode'=>'122010','label'=>'Manesar / IMT','lat'=>28.357,'lon'=>76.938,'city'=>'Gurugram','state'=>'Haryana','active'=>true],
            ['pincode'=>'122011','label'=>'Golf Course Ext. / Sec 65','lat'=>28.402,'lon'=>77.093,'city'=>'Gurugram','state'=>'Haryana','active'=>true],
            ['pincode'=>'122018','label'=>'Dwarka Expy / Sec 110','lat'=>28.524,'lon'=>77.010,'city'=>'Gurugram','state'=>'Haryana','active'=>true],

            // extend district coverage as needed...
            ['pincode'=>'122101','label'=>'Sohna Town','lat'=>28.246,'lon'=>77.064,'city'=>'Gurugram','state'=>'Haryana','active'=>true],
            ['pincode'=>'122102','label'=>'Bhondsi / Ghamroj','lat'=>28.312,'lon'=>77.105,'city'=>'Gurugram','state'=>'Haryana','active'=>true],
            ['pincode'=>'122103','label'=>'Pataudi','lat'=>28.323,'lon'=>76.783,'city'=>'Gurugram','state'=>'Haryana','active'=>true],
            ['pincode'=>'122104','label'=>'Farrukhnagar','lat'=>28.456,'lon'=>76.824,'city'=>'Gurugram','state'=>'Haryana','active'=>true],
        ];

        // upsert by pincode
        $now = now();
        foreach ($rows as &$r) { $r['created_at']=$now; $r['updated_at']=$now; }

        DB::table('geo_pincodes')->upsert(
            $rows,
            ['pincode'],
            ['label','lat','lon','city','state','active','updated_at']
        );
    }
}
