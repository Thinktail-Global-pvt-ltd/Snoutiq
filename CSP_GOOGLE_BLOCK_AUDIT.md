# CSP Google Block Audit

Audit date: March 18, 2026

## 1. Executive summary

No active Content Security Policy is being set by the current checked-in project code, build output, or the production pages I inspected.

The only CSP string found in this repo is an inactive commented-out `<meta http-equiv="Content-Security-Policy">` in `index.html` and `dist/index.html`.

Google Tag Manager and Google Ads / gtag loading are active in the frontend, so if a browser is reporting CSP blocks for `googletagmanager.com` or `google-analytics.com`, that blocking policy is not coming from the current version-controlled app code. It is most likely coming from:

- an upstream host / reverse proxy / CDN rule outside this repo
- a stale deployment that does not match this checkout
- a different environment than the one currently live at `https://www.snoutiq.com/`

As of March 18, 2026, the live `https://www.snoutiq.com/` response also does not send a `Content-Security-Policy` header.

## 2. Exact root cause

### Proven root cause in the current repo

There is no active CSP in this repo that could currently block Google domains.

### Closest related finding

There is one commented-out CSP example in [`index.html`](index.html) line 241 and the same inactive comment in [`dist/index.html`](dist/index.html) line 241:

```html
<!-- <meta http-equiv="Content-Security-Policy" content="default-src 'self' https://www.googletagmanager.com https://connect.facebook.net; img-src 'self' https://www.facebook.com data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://connect.facebook.net;"> -->
```

If that exact policy were uncommented in some other environment, it would still be incomplete:

- it would allow `www.googletagmanager.com` in `script-src`
- it would **not** allow `www.google-analytics.com` in `connect-src` because `connect-src` is missing and would fall back to `default-src`
- it would **not** allow `www.google-analytics.com` in `img-src`

That means this commented policy could explain `google-analytics.com` blocks, but it does **not** explain `googletagmanager.com` being blocked, because that domain is explicitly present in the example.

### Final root-cause conclusion

If the browser is really blocking both `googletagmanager.com` and `google-analytics.com`, the active CSP must be a different policy than anything present in this repository.

## 3. Where CSP is defined

### Active CSP definitions found

None.

### Inactive CSP references found

- [`index.html`](index.html) line 241: commented-out CSP meta tag
- [`dist/index.html`](dist/index.html) line 241: same commented-out CSP meta tag in build output

### Locations checked with no active CSP found

| Surface | File / endpoint | Result |
| --- | --- | --- |
| Frontend HTML meta | [`index.html`](index.html) | No active CSP meta; only commented example |
| Built frontend HTML | [`dist/index.html`](dist/index.html) | No active CSP meta; only commented example |
| Vite build config | [`vite.config.js`](vite.config.js) | No CSP header/meta generation |
| Apache SPA config | [`public/.htaccess`](public/.htaccess) | Only cache/rewrite rules |
| Deploy Apache template | [`deploy/apache-spa-404.htaccess`](deploy/apache-spa-404.htaccess) | Only cache/rewrite rules |
| Deploy Nginx template | [`deploy/nginx-spa-404.conf`](deploy/nginx-spa-404.conf) | Only cache headers; no CSP |
| Laravel public Apache config | [`backend/public/.htaccess`](backend/public/.htaccess) | Only cache/rewrite rules |
| Laravel bootstrap / middleware | [`backend/bootstrap/app.php`](backend/bootstrap/app.php) | No CSP middleware |
| Laravel service provider | [`backend/app/Providers/AppServiceProvider.php`](backend/app/Providers/AppServiceProvider.php) | No CSP/header injection |
| React Helmet usage | `src/**/*.jsx` | `Helmet` used for SEO only; no `httpEquiv="Content-Security-Policy"` found |
| Hosting config files | repo root | No `vercel.json`, `netlify.toml`, `_headers`, `next.config.*`, custom Node CSP server file found |

## 4. Which files are involved

### Primary files

- [`index.html`](index.html)
- [`dist/index.html`](dist/index.html)
- [`src/layouts/MainLayout.jsx`](src/layouts/MainLayout.jsx)
- [`vite.config.js`](vite.config.js)
- [`public/.htaccess`](public/.htaccess)
- [`deploy/apache-spa-404.htaccess`](deploy/apache-spa-404.htaccess)
- [`deploy/nginx-spa-404.conf`](deploy/nginx-spa-404.conf)
- [`backend/public/.htaccess`](backend/public/.htaccess)
- [`backend/bootstrap/app.php`](backend/bootstrap/app.php)

### Additional pages that would matter if a site-wide CSP is added

- [`petparent/index.html`](petparent/index.html)
- [`backend/resources/views/vet/landing.blade.php`](backend/resources/views/vet/landing.blade.php)

### Downstream features that depend on `window.gtag`

- [`src/screen/Paymentscreen.jsx`](src/screen/Paymentscreen.jsx) lines 950-951
- [`src/newflow/PetDoctorOnline.jsx`](src/newflow/PetDoctorOnline.jsx) lines 1328-1329
- [`src/newflow/TalkToVet.jsx`](src/newflow/TalkToVet.jsx) lines 1392-1393
- [`src/newflow/VideoConsultLP.jsx`](src/newflow/VideoConsultLP.jsx) lines 1408-1409
- [`src/newflow/OnlineVetConsultationApp.jsx`](src/newflow/OnlineVetConsultationApp.jsx) lines 382-383

If GTM / gtag is blocked, those event calls silently stop doing anything.

## 5. Which Google resources are blocked

The repo directly or indirectly requires these Google resources.

### Directly loaded by the SPA

From [`index.html`](index.html):

```html
291:       const loadGoogleTag = () =>
292:         loadExternalScript(
293:           "https://www.googletagmanager.com/gtag/js?id=AW-17874635845",
294:         )
...
300:       const loadGoogleTagManager = () => {
...
308:           j.src = "https://www.googletagmanager.com/gtm.js?id=" + i + dl;
...
370:         src="https://www.googletagmanager.com/ns.html?id=GTM-5D756BMW"
```

### Indirectly required at runtime

The code does not hardcode `google-analytics.com`, but `gtag.js` / GTM will send analytics beacons to Google Analytics endpoints at runtime. In practice that means CSP must also allow:

- `https://www.google-analytics.com`
- often `https://region1.google-analytics.com`

### Other Google resources elsewhere in the repo

From [`backend/resources/views/vet/landing.blade.php`](backend/resources/views/vet/landing.blade.php):

- Google Maps embeds via `https://www.google.com/maps/embed/v1/place`
- fallback map embeds via `https://maps.google.com/maps`

## 6. Whether the issue is from frontend meta tag, backend header, reverse proxy, deployment config, or middleware

### Frontend meta tag

Not active in the current repo. Only a commented example exists.

### Backend header

Not found in Laravel middleware, service providers, routes, or Apache config in this repo.

### Reverse proxy

Not present in the checked-in Nginx template. If the reported CSP exists, this is still the most likely place if there is an untracked server config.

### Deployment config

No repo-managed Vercel / Netlify / `_headers` / Next.js header config exists.

### Middleware

No Node / Express / Helmet CSP middleware found. No Laravel CSP middleware found.

### Most likely source if the browser error is real

An external deployment-layer header outside version control.

## 7. Exact broken CSP directives

### In the current repo as checked in

None. No active CSP is present.

### If the commented CSP in `index.html` were activated

The broken directives would be:

- `connect-src`
  - missing entirely, so browser would fall back to `default-src`
  - `default-src` does not include `https://www.google-analytics.com`
- `img-src`
  - only allows `'self'`, `https://www.facebook.com`, and `data:`
  - does not include `https://www.google-analytics.com`

That would break Google Analytics collection even though GTM script loading might still work.

### If the browser is blocking `googletagmanager.com` as the user reported

Then the real active CSP is missing at least one of these directives:

- `script-src` or `script-src-elem` missing `https://www.googletagmanager.com`
- `frame-src` missing `https://www.googletagmanager.com` for the GTM `noscript` iframe
- nonce/hash handling for the inline GTM bootstrap script is missing

## 8. Recommended minimal fix

Fix the policy in the place that actually reaches the browser. Based on this audit, that place is not in the current repo.

Minimal allowlist for the current SPA GTM / GA usage should include:

- `script-src` and `script-src-elem`: `https://www.googletagmanager.com`
- `connect-src`: `https://www.google-analytics.com` and `https://region1.google-analytics.com`
- `img-src`: `https://www.google-analytics.com`
- `frame-src`: `https://www.googletagmanager.com`

Do not use wildcard `*`.

Do not add `unsafe-inline` to `script-src` unless there is no other option.

## 9. Safe fix recommendation without weakening security too much

### Best practical approach for this project

For the static SPA in [`index.html`](index.html), move inline scripts out of HTML and into versioned JS files, then apply CSP to those external files only.

Reason:

- this project currently contains multiple inline executable scripts in `index.html`
- `vite.config.js` injects another inline script during build
- `vite.config.js` also injects an inline `onload=...` handler on stylesheet preload links
- static Apache / Nginx hosting makes per-request nonces harder than in a server-rendered app

For Laravel-rendered backend pages such as [`backend/resources/views/vet/landing.blade.php`](backend/resources/views/vet/landing.blade.php), a nonce-based CSP is practical because Blade can inject a per-request nonce.

### Safer rollout sequence

1. Remove inline GTM / preload helper scripts from static HTML where possible.
2. Remove inline `onload=` handlers from preload links or replace them with external JS.
3. Move inline styles and style attributes into CSS classes.
4. Add a strict header-based CSP at the actual edge / origin that serves the page.
5. Start with `Content-Security-Policy-Report-Only` first.

## 10. Whether nonce/hash is needed

Yes.

If you want a strict CSP without `unsafe-inline`, the current project needs nonce or hash handling for at least these cases:

- inline analytics / GTM bootstrap in [`index.html`](index.html) lines 244-363
- inline build-time preload helper injected by [`vite.config.js`](vite.config.js) lines 45-50
- inline `onload="this.onload=null;this.rel='stylesheet'"` injected by [`vite.config.js`](vite.config.js) line 41
- inline scripts on backend pages such as [`backend/resources/views/vet/landing.blade.php`](backend/resources/views/vet/landing.blade.php) lines 43-118

Important detail:

- nonces are easy on Blade / Laravel pages
- nonces are awkward on a fully static `index.html`
- hashes are possible for static HTML, but they are brittle because the inline script content changes when the file changes

## 11. Proposed corrected CSP example

This is a reasonable minimal example for the current SPA if you keep inline scripts and solve them with nonces at the layer that actually serves the document:

```http
Content-Security-Policy:
  default-src 'self';
  base-uri 'self';
  object-src 'none';
  frame-ancestors 'self';
  script-src 'self' 'nonce-{NONCE}' https://www.googletagmanager.com https://connect.facebook.net;
  script-src-elem 'self' 'nonce-{NONCE}' https://www.googletagmanager.com https://connect.facebook.net;
  connect-src 'self' https://www.google-analytics.com https://region1.google-analytics.com;
  img-src 'self' data: https://www.google-analytics.com https://www.facebook.com;
  frame-src 'self' https://www.googletagmanager.com;
  style-src 'self' 'nonce-{NONCE}';
```

Notes:

- If you reuse one CSP across backend pages too, you also need extra domains for pages like [`backend/resources/views/vet/landing.blade.php`](backend/resources/views/vet/landing.blade.php):
  - `https://fonts.googleapis.com`
  - `https://fonts.gstatic.com`
  - `https://cdnjs.cloudflare.com`
  - `https://cdn.tailwindcss.com`
  - `https://api.qrserver.com`
  - `https://www.google.com`
  - `https://maps.google.com`
- If inline style attributes remain in HTML, `style-src 'nonce-{NONCE}'` alone will still break them. Refactor those styles before enforcing a strict `style-src`.

## 12. Any risks or side effects of the fix

- If you only add `googletagmanager.com` and forget `google-analytics.com`, scripts may load but analytics beacons will still fail.
- If you only fix `script-src` but not `frame-src`, the GTM `noscript` iframe remains blocked.
- If you only fix GTM and ignore `vite.config.js` inline script / `onload=` injection, a strict CSP will still break the page.
- If you add a CSP header upstream and later uncomment the meta tag in `index.html`, both policies will be enforced together and the result becomes more restrictive, not less.
- If `VITE_GTM_ID` is set in deployment, [`src/layouts/MainLayout.jsx`](src/layouts/MainLayout.jsx) can inject GTM in addition to the hard-coded loader in `index.html`, which complicates debugging and may cause duplicate loading.

## 13. Step-by-step verification checklist

1. On the exact environment where the error occurs, inspect the document response headers and confirm whether `Content-Security-Policy` or `Content-Security-Policy-Report-Only` is present.
2. View page source and confirm whether any `<meta http-equiv="Content-Security-Policy">` is active.
3. Confirm there is only one CSP source. If both header and meta exist, both will be enforced.
4. Trigger a real user interaction on the homepage, because GTM / gtag are loaded after `pointerdown`, `keydown`, or `touchstart`.
5. In DevTools Network, confirm:
   - `https://www.googletagmanager.com/gtag/js?...` loads
   - `https://www.googletagmanager.com/gtm.js?...` loads
   - `https://www.googletagmanager.com/ns.html?...` is allowed
6. Confirm analytics beacons to `https://www.google-analytics.com/...` or `https://region1.google-analytics.com/...` are no longer blocked.
7. Check console for inline-script CSP violations. If they remain, nonce/hash coverage is incomplete.
8. Check that CSS still loads. If preload-to-stylesheet conversion breaks, the inline `onload=` handler is still conflicting with CSP.
9. Check backend-rendered pages separately, especially `/backend/custom-doctor-login` and `/vets/{slug}`, because they have different HTML and external domains.
10. If using nonces, confirm the same nonce value appears in the CSP header and on every inline script tag for that response.
11. If using hashes, recompute them after every inline script change.
12. Prefer a `Report-Only` rollout first, then switch to enforcing mode after the browser console is clean.

## 14. Effective config that actually reaches the browser

### Live homepage check on March 18, 2026

`curl -I https://www.snoutiq.com/` returned:

```http
HTTP/1.1 200 OK
Server: Apache/2.4.58 (Ubuntu)
Cache-Control: no-cache, no-store, must-revalidate
Content-Type: text/html
```

No `Content-Security-Policy` header was present.

### Live backend page check on March 18, 2026

`curl -I https://www.snoutiq.com/backend/custom-doctor-login` returned `200 OK` with no `Content-Security-Policy` header.

## 15. Development and production differences

No repo-defined CSP difference was found between development and production:

- Vite dev config does not set CSP
- checked-in Apache / Nginx deploy templates do not set CSP
- current live pages tested do not return CSP headers

Any CSP difference between environments must be coming from infrastructure outside this repository.

## 16. Final verdict

The CSP that is blocking Google domains is not defined in the current checked-in project and is not present on the current live homepage or backend page responses I tested on March 18, 2026.

The only CSP string in the repo is an inactive comment in [`index.html`](index.html) and [`dist/index.html`](dist/index.html). If that comment is being used in some other environment, it is still incomplete and would block `google-analytics.com`, but it would not explain a `googletagmanager.com` block.

The exact issue location, based on the evidence, is therefore:

- **not** frontend meta in the current repo
- **not** backend middleware / headers in the current repo
- **not** checked-in Apache / Nginx deploy config
- **most likely** an external deployment-layer CSP outside version control, or a stale environment that does not match this repository
