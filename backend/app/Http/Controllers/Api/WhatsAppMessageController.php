<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class WhatsAppMessageController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsApp)
    {
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile_number' => ['required', 'string', 'max:20'],
            'type' => ['nullable', Rule::in(['text', 'template'])],
            'message' => ['required_if:type,text', 'string', 'max:1000'],
            'template_name' => ['required_if:type,template', 'string', 'max:255'],
        ]);

        $type = $data['type'] ?? 'template';

        if (!$this->whatsApp->isConfigured()) {
            return response()->json([
                'message' => 'WhatsApp credentials are not configured.',
            ], 503);
        }

        try {
            if ($type === 'text') {
                $result = $this->whatsApp->sendTextWithResult($data['mobile_number'], $data['message']);
            } else {
                $result = $this->whatsApp->sendTemplateWithResult($data['mobile_number'], $data['template_name'] ?? null);
            }
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => 'Failed to send WhatsApp message.',
            ], 502);
        }

        return response()->json([
            'message' => 'Message queued for delivery.',
            'data' => [
                'to' => $data['mobile_number'],
                'type' => $type,
                'message_id' => $result['messages'][0]['id'] ?? null,
            ],
        ]);
    }
}
