import React from "react";

export default function CookiePolicy() {
  return (
    <div className="max-w-5xl mx-auto px-4 py-8 text-gray-800">
      <h1 className="text-2xl font-bold mb-2">Cookie Policy</h1>
      <p className="text-sm text-gray-500 mb-6">Last Updated: May 31, 2025 </p>

      <p className="mb-6">
        This Cookie Policy explains how Snoutiq (operated by Thinktail Global Pvt.
        Ltd.) uses cookies and similar tracking technologies on the Snoutiq website
        and mobile application (collectively, “Snoutiq” or the “Platform”). By
        accessing or using Snoutiq, you (“User,” “you,” or “your”) consent to our use
        of cookies as described below. If you do not agree, please disable cookies as
        instructed in Section 4 or refrain from using Snoutiq.
      </p>

      <Section title="1. What Are Cookies?">
        <p>Cookies are small text files placed on your device when you visit Snoutiq.</p>
        <ul className="list-disc ml-6 mt-2 space-y-1">
          <li>Session Cookies: Deleted automatically when you close your browser or app.</li>
          <li>Persistent Cookies: Remain until expiry or manual deletion.</li>
        </ul>
      </Section>

      <Section title="2. Why Snoutiq Uses Cookies">
        <SubSection subtitle="Essential Functionality">
          <ul className="list-disc ml-6 space-y-1">
            <li>Authenticate your account and keep you logged in.</li>
            <li>Prevent fraudulent activity and enable secure checkout.</li>
          </ul>
        </SubSection>
        <SubSection subtitle="Performance & Analytics">
          <ul className="list-disc ml-6 space-y-1">
            <li>Understand how you interact with Snoutiq (pages, sessions, errors).</li>
            <li>Measure marketing effectiveness and improve experience.</li>
          </ul>
        </SubSection>
        <SubSection subtitle="Personalization & Preferences">
          <ul className="list-disc ml-6 space-y-1">
            <li>Remember language, region, and display preferences.</li>
            <li>Keep items in your cart or wishlist.</li>
          </ul>
        </SubSection>
        <SubSection subtitle="Advertising & Marketing">
          <ul className="list-disc ml-6 space-y-1">
            <li>Deliver targeted ads on Snoutiq and third-party sites.</li>
            <li>Measure ad performance and limit repetition.</li>
          </ul>
        </SubSection>
      </Section>

      <Section title="3. Categories of Cookies We Use">
        <ul className="list-disc ml-6 space-y-1">
          <li><strong>Strictly Necessary:</strong> Authentication & security cookies.</li>
          <li><strong>Performance & Analytics:</strong> e.g., Google Analytics, heatmaps.</li>
          <li><strong>Functionality:</strong> Preferences, region, cart/wishlist.</li>
          <li><strong>Targeting/Advertising:</strong> Third-party ad & tracking cookies.</li>
        </ul>
      </Section>

      <Section title="4. How to Control or Opt Out of Cookies">
        <SubSection subtitle="4.1 Browser Settings">
          Most browsers allow blocking/deleting cookies (all, third-party, or session).
        </SubSection>
        <SubSection subtitle="4.2 In-App Cookie Settings">
          <p>Manage preferences in Cookie Settings (web footer / app settings).</p>
        </SubSection>
        <SubSection subtitle="4.3 Do Not Track (DNT)">
          Snoutiq honors DNT for third-party cookies but still sets essentials.
        </SubSection>
      </Section>

      <Section title="5. Third-Party Cookies & Partners">
        <ul className="list-disc ml-6 space-y-1">
          <li><strong>Analytics:</strong> Google Analytics, Mixpanel.</li>
          <li><strong>Advertising:</strong> Google Ads, Facebook Ads, Taboola.</li>
          <li><strong>Social Plugins:</strong> Facebook, Instagram embeds.</li>
          <li><strong>Payments:</strong> Razorpay, PayU for secure transactions.</li>
          <li><strong>Video SDKs:</strong> Zoom, Agora for teleconsultations.</li>
        </ul>
      </Section>

      <Section title="6. Consequences of Disabling Cookies">
        <ul className="list-disc ml-6 space-y-1">
          <li><strong>Essential:</strong> Snoutiq will not function correctly.</li>
          <li><strong>Performance:</strong> May slow down or prevent issue diagnosis.</li>
          <li><strong>Functionality:</strong> Preferences & carts not remembered.</li>
          <li><strong>Advertising:</strong> Ads shown but less relevant.</li>
        </ul>
      </Section>

      <Section title="7. Cookie Lifespan & Examples">
        <table className="w-full text-sm border border-gray-300">
          <thead>
            <tr className="bg-gray-100">
              <th className="border px-2 py-1 text-left">Category</th>
              <th className="border px-2 py-1 text-left">Example</th>
              <th className="border px-2 py-1 text-left">Purpose</th>
              <th className="border px-2 py-1 text-left">Expires</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td className="border px-2 py-1">Strictly Necessary</td>
              <td className="border px-2 py-1">snoutiq_session</td>
              <td className="border px-2 py-1">Maintains login session</td>
              <td className="border px-2 py-1">Session</td>
            </tr>
            <tr>
              <td className="border px-2 py-1">Performance</td>
              <td className="border px-2 py-1">_ga, _gid, _gat</td>
              <td className="border px-2 py-1">Track user behavior</td>
              <td className="border px-2 py-1">2y, 24h, 1m</td>
            </tr>
            <tr>
              <td className="border px-2 py-1">Functionality</td>
              <td className="border px-2 py-1">language_pref</td>
              <td className="border px-2 py-1">Save language/region</td>
              <td className="border px-2 py-1">1 year</td>
            </tr>
            <tr>
              <td className="border px-2 py-1">Advertising</td>
              <td className="border px-2 py-1">fr, IDE</td>
              <td className="border px-2 py-1">Track ad interactions</td>
              <td className="border px-2 py-1">3m, 2y</td>
            </tr>
          </tbody>
        </table>
      </Section>

      <Section title="8. Updates to This Cookie Policy">
        <p>
          We may update this Cookie Policy due to practice changes or regulations.
          Updated versions will show a revised date and may require re-consent.
        </p>
      </Section>

      <Section title="9. Contact Us">
        <p>
          Privacy & Data Protection Team <br />
          Thinktail Global Pvt. Ltd. (Snoutiq) <br />
          Plot No. 20, Block H-1/A, Sector-63, Noida-201301, Uttar Pradesh, India
          <br />
          Email: <a href="mailto:privacy@snoutiq.com" className="text-blue-600">privacy@snoutiq.com</a>
        </p>
      </Section>

      <p className="mt-6">
        By using Snoutiq, you acknowledge that you have read and understood this
        Cookie Policy and consent to our use of cookies and similar technologies.
      </p>
    </div>
  );
}

function Section({ title, children }) {
  return (
    <div className="mb-8">
      <h2 className="text-xl font-semibold mb-3">{title}</h2>
      <div className="space-y-3 text-gray-700 text-sm leading-relaxed">
        {children}
      </div>
    </div>
  );
}

function SubSection({ subtitle, children }) {
  return (
    <div className="mb-4">
      <h3 className="font-medium mb-1">{subtitle}</h3>
      <div>{children}</div>
    </div>
  );
}
