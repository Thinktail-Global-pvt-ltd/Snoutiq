# Socket Server

This Node process powers the real-time doctor and patient signalling layer (Socket.IO) and emits webhook / notification events when calls change state.

## Notifications Overview

When a pending call is queued for a doctor the server can notify external systems through:

- A configurable HTTP webhook (`DOCTOR_ALERT_ENDPOINT`)
- Direct WhatsApp Business API alerts (Meta Cloud API)

Both channels can be enabled simultaneously; failures in one channel do not block the other.

## Environment Variables

| Variable | Description |
| --- | --- |
| `DOCTOR_ALERT_ENDPOINT` | Optional HTTP endpoint that receives pending call payloads. Set together with `DOCTOR_ALERT_SECRET` if you need an HMAC-style header. |
| `DOCTOR_ALERT_SECRET` | Optional shared secret added as `X-Notification-Key` when calling the webhook. |
| `DOCTOR_ALERT_TIMEOUT_MS` | Timeout (ms) for the webhook request. Defaults to `3000`. |
| `WHATSAPP_ACCESS_TOKEN` | **Required for WhatsApp alerts.** Permanent or short-lived token created in Meta Business Manager. Never hardcode this value; inject via environment or secret storage. |
| `WHATSAPP_PHONE_NUMBER_ID` | **Required for WhatsApp alerts.** Phone number ID from Meta's WhatsApp Business configuration (not the display phone number). |
| `WHATSAPP_ALERT_MODE` | `text` (default) or `template`. Selects which WhatsApp payload format to use. |
| `WHATSAPP_ALERT_TEXT_TEMPLATE` | Optional text body with placeholders such as `{{doctorId}}`, `{{patientId}}`, `{{channel}}`, `{{callId}}`, `{{timestamp}}`, `{{brandName}}`. Applies when `WHATSAPP_ALERT_MODE=text`. |
| `WHATSAPP_ALERT_TEMPLATE_NAME` | Template name to send when `WHATSAPP_ALERT_MODE=template`. Defaults to `hello_world`. |
| `WHATSAPP_TEMPLATE_LANGUAGE` | Optional language code for template messages (defaults to `en_US`). |
| `WHATSAPP_ALERT_TEMPLATE_COMPONENTS` | Optional JSON describing template components. Placeholders are interpolated using the same tokens as the text template. |
| `WHATSAPP_BRAND_NAME` | Label injected into templates as `{{brandName}}`. Defaults to `SnoutIQ`. |
| `DOCTOR_WHATSAPP_MAP` | Comma-separated mapping of doctor IDs to WhatsApp numbers. Example: `123=919876543210,456=911234567890`. |
| `DOCTOR_ALERT_DEFAULT_WHATSAPP` | Fallback number used when a doctor ID is not present in `DOCTOR_WHATSAPP_MAP`. Useful for catch-all alerts or testing. |
| `WHATSAPP_TIMEOUT_MS` | Optional timeout (ms) for WhatsApp API calls. Defaults to `5000`. |

Numbers can include the `+` prefix or be provided in international format (`91XXXXXXXXXX`). The integration automatically strips non-numeric characters.

## WhatsApp Template Components

If you use a template with variables, provide the `components` payload as JSON. For example:

```env
WHATSAPP_ALERT_TEMPLATE_NAME=incoming_alert
WHATSAPP_ALERT_TEMPLATE_COMPONENTS=[
  {
    "type": "body",
    "parameters": [
      { "type": "text", "text": "{{doctorId}}" },
      { "type": "text", "text": "{{patientId}}" },
      { "type": "text", "text": "{{callId}}" }
    ]
  }
]
```

Each `{{placeholder}}` will be replaced with the runtime value before dispatch.

## Testing With cURL

You can verify your credentials with a one-off request (replace placeholders with real values):

```bash
curl -i -X POST \
  "https://graph.facebook.com/v22.0/<PHONE_NUMBER_ID>/messages" \
  -H "Authorization: Bearer <WHATSAPP_ACCESS_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
        "messaging_product": "whatsapp",
        "to": "<DESTINATION_NUMBER>",
        "type": "template",
        "template": { "name": "hello_world", "language": { "code": "en_US" } }
      }'
```

> ⚠️ **Security:** Treat tokens and phone-number IDs as secrets. Never commit them to the repository or share in logs. Prefer secret managers (e.g. Doppler, AWS Secrets Manager, 1Password) and export them in your process environment before starting the socket server.
