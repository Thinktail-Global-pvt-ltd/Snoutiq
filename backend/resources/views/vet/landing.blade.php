{{-- resources/views/vet/landing.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  @php
    $snoutiqLogo = 'data:image/webp;base64,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';
    $appDownloadUrl = 'https://play.google.com/store/apps/details?id=com.petai.snoutiq';
    $downloadQrSrc  = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($appDownloadUrl);
    $clinicName     = $vet->name ?? 'SnoutIQ Clinic';
    $clinicCity     = $vet->city ?? 'Veterinary Clinic';
    $rawClinicPhone = $vet->mobile ?: '';
    $cleanDigits    = preg_replace('/\D+/', '', $rawClinicPhone);
    $clinicPhone    = ($cleanDigits === '1244568900') ? '' : $rawClinicPhone;
    $phoneHref      = $clinicPhone ? 'tel:' . preg_replace('/[^0-9+]/', '', $clinicPhone) : 'tel:#';
    $clinicEmail    = $vet->email ?? 'care@snoutiq.com';
    $services       = $services ?? collect();
    $websiteTitle = trim((string) ($vet->website_title ?? ''));
    $websiteSubtitle = trim((string) ($vet->website_subtitle ?? ''));
    $websiteAbout = trim((string) ($vet->website_about ?? ''));
    $websiteGallery = $vet->website_gallery ?? [];
    if (!is_array($websiteGallery)) {
        $decodedGallery = json_decode((string) $websiteGallery, true);
        $websiteGallery = is_array($decodedGallery) ? $decodedGallery : [];
    }
    $websiteGallery = array_values(array_filter($websiteGallery, function ($path) {
        return is_string($path) && trim($path) !== '';
    }));
  @endphp
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>{{ $clinicName }} | SnoutIQ</title>
  <meta name="description" content="Book appointments at {{ $clinicName }}. Video consults, clinic visits, vaccinations and more."/>
  <link rel="icon" href="{{ asset('favicon.png') }}" sizes="32x32" type="image/png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: {
              50: '#eff6ff',
              100: '#dbeafe',
              200: '#bfdbfe',
              300: '#93c5fd',
              400: '#60a5fa',
              500: '#3b82f6',
              600: '#2563eb',
              700: '#1d4ed8',
              800: '#1e40af',
              900: '#1e3a8a',
            },
            secondary: {
              50: '#f8fafc',
              100: '#f1f5f9',
              200: '#e2e8f0',
              300: '#cbd5e1',
              400: '#94a3b8',
              500: '#64748b',
              600: '#475569',
              700: '#334155',
              800: '#1e293b',
              900: '#0f172a',
            }
          },
          fontFamily: {
            'sans': ['Inter', 'system-ui', 'sans-serif'],
            'heading': ['Poppins', 'Inter', 'sans-serif'],
          },
          spacing: {
            '18': '4.5rem',
            '88': '22rem',
            '128': '32rem',
          }
        }
      }
    }
  </script>
  <style>
    html { scroll-behavior: smooth; }
    .section-padding { padding-top: 5rem; padding-bottom: 5rem; }
    @media (max-width: 768px) {
      .section-padding { padding-top: 3rem; padding-bottom: 3rem; }
    }
    .card-hover { transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
    .testimonial-card {
      background: linear-gradient(145deg, #ffffff, #f8fafc);
      box-shadow: 5px 5px 15px rgba(0, 0, 0, 0.05), -5px -5px 15px rgba(255, 255, 255, 0.8);
    }
    .gradient-bg { background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #f8fafc 100%); }
    .qr-code { background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); }
  </style>

  <script>
    window.SNOUTIQ = {
      user: @json($authUser ?? null),
      token: @json($apiToken ?? null)
    };
  </script>

  <script>
    setTimeout(function() {
      !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
      n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
      n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;
      s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
      fbq('init','1909812439872823'); fbq('track','PageView');
    },7000);
  </script>
</head>
<body class="font-sans text-secondary-800 bg-white">
  @php
    $qrI = request('qr_i');
    $qrCounted = request('qr_counted');
  @endphp
  @if(!empty($qrI) && (string)$qrCounted !== '1')
    <img src="{{ route('qr.beacon', ['i' => $qrI]) }}" alt="" width="1" height="1" style="position:absolute;left:-9999px;top:-9999px;" />
  @endif

  @if($isDraft)
    @php
      $draftMapQuery = trim((string)($mapQuery ?? ''));
      $draftMapSrc = null;
      if ($draftMapQuery !== '') {
          $encodedQuery = urlencode($draftMapQuery);
          if (!empty($mapsEmbedKey)) {
              $draftMapSrc = 'https://www.google.com/maps/embed/v1/place?key='.urlencode($mapsEmbedKey).'&q='.$encodedQuery;
          } else {
              $draftMapSrc = 'https://maps.google.com/maps?q='.$encodedQuery.'&t=&z=13&ie=UTF8&iwloc=&output=embed';
          }
      }
    @endphp
    <section class="min-h-screen bg-secondary-50 flex items-center">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="max-w-3xl mx-auto bg-white border border-secondary-200 rounded-2xl shadow-xl p-8">
          <div class="inline-flex items-center px-3 py-1 rounded-full bg-primary-100 text-primary-700 font-semibold mb-4">
            <span class="mr-2">🚧</span> Clinic Profile In Draft
          </div>
          <h1 class="font-heading text-3xl font-bold text-secondary-900 mb-3">{{ $clinicName }}</h1>
          <p class="text-secondary-600 mb-6">
            This clinic page is being set up with SnoutIQ. Share the QR or short link below so the clinic owner can claim
            the profile and complete onboarding. Until then, only safe placeholder details are visible to the public.
          </p>

          <div class="bg-secondary-50 border border-secondary-200 rounded-xl p-4 mb-6">
            <p class="text-secondary-600 text-sm mb-2">Permanent short link</p>
            <a href="{{ $publicUrl }}" data-permanent-short-link class="text-primary-700 font-semibold break-words">{{ $publicUrl }}</a>
          </div>

          @if($canClaim)
            @php $claimToken = request('claim_token'); @endphp
            <p class="text-secondary-700 mb-4">Looks like you have the invite link. Claim this clinic to unlock full editing.</p>
            <a class="inline-flex items-center justify-center bg-primary-600 hover:bg-primary-700 text-white font-semibold px-5 py-3 rounded-lg transition-colors"
               href="https://snoutiq.com/backend/custom-doctor-register?claim_token={{ urlencode($claimToken) }}&public_id={{ $vet->public_id }}">
              Claim This Clinic
            </a>
          @else
            <div class="flex flex-wrap gap-3">
              <a class="inline-flex items-center justify-center bg-white border border-secondary-200 text-primary-700 font-semibold px-5 py-3 rounded-lg hover:bg-secondary-50 transition-colors"
                 href="https://snoutiq.com/contact?topic=clinic">
                Notify Me
              </a>
              <a class="inline-flex items-center justify-center bg-primary-600 hover:bg-primary-700 text-white font-semibold px-5 py-3 rounded-lg transition-colors"
                 href="https://snoutiq.com/contact">
                Talk To SnoutIQ Sales
              </a>
            </div>
          @endif
        </div>

        @if($draftMapSrc)
          <div class="max-w-3xl mx-auto mt-8 bg-white border border-secondary-200 rounded-2xl shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-secondary-100 flex items-center justify-between">
              <div>
                <div class="text-sm text-secondary-500 uppercase tracking-wide">Location Preview</div>
                <div class="text-secondary-800 font-semibold">{{ $vet->formatted_address ?? $vet->address ?? $vet->city ?? 'Location coming soon' }}</div>
              </div>
              <div class="w-10 h-10 rounded-full bg-primary-50 flex items-center justify-center text-primary-600">
                <i class="fas fa-map-marker-alt"></i>
              </div>
            </div>
            <div class="aspect-[4/3]">
              <iframe
                src="{{ $draftMapSrc }}"
                width="100%"
                height="100%"
                style="border:0"
                allowfullscreen
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
          </div>
        @endif
      </div>
    </section>
  @else
    @php
      $mapQuery = ($vet->lat && $vet->lng) ? ($vet->lat.','.$vet->lng) : ($vet->formatted_address ?: ($vet->address ?: $vet->city));
      $mapSrc = null;
      if (!empty($mapQuery)) {
          $encodedQuery = urlencode($mapQuery);
          if (!empty($mapsEmbedKey)) {
              $mapSrc = 'https://www.google.com/maps/embed/v1/place?key='.urlencode($mapsEmbedKey).'&q='.$encodedQuery;
          } else {
              $mapSrc = 'https://maps.google.com/maps?q='.$encodedQuery.'&t=&z=13&ie=UTF8&iwloc=&output=embed';
          }
      }
      $doctors = $vet->doctors()->orderBy('doctor_name')->get();
    @endphp

    <!-- Navigation -->
    <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-sm border-b border-secondary-100">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
          <div class="flex items-center">
            <div class="flex items-center space-x-2">
              <div class="w-10 h-10 rounded-full bg-primary-500 flex items-center justify-center">
                <i class="fas fa-paw text-white text-xl"></i>
              </div>
              <div>
                <h1 class="font-heading text-xl font-bold text-primary-700">{{ $clinicName }}</h1>
                <p class="text-xs text-secondary-500">{{ $clinicCity }}</p>
              </div>
            </div>
          </div>

          <div class="flex items-center space-x-4">
            @if(!empty($clinicPhone))
              <a href="{{ $phoneHref }}" class="hidden sm:inline-flex items-center space-x-2 text-secondary-700 hover:text-primary-600">
                <i class="fas fa-phone"></i>
                <span class="font-medium">{{ $clinicPhone }}</span>
              </a>
            @endif
            <a href="{{ $appDownloadUrl }}" target="_blank" rel="noopener" class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2.5 px-5 rounded-lg transition-colors shadow-md">
              <i class="fas fa-mobile-alt mr-2"></i>Get App
            </a>
          </div>
        </div>
      </div>
    </header>

    <main>
      <!-- Hero Section with App Download -->
      <section id="app-download" class="gradient-bg section-padding">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
          <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div class="max-w-xl">
              <div class="inline-flex items-center px-3 py-1 rounded-full bg-primary-100 text-primary-700 text-sm font-medium mb-6">
                <i class="fas fa-star mr-2"></i> Partnered with SnoutIQ
              </div>
              <h1 class="font-heading text-4xl md:text-5xl font-bold text-secondary-900 mb-6 leading-tight">
                @if(!empty($websiteTitle))
                  {{ $websiteTitle }}
                @else
                  Premium Care for Your <span class="text-primary-600">Furry Family</span>
                @endif
              </h1>
              <p class="text-lg text-secondary-600 mb-8">
                @if(!empty($websiteSubtitle))
                  {{ $websiteSubtitle }}
                @else
                  At {{ $clinicName }}, we combine expert veterinary medicine with compassionate care.
                  Download the SnoutIQ app for seamless appointment booking, health tracking, and 24/7 pet support.
                @endif
              </p>

              <div class="mb-10">
                <h3 class="font-medium text-secondary-900 mb-4">Download SnoutIQ App</h3>
                <div class="flex flex-wrap gap-4 mb-6">
                  <a href="{{ $appDownloadUrl }}" target="_blank" rel="noopener" class="flex items-center bg-secondary-900 hover:bg-secondary-800 text-white px-6 py-3 rounded-lg transition-colors card-hover">
                    <i class="fab fa-apple text-2xl mr-3"></i>
                    <div>
                      <div class="text-xs">Download on the</div>
                      <div class="font-semibold">App Store</div>
                    </div>
                  </a>
                  <a href="{{ $appDownloadUrl }}" target="_blank" rel="noopener" class="flex items-center bg-secondary-900 hover:bg-secondary-800 text-white px-6 py-3 rounded-lg transition-colors card-hover">
                    <i class="fab fa-google-play text-xl mr-3"></i>
                    <div>
                      <div class="text-xs">Get it on</div>
                      <div class="font-semibold">Google Play</div>
                    </div>
                  </a>
                </div>

                <div class="flex items-center text-secondary-600">
                  <i class="fas fa-shield-alt text-primary-500 mr-2"></i>
                  <span class="text-sm">Secure & HIPAA compliant • Thousands of pet parents trust SnoutIQ</span>
                </div>
              </div>
            </div>

            <div class="relative">
              <div class="bg-white rounded-2xl shadow-xl p-8 max-w-md mx-auto">
                <div class="text-center mb-8">
                  <h3 class="font-heading text-2xl font-bold text-secondary-900 mb-2">Scan to Download</h3>
                  <p class="text-secondary-600">Use your phone camera to scan the QR code</p>
                </div>

                <div class="flex flex-col items-center">
                  <div class="qr-code p-6 rounded-xl mb-6">
                    <div class="w-48 h-48 bg-gradient-to-br from-primary-100 to-primary-300 rounded-lg flex items-center justify-center">
                      <div class="w-40 h-40 bg-white rounded flex items-center justify-center">
                        <img src="{{ $downloadQrSrc }}" alt="QR code to download the SnoutIQ app" class="w-32 h-32 object-contain rounded"/>
                      </div>
                    </div>
                  </div>

                  <div class="text-center">
                    <h4 class="font-medium text-secondary-900 mb-2">App Features</h4>
                    <div class="grid grid-cols-2 gap-3 text-sm text-secondary-600">
                      <div class="flex items-center">
                        <i class="fas fa-calendar-check text-primary-500 mr-2"></i>
                        <span>Easy Booking</span>
                      </div>
                      <div class="flex items-center">
                        <i class="fas fa-file-medical text-primary-500 mr-2"></i>
                        <span>Health Records</span>
                      </div>
                      <div class="flex items-center">
                        <i class="fas fa-video text-primary-500 mr-2"></i>
                        <span>Virtual Visits</span>
                      </div>
                      <div class="flex items-center">
                        <i class="fas fa-bell text-primary-500 mr-2"></i>
                        <span>Medication Alerts</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="absolute -bottom-6 -left-6 w-32 h-32 bg-primary-100 rounded-full -z-10"></div>
              <div class="absolute -top-6 -right-6 w-40 h-40 bg-primary-50 rounded-full -z-10"></div>
            </div>
          </div>
        </div>
      </section>

      @if(!empty($websiteAbout) || !empty($websiteGallery))
        @php
          $showAbout = !empty($websiteAbout);
          $showGallery = !empty($websiteGallery);
          $gridCols = ($showAbout && $showGallery) ? 'lg:grid-cols-2' : 'lg:grid-cols-1';
        @endphp
        <section id="clinic-story" class="section-padding bg-white">
          <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid {{ $gridCols }} gap-10 items-start">
              @if($showAbout)
                <div>
                  <div class="inline-flex items-center px-3 py-1 rounded-full bg-secondary-100 text-secondary-700 text-sm font-medium mb-4">
                    <i class="fas fa-heart mr-2"></i> Our Story
                  </div>
                  <h2 class="font-heading text-3xl md:text-4xl font-bold text-secondary-900 mb-4">About {{ $clinicName }}</h2>
                  <p class="text-secondary-600 text-lg leading-relaxed">{{ $websiteAbout }}</p>
                </div>
              @endif

              @if($showGallery)
                <div>
                  <div class="inline-flex items-center px-3 py-1 rounded-full bg-primary-100 text-primary-700 text-sm font-medium mb-4">
                    <i class="fas fa-camera mr-2"></i> Clinic Gallery
                  </div>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($websiteGallery as $photo)
                      @php
                        $photoSrc = $photo;
                        if (!\Illuminate\Support\Str::startsWith($photoSrc, ['http://', 'https://', 'data:image', '/'])) {
                            $photoSrc = asset($photoSrc);
                        }
                      @endphp
                      <div class="overflow-hidden rounded-2xl bg-secondary-50 shadow-md">
                        <img src="{{ $photoSrc }}" alt="Clinic photo" class="h-48 w-full object-cover">
                      </div>
                    @endforeach
                  </div>
                </div>
              @endif
            </div>
          </div>
        </section>
      @endif

      <!-- Doctor Showcase -->
      <section id="doctors" class="section-padding bg-secondary-50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
          <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="font-heading text-3xl md:text-4xl font-bold text-secondary-900 mb-4">Meet Our Veterinary Team</h2>
            <p class="text-lg text-secondary-600">Our caring team is dedicated to providing exceptional care for your pets.</p>
          </div>

          @if($doctors->isNotEmpty())
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
              @foreach($doctors as $doc)
                <div class="bg-white rounded-xl overflow-hidden card-hover">
                  <div class="h-64 bg-gradient-to-r from-primary-100 to-primary-200 flex items-center justify-center">
                    @php
                      $doctorImageSrc = null;
                      if (!empty($doc->doctor_image)) {
                          $doctorImageSrc = $doc->doctor_image;
                          if (!\Illuminate\Support\Str::startsWith($doctorImageSrc, ['http://', 'https://', 'data:image', '/'])) {
                              $doctorImageSrc = asset($doctorImageSrc);
                          }
                      }
                    @endphp
                    @if(!empty($doctorImageSrc))
                      <img src="{{ $doctorImageSrc }}" alt="{{ $doc->doctor_name ?: 'Doctor' }}" class="w-40 h-40 rounded-full object-cover border-4 border-white shadow-md">
                    @else
                      <div class="text-center">
                        <div class="w-40 h-40 rounded-full bg-white mx-auto mb-4 flex items-center justify-center">
                          <i class="fas fa-user-md text-primary-500 text-6xl"></i>
                        </div>
                        <h3 class="font-heading text-2xl font-bold text-secondary-900">{{ $doc->doctor_name ?: 'Doctor' }}</h3>
                        @if(!empty($doc->doctor_license))
                          <p class="text-primary-600 font-medium">License {{ $doc->doctor_license }}</p>
                        @endif
                      </div>
                    @endif
                  </div>
                  <div class="p-6">
                    <p class="text-secondary-600 mb-4">
                      {{ $doc->doctor_description ?? $doc->doctor_email ?? 'Available for consults via SnoutIQ. Connect for appointments and follow-ups.' }}
                    </p>
                    <div class="flex items-center text-sm text-secondary-500">
                      @if(!empty($doc->doctor_email))
                        <i class="fas fa-envelope mr-2"></i>
                        <span>{{ $doc->doctor_email }}</span>
                      @elseif(!empty($doc->doctor_license))
                        <i class="fas fa-id-card mr-2"></i>
                        <span>License {{ $doc->doctor_license }}</span>
                      @else
                        <i class="fas fa-graduation-cap mr-2"></i>
                        <span>Clinic veterinarian</span>
                      @endif
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
              <div class="bg-white rounded-xl overflow-hidden card-hover">
                <div class="h-64 bg-gradient-to-r from-primary-100 to-primary-200 flex items-center justify-center">
                  <div class="text-center">
                    <div class="w-40 h-40 rounded-full bg-white mx-auto mb-4 flex items-center justify-center">
                      <i class="fas fa-user-md text-primary-500 text-6xl"></i>
                    </div>
                    <h3 class="font-heading text-2xl font-bold text-secondary-900">Dr. Sarah Johnson</h3>
                    <p class="text-primary-600 font-medium">Chief Veterinarian</p>
                  </div>
                </div>
                <div class="p-6">
                  <p class="text-secondary-600 mb-4">DVM with 15+ years of experience in internal medicine and surgery. Special interest in feline medicine.</p>
                  <div class="flex items-center text-sm text-secondary-500">
                    <i class="fas fa-graduation-cap mr-2"></i>
                    <span>University of California, Davis</span>
                  </div>
                </div>
              </div>

              <div class="bg-white rounded-xl overflow-hidden card-hover">
                <div class="h-64 bg-gradient-to-r from-primary-100 to-primary-200 flex items-center justify-center">
                  <div class="text-center">
                    <div class="w-40 h-40 rounded-full bg-white mx-auto mb-4 flex items-center justify-center">
                      <i class="fas fa-user-md text-primary-500 text-6xl"></i>
                    </div>
                    <h3 class="font-heading text-2xl font-bold text-secondary-900">Dr. Michael Chen</h3>
                    <p class="text-primary-600 font-medium">Surgical Specialist</p>
                  </div>
                </div>
                <div class="p-6">
                  <p class="text-secondary-600 mb-4">Expertise in orthopedic and soft tissue surgery. Passionate about pain management and rehabilitation.</p>
                  <div class="flex items-center text-sm text-secondary-500">
                    <i class="fas fa-graduation-cap mr-2"></i>
                    <span>Cornell University College of Veterinary Medicine</span>
                  </div>
                </div>
              </div>

              <div class="bg-white rounded-xl overflow-hidden card-hover">
                <div class="h-64 bg-gradient-to-r from-primary-100 to-primary-200 flex items-center justify-center">
                  <div class="text-center">
                    <div class="w-40 h-40 rounded-full bg-white mx-auto mb-4 flex items-center justify-center">
                      <i class="fas fa-user-md text-primary-500 text-6xl"></i>
                    </div>
                    <h3 class="font-heading text-2xl font-bold text-secondary-900">Dr. Emily Rodriguez</h3>
                    <p class="text-primary-600 font-medium">Emergency & Critical Care</p>
                  </div>
                </div>
                <div class="p-6">
                  <p class="text-secondary-600 mb-4">Specializing in emergency medicine and critical care. Available for urgent consultations.</p>
                  <div class="flex items-center text-sm text-secondary-500">
                    <i class="fas fa-graduation-cap mr-2"></i>
                    <span>University of Florida College of Veterinary Medicine</span>
                  </div>
                </div>
              </div>
            </div>
          @endif

          <div class="text-center mt-12">
            <a href="#app-download" class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-3 px-8 rounded-lg transition-colors shadow-md inline-flex items-center">
              <i class="fas fa-calendar-alt mr-2"></i> Schedule Appointment with Our Team
            </a>
          </div>
        </div>
      </section>

      <!-- Testimonials -->
      <section id="testimonials" class="section-padding bg-white">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
          <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="font-heading text-3xl md:text-4xl font-bold text-secondary-900 mb-4">What Pet Parents Say</h2>
            <p class="text-lg text-secondary-600">Here is what our clients have to say about their experience at {{ $clinicName }}.</p>
          </div>

          <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="testimonial-card rounded-xl p-6">
              <div class="flex items-center mb-6">
                <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center mr-4">
                  <i class="fas fa-user text-primary-600"></i>
                </div>
                <div>
                  <h4 class="font-bold text-secondary-900">Jennifer L.</h4>
                  <div class="flex text-amber-400">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                  </div>
                </div>
              </div>
              <p class="text-secondary-600 italic mb-4">"Exceptional care and a seamless experience with the SnoutIQ app for follow-ups and medication reminders."</p>
              <div class="flex items-center text-sm text-secondary-500">
                <i class="fas fa-paw mr-2"></i>
                <span>Cat parent for 3 years</span>
              </div>
            </div>

            <div class="testimonial-card rounded-xl p-6">
              <div class="flex items-center mb-6">
                <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center mr-4">
                  <i class="fas fa-user text-primary-600"></i>
                </div>
                <div>
                  <h4 class="font-bold text-secondary-900">Robert K.</h4>
                  <div class="flex text-amber-400">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                  </div>
                </div>
              </div>
              <p class="text-secondary-600 italic mb-4">"The team treated our pet like family. App notifications for medication times have been a lifesaver."</p>
              <div class="flex items-center text-sm text-secondary-500">
                <i class="fas fa-paw mr-2"></i>
                <span>Dog parent for 5 years</span>
              </div>
            </div>

            <div class="testimonial-card rounded-xl p-6">
              <div class="flex items-center mb-6">
                <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center mr-4">
                  <i class="fas fa-user text-primary-600"></i>
                </div>
                <div>
                  <h4 class="font-bold text-secondary-900">Amanda S.</h4>
                  <div class="flex text-amber-400">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                  </div>
                </div>
              </div>
              <p class="text-secondary-600 italic mb-4">"As a first-time pet owner, the SnoutIQ resources and the clinic team helped me feel confident about my rabbit&apos;s care."</p>
              <div class="flex items-center text-sm text-secondary-500">
                <i class="fas fa-paw mr-2"></i>
                <span>Rabbit parent for 1 year</span>
              </div>
            </div>
          </div>

          <div class="text-center mt-12">
            <div class="inline-flex items-center space-x-4">
              <div class="flex items-center text-secondary-700">
                <i class="fas fa-shield-alt text-primary-500 text-2xl mr-3"></i>
                <div class="text-left">
                  <div class="font-bold text-lg">4.9/5</div>
                  <div class="text-sm">Average Rating</div>
                </div>
              </div>
              <div class="h-8 w-px bg-secondary-200"></div>
              <div class="flex items-center text-secondary-700">
                <i class="fas fa-heart text-primary-500 text-2xl mr-3"></i>
                <div class="text-left">
                  <div class="font-bold text-lg">2,500+</div>
                  <div class="text-sm">Happy Pets</div>
                </div>
              </div>
              <div class="h-8 w-px bg-secondary-200"></div>
              <div class="flex items-center text-secondary-700">
                <i class="fas fa-award text-primary-500 text-2xl mr-3"></i>
                <div class="text-left">
                  <div class="font-bold text-lg">15</div>
                  <div class="text-sm">Years Experience</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Location & Map -->
      <section id="location" class="section-padding bg-white">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
          <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="font-heading text-3xl md:text-4xl font-bold text-secondary-900 mb-4">Visit {{ $clinicName }}</h2>
            <p class="text-lg text-secondary-600">Conveniently located with easy access. We welcome walk-ins for emergencies.</p>
          </div>

          <div class="grid lg:grid-cols-2 gap-12">
            <div>
              <div class="bg-secondary-50 rounded-xl p-8 mb-8">
                <h3 class="font-heading text-2xl font-bold text-secondary-900 mb-6">Contact Information</h3>

                <div class="space-y-6">
                  <div class="flex items-start">
                    <div class="w-10 h-10 rounded-lg bg-primary-50 flex items-center justify-center mr-4">
                      <i class="fas fa-map-marker-alt text-primary-600"></i>
                    </div>
                    <div>
                      <h4 class="font-medium text-secondary-900">Address</h4>
                      <p class="text-secondary-600">{{ $vet->formatted_address ?? $vet->address ?? 'Address coming soon' }}</p>
                    </div>
                  </div>

                  <div class="flex items-start">
                    <div class="w-10 h-10 rounded-lg bg-primary-50 flex items-center justify-center mr-4">
                      <i class="fas fa-clock text-primary-600"></i>
                    </div>
                    <div>
                      <h4 class="font-medium text-secondary-900">Hours</h4>
                      <p class="text-secondary-600">
                        <span class="font-medium">Mon-Fri:</span> 8:00 AM - 8:00 PM<br>
                        <span class="font-medium">Saturday:</span> 9:00 AM - 6:00 PM<br>
                        <span class="font-medium">Sunday:</span> 10:00 AM - 4:00 PM<br>
                        @if(!is_null($vet->open_now))
                          <span class="font-medium">Status:</span> {{ $vet->open_now ? 'Open now' : 'Closed now' }}
                        @endif
                      </p>
                    </div>
                  </div>

                  <div class="flex items-start">
                    <div class="w-10 h-10 rounded-lg bg-primary-50 flex items-center justify-center mr-4">
                      <i class="fas fa-phone text-primary-600"></i>
                    </div>
                    <div>
                      <h4 class="font-medium text-secondary-900">Contact</h4>
                      <p class="text-secondary-600">
                        @if(!empty($clinicPhone))
                          <span class="font-medium">Phone:</span> {{ $clinicPhone }}<br>
                        @endif
                        <span class="font-medium">Email:</span> {{ $clinicEmail }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <div class="bg-primary-50 rounded-xl p-6">
                <h4 class="font-heading text-xl font-bold text-secondary-900 mb-4">Before Your Visit</h4>
                <p class="text-secondary-600 mb-4">Download the SnoutIQ app to complete new patient forms, upload vaccination records, and check in before arrival.</p>
                <a href="{{ $appDownloadUrl }}" target="_blank" rel="noopener" class="inline-flex items-center text-primary-600 font-medium hover:text-primary-700">
                  <i class="fas fa-external-link-alt mr-2"></i>
                  <span>Get the app for faster check-in</span>
                </a>
              </div>
            </div>

            <div>
              @if($mapSrc)
                <div class="rounded-xl overflow-hidden shadow-lg h-full min-h-96 border border-secondary-100">
                  <iframe
                    src="{{ $mapSrc }}"
                    class="w-full h-full min-h-96"
                    style="border:0"
                    allowfullscreen
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
              @else
                <div class="bg-gradient-to-br from-primary-100 to-primary-200 rounded-xl h-full min-h-96 flex items-center justify-center">
                  <div class="text-center p-8">
                    <div class="w-20 h-20 rounded-full bg-white flex items-center justify-center mx-auto mb-6">
                      <i class="fas fa-map-marked-alt text-primary-600 text-3xl"></i>
                    </div>
                    <h3 class="font-heading text-2xl font-bold text-secondary-900 mb-4">Our Location</h3>
                    <p class="text-secondary-700 mb-6">Interactive map will be shown here once the clinic address is finalized.</p>
                    <div class="inline-flex items-center space-x-2 text-primary-700 bg-white/70 px-4 py-2 rounded-lg">
                      <i class="fas fa-car"></i>
                      <span>Free parking available</span>
                    </div>
                    <div class="mt-4 inline-flex items-center space-x-2 text-primary-700 bg-white/70 px-4 py-2 rounded-lg">
                      <i class="fas fa-wheelchair"></i>
                      <span>Wheelchair accessible</span>
                    </div>
                  </div>
                </div>
              @endif
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- Footer -->
    <footer class="bg-secondary-900 text-white pt-12 pb-8">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-2 lg:grid-cols-5 gap-8 mb-12">
          <div class="lg:col-span-2">
            <div class="flex items-center mb-6">
              <div class="w-12 h-12 rounded-full bg-primary-500 flex items-center justify-center mr-4">
                <i class="fas fa-paw text-white text-xl"></i>
              </div>
              <div>
                <h2 class="font-heading text-2xl font-bold">{{ $clinicName }}</h2>
                <p class="text-secondary-300">Veterinary Clinic</p>
              </div>
            </div>
            <p class="text-secondary-300 mb-6 max-w-md">Compassionate, comprehensive veterinary care for your furry family members. Partnered with SnoutIQ for seamless digital pet health management.</p>
            <div class="flex space-x-4">
              <a href="#" class="w-10 h-10 rounded-full bg-secondary-800 hover:bg-secondary-700 flex items-center justify-center">
                <i class="fab fa-facebook-f"></i>
              </a>
              <a href="#" class="w-10 h-10 rounded-full bg-secondary-800 hover:bg-secondary-700 flex items-center justify-center">
                <i class="fab fa-instagram"></i>
              </a>
              <a href="#" class="w-10 h-10 rounded-full bg-secondary-800 hover:bg-secondary-700 flex items-center justify-center">
                <i class="fab fa-twitter"></i>
              </a>
              <a href="#" class="w-10 h-10 rounded-full bg-secondary-800 hover:bg-secondary-700 flex items-center justify-center">
                <i class="fab fa-yelp"></i>
              </a>
            </div>
          </div>

          <div>
            <h3 class="font-heading text-lg font-bold mb-6">Quick Links</h3>
            <ul class="space-y-3">
              <li><a href="#services" class="text-secondary-300 hover:text-white transition-colors">Services</a></li>
              <li><a href="#doctors" class="text-secondary-300 hover:text-white transition-colors">Our Team</a></li>
              <li><a href="#testimonials" class="text-secondary-300 hover:text-white transition-colors">Testimonials</a></li>
              <li><a href="#location" class="text-secondary-300 hover:text-white transition-colors">Location</a></li>
            </ul>
          </div>

          <div>
            <h3 class="font-heading text-lg font-bold mb-6">Policies</h3>
            <ul class="space-y-3">
              <li><a href="https://snoutiq.com/privacy-policy" class="text-secondary-300 hover:text-white transition-colors">Privacy Policy</a></li>
              <li><a href="https://snoutiq.com/terms-of-service" class="text-secondary-300 hover:text-white transition-colors">Terms of Service</a></li>
              <li><a href="https://snoutiq.com/cancellation-policy" class="text-secondary-300 hover:text-white transition-colors">Financial Policy</a></li>
              <li><a href="https://snoutiq.com/terms-of-service" class="text-secondary-300 hover:text-white transition-colors">Appointment Policy</a></li>
              <li><a href="https://snoutiq.com/medical-data-consent" class="text-secondary-300 hover:text-white transition-colors">Emergency Protocol</a></li>
            </ul>
          </div>

          <div>
            <h3 class="font-heading text-lg font-bold mb-6">SnoutIQ App</h3>
            <ul class="space-y-3">
              <li><a href="#app-download" class="text-secondary-300 hover:text-white transition-colors">Download Now</a></li>
              <li><a href="{{ $appDownloadUrl }}" class="text-secondary-300 hover:text-white transition-colors">App Features</a></li>
              <li><a href="https://snoutiq.com/contact" class="text-secondary-300 hover:text-white transition-colors">FAQ</a></li>
              <li><a href="https://snoutiq.com/contact" class="text-secondary-300 hover:text-white transition-colors">Support</a></li>
              <li><a href="https://snoutiq.com" class="text-secondary-300 hover:text-white transition-colors">Pet Health Resources</a></li>
            </ul>
          </div>
        </div>

        <div class="pt-8 border-t border-secondary-800 flex flex-col md:flex-row justify-between items-center">
          <div class="text-secondary-400 text-sm mb-4 md:mb-0">
            &copy; {{ date('Y') }} {{ $clinicName }}. All rights reserved.
          </div>
          <div class="flex items-center">
            <div class="flex items-center mr-6">
              <i class="fas fa-shield-alt text-primary-400 mr-2"></i>
              <span class="text-sm text-secondary-300">HIPAA Compliant</span>
            </div>
            <div class="flex items-center">
              <i class="fas fa-award text-primary-400 mr-2"></i>
              <span class="text-sm text-secondary-300">AAHA Accredited</span>
            </div>
          </div>
        </div>

        <div class="mt-8 text-center text-secondary-500 text-xs">
          <p>This website is for demonstration purposes. {{ $clinicName }} showcases the SnoutIQ platform integration.</p>
        </div>
      </div>
    </footer>

    <!-- Fixed App Download CTA (Mobile) -->
    <div class="fixed bottom-0 left-0 right-0 bg-primary-700 text-white p-4 flex justify-between items-center md:hidden z-40">
      <div>
        <div class="font-medium">Get the SnoutIQ App</div>
        <div class="text-sm text-primary-200">For appointments & pet health tracking</div>
      </div>
      <a href="{{ $appDownloadUrl }}" class="bg-white text-primary-700 font-semibold py-2 px-4 rounded-lg">
        Download
      </a>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
          anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if(targetId === '#') return;
            const targetElement = document.querySelector(targetId);
            if(targetElement) {
              window.scrollTo({
                top: targetElement.offsetTop - 80,
                behavior: 'smooth'
              });
            }
          });
        });

        // Add active state to navigation on scroll
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('nav a');

        window.addEventListener('scroll', function() {
          let current = '';
          sections.forEach(section => {
            const sectionTop = section.offsetTop;
            if(scrollY >= (sectionTop - 100)) {
              current = section.getAttribute('id');
            }
          });

          navLinks.forEach(link => {
            link.classList.remove('text-primary-600');
            link.classList.add('text-secondary-700');

            if(link.getAttribute('href').substring(1) === current) {
              link.classList.remove('text-secondary-700');
              link.classList.add('text-primary-600');
            }
          });
        });
      });
    </script>

    <noscript>
      <img height="1" width="1" style="display:none"
           src="https://www.facebook.com/tr?id=1909812439872823&ev=PageView&noscript=1"/>
    </noscript>
  @endif

  @if(! empty($publicUrl))
  <script>
  (function() {
    if (window.__snoutiqPermanentShortLinkPinged) {
      return;
    }

    var fetchUrl = null;

    try {
      var params = new URLSearchParams(window.location.search);
      var slugParam = params.get('slug');
      if (slugParam) {
        fetchUrl = new URL(
          '/backend/legacy-qr/' + encodeURIComponent(slugParam),
          window.location.origin
        ).toString();
      }
    } catch (err) {
      if (typeof console !== 'undefined' && typeof console.debug === 'function') {
        console.debug('short-link slug parse failed', err);
      }
    }

    if (!fetchUrl) {
      var link = document.querySelector('[data-permanent-short-link]');
      if (link) {
        fetchUrl = link.getAttribute('href');
      }
    }

    if (!fetchUrl) {
      return;
    }

    window.__snoutiqPermanentShortLinkPinged = true;

    try {
      fetch(fetchUrl, {
        method: 'GET',
        credentials: 'include',
        cache: 'no-store'
      }).catch(function(err) {
        if (typeof console !== 'undefined' && typeof console.debug === 'function') {
          console.debug('short-link ping failed', err);
        }
      });
    } catch (err) {
      if (typeof console !== 'undefined' && typeof console.debug === 'function') {
        console.debug('short-link ping error', err);
      }
    }
  })();
  </script>
  @endif

  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "MedicalClinic",
    "name": "{{ $clinicName }}",
    "image": "{{ $vet->image ?? $snoutiqLogo }}",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "{{ $vet->formatted_address ?? $vet->address ?? '' }}",
      "addressLocality": "{{ $vet->city ?? '' }}",
      "postalCode": "{{ $vet->pincode ?? '' }}",
      "addressCountry": "IN"
    },
    "telephone": "{{ $clinicPhone ?? '' }}",
    "url": "{{ url('/backend/vet/'.$vet->slug) }}"
  }
  </script>
</body>
</html>
