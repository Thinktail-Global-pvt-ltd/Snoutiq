# Backend QR -> Play Store Referral Changes (Plan Only)

Goal: QR scan -> vet landing -> Play Store link carries clinic id -> app sends `referral_clinic_id` on OTP verify -> backend saves and returns clinic id + slug without breaking current flow.

## Source of clinic identifier (existing)
- Clinic object in landing view is `VetRegisterationTemp` as `$vet` from `backend/app/Http/Controllers/VetLandingController.php`.
- Stable numeric id is `$vet->id` (table: `vet_registerations_temp`).
- Existing helper already uses `$vet->id` for referral codes and public IDs.

## 1) Landing Play Store URL (append referrer)
File:
- `backend/resources/views/vet/landing.blade.php`

Change:
- Build a referrer URL using `$vet->id` and add to `$appDownloadUrl`.
- Keep fallback to base URL if `$vet->id` is missing.
- All CTAs + QR use `$appDownloadUrl` already, so a single change at the top covers all:

Example logic (conceptual):
- `$referrerRaw = "clinic_id={$vet->id}&src=qr";`
- `$appDownloadUrl = "https://play.google.com/store/apps/details?id=com.petai.snoutiq&referrer=" . urlencode($referrerRaw);`
- If `$vet->id` is empty, keep original base URL.

Notes:
- This updates all CTA links + QR because they all use `$appDownloadUrl`.

## 2) OTP verify should accept referral_clinic_id
Route:
- `backend/routes/api.php`
  - `POST /verify-otp` -> `AuthController@verify_otp`
  - (Also defined: `POST /v1/auth/otp/verify` -> `Api\V1\OtpController@verify`, but the controller file is missing in repo.)

Controller:
- `backend/app/Http/Controllers/Api/AuthController.php` in `verify_otp` (starts around line ~555).

Change:
- Accept optional `referral_clinic_id` in request validation.
- If user exists and `last_vet_id` is empty, set `last_vet_id = referral_clinic_id`.
- Save before calling `ensureLastVetSlug(...)` so slug can be derived.
- In response, include `last_vet_id` (or `clinic_id`) alongside `last_vet_slug`.

Overwrite policy:
- Do NOT override if `last_vet_id` already set (preserve existing clinic association).

## 3) Data fields already exist (no migration needed)
User schema already has:
- `last_vet_id` (clinic id)
- `last_vet_slug` (clinic slug)

Files:
- `backend/database/migrations/2025_12_22_130500_add_last_vet_id_to_users_table.php`
- `backend/database/migrations/2025_12_22_123500_add_last_vet_slug_to_users_table.php`
- `backend/app/Models/User.php` (fillable includes `last_vet_id`, `last_vet_slug`)

So referral can be stored in existing fields without new migration.

## 4) Optional existing endpoint (already supports clinic_id)
Endpoint:
- `POST /downloads/track` -> `backend/app/Http/Controllers/Api/ReferralController.php::trackDownload`
  - Accepts `clinic_id` or `vet_slug`
  - Sets `last_vet_id` and `last_vet_slug`

This can be used for pre-login tracking, but your requested flow is OTP verify.

## 5) Files to touch (summary)
- `backend/resources/views/vet/landing.blade.php`
  - Update `$appDownloadUrl` to include `referrer=clinic_id=<ID>&src=qr`.
- `backend/app/Http/Controllers/Api/AuthController.php`
  - `verify_otp`: accept `referral_clinic_id`, set `last_vet_id` (if empty), include `last_vet_id` in response.
- `backend/routes/api.php`
  - (Optional) Update any route docs/validation notes for `/verify-otp`.
- If `/v1/auth/otp/verify` is used in app:
  - Implement/update `App\Http\Controllers\Api\V1\OtpController` (file missing in repo) with the same referral logic.
