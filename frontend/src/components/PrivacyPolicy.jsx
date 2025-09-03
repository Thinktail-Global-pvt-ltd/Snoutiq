import React from "react";

export default function PrivacyPolicy() {
    return (
        <div className="max-w-4xl mx-auto px-6 py-8 bg-white">
            {/* Header */}
            <div className="border-b border-gray-200 pb-6 mb-8">
                <h1 className="text-3xl font-bold text-gray-900 mb-2">
                    Privacy Policy
                </h1>
                <p className="text-sm text-gray-600">
                    Last Updated: May 31, 2025
                </p>
            </div>

            {/* Introduction */}
            <div className="mb-8 p-4 bg-blue-50 border-l-4 border-blue-400 rounded-r-lg">
                <p className="text-gray-700 leading-relaxed">
                    Thinktail Global Pvt. Ltd. ("Thinktail," "we," "us," or "our") respects your privacy and is committed to protecting your personal data. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit or interact with the Snoutiq website or mobile application (together, "Snoutiq" or the "Platform"). Please read this Privacy Policy carefully. By accessing or using Snoutiq, you agree to the collection and use of your personal data in accordance with this policy. If you disagree with our policies and practices, do not use Snoutiq.
                </p>
            </div>

            {/* Section 1 - Definitions */}
            <section className="mb-8">
                <h2 className="text-2xl font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                    1. Definitions
                </h2>
                <div className="space-y-4">
                    {[
                        { term: "Personal Data or Personal Information", definition: "refers to any information relating to an identified or identifiable individual, including sensitive personal data or information (\"SPDI\") under India's Information Technology (Reasonable Security Practices and Procedures and Sensitive Personal Data or Information) Rules, 2011." },
                        { term: "User, you, or your", definition: "means any individual or entity accessing or using Snoutiq." },
                        { term: "Services", definition: "refers to all features and functionalities offered on Snoutiq, including product browsing, purchasing, veterinary consultations, medical content delivery, and related services." }
                    ].map((item, index) => (
                        <div key={index} className="bg-gray-50 p-4 rounded-lg">
                            <h3 className="font-semibold text-gray-900 mb-2">"{item.term}"</h3>
                            <p className="text-gray-700">{item.definition}</p>
                        </div>
                    ))}
                </div>
            </section>

            {/* Section 2 - Information We Collect */}
            <section className="mb-8">
                <h2 className="text-2xl font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                    2. Information We Collect
                </h2>
                <p className="text-gray-700 mb-6">
                    We collect various types of Personal Data from Users through different means: when you browse Snoutiq, register an account, place orders, book services, upload content, or otherwise interact with the Platform.
                </p>

                <div className="space-y-6">
                    <div>
                        <h3 className="text-xl font-medium text-gray-900 mb-4">2.1. Information You Provide Directly</h3>

                        <div className="space-y-4">
                            <div className="bg-green-50 border border-green-200 p-4 rounded-lg">
                                <h4 className="font-semibold text-green-900 mb-3">Account Registration & Profile Data</h4>
                                <ul className="list-disc pl-5 space-y-1 text-green-800">
                                    <li>Name</li>
                                    <li>Email address</li>
                                    <li>Phone number</li>
                                    <li>Password (hashed and stored securely)</li>
                                    <li>Profile picture (optional)</li>
                                    <li>Delivery address(es)</li>
                                </ul>
                            </div>

                            <div className="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                                <h4 className="font-semibold text-blue-900 mb-3">Seller/Veterinarian KYC & Professional Data</h4>
                                <ul className="list-disc pl-5 space-y-1 text-blue-800">
                                    <li>Government-issued ID (e.g., Aadhar, passport, driver's license) for identity verification</li>
                                    <li>Professional licenses and certifications (e.g., veterinarian registration number, clinic registration)</li>
                                    <li>Business registration documents (for Clinics or registered entities)</li>
                                    <li>Bank account or payment details (for payouts)</li>
                                </ul>
                            </div>

                            <div className="bg-yellow-50 border border-yellow-200 p-4 rounded-lg">
                                <h4 className="font-semibold text-yellow-900 mb-3">Transactional Data</h4>
                                <ul className="list-disc pl-5 space-y-1 text-yellow-800">
                                    <li>Order history (products purchased, prices, quantities)</li>
                                    <li>Service bookings (consultation date/time, service details)</li>
                                    <li>Payment information (last four digits of card, UPI ID, transaction reference); full card data and bank account details are collected and processed by our third-party payment gateways and not stored on our servers.</li>
                                </ul>
                            </div>

                            <div className="bg-red-50 border border-red-200 p-4 rounded-lg">
                                <h4 className="font-semibold text-red-900 mb-3">Medical & Pet Health Data</h4>
                                <ul className="list-disc pl-5 space-y-1 text-red-800">
                                    <li>Pet details (name, age, breed, medical history, vaccination records)</li>
                                    <li>Information shared during consultations (symptoms, diagnoses, prescriptions, treatment notes)</li>
                                    <li>User-provided pet photos or radiographs (when relevant to medical services)</li>
                                </ul>
                            </div>

                            <div className="bg-purple-50 border border-purple-200 p-4 rounded-lg">
                                <h4 className="font-semibold text-purple-900 mb-3">User-Generated Content & Communications</h4>
                                <ul className="list-disc pl-5 space-y-1 text-purple-800">
                                    <li>Reviews, ratings, feedback, and Q&A posted on product or provider pages</li>
                                    <li>Chat transcripts or call logs (text, audio, video) between Users and Providers (shared via in-App messaging or teleconsultation)</li>
                                    <li>Any other content you upload, post, or transmit through Snoutiq (e.g., photos, documents)</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 className="text-xl font-medium text-gray-900 mb-4">2.2. Information Collected Automatically</h3>

                        <div className="space-y-4">
                            <div className="bg-indigo-50 border border-indigo-200 p-4 rounded-lg">
                                <h4 className="font-semibold text-indigo-900 mb-3">Device & Usage Information</h4>
                                <ul className="list-disc pl-5 space-y-1 text-indigo-800">
                                    <li>IP address</li>
                                    <li>Browser type and version</li>
                                    <li>Operating system and device model</li>
                                    <li>Unique device identifiers (IDFA, Android Advertising ID)</li>
                                    <li>Log files (access time, pages viewed, errors)</li>
                                </ul>
                            </div>

                            <div className="bg-orange-50 border border-orange-200 p-4 rounded-lg">
                                <h4 className="font-semibold text-orange-900 mb-3">Cookies & Tracking Technologies</h4>
                                <p className="text-orange-800 mb-3">We use cookies, web beacons, local storage, and similar technologies to:</p>
                                <ul className="list-disc pl-5 space-y-1 text-orange-800">
                                    <li>Understand how you navigate and use Snoutiq (analytics)</li>
                                    <li>Remember your preferences and settings (language, region)</li>
                                    <li>Deliver targeted or interest-based advertising (where applicable)</li>
                                    <li>Ensure the security and integrity of your session (authentication cookies)</li>
                                </ul>
                                <p className="text-orange-800 mt-3 text-sm">
                                    You can control cookies via your browser settings; however, disabling essential cookies may impair your ability to use certain features of Snoutiq.
                                </p>
                            </div>

                            <div className="bg-teal-50 border border-teal-200 p-4 rounded-lg">
                                <h4 className="font-semibold text-teal-900 mb-3">Location Data (if enabled)</h4>
                                <ul className="list-disc pl-5 space-y-1 text-teal-800">
                                    <li>Approximate location (via IP geolocation)</li>
                                    <li>Precise location (if you grant "Location Services" permission on your mobile device)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Section 3 - How We Use Your Information */}
            <section className="mb-8">
                <h2 className="text-2xl font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                    3. How We Use Your Information
                </h2>
                <p className="text-gray-700 mb-6">
                    We process your Personal Data for the following purposes, always ensuring a lawful basis under applicable data protection laws:
                </p>

                <div className="space-y-4">
                    {[
                        {
                            title: "Provision of Services",
                            items: [
                                "Create and manage your Snoutiq account",
                                "Facilitate browsing, shopping, and service bookings (video consultations, grooming, training)",
                                "Process payments, hold funds in escrow, and remit payouts to Sellers/Providers",
                                "Coordinate shipping, delivery, and order fulfillment through third-party couriers (Blinkit, Porter, etc.)",
                                "Schedule, conduct, and record teleconsultations between Users and Veterinarians",
                                "Provide relevant medical content, dosage calculators, and educational materials"
                            ],
                            color: "blue"
                        },
                        {
                            title: "Verification & Compliance",
                            items: [
                                "Verify identity and professional credentials of Sellers and Veterinarians (KYC) to comply with legal and regulatory obligations",
                                "Prevent fraud, money laundering, and unauthorized access",
                                "Comply with tax, accounting, and record-keeping requirements"
                            ],
                            color: "green"
                        },
                        {
                            title: "Customer Support & Dispute Resolution",
                            items: [
                                "Respond to your inquiries, complaints, and requests via email, chat, or phone",
                                "Mediate disputes between Buyers and Sellers/Providers, initiate refunds, and resolve chargebacks",
                                "Enforce our Terms & Conditions (including Cancellation & Refund Policy)"
                            ],
                            color: "yellow"
                        },
                        {
                            title: "Improvements & Personalization",
                            items: [
                                "Analyze usage patterns to improve Snoutiq's performance, features, and user experience",
                                "Personalize product recommendations, promotions, and marketing communications (where consented)",
                                "Conduct A/B testing, surveys, and feedback collection to optimize our Services"
                            ],
                            color: "purple"
                        },
                        {
                            title: "Marketing & Communications",
                            items: [
                                "Send transactional emails (order confirmations, shipping notifications, appointment reminders)",
                                "With your consent, send you promotional messages (newsletters, offers, targeted ads) via email, SMS, or in-App notifications",
                                "Provide opt-out or unsubscribe mechanisms in all marketing communications"
                            ],
                            color: "indigo"
                        },
                        {
                            title: "Security & Legal Compliance",
                            items: [
                                "Protect against, detect, and respond to security incidents (intrusions, data breaches)",
                                "Enforce our legal rights (e.g., prevent misuse of Snoutiq, defend against claims)",
                                "Comply with applicable laws, regulations, and court orders (e.g., share data with law enforcement when legally required)"
                            ],
                            color: "red"
                        }
                    ].map((section, index) => (
                        <div key={index} className={`bg-${section.color}-50 border border-${section.color}-200 p-4 rounded-lg`}>
                            <h3 className={`font-semibold text-${section.color}-900 mb-3`}>{section.title}</h3>
                            <ul className={`list-disc pl-5 space-y-1 text-${section.color}-800`}>
                                {section.items.map((item, itemIndex) => (
                                    <li key={itemIndex}>{item}</li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>
            </section>

            {/* Section 4 - Sharing & Disclosure */}
            <section className="mb-8">
                <h2 className="text-2xl font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                    4. Sharing & Disclosure of Information
                </h2>
                <p className="text-gray-700 mb-6">We only share your Personal Data in the following circumstances:</p>

                <div className="space-y-6">
                    <div className="bg-green-50 border-l-4 border-green-400 p-4 rounded-r-lg">
                        <h3 className="text-lg font-medium text-green-900 mb-2">With Your Consent</h3>
                        <p className="text-green-800">
                            When you explicitly authorize us to share your data with third parties (e.g., linking a third-party calendar for scheduling).
                        </p>
                    </div>

                    <div className="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                        <h3 className="text-lg font-medium text-blue-900 mb-3">Service Providers & Third Parties</h3>
                        <p className="text-blue-800 mb-3">
                            We engage third parties who process Personal Data on our behalf under contract and restricted to permitted purposes:
                        </p>
                        <div className="space-y-2 text-sm">
                            <p className="text-blue-800"><strong>Payment Gateways:</strong> (e.g., Razorpay, PayU)—process payment transactions; they receive your payment method details (card, UPI) directly and handle PCI-compliant storage.</p>
                            <p className="text-blue-800"><strong>Couriers & Logistics Partners:</strong> (e.g., Blinkit, Porter)—receive your shipping address and contact number to fulfill orders.</p>
                            <p className="text-blue-800"><strong>Cloud & Hosting Providers:</strong> (e.g., AWS, DigitalOcean)—store and process data (encrypted at rest) to run Snoutiq's backend infrastructure.</p>
                            <p className="text-blue-800"><strong>Analytics & Marketing Platforms:</strong> (e.g., Google Analytics, Mixpanel, Braze)—receive aggregated and/or pseudonymized data to analyze usage, run A/B tests, and send marketing campaigns.</p>
                            <p className="text-blue-800"><strong>Customer Support Platforms:</strong> (e.g., Zendesk)—store support tickets, chat logs, and related attachments when you contact support.</p>
                            <p className="text-blue-800"><strong>Telemedicine Technology Providers:</strong> (e.g., WebRTC partners, video call SDKs)—facilitate secure video and audio consultations; these providers may see encrypted audio/video streams.</p>
                        </div>
                        <p className="text-blue-800 mt-3 text-sm italic">
                            All service providers are contractually obligated to implement appropriate technical and organizational measures to protect Personal Data and only process it on our instructions.
                        </p>
                    </div>

                    <div className="space-y-4">
                        {[
                            {
                                title: "Business Partners & Affiliates",
                                content: [
                                    "Co-branded Promotions: We may collaborate with pet-food suppliers, pharmaceutical brands, or animal welfare organizations. In such cases, only minimal, anonymized, or aggregated data is shared to measure campaign performance, unless you expressly opt in to share more.",
                                    "Professional Referrals: If you request a specialist or second opinion, we may share your relevant pet health data with the referred Veterinarian or clinic—only after obtaining your explicit consent."
                                ],
                                color: "purple"
                            },
                            {
                                title: "Legal & Safety Obligations",
                                content: [
                                    "To comply with applicable laws, regulations, or binding governmental requests (e.g., court orders, subpoenas).",
                                    "To enforce or protect our legal rights, including investigating potential violations of our Terms & Conditions.",
                                    "To protect the safety, property, or rights of Thinktail, Users, or the public. For example, if you report animal cruelty, we may share details with law enforcement or regulatory authorities."
                                ],
                                color: "red"
                            },
                            {
                                title: "Corporate Transactions",
                                content: [
                                    "If Thinktail is involved in a merger, acquisition, reorganization, sale of assets, or bankruptcy, your Personal Data may be transferred as part of that transaction, subject to any contractual restrictions on transferring personal data."
                                ],
                                color: "orange"
                            }
                        ].map((section, index) => (
                            <div key={index} className={`bg-${section.color}-50 border border-${section.color}-200 p-4 rounded-lg`}>
                                <h3 className={`text-lg font-medium text-${section.color}-900 mb-3`}>{section.title}</h3>
                                <ul className={`space-y-2 text-${section.color}-800 text-sm`}>
                                    {section.content.map((item, itemIndex) => (
                                        <li key={itemIndex}>• {item}</li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Section 5 - Data Retention */}
            <section className="mb-8">
                <h2 className="text-2xl font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                    5. Data Retention
                </h2>

                <div className="space-y-6">
                    <div>
                        <h3 className="text-xl font-medium text-gray-900 mb-4">Retention Periods</h3>
                        <div className="space-y-4">
                            {[
                                {
                                    title: "Account Data & Profile",
                                    content: "We retain your profile data (name, email, phone) as long as your account is active or as needed to provide you Services. After you delete your account or it is terminated, we retain minimal data (e.g., anonymized transaction history) for statutory compliance (GST, tax, audit) for a period of up to seven (7) years, as required by Indian law.",
                                    color: "blue"
                                },
                                {
                                    title: "Transactional & Order History",
                                    content: "We retain order records, invoices, and payment details for at least seven (7) years for tax and audit purposes, then archive or anonymize them.",
                                    color: "green"
                                },
                                {
                                    title: "Medical & Pet Health Records",
                                    content: "Veterinary notes, prescriptions, and medical history are retained for a minimum of seven (7) years to comply with professional guidelines and ensure continuity of care; after that period, records may be anonymized or securely deleted.",
                                    color: "red"
                                },
                                {
                                    title: "Support & Communication Records",
                                    content: "Chat logs, emails, and call recordings are stored for up to three (3) years or as needed to resolve disputes, comply with legal obligations, and improve service quality.",
                                    color: "yellow"
                                },
                                {
                                    title: "Marketing & Behavioral Data",
                                    content: "Cookies, usage logs, and analytics data are retained for up to two (2) years, unless you opt out or request deletion earlier.",
                                    color: "purple"
                                }
                            ].map((item, index) => (
                                <div key={index} className={`bg-${item.color}-50 border-l-4 border-${item.color}-400 p-4 rounded-r-lg`}>
                                    <h4 className={`font-semibold text-${item.color}-900 mb-2`}>{item.title}:</h4>
                                    <p className={`text-${item.color}-800 text-sm`}>{item.content}</p>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="bg-gray-50 border border-gray-200 p-4 rounded-lg">
                        <h3 className="text-lg font-medium text-gray-900 mb-3">Deletion & Anonymization</h3>
                        <ul className="space-y-2 text-gray-700 text-sm">
                            <li>• When retention periods expire, we either permanently delete your Personal Data or anonymize it so you cannot be identified.</li>
                            <li>• If you request deletion of your account and Personal Data (Subject to Section 6 below), we will delete or anonymize data within 30 days, except for copies that must be retained to fulfill our legal obligations or legitimate business purposes (e.g., unresolved disputes, fraud prevention, tax compliance).</li>
                        </ul>
                    </div>
                </div>
            </section>

            {/* Section 6 - Your Rights & Choices */}
            <section className="mb-8">
                <h2 className="text-2xl font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                    6. Your Rights & Choices
                </h2>

                <div className="space-y-6">
                    <div className="bg-indigo-50 border border-indigo-200 p-5 rounded-lg">
                        <h3 className="text-lg font-medium text-indigo-900 mb-4">6.1. Access, Rectification & Deletion</h3>
                        <div className="space-y-3">
                            <div>
                                <h4 className="font-semibold text-indigo-800 mb-1">Access:</h4>
                                <p className="text-indigo-700 text-sm">You may request a copy of the Personal Data we hold about you.</p>
                            </div>
                            <div>
                                <h4 className="font-semibold text-indigo-800 mb-1">Rectification:</h4>
                                <p className="text-indigo-700 text-sm">If your data is inaccurate or incomplete, you can update it in your account settings or by contacting privacy@snoutiq.com.</p>
                            </div>
                            <div>
                                <h4 className="font-semibold text-indigo-800 mb-1">Deletion:</h4>
                                <p className="text-indigo-700 text-sm mb-2">You may request deletion of your account and Personal Data. We will comply unless:</p>
                                <ul className="list-disc pl-5 space-y-1 text-indigo-700 text-sm">
                                    <li>We need to retain data to comply with legal obligations (e.g., tax laws, audit requirements).</li>
                                    <li>We need to retain data to enforce our Terms & Conditions, resolve disputes, or protect our rights.</li>
                                    <li>We need to honor legitimate requests from law enforcement or regulators.</li>
                                </ul>
                            </div>
                        </div>
                        <div className="mt-4 p-3 bg-indigo-100 rounded">
                            <p className="text-indigo-800 text-sm">
                                <strong>To submit a request:</strong> Email <a href="mailto:privacy@snoutiq.com" className="underline hover:text-indigo-600">privacy@snoutiq.com</a> with the subject line "Privacy Rights Request." We will respond within thirty (30) days as required by applicable law.
                            </p>
                        </div>
                    </div>

                    <div className="space-y-4">
                        {[
                            {
                                title: "6.2. Objection & Restriction",
                                items: [
                                    "Marketing Communications: You may opt out of marketing emails, SMS, or push notifications by clicking \"Unsubscribe\" in any promotional message or changing settings in your account.",
                                    "Cookies & Tracking: You can disable non-essential cookies via your browser settings. Note that disabling certain cookies may limit functionality.",
                                    "Profiling & Automated Decisions: We do not make solely automated decisions with legal or significant effects (e.g., automatically denying you service) without human review, except as required for fraud detection and security, in which case you may request human review by emailing privacy@snoutiq.com."
                                ],
                                color: "green"
                            },
                            {
                                title: "6.3. Data Portability",
                                items: [
                                    "Where technically feasible, you may request a machine-readable copy of the data you provided (e.g., order history, profile information). We will make a best effort to provide such data within thirty (30) days."
                                ],
                                color: "blue"
                            }
                        ].map((section, index) => (
                            <div key={index} className={`bg-${section.color}-50 border border-${section.color}-200 p-4 rounded-lg`}>
                                <h3 className={`text-lg font-medium text-${section.color}-900 mb-3`}>{section.title}</h3>
                                <ul className={`space-y-2 text-${section.color}-800 text-sm`}>
                                    {section.items.map((item, itemIndex) => (
                                        <li key={itemIndex}>• {item}</li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Remaining sections condensed for space... */}
            <section className="mb-8">
                <h2 className="text-2xl font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                    7. Children's Privacy
                </h2>
                <div className="bg-red-50 border-l-4 border-red-400 p-4 rounded-r-lg">
                    <p className="text-red-800">
                        Snoutiq is not directed to children under the age of eighteen (18). We do not knowingly collect Personal Data from minors. If you believe we have collected information from a minor, please contact us at <a href="mailto:privacy@snoutiq.com" className="underline hover:text-red-600">privacy@snoutiq.com</a>. We will take steps to delete that information as soon as possible.
                    </p>
                </div>
            </section>

            <section className="mb-8">
                <h2 className="text-2xl font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                    8. Security of Your Information
                </h2>
                <div className="space-y-4">
                    {[
                        {
                            title: "Technical & Organizational Measures",
                            content: "We implement industry-standard security practices, including encrypted communication (HTTPS/TLS), data encryption at rest, firewalls, intrusion detection, and regular security audits. Access to Personal Data is restricted to authorized personnel on a \"need-to-know\" basis. All employees and service providers are bound by confidentiality obligations. Payment information (full card data, bank account numbers) is never stored on our servers; we rely on PCI-compliant third-party payment gateways.",
                            color: "green"
                        },
                        {
                            title: "Breach Notification",
                            content: "In the unlikely event of a data breach that compromises your Personal Data, we will notify you and relevant regulatory authorities within seventy-two (72) hours, as required by law. We will also provide guidance on mitigating potential harm.",
                            color: "yellow"
                        },
                        {
                            title: "Your Role in Security",
                            content: "You are responsible for maintaining the confidentiality of your account credentials. Do not share your password with anyone. Log out of your account when using a shared or public device.",
                            color: "blue"
                        }
                    ].map((section, index) => (
                        <div key={index} className={`bg-${section.color}-50 border border-${section.color}-200 p-4 rounded-lg`}>
                            <h3 className={`font-semibold text-${section.color}-900 mb-2`}>{section.title}</h3>
                            <p className={`text-${section.color}-800 text-sm`}>{section.content}</p>
                        </div>
                    ))}
                </div>
            </section>

            {/* Additional sections 9-11 condensed */}
            {/* <div className="space-y-6 mb-8">
                {[
                    {
                        title: "9. International Transfers",
                        content: "Although Thinktail is based in India, your Personal Data may be transferred to and processed by our service providers and affiliates located in other jurisdictions (e.g., cloud hosting in the U.S. or Europe). When we transfer data outside India, we ensure that appropriate safeguards are in place, such as standard contractual clauses or other legally recognized transfer mechanisms, to protect your data."
                    },
                    {
                        title: "10. Third-Party Links & Services",
                        content: " Snoutiq may contain links to third-party websites or services (e.g., external payment gateways, social media, scheduling tools). This Privacy Policy does not apply to those third parties. We encourage you to review their privacy notices before providing any Personal Data. We are not responsible for the content or privacy practices of those third-party sites."
                    },
                    {
                        title: "11. Changes to This Privacy Policy",
                        content: "We may update this Privacy Policy from time to time to reflect changes in our practices, legal requirements, or new Services. When we make significant changes, we will: Post the updated Privacy Policy on Snoutiq with an updated “Last Updated” date."
                    },
                ]}
            </div>*/}


            <div> Send an email notification to all Registered Users at least seven (7) days before changes take effect.</div>


            <div>Your continued use of Snoutiq after the effective date constitutes acceptance of the updated Privacy Policy.</div>

            <section className="mt-12 bg-gray-50 border border-gray-200 p-6 rounded-lg">
                <h2 className="text-2xl font-semibold text-gray-900 mb-4">Contact Information</h2>
                <p className="text-gray-700 mb-4">
                    If you have questions or concerns regarding this Cancellation & Refund Policy, please contact:
                </p>
                <div className="bg-white p-4 rounded border">
                    <h3 className="font-semibold text-gray-900 mb-2">Privacy & Data Protection Team</h3>
                    <p className="text-gray-700">
                        Thinktail Global Pvt. Ltd. (Snoutiq)<br />
                        Plot No. 20, Block H-1/A, Sector-63<br />
                        Noida-201301, Uttar Pradesh, India<br />
                        Email: <a href="mailto:privacy@snoutiq.com" className="text-blue-600 hover:text-blue-800 underline">privacy@snoutiq.com</a>
                    </p>
                </div>
            </section>


            <div className="mt-8 pt-6 border-t border-gray-200">
                <p className="text-sm text-gray-600 text-center">
                    By using Snoutiq, you acknowledge that you have read and understood this Privacy Policy and consent to the collection, use, and disclosure of your Personal Data as described herein.
                </p>
            </div>
              <section className="mt-12 bg-gray-50 border border-gray-200 p-6 rounded-lg">
             <p className="text-gray-700">For general inquiries (non-privacy related), you may also contact:snoutiq@gmail.com</p>

               <p className="text-sm text-gray-600 text-center">info@snoutiq.com</p> 
            </section>




        </div>
    )
}