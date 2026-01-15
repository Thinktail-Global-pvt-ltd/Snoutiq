<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_pets') || !Schema::hasTable('pets')) {
            return;
        }

        if (!Schema::hasColumn('pets', 'user_id') || !Schema::hasColumn('pets', 'name')) {
            return;
        }

        $petColumns = [
            'id' => Schema::hasColumn('pets', 'id'),
            'user_id' => Schema::hasColumn('pets', 'user_id'),
            'name' => Schema::hasColumn('pets', 'name'),
            'breed' => Schema::hasColumn('pets', 'breed'),
            'pet_type' => Schema::hasColumn('pets', 'pet_type'),
            'type' => Schema::hasColumn('pets', 'type'),
            'pet_gender' => Schema::hasColumn('pets', 'pet_gender'),
            'gender' => Schema::hasColumn('pets', 'gender'),
            'pet_dob' => Schema::hasColumn('pets', 'pet_dob'),
            'dob' => Schema::hasColumn('pets', 'dob'),
            'pet_age' => Schema::hasColumn('pets', 'pet_age'),
            'pet_age_months' => Schema::hasColumn('pets', 'pet_age_months'),
            'pet_doc1' => Schema::hasColumn('pets', 'pet_doc1'),
            'pic_link' => Schema::hasColumn('pets', 'pic_link'),
            'weight' => Schema::hasColumn('pets', 'weight'),
            'temprature' => Schema::hasColumn('pets', 'temprature'),
            'vaccenated_yes_no' => Schema::hasColumn('pets', 'vaccenated_yes_no'),
            'last_vaccenated_date' => Schema::hasColumn('pets', 'last_vaccenated_date'),
            'vaccination_date' => Schema::hasColumn('pets', 'vaccination_date'),
            'medical_history' => Schema::hasColumn('pets', 'medical_history'),
            'vaccination_log' => Schema::hasColumn('pets', 'vaccination_log'),
            'created_at' => Schema::hasColumn('pets', 'created_at'),
            'updated_at' => Schema::hasColumn('pets', 'updated_at'),
        ];

        $userColumns = [
            'weight' => Schema::hasColumn('user_pets', 'weight'),
            'temprature' => Schema::hasColumn('user_pets', 'temprature'),
            'vaccenated_yes_no' => Schema::hasColumn('user_pets', 'vaccenated_yes_no'),
            'last_vaccenated_date' => Schema::hasColumn('user_pets', 'last_vaccenated_date'),
            'pic_link' => Schema::hasColumn('user_pets', 'pic_link'),
            'medical_history' => Schema::hasColumn('user_pets', 'medical_history'),
            'vaccination_log' => Schema::hasColumn('user_pets', 'vaccination_log'),
        ];

        $select = [
            'id',
            'user_id',
            'name',
            'type',
            'breed',
            'dob',
            'gender',
            'created_at',
            'updated_at',
        ];

        if ($userColumns['pic_link']) {
            $select[] = 'pic_link';
        }
        if ($userColumns['medical_history']) {
            $select[] = 'medical_history';
        }
        if ($userColumns['vaccination_log']) {
            $select[] = 'vaccination_log';
        }
        if ($userColumns['weight']) {
            $select[] = 'weight';
        }
        if ($userColumns['temprature']) {
            $select[] = 'temprature';
        }
        if ($userColumns['vaccenated_yes_no']) {
            $select[] = 'vaccenated_yes_no';
        }
        if ($userColumns['last_vaccenated_date']) {
            $select[] = 'last_vaccenated_date';
        }

        $now = Carbon::now();

        DB::table('user_pets')
            ->select($select)
            ->orderBy('id')
            ->chunkById(250, function ($rows) use ($petColumns, $userColumns, $now) {
                $inserts = [];

                foreach ($rows as $row) {
                    $data = [];

                    if ($petColumns['id']) {
                        $data['id'] = $row->id;
                    }
                    if ($petColumns['user_id']) {
                        $data['user_id'] = $row->user_id;
                    }
                    if ($petColumns['name']) {
                        $data['name'] = $row->name;
                    }
                    if ($petColumns['breed']) {
                        $data['breed'] = $row->breed;
                    }

                    $type = $row->type ?? null;
                    if ($type !== null && $type !== '') {
                        if ($petColumns['pet_type']) {
                            $data['pet_type'] = $type;
                        }
                        if ($petColumns['type']) {
                            $data['type'] = $type;
                        }
                    }

                    $gender = $row->gender ?? null;
                    if ($gender !== null && $gender !== '') {
                        if ($petColumns['pet_gender']) {
                            $data['pet_gender'] = $gender;
                        }
                        if ($petColumns['gender']) {
                            $data['gender'] = $gender;
                        }
                    }

                    $dobRaw = $row->dob ?? null;
                    $dobValue = null;
                    $ageYears = null;
                    $ageMonths = null;

                    if (is_string($dobRaw)) {
                        $dobRaw = trim($dobRaw);
                    }

                    if (!empty($dobRaw) && is_string($dobRaw) && strtolower($dobRaw) !== 'unknown' && $dobRaw !== '0000-00-00') {
                        try {
                            $dob = Carbon::parse($dobRaw);
                            $dobValue = $dob->toDateString();

                            $totalMonths = $dob->diffInMonths($now);
                            $ageYears = intdiv($totalMonths, 12);
                            $ageMonths = $totalMonths % 12;
                        } catch (\Throwable $e) {
                            $dobValue = null;
                        }
                    }

                    if ($dobValue === null && is_string($dobRaw) && $dobRaw !== '') {
                        $dobValue = $dobRaw;
                    }

                    if ($dobValue !== null) {
                        if ($petColumns['pet_dob']) {
                            $data['pet_dob'] = $dobValue;
                        }
                        if ($petColumns['dob']) {
                            $data['dob'] = $dobValue;
                        }
                    }

                    if ($petColumns['pet_age']) {
                        $data['pet_age'] = $ageYears;
                    }
                    if ($petColumns['pet_age_months']) {
                        $data['pet_age_months'] = $ageMonths;
                    }

                    if ($userColumns['pic_link']) {
                        $picLink = $row->pic_link ?? null;
                        if ($picLink !== null && $picLink !== '') {
                            if ($petColumns['pet_doc1']) {
                                $data['pet_doc1'] = $picLink;
                            }
                            if ($petColumns['pic_link']) {
                                $data['pic_link'] = $picLink;
                            }
                        }
                    }

                    if ($userColumns['medical_history'] && $petColumns['medical_history']) {
                        $data['medical_history'] = $row->medical_history;
                    }
                    if ($userColumns['vaccination_log'] && $petColumns['vaccination_log']) {
                        $data['vaccination_log'] = $row->vaccination_log;
                    }

                    if ($userColumns['weight'] && $petColumns['weight']) {
                        $data['weight'] = $row->weight;
                    }
                    if ($userColumns['temprature'] && $petColumns['temprature']) {
                        $data['temprature'] = $row->temprature;
                    }
                    if ($userColumns['vaccenated_yes_no'] && $petColumns['vaccenated_yes_no']) {
                        $data['vaccenated_yes_no'] = (bool) $row->vaccenated_yes_no;
                    }
                    if ($userColumns['last_vaccenated_date'] && $petColumns['last_vaccenated_date']) {
                        $data['last_vaccenated_date'] = $row->last_vaccenated_date;
                    }
                    if ($userColumns['last_vaccenated_date'] && $petColumns['vaccination_date'] && !empty($row->last_vaccenated_date)) {
                        $data['vaccination_date'] = $row->last_vaccenated_date;
                    }

                    if ($petColumns['created_at']) {
                        $data['created_at'] = $row->created_at ?? $now;
                    }
                    if ($petColumns['updated_at']) {
                        $data['updated_at'] = $row->updated_at ?? $now;
                    }

                    if ($data) {
                        $inserts[] = $data;
                    }
                }

                if ($inserts) {
                    DB::table('pets')->insertOrIgnore($inserts);
                }
            });
    }

    public function down(): void
    {
        // No-op: data migrations are not safely reversible.
    }
};
