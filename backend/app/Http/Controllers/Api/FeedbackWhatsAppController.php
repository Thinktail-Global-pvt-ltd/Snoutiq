<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Pet;
use App\Models\Doctor;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FeedbackWhatsAppController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsApp)
    {
    }

    /**
     * POST /api/consultation/feedback/send-by-pet-vet
     * Body:
     *  - pet_id (required)
     *  - vet_id (required)  // doctor id
     *  - review_link (required)
     */
    public function sendByPetVet(Request $request)
    {
        $data = $request->validate([
            'pet_id' => ['required', 'integer', 'exists:pets,id'],
            'vet_id' => ['required', 'integer', 'exists:doctors,id'],
            'review_link' => ['required', 'string', 'max:500'],
        ]);

        if (! $this->whatsApp->isConfigured()) {
            return response()->json(['success' => false, 'error' => 'whatsapp_not_configured'], 503);
        }

        $pet = Pet::with('owner')->find($data['pet_id']);
        $doctor = Doctor::find($data['vet_id']);

        if (! $pet || ! $doctor) {
            return response()->json(['success' => false, 'error' => 'not_found'], 404);
        }

        $user = $pet->owner;
        $phone = $user?->phone;
        if (! $phone) {
            return response()->json(['success' => false, 'error' => 'user_phone_missing'], 422);
        }

        $parentName = $user?->name ?: 'Pet Parent';
        $petName = $pet->name ?? 'your pet';
        $vetName = $doctor->doctor_name ?: 'Doctor';

        $components = [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $parentName],           // {{1}}
                    ['type' => 'text', 'text' => $petName],              // {{2}}
                    ['type' => 'text', 'text' => $vetName],              // {{3}}
                    ['type' => 'text', 'text' => $data['review_link']],  // {{4}}
                ],
            ],
        ];

        try {
            $this->whatsApp->sendTemplate($phone, 'pp_consultation_feedback', $components, 'en');
        } catch (\RuntimeException $e) {
            Log::error('feedback.whatsapp.by_pet_vet.failed', ['pet_id' => $pet->id, 'vet_id' => $doctor->id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'send_failed', 'message' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'pet_id' => $pet->id,
            'vet_id' => $doctor->id,
            'sent_to' => $phone,
            'template' => 'pp_consultation_feedback',
            'language' => 'en',
        ]);
    }

    /**
     * POST /api/consultation/feedback/send
     * Body:
     *  - transaction_id (required): video consult or excell_export_campaign txn id
     *  - review_link (required): URL shown in the template
     */
    public function send(Request $request)
    {
        $data = $request->validate([
            'transaction_id' => ['required', 'integer', 'exists:transactions,id'],
            'review_link' => ['required', 'string', 'max:500'],
        ]);

        if (! $this->whatsApp->isConfigured()) {
            return response()->json(['success' => false, 'error' => 'whatsapp_not_configured'], 503);
        }

        $txn = Transaction::with(['user', 'pet', 'doctor'])->find($data['transaction_id']);
        if (! $txn) {
            return response()->json(['success' => false, 'error' => 'transaction_not_found'], 404);
        }

        $user = $txn->user;
        $phone = $user?->phone;
        if (! $phone) {
            return response()->json(['success' => false, 'error' => 'user_phone_missing'], 422);
        }

        $petName = $txn->pet?->name
            ?? ($txn->metadata['pet_name'] ?? null)
            ?? 'your pet';

        $vetName = $txn->doctor?->doctor_name
            ?? ($txn->metadata['doctor_name'] ?? null)
            ?? 'Doctor';

        $parentName = $user?->name ?: 'Pet Parent';

        $components = [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $parentName],          // {{1}} Pet parent name
                    ['type' => 'text', 'text' => $petName],             // {{2}} Pet name
                    ['type' => 'text', 'text' => $vetName],             // {{3}} Vet name
                    ['type' => 'text', 'text' => $data['review_link']], // {{4}} Review link
                ],
            ],
        ];

        $template = 'pp_consultation_feedback';
        $language = 'en';

        try {
            $this->whatsApp->sendTemplate($phone, $template, $components, $language);
        } catch (\RuntimeException $e) {
            Log::error('feedback.whatsapp.failed', ['txn_id' => $txn->id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'send_failed', 'message' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'transaction_id' => $txn->id,
            'sent_to' => $phone,
            'template' => $template,
            'language' => $language,
        ]);
    }
}
