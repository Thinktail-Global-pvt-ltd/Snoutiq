<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PublicPrescriptionDiagnosisUsersController extends Controller
{
    public function __invoke(Request $request)
    {
        $hasRequiredTables = Schema::hasTable('prescriptions') && Schema::hasTable('users');
        $diagnosisColumn = $this->diagnosisColumn();
        $hasRequiredColumns = $hasRequiredTables
            && Schema::hasColumn('prescriptions', 'user_id')
            && $diagnosisColumn !== null;

        $prescriptions = collect();
        $metrics = [
            'prescriptions' => 0,
            'unique_users' => 0,
        ];

        if ($hasRequiredColumns) {
            $query = $this->baseQuery($diagnosisColumn);

            $metrics = [
                'prescriptions' => (clone $query)->count(),
                'unique_users' => (clone $query)->distinct('user_id')->count('user_id'),
            ];

            $prescriptions = $query
                ->orderByDesc(Schema::hasColumn('prescriptions', 'created_at') ? 'created_at' : 'id')
                ->orderByDesc('id')
                ->paginate(100)
                ->withQueryString();
        }

        return view('public.prescription-diagnosis-users', [
            'prescriptions' => $prescriptions,
            'metrics' => $metrics,
            'hasRequiredTables' => $hasRequiredTables,
            'hasRequiredColumns' => $hasRequiredColumns,
            'diagnosisColumn' => $diagnosisColumn,
        ]);
    }

    private function baseQuery(string $diagnosisColumn)
    {
        $query = Prescription::query()
            ->select($this->prescriptionColumns($diagnosisColumn))
            ->whereNotNull('user_id')
            ->whereNotNull($diagnosisColumn)
            ->where($diagnosisColumn, '!=', '')
            ->whereHas('user')
            ->with([
                'user' => fn ($query) => $query->select($this->userColumns()),
            ]);

        if (Schema::hasTable('pets') && Schema::hasColumn('prescriptions', 'pet_id')) {
            $query->with(['pet' => fn ($query) => $query->select($this->petColumns())]);
        }

        if (Schema::hasTable('doctors') && Schema::hasColumn('prescriptions', 'doctor_id')) {
            $query->with(['doctor' => fn ($query) => $query->select($this->doctorColumns())]);
        }

        return $query;
    }

    private function diagnosisColumn(): ?string
    {
        if (! Schema::hasTable('prescriptions')) {
            return null;
        }

        foreach (['diagnosis', 'diagnosys'] as $column) {
            if (Schema::hasColumn('prescriptions', $column)) {
                return $column;
            }
        }

        return null;
    }

    private function prescriptionColumns(string $diagnosisColumn): array
    {
        $columns = ['id', 'user_id', $diagnosisColumn];
        foreach ([
            'pet_id',
            'doctor_id',
            'call_session',
            'disease_name',
            'diagnosis_status',
            'created_at',
        ] as $column) {
            if (Schema::hasColumn('prescriptions', $column)) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
    }

    private function userColumns(): array
    {
        $columns = ['id'];
        foreach (['name', 'email', 'phone', 'city'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
    }

    private function petColumns(): array
    {
        $columns = ['id'];
        foreach (['user_id', 'name', 'breed', 'pet_type', 'type', 'pet_age', 'pet_gender'] as $column) {
            if (Schema::hasColumn('pets', $column)) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
    }

    private function doctorColumns(): array
    {
        $columns = ['id'];
        foreach (['doctor_name', 'doctor_email', 'doctor_mobile', 'degree'] as $column) {
            if (Schema::hasColumn('doctors', $column)) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
    }
}
