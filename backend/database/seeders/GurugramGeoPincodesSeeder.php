<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GurugramGeoPincodesSeeder extends Seeder
{
    public function run(): void
    {
        // NOTE: Coords are approximate centroids to get you productive now.
        // If you have authoritative lat/lon, just replace them here and re-run.
        $pins = [
            // ---- Urban core 1220xx ----
            ['pincode'=>'122001','label'=>'Old Gurgaon / Civil Lines','lat'=>28.4640,'lon'=>77.0200],
            ['pincode'=>'122002','label'=>'DLF Ph 1–3 / MG Road','lat'=>28.4780,'lon'=>77.0950],
            ['pincode'=>'122003','label'=>'Sec 31–45 / IFFCO Chowk','lat'=>28.4710,'lon'=>77.0330],
            ['pincode'=>'122004','label'=>'HUDA City / Sec 41–46','lat'=>28.4585,'lon'=>77.0730],
            ['pincode'=>'122005','label'=>'Rajiv Chowk / NH48','lat'=>28.4590,'lon'=>77.0440],
            ['pincode'=>'122006','label'=>'Palam Vihar / Sec 22–23','lat'=>28.5210,'lon'=>76.9900],
            ['pincode'=>'122007','label'=>'Sohna Rd / Badshahpur','lat'=>28.3675,'lon'=>77.0650],
            ['pincode'=>'122008','label'=>'Golf Course Rd (56–57)','lat'=>28.4300,'lon'=>77.1050],
            ['pincode'=>'122009','label'=>'Udyog Vihar / Cyber Hub','lat'=>28.5040,'lon'=>77.0900],
            ['pincode'=>'122010','label'=>'Manesar / IMT','lat'=>28.3570,'lon'=>76.9380],
            ['pincode'=>'122011','label'=>'Dwarka Expy / Sec 110','lat'=>28.5270,'lon'=>76.9955],
            ['pincode'=>'122012','label'=>'Sohna Town','lat'=>28.2460,'lon'=>77.0640],
            ['pincode'=>'122013','label'=>'Bhondsi / Ghamroj','lat'=>28.3010,'lon'=>77.0440],
            ['pincode'=>'122014','label'=>'Pataudi','lat'=>28.3230,'lon'=>76.7830],
            ['pincode'=>'122015','label'=>'Farrukhnagar (urban fringe)','lat'=>28.4560,'lon'=>76.8240],
            ['pincode'=>'122016','label'=>'Wazirabad / Sushant Lok','lat'=>28.4590,'lon'=>77.0820],
            ['pincode'=>'122017','label'=>'Sector 47 / Subhash Chowk','lat'=>28.4310,'lon'=>77.0390],
            ['pincode'=>'122018','label'=>'New Gurgaon / Sec 81–95','lat'=>28.3900,'lon'=>76.9930],

            // ---- Rural / 1221xx frequently used around Gurugram district ----
            ['pincode'=>'122101','label'=>'Dhankot / Basai','lat'=>28.4850,'lon'=>76.9700],
            ['pincode'=>'122102','label'=>'Badshahpur (rural)','lat'=>28.4010,'lon'=>77.0520],
            ['pincode'=>'122103','label'=>'Garhi Harsaru / Kherki Daula','lat'=>28.4420,'lon'=>76.9840],
            ['pincode'=>'122104','label'=>'Farrukhnagar','lat'=>28.4570,'lon'=>76.8280],
            ['pincode'=>'122105','label'=>'Pataudi (rural)','lat'=>28.3250,'lon'=>76.7800],
            ['pincode'=>'122108','label'=>'Sultanpur / CRPF / Bird Sanctuary','lat'=>28.4650,'lon'=>76.8560],
        ];

        $now = now();
        foreach ($pins as &$p) {
            $p['city']   = 'Gurugram';
            $p['state']  = 'Haryana';
            $p['active'] = 1;
            $p['created_at'] = $now;
            $p['updated_at'] = $now;
        }

        DB::table('geo_pincodes')->upsert(
            $pins,
            ['pincode'],                                  // unique key
            ['label','lat','lon','city','state','active','updated_at'] // columns to update
        );

        // helpful index if missing (safe if already exists)
        try {
            DB::statement("CREATE INDEX IF NOT EXISTS geo_pincodes_city_active_idx ON geo_pincodes (city, active)");
        } catch (\Throwable $e) { /* ignore */ }
    }
}
