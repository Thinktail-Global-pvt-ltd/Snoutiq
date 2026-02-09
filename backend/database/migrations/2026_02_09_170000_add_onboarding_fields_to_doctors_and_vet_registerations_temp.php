<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (!Schema::hasColumn('doctors', 'degree')) {
                $table->string('degree')->nullable();
            }
            if (!Schema::hasColumn('doctors', 'years_of_experience')) {
                $table->string('years_of_experience')->nullable();
            }
            if (!Schema::hasColumn('doctors', 'specialization_select_all_that_apply')) {
                $table->text('specialization_select_all_that_apply')->nullable();
            }
            if (!Schema::hasColumn('doctors', 'response_time_for_online_consults_day')) {
                $table->string('response_time_for_online_consults_day')->nullable();
            }
            if (!Schema::hasColumn('doctors', 'response_time_for_online_consults_night')) {
                $table->string('response_time_for_online_consults_night')->nullable();
            }
            if (!Schema::hasColumn('doctors', 'break_do_not_disturb_time_example_2_4_pm')) {
                $table->string('break_do_not_disturb_time_example_2_4_pm')->nullable();
            }
            if (!Schema::hasColumn('doctors', 'do_you_offer_a_free_follow_up_within_3_days_after_a_consulta')) {
                $table->string('do_you_offer_a_free_follow_up_within_3_days_after_a_consulta')->nullable();
            }
            if (!Schema::hasColumn('doctors', 'preferred_payout_method_upi_number_to_receive_payment')) {
                $table->string('preferred_payout_method_upi_number_to_receive_payment')->nullable();
            }
            if (!Schema::hasColumn('doctors', 'commission_and_agreement')) {
                $table->string('commission_and_agreement')->nullable();
            }
            if (!Schema::hasColumn('doctors', 'exported_from_excell')) {
                $table->string('exported_from_excell')->nullable();
            }
        });

        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            if (!Schema::hasColumn('vet_registerations_temp', 'exported_from_excell')) {
                $table->string('exported_from_excell')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (Schema::hasColumn('doctors', 'degree')) {
                $table->dropColumn('degree');
            }
            if (Schema::hasColumn('doctors', 'years_of_experience')) {
                $table->dropColumn('years_of_experience');
            }
            if (Schema::hasColumn('doctors', 'specialization_select_all_that_apply')) {
                $table->dropColumn('specialization_select_all_that_apply');
            }
            if (Schema::hasColumn('doctors', 'response_time_for_online_consults_day')) {
                $table->dropColumn('response_time_for_online_consults_day');
            }
            if (Schema::hasColumn('doctors', 'response_time_for_online_consults_night')) {
                $table->dropColumn('response_time_for_online_consults_night');
            }
            if (Schema::hasColumn('doctors', 'break_do_not_disturb_time_example_2_4_pm')) {
                $table->dropColumn('break_do_not_disturb_time_example_2_4_pm');
            }
            if (Schema::hasColumn('doctors', 'do_you_offer_a_free_follow_up_within_3_days_after_a_consulta')) {
                $table->dropColumn('do_you_offer_a_free_follow_up_within_3_days_after_a_consulta');
            }
            if (Schema::hasColumn('doctors', 'preferred_payout_method_upi_number_to_receive_payment')) {
                $table->dropColumn('preferred_payout_method_upi_number_to_receive_payment');
            }
            if (Schema::hasColumn('doctors', 'commission_and_agreement')) {
                $table->dropColumn('commission_and_agreement');
            }
            if (Schema::hasColumn('doctors', 'exported_from_excell')) {
                $table->dropColumn('exported_from_excell');
            }
        });

        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            if (Schema::hasColumn('vet_registerations_temp', 'exported_from_excell')) {
                $table->dropColumn('exported_from_excell');
            }
        });
    }
};
