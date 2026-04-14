<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DoctorCsvExportController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $doctorColumns = $this->resolveColumns(
            Schema::getColumnListing('doctors'),
            [
                'id',
                'vet_registeration_id',
                'doctor_name',
                'staff_role',
                'doctor_email',
                'doctor_mobile',
                'doctor_license',
                'doctor_status',
                'toggle_availability',
                'doctors_price',
                'video_day_rate',
                'video_night_rate',
                'degree',
                'years_of_experience',
                'specialization_select_all_that_apply',
                'languages_spoken',
                'response_time_for_online_consults_day',
                'response_time_for_online_consults_night',
                'break_do_not_disturb_time_example_2_4_pm',
                'do_you_offer_a_free_follow_up_within_3_days_after_a_consulta',
                'preferred_payout_method_upi_number_to_receive_payment',
                'payout_preference',
                'commission_and_agreement',
                'exported_from_excell',
                'created_at',
                'updated_at',
            ],
            ['doctor_image_blob']
        );

        $clinicColumns = $this->resolveColumns(
            Schema::getColumnListing('vet_registerations_temp'),
            [
                'id',
                'public_id',
                'name',
                'slug',
                'status',
                'owner_user_id',
                'draft_created_by_user_id',
                'mobile',
                'email',
                'city',
                'pincode',
                'address',
                'license_no',
                'license_document',
                'chat_price',
                'bio',
                'website_title',
                'website_subtitle',
                'website_about',
                'google_review_url',
                'hospital_profile',
                'clinic_profile',
                'employee_id',
                'place_id',
                'business_status',
                'formatted_address',
                'lat',
                'lng',
                'rating',
                'user_ratings_total',
                'exported_from_excell',
                'claimed_at',
                'created_at',
                'updated_at',
            ],
            ['password', 'api_token_hash', 'claim_token']
        );

        $selectColumns = [];
        foreach ($doctorColumns as $column) {
            $selectColumns[] = DB::raw("d.`{$column}` as doctor_{$column}");
        }
        foreach ($clinicColumns as $column) {
            $selectColumns[] = DB::raw("v.`{$column}` as clinic_{$column}");
        }

        $query = DB::table('doctors as d')
            ->leftJoin('vet_registerations_temp as v', 'v.id', '=', 'd.vet_registeration_id')
            ->select($selectColumns)
            ->orderBy('d.id');

        if ($request->filled('clinic_id')) {
            $query->where('d.vet_registeration_id', (int) $request->query('clinic_id'));
        }

        if ($request->filled('doctor_status') && in_array('doctor_status', $doctorColumns, true)) {
            $query->where('d.doctor_status', (string) $request->query('doctor_status'));
        }

        $headers = array_merge(
            array_map(static fn (string $column) => "doctor_{$column}", $doctorColumns),
            array_map(static fn (string $column) => "clinic_{$column}", $clinicColumns)
        );

        $rows = $query->cursor();
        $filename = 'doctors-vet-registrations-dump-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows, $headers) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);

            foreach ($rows as $row) {
                $csvRow = [];
                foreach ($headers as $header) {
                    $csvRow[] = $this->normalizeValue($row->{$header} ?? null);
                }
                fputcsv($output, $csvRow);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function resolveColumns(array $availableColumns, array $priorityOrder, array $excludeColumns = []): array
    {
        $available = array_values(array_unique($availableColumns));
        $excluded = array_flip($excludeColumns);
        $result = [];

        foreach ($priorityOrder as $column) {
            if (in_array($column, $available, true) && ! $this->shouldExcludeColumn($column, $excluded)) {
                $result[] = $column;
            }
        }

        foreach ($available as $column) {
            if (! $this->shouldExcludeColumn($column, $excluded) && ! in_array($column, $result, true)) {
                $result[] = $column;
            }
        }

        return $result;
    }

    private function shouldExcludeColumn(string $column, array $explicitExclusions): bool
    {
        if (isset($explicitExclusions[$column])) {
            return true;
        }

        $normalized = strtolower($column);
        if (str_contains($normalized, 'password') || str_contains($normalized, 'token')) {
            return true;
        }

        return str_ends_with($normalized, '_blob');
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    }
}
