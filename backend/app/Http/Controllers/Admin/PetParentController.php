<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallSession;
use App\Models\Consultation;
use App\Models\GroomerBooking;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserObservation;
use App\Models\UserPet;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PetParentController extends Controller
{
    private const PET_PARENT_ROLES = ['pet_owner', 'pet', 'patient', 'user'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $petParentsQuery = User::query()
            ->when(
                Schema::hasColumn('users', 'role'),
                function ($query) {
                    $query->where(function ($inner) {
                        $inner->whereIn('role', self::PET_PARENT_ROLES)
                              ->orWhereNull('role');
                    });
                }
            );

        if ($search !== '') {
            $petParentsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $petParents = $petParentsQuery
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $selectedId = (int) $request->input('user_id', 0);
        if (! $selectedId && $petParents->count() > 0) {
            $selectedId = (int) $petParents->first()->id;
        }

        $selected = $selectedId ? $this->buildPetParentProfile($selectedId) : null;

        return view('admin.pet-parents.index', [
            'petParents' => $petParents,
            'selectedPetParent' => $selected,
            'search' => $search,
        ]);
    }

    private function buildPetParentProfile(int $userId): ?array
    {
        $user = User::with(['qrScanner'])->find($userId);

        if (! $user) {
            return null;
        }

        $userPets = UserPet::where('user_id', $user->id)->orderByDesc('created_at')->get();
        $pets = $user->pets()->orderByDesc('created_at')->get();

        $callSessions = CallSession::with(['doctor', 'payment', 'qrScanner'])
            ->where('patient_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $consultations = Consultation::with(['doctor', 'pet'])
            ->where('user_id', $user->id)
            ->orderByDesc('start_time')
            ->limit(50)
            ->get();

        $transactions = Transaction::with(['doctor', 'clinic'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $bookings = DB::table('bookings')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $groomerBookings = GroomerBooking::with(['groomerEmployee'])
            ->where('customer_type', 'online')
            ->where('customer_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $observations = UserObservation::where('user_id', $user->id)
            ->orderByDesc('observed_at')
            ->limit(25)
            ->get();

        $medicalRecords = MedicalRecord::with(['doctor', 'clinic'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();

        $prescriptions = Prescription::with(['doctor'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();

        $aiChats = DB::table('user_ai_chats')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();

        $aiChatMessages = DB::table('user_ai_chat_histories')
            ->where('user_id', $user->id)
            ->count();

        $supportTickets = DB::table('customer_tickets')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $metrics = $this->buildMetrics(
            $user,
            $callSessions,
            $consultations,
            $transactions,
            $pets,
            $userPets,
            $bookings,
            $groomerBookings,
            $observations
        );

        return [
            'user' => $user,
            'pets' => $pets,
            'userPets' => $userPets,
            'callSessions' => $callSessions,
            'consultations' => $consultations,
            'transactions' => $transactions,
            'bookings' => $bookings,
            'groomerBookings' => $groomerBookings,
            'observations' => $observations,
            'medicalRecords' => $medicalRecords,
            'prescriptions' => $prescriptions,
            'aiChats' => $aiChats,
            'aiChatMessages' => $aiChatMessages,
            'supportTickets' => $supportTickets,
            'lifecycle' => $this->buildLifecycleTimeline($user, [
                'pets' => $pets,
                'userPets' => $userPets,
                'callSessions' => $callSessions,
                'consultations' => $consultations,
                'transactions' => $transactions,
                'bookings' => $bookings,
                'groomerBookings' => $groomerBookings,
                'observations' => $observations,
                'aiChats' => $aiChats,
                'prescriptions' => $prescriptions,
            ]),
            'metrics' => $metrics,
        ];
    }

    private function buildLifecycleTimeline(User $user, array $data): array
    {
        $events = collect();

        $this->pushEvent($events, $this->event(
            $user->created_at,
            'Account created',
            'identity',
            'Pet parent profile created in SnoutIQ.'
        ));

        $this->pushEvent($events, $this->event(
            $user->phone_verified_at ?? null,
            'Phone verified',
            'identity',
            'User verified their phone number.'
        ));

        foreach ($data['userPets'] as $pet) {
            $this->pushEvent($events, $this->event(
                $pet->created_at,
                'Pet added (legacy)',
                'pet',
                sprintf('%s • %s', $pet->name ?? 'Pet', $pet->breed ?? $pet->type ?? '')
            ));
        }

        foreach ($data['pets'] as $pet) {
            $this->pushEvent($events, $this->event(
                $pet->created_at,
                'Pet added',
                'pet',
                sprintf('%s • %s', $pet->name ?? 'Pet', $pet->breed ?? '')
            ));
        }

        foreach ($data['callSessions'] as $session) {
            $this->pushEvent($events, $this->event(
                $session->created_at,
                'Call session created',
                'call',
                sprintf(
                    'Status: %s | Doctor: %s',
                    strtoupper($session->status ?? 'pending'),
                    $session->doctor?->doctor_name ?? 'Unassigned'
                ),
                [
                    'payment_status' => $session->payment_status ?? 'unpaid',
                    'channel' => $session->channel_name ?? null,
                ]
            ));

            $this->pushEvent($events, $this->event(
                $session->accepted_at,
                'Doctor accepted call',
                'call',
                sprintf('Doctor %s accepted the session.', $session->doctor?->doctor_name ?? '#'.$session->doctor_id)
            ));

            $this->pushEvent($events, $this->event(
                $session->started_at,
                'Call started',
                'call',
                sprintf('Call started (%s).', $session->payment_status === 'paid' ? 'paid' : 'unpaid')
            ));

            $this->pushEvent($events, $this->event(
                $session->ended_at,
                'Call ended',
                'call',
                sprintf(
                    'Duration %ss • %s',
                    $session->duration_seconds ?? 0,
                    $session->payment_status === 'paid' ? 'paid' : 'unpaid'
                )
            ));
        }

        foreach ($data['consultations'] as $consultation) {
            $this->pushEvent($events, $this->event(
                $consultation->start_time,
                'Consultation scheduled',
                'consultation',
                sprintf(
                    'Status: %s | Doctor: %s | Pet: %s',
                    strtoupper($consultation->status ?? 'scheduled'),
                    $consultation->doctor?->doctor_name ?? '#'.$consultation->doctor_id,
                    $consultation->pet?->name ?? '#'.$consultation->pet_id
                )
            ));

            $this->pushEvent($events, $this->event(
                $consultation->end_time,
                'Consultation completed',
                'consultation',
                sprintf(
                    'Mode: %s | Follow-up: %s',
                    strtoupper($consultation->mode ?? 'video'),
                    $consultation->follow_up_after_days ? $consultation->follow_up_after_days.' days' : 'n/a'
                )
            ));
        }

        foreach ($data['prescriptions'] ?? [] as $prescription) {
            $this->pushEvent($events, $this->event(
                $prescription->created_at,
                'Prescription issued',
                'consultation',
                sprintf(
                    'Doctor: %s%s',
                    $prescription->doctor?->doctor_name ?? ('#'.$prescription->doctor_id),
                    $prescription->follow_up_date ? ' • Follow-up: '.$prescription->follow_up_date : ''
                )
            ));
        }

        foreach ($data['transactions'] as $transaction) {
            $amount = $transaction->amount_paise ? $transaction->amount_paise / 100 : 0;
            $this->pushEvent($events, $this->event(
                $transaction->created_at,
                'Payment / Transaction',
                'payment',
                sprintf(
                    '₹%s • %s',
                    number_format($amount, 2),
                    strtoupper($transaction->status ?? 'pending')
                ),
                [
                    'type' => $transaction->type ?? null,
                    'doctor' => $transaction->doctor?->doctor_name ?? null,
                ]
            ));
        }

        foreach ($data['bookings'] as $booking) {
            $this->pushEvent($events, $this->event(
                $booking->scheduled_for ?? $booking->booking_created_at ?? $booking->created_at ?? null,
                'Booking created',
                'booking',
                sprintf(
                    'Status: %s | Clinic #%s | Doctor #%s',
                    strtoupper($booking->status ?? 'pending'),
                    $booking->clinic_id ?? '—',
                    $booking->assigned_doctor_id ?? '—'
                )
            ));
        }

        foreach ($data['groomerBookings'] as $booking) {
            $this->pushEvent($events, $this->event(
                $booking->created_at,
                'Grooming booking',
                'booking',
                sprintf(
                    '%s | %s - %s',
                    ucfirst($booking->status ?? 'pending'),
                    $booking->date ?? 'Date',
                    $booking->start_time ? $booking->start_time . ' - ' . $booking->end_time : ''
                )
            ));
        }

        foreach ($data['observations'] as $observation) {
            $this->pushEvent($events, $this->event(
                $observation->observed_at ?? $observation->created_at,
                'Daily observation logged',
                'health',
                trim(($observation->notes ?? '') . ' ' . implode(', ', $observation->symptoms ?? []))
            ));
        }

        foreach ($data['aiChats'] as $chat) {
            $this->pushEvent($events, $this->event(
                $chat->created_at ?? null,
                'AI chat started',
                'engagement',
                $chat->title ?? 'Conversation with assistant'
            ));
        }

        return $events
            ->filter()
            ->sortByDesc('at')
            ->values()
            ->all();
    }

    private function buildMetrics(
        User $user,
        Collection $callSessions,
        Collection $consultations,
        Collection $transactions,
        Collection $pets,
        Collection $userPets,
        Collection $bookings,
        Collection $groomerBookings,
        Collection $observations
    ): array {
        $firstBooking = $bookings->first();
        $lastActivity = collect([
            $user->updated_at,
            $callSessions->first()?->created_at,
            $consultations->first()?->start_time,
            $transactions->first()?->created_at,
            $firstBooking ? ($firstBooking->created_at ?? $firstBooking->booking_created_at ?? null) : null,
            $groomerBookings->first()?->created_at,
            $observations->first()?->observed_at,
        ])
            ->filter()
            ->map(function ($value) {
                try {
                    return $value instanceof Carbon ? $value : Carbon::parse((string) $value);
                } catch (\Throwable) {
                    return null;
                }
            })
            ->filter()
            ->sort()
            ->last();

        return [
            'pets_count' => $pets->count() + $userPets->count(),
            'call_sessions' => $callSessions->count(),
            'consultations' => $consultations->count(),
            'transactions' => [
                'count' => $transactions->count(),
                'value_rupees' => round($transactions->sum(fn ($tx) => (int) ($tx->amount_paise ?? 0)) / 100, 2),
            ],
            'bookings' => $bookings->count(),
            'groomer_bookings' => $groomerBookings->count(),
            'observations' => $observations->count(),
            'last_activity' => $lastActivity,
        ];
    }

    private function event($at, string $title, string $type, string $description, array $meta = []): ?array
    {
        if (! $at) {
            return null;
        }

        try {
            $timestamp = $at instanceof Carbon ? $at : Carbon::parse((string) $at);
        } catch (\Throwable) {
            return null;
        }

        return [
            'at' => $timestamp,
            'title' => $title,
            'type' => $type,
            'description' => $description,
            'meta' => $meta,
        ];
    }

    private function pushEvent(Collection $events, ?array $event): void
    {
        if ($event !== null) {
            $events->push($event);
        }
    }
}
