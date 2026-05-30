<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columns = [
        'puppy_vaccination_package_price' => 'dog_vaccination_female_package_price',
        'adult_dog_vaccination_package_price' => 'dog_vaccination_female_package_price',
        'kitten_vaccination_package_price' => 'cat_vaccination_female_package_price',
        'adult_cat_vaccination_package_price' => 'cat_vaccination_female_package_price',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('clinic_specialized_packages')) {
            return;
        }

        Schema::table('clinic_specialized_packages', function (Blueprint $table) {
            foreach ($this->columns as $column => $after) {
                if (! Schema::hasColumn('clinic_specialized_packages', $column)) {
                    $fallbackAfter = str_contains($column, 'dog')
                        || str_contains($column, 'puppy')
                        ? 'dog_vaccination_package_price'
                        : 'cat_vaccination_package_price';
                    $afterColumn = Schema::hasColumn('clinic_specialized_packages', $after)
                        ? $after
                        : $fallbackAfter;

                    $table->decimal($column, 10, 2)->nullable()->after($afterColumn);
                }
            }
        });

        $legacyMap = [
            'puppy_vaccination_package_price' => ['dog_vaccination_male_package_price', 'dog_vaccination_package_price'],
            'adult_dog_vaccination_package_price' => ['dog_vaccination_female_package_price', 'dog_vaccination_package_price'],
            'kitten_vaccination_package_price' => ['cat_vaccination_male_package_price', 'cat_vaccination_package_price'],
            'adult_cat_vaccination_package_price' => ['cat_vaccination_female_package_price', 'cat_vaccination_package_price'],
        ];

        foreach ($legacyMap as $column => $fallbackColumns) {
            if (! Schema::hasColumn('clinic_specialized_packages', $column)) {
                continue;
            }

            foreach ($fallbackColumns as $fallbackColumn) {
                if (! Schema::hasColumn('clinic_specialized_packages', $fallbackColumn)) {
                    continue;
                }

                DB::table('clinic_specialized_packages')
                    ->whereNull($column)
                    ->whereNotNull($fallbackColumn)
                    ->update([$column => DB::raw($fallbackColumn)]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('clinic_specialized_packages')) {
            return;
        }

        Schema::table('clinic_specialized_packages', function (Blueprint $table) {
            foreach (array_keys($this->columns) as $column) {
                if (Schema::hasColumn('clinic_specialized_packages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
