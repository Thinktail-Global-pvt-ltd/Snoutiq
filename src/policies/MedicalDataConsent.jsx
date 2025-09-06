import React from "react";

export default function MedicalDataConsent() {
  return (
    <div className="max-w-5xl mx-auto px-4 py-8 text-gray-800">
      <h1 className="text-2xl font-bold mb-2">
        User Consent for Collection & Processing of Medical Data
      </h1>
      <p className="text-sm text-gray-500 mb-6">Last Updated: May 31, 2025</p>

      <p className="mb-6">
        This User Consent (“Consent”) describes how Thinktail Global Pvt. Ltd.
        (“Thinktail,” “we,” “us,” or “our”) collects, uses, discloses, and
        protects the medical and health-related data you (“User,” “you,” or
        “your”) provide on the Snoutiq website and mobile application (together,
        “Snoutiq” or the “Platform”). This Consent is a standalone document
        supplementing Snoutiq’s Privacy Policy. By providing any medical or
        health information (including pet-related health data) through
        Snoutiq—whether during a consultation, by uploading documents, or when
        completing forms—you acknowledge and agree to the terms of this Consent.
        If you do not consent, please do not submit any medical data on Snoutiq.
      </p>

      <Section title="1. Why We Need Your Consent">
        <ul className="list-disc ml-6 space-y-2">
          <li>Facilitate accurate diagnosis and treatment</li>
          <li>Enable veterinarians to provide personalized care</li>
          <li>Store and access pet health records securely</li>
          <li>
            Comply with applicable regulations on processing “Sensitive Personal
            Data or Information” (SPDI) under India’s IT Act and related rules
          </li>
        </ul>
        <p>
          We must obtain your explicit, informed consent before collecting,
          storing, or processing any medical or health-related data you provide.
        </p>
      </Section>

      <Section title="2. Types of Medical Data Collected">
        <SubSection subtitle="Pet Identification & Demographics">
          Pet’s name, species, breed, age, sex, microchip ID, and owner’s
          relationship to pet.
        </SubSection>
        <SubSection subtitle="Medical History & Health Records">
          Diagnoses, vaccination records, medications, allergies, surgeries,
          diagnostic reports, and images/videos.
        </SubSection>
        <SubSection subtitle="Symptoms & Current Condition">
          Descriptions of symptoms, duration, severity, and triggers.
        </SubSection>
        <SubSection subtitle="Treatment & Prescription Data">
          Prescribed medications, therapies, dietary plans, and follow-up notes.
        </SubSection>
        <SubSection subtitle="Consultation & Communication Records">
          Teleconsultation recordings (with permission), chat transcripts, and
          veterinarian notes.
        </SubSection>
        <SubSection subtitle="Billing & Payment-Related Medical Information">
          Diagnostic fee breakdowns, insurance policy numbers, and claim data.
        </SubSection>
        <SubSection subtitle="Third-Party Health Data">
          Data from linked wearable devices or third-party veterinary platforms
          (with your separate consent).
        </SubSection>
      </Section>

      <Section title="3. Purpose & Legal Basis for Processing">
        <ul className="list-disc ml-6 space-y-2">
          <li>Provision of veterinary services and continuity of care</li>
          <li>Compliance with medical record regulations and guidelines</li>
          <li>Responding to legal or regulatory requests</li>
          <li>Quality control, training, and anonymized analytics</li>
          <li>Emergency care and notifications</li>
        </ul>
        <p>
          Legal Basis: Under India’s IT Act and Rules, processing SPDI requires
          explicit consent. By accepting this Consent, you authorize Thinktail
          to process your medical data.
        </p>
      </Section>

      <Section title="4. How We Share & Disclose Your Medical Data">
        <SubSection subtitle="With Your Explicit Consent">
          Shared with specialists or referrals only with your confirmation.
        </SubSection>
        <SubSection subtitle="Authorized Service Providers">
          Secure cloud storage and telemedicine technology providers.
        </SubSection>
        <SubSection subtitle="Regulatory Authorities">
          Shared if required by law or court order.
        </SubSection>
        <SubSection subtitle="Emergency Situations">
          Shared with emergency hospitals if your pet’s condition is
          life-threatening.
        </SubSection>
        <SubSection subtitle="Anonymized Data">
          Used for research or analytics without identifiers.
        </SubSection>
      </Section>

      <Section title="5. Your Rights Regarding Medical Data">
        <ul className="list-disc ml-6 space-y-2">
          <li>Right to Access</li>
          <li>Right to Rectification</li>
          <li>Right to Restrict or Withdraw Consent</li>
          <li>Right to Erasure</li>
          <li>Right to Data Portability</li>
          <li>Right to Object to Processing</li>
          <li>Right to Lodge a Complaint</li>
        </ul>
      </Section>

      <Section title="6. Retention & Secure Storage">
        <p>
          Records are retained for a minimum of seven (7) years, encrypted at
          rest and in transit, with strict access controls and audits.
        </p>
      </Section>

      <Section title="7. Data Sharing & Third-Party Disclosures">
        <p>
          Data may be shared with veterinarians, telemedicine providers,
          diagnostic labs, and shipping partners (for sample transport).
          Disclosures to authorities are logged and notified when permitted.
        </p>
      </Section>

      <Section title="8. How to Grant & Withdraw Consent">
        <p>
          You must click “I Agree” before submitting medical data. Withdrawal is
          possible anytime via app settings or by emailing
          privacy@snoutiq.com.
        </p>
      </Section>

      <Section title="9. Risks of Withdrawing Consent">
        <p>
          Withdrawing consent may impact veterinary care, disable features, and
          delay emergency treatment.
        </p>
      </Section>

      <Section title="10. Contact Information & Complaints">
        <p>
          Data Protection Officer, Thinktail Global Pvt. Ltd., Sector-63, Noida,
          India. Email: privacy@snoutiq.com. Phone: +91-120-XXXXXXX.
        </p>
      </Section>

      <Section title="11. Amendments to This Consent">
        <p>
          Updates will be posted with prior notice. Continued use of Snoutiq
          after updates implies acceptance.
        </p>
      </Section>

      <p className="mt-6 font-medium">
        By clicking “I Agree” (or submitting any medical data), you confirm that
        you have read, understood, and consent to this policy.
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
    <div className="mb-3">
      <h3 className="font-medium mb-1">{subtitle}</h3>
      <div>{children}</div>
    </div>
  );
}
