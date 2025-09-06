import React from "react";

export default function TermsCondition() {
    return (
        <div className="max-w-4xl mx-auto px-6 py-8 bg-white">
            {/* Header */}
            <div className="border-b border-gray-200 pb-6 mb-8">
                <h1 className="text-3xl font-bold text-gray-900 mb-2">
                    Terms & Conditions
                </h1>
                <p className="text-sm text-gray-600">
                    Last Updated: May 31, 2025
                </p>
            </div>

            {/* Introduction */}
            <div className="mb-8 p-4 bg-blue-50 border-l-4 border-blue-400 rounded-r-lg">
                <p className="text-gray-700 leading-relaxed">
                    These Terms & Conditions ("Terms") govern your access to and use of the Snoutiq website and mobile application (together, "Snoutiq" or the "Platform"), operated by Thinktail Global Pvt. Ltd. ("Thinktail," "we," "us," or "our"). By accessing or using Snoutiq in any way—whether as a Guest, Registered User, Seller, Veterinarian, Clinic, or other Provider—you ("you" or "User") agree to be bound by these Terms. If you do not agree with any provision herein, please stop using Snoutiq immediately.
                </p>
            </div>

            {/* Section 1 - Definitions */}
            <section className="mb-8">
                <h2 className="text-2xl font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                    1. Definitions
                </h2>
                <div className="space-y-4">
                    {[
                        { term: "Snoutiq or Platform", definition: "means the Snoutiq website and mobile application, including all features, content, and functionality." },
                        { term: "Company, we, us, or our", definition: "refers to Thinktail Global Pvt. Ltd., a company incorporated under the laws of India, with its registered office at Plot No. 20, Block H-1/A, Sector-63, Noida-201301, Uttar Pradesh, India." },
                        { term: "User, you, or your", definition: "means any individual or entity accessing or using Snoutiq in any capacity." }
                    ].map((item, index) => (
                        <div key={index} className="bg-gray-50 p-4 rounded-lg">
                            <h3 className="font-semibold text-gray-900 mb-2">"{item.term}"</h3>
                            <p className="text-gray-700">{item.definition}</p>
                        </div>
                    ))}
                    
                    <div className="bg-indigo-50 border border-indigo-200 p-4 rounded-lg">
                        <h3 className="font-semibold text-indigo-900 mb-3">User Categories:</h3>
                        <div className="space-y-3">
                            {[
                                { term: "Guest User", definition: "Any person browsing or accessing Snoutiq without registering an account." },
                                { term: "Registered User", definition: "A User who has created an account on Snoutiq by providing name, phone number, and email address." },
                                { term: "Seller", definition: "A registered legal entity or individual (including Veterinarians, Clinics, or other Providers) that lists and sells pet products or services on Snoutiq. Sellers must complete KYC (Know Your Customer) verification, providing relevant licenses and certifications." },
                                { term: "Veterinarian", definition: "A Seller who is licensed to provide veterinary consultations (video or in-person). Technically categorized under \"Seller\" but subject to additional licensing and professional requirements." },
                                { term: "Clinic", definition: "A physical or virtual facility (also a Seller) where veterinary or grooming services are offered." }
                            ].map((item, index) => (
                                <div key={index} className="border-l-2 border-indigo-300 pl-3">
                                    <h4 className="font-medium text-indigo-800">"{item.term}":</h4>
                                    <p className="text-indigo-700 text-sm">{item.definition}</p>
                                </div>
                            ))}
                        </div>
                        <p className="text-indigo-700 text-sm mt-3 italic">
                            Additional Terms: In these Terms, "Provider" may refer collectively to Veterinarians, Clinics, Groomers, or any third-party service providers.
                        </p>
                    </div>
                </div>
            </section>

            {/* Section 2 - Acceptance & Eligibility */}
            <section className="mb-8">
                <h2 className="text-2xl font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                    2. Acceptance & Eligibility
                </h2>
                <div className="space-y-6">
                    <div className="bg-green-50 border border-green-200 p-4 rounded-lg">
                        <h3 className="text-lg font-medium text-green-900 mb-2">Agreement to Terms</h3>
                        <p className="text-green-800">
                            By accessing or using any part of Snoutiq, you confirm that you have read, understood, and agree to these Terms (including any updates we publish). If you do not agree, you must immediately cease all use of Snoutiq.
                        </p>
                    </div>

                    <div className="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                        <h3 className="text-lg font-medium text-blue-900 mb-2">Eligibility</h3>
                        <p className="text-blue-800">
                            There is no age limit to browse Snoutiq; however, to purchase products or services (including booking consultations), you must be legally capable of entering into contracts under applicable law. When registering as a Seller or Provider, you must be at least 18 years old and provide valid government-issued identification, applicable professional licenses (for Veterinarians), and any other documentation we request for KYC.
                        </p>
                    </div>

                    <div className="bg-yellow-50 border border-yellow-200 p-4 rounded-lg">
                        <h3 className="text-lg font-medium text-yellow-900 mb-3">Account Registration</h3>
                        <div className="space-y-3">
                            <div>
                                <h4 className="font-medium text-yellow-800 mb-1">Guest Use:</h4>
                                <p className="text-yellow-700 text-sm">You may browse (view) product listings, medical articles, and general content on Snoutiq without registering.</p>
                            </div>
                            <div>
                                <h4 className="font-medium text-yellow-800 mb-1">Registration Requirements:</h4>
                                <p className="text-yellow-700 text-sm">To purchase items, book services, or list products/services, you must register by providing your name, phone number, email address, and creating a secure password. You are responsible for maintaining the confidentiality of your account credentials and for all activities under your account.</p>
                            </div>
                            <div>
                                <h4 className="font-medium text-yellow-800 mb-1">Verification for Sellers/Providers:</h4>
                                <p className="text-yellow-700 text-sm">All Sellers (including Veterinarians and Clinics) must submit and maintain valid professional licenses, certifications, or proof of legal right to sell/offer services. We reserve the right to suspend or refuse any application that fails verification.</p>
                            </div>
                            <div>
                                <h4 className="font-medium text-yellow-800 mb-1">Accurate Information:</h4>
                                <p className="text-yellow-700 text-sm">You agree to provide accurate and up-to-date information. Failure to do so may result in suspension or termination of your account.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Section 3 - Platform Services & Scope */}
            <section className="mb-8">
                <h2 className="text-2xl font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                    3. Platform Services & Scope
                </h2>
                <div className="space-y-6">
                    <div className="bg-purple-50 border border-purple-200 p-4 rounded-lg">
                        <h3 className="text-lg font-medium text-purple-900 mb-3">Marketplace Functionality</h3>
                        <p className="text-purple-800 mb-2">Snoutiq enables:</p>
                        <ul className="list-disc pl-5 space-y-1 text-purple-800 text-sm">
                            <li><strong>Browsing & Listing Products:</strong> Pet food, toys, medicines, supplies, and accessories.</li>
                            <li><strong>Booking Services:</strong> Video and in-person veterinary consultations; grooming; training; boarding; and other pet-related services.</li>
                            <li><strong>Accessing Medical Content:</strong> Articles, guides, dosage calculators, treatment overviews, and educational materials.</li>
                        </ul>
                    </div>

                    <div className="bg-green-50 border border-green-200 p-4 rounded-lg">
                        <h3 className="text-lg font-medium text-green-900 mb-3">Payment Processing & Escrow</h3>
                        <div className="space-y-3">
                            <div>
                                <h4 className="font-medium text-green-800 mb-1">Accepted Methods:</h4>
                                <p className="text-green-700 text-sm">Snoutiq accepts all major credit/debit cards, UPI, digital wallets, net banking, and other methods displayed at checkout.</p>
                            </div>
                            <div>
                                <h4 className="font-medium text-green-800 mb-1">Escrow Mechanism:</h4>
                                <p className="text-green-700 text-sm">When you place an order for a product or book a service (consultation, grooming, etc.), your payment is collected and held in escrow by Thinktail. Funds are released to the Seller/Provider only after confirmation of delivery or completion of the service (e.g., Veterinarian confirms consultation took place).</p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-indigo-50 border border-indigo-200 p-4 rounded-lg">
                        <h3 className="text-lg font-medium text-indigo-900 mb-3">Pricing & Commissions</h3>
                        <div className="space-y-2 text-sm">
                            <p className="text-indigo-800"><strong>Veterinarian Consultation Fees:</strong> Sellers (Veterinarians) set their own consultation rates. Thinktail charges a 7% commission for "Early Adopter" Veterinarians (for the first two months after onboarding) and 10% thereafter.</p>
                            <p className="text-indigo-800"><strong>Products & Supplies:</strong> Sellers set product prices; Thinktail charges a 20% commission on pet medicines, food, toys, supplies, and other retail items.</p>
                            <p className="text-indigo-800"><strong>Membership Plans:</strong> Providers (Veterinarians, Groomers, Clinics, etc.) may subscribe to the Premium Provider Plan at ₹5,999 per month for enhanced visibility, priority listing, access to analytics, and other premium features. Thinktail retains the membership fee in full.</p>
                            <p className="text-indigo-800"><strong>Refunds & Chargebacks:</strong> All refund requests and chargebacks are governed by our Cancellation & Refund Policy (see Section 7). We reserve the right to deduct commissions, fees, or chargeback amounts from your escrow or future payouts if a legitimate refund or chargeback occurs.</p>
                        </div>
                    </div>

                    <div className="bg-orange-50 border border-orange-200 p-4 rounded-lg">
                        <h3 className="text-lg font-medium text-orange-900 mb-3">Order Fulfillment & Delivery</h3>
                        <div className="space-y-2 text-sm">
                            <p className="text-orange-800"><strong>Seller Responsibilities:</strong> Sellers must prepare and hand over products to a third-party courier partner (e.g., Blinkit, Porter) within five (5) hours of order confirmation. Sellers are responsible for proper packaging, compliance with shipping regulations (especially for temperature-sensitive or medical items), and transferring possession to the courier.</p>
                            <p className="text-orange-800"><strong>Courier Responsibility:</strong> Once the courier accepts the package, responsibility for transit resides with the courier. Estimated delivery timelines vary by location; Sellers should communicate expected delivery dates to Buyers.</p>
                            <p className="text-orange-800"><strong>Service Scheduling:</strong> Video consultations are available instantly (subject to Veterinarian availability). In-person consultations or grooming appointments are scheduled within a window of ±30 minutes to 3 hours of the chosen time slot, depending on the Provider's availability. Users are responsible for ensuring they—and their pets—are available within that window.</p>
                            <p className="text-orange-800"><strong>Order Cancellation Prior to Dispatch:</strong> Buyers may cancel an order before it is handed to a courier; Sellers must refund according to our Cancellation & Refund Policy. Once an item is with the courier, cancellation must follow that policy's terms.</p>
                            <p className="text-orange-800"><strong>Inspections & Damages:</strong> Upon delivery, Buyers should inspect items immediately. Any damage, defect, or missing item must be reported to Seller (via in-app messaging) and Customer Support within 24 hours. Otherwise, disputes may not be honored.</p>
                        </div>
                    </div>
                </div>
            </section>

            {/* Section 4 - User Responsibilities & Code of Conduct */}
            <section className="mb-8">
                <h2 className="text-2xl font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                    4. User Responsibilities & Code of Conduct
                </h2>
                <div className="space-y-6">
                    <div className="bg-green-50 border border-green-200 p-4 rounded-lg">
                        <h3 className="text-lg font-medium text-green-900 mb-3">General Obligations</h3>
                        <p className="text-green-800 mb-2">When using Snoutiq, you agree to:</p>
                        <ul className="list-disc pl-5 space-y-1 text-green-800 text-sm">
                            <li>Provide complete, accurate, and lawful information.</li>
                            <li>Keep your account credentials secure; immediately notify us of any unauthorized access.</li>
                            <li>Comply with all applicable laws, regulations, and professional standards (especially when providing medical advice or treatment).</li>
                            <li>Communicate and transact in a professional, truthful, and respectful manner.</li>
                        </ul>
                    </div>

                    <div className="bg-red-50 border border-red-200 p-4 rounded-lg">
                        <h3 className="text-lg font-medium text-red-900 mb-3">Prohibited Conduct</h3>
                        <p className="text-red-800 mb-2">You may not, under any circumstances:</p>
                        <ul className="list-disc pl-5 space-y-1 text-red-800 text-sm">
                            <li>Use Snoutiq for any unlawful purpose or to promote illegal activities.</li>
                            <li>Upload or distribute any content that is defamatory, discriminatory, harassing, obscene, or infringing on any third-party rights.</li>
                            <li>Attempt to disguise your identity, create multiple accounts, or misrepresent your affiliation.</li>
                            <li>Post false, misleading, or fraudulent listings or reviews.</li>
                            <li>Attempt to bypass or manipulate our payment, escrow, or dispute resolution mechanisms (e.g., requesting off-Platform payments).</li>
                            <li>Access or attempt to access data belonging to other Users, Sellers, or Providers without authorization.</li>
                            <li>Introduce viruses, malware, or other harmful code to Snoutiq.</li>
                            <li>Interfere with or disrupt the operation of Snoutiq (e.g., denial-of-service attacks, excessive bots, scraping).</li>
                        </ul>
                    </div>

                    <div className="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                        <h3 className="text-lg font-medium text-blue-900 mb-3">Seller/Provider-Specific Obligations</h3>
                        <p className="text-blue-800 mb-2">In addition to the above, Sellers and Providers must:</p>
                        <ul className="list-disc pl-5 space-y-1 text-blue-800 text-sm">
                            <li>Maintain valid licenses, certifications, and insurance (as required by applicable law). Upload documentation for our review and promptly update any expired or revoked credentials.</li>
                            <li>Provide accurate, up-to-date descriptions, images, and pricing for all products and services.</li>
                            <li>Fulfill orders and appointments in a timely, professional manner.</li>
                            <li>Handle sensitive medical data (pet health records, owner medical history) in compliance with applicable privacy laws (e.g., India IT Act, rules on sensitive personal data).</li>
                            <li>Refrain from prescribing, dosing, or recommending prescriptions for any condition without a proper veterinary consultation.</li>
                            <li>Comply with all applicable health, safety, and biosecurity guidelines (e.g., packaging of medications, disposal of medical waste).</li>
                        </ul>
                    </div>
                </div>
            </section>

            {/* Section 5 - Intellectual Property */}
            <section className="mb-8">
                <h2 className="text-2xl font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                    5. Intellectual Property
                </h2>
                <div className="space-y-4">
                    {[
                        {
                            title: "Ownership by Thinktail",
                            content: "All Snoutiq content—such as logos, trademarks, text, graphics, software, code, sound recordings, videos, designs, and \"look and feel\"—is owned by Thinktail or its licensors. All rights are reserved.",
                            color: "purple"
                        },
                        {
                            title: "Seller/Provider Content",
                            content: "By uploading any photographs, images, articles, guides, or other content (\"User Content\") to Snoutiq, Sellers and Providers represent and warrant that they either own all rights to that content or have obtained all necessary licenses, consents, and permissions. By posting, you grant Thinktail a worldwide, royalty-free, sublicensable, and transferable license to use, reproduce, distribute, prepare derivative works, display, and perform the User Content in connection with operating and promoting Snoutiq.",
                            color: "blue"
                        },
                        {
                            title: "Limited License to Users",
                            content: "Subject to your compliance with these Terms, Thinktail grants you a limited, non-exclusive, non-transferable, revocable license to access and use Snoutiq's content for your personal, non-commercial purposes. Any other use (e.g., copying, modifying, distributing, retransmitting, or displaying any content) without our express written consent is strictly prohibited.",
                            color: "green"
                        },
                        {
                            title: "Feedback",
                            content: "If you provide us with any suggestions, feedback, or ideas regarding features, functionality, or design (\"Feedback\"), you acknowledge and agree that we may use, implement, modify, and commercialize such Feedback without any obligation or compensation to you.",
                            color: "yellow"
                        }
                    ].map((section, index) => (
                        <div key={index} className={`bg-${section.color}-50 border border-${section.color}-200 p-4 rounded-lg`}>
                            <h3 className={`font-semibold text-${section.color}-900 mb-2`}>{section.title}</h3>
                            <p className={`text-${section.color}-800 text-sm`}>{section.content}</p>
                        </div>
                    ))}
                </div>
            </section>

            {/* Section 6 - Medical Information & Disclaimers */}
            <section className="mb-8">
                <h2 className="text-2xl font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                    6. Medical Information & Disclaimers
                </h2>
                <div className="space-y-4">
                    {[
                        {
                            title: "Educational Purposes Only",
                            content: "Snoutiq may contain medical articles, dosage calculators, treatment overviews, and other veterinary-related content intended solely for educational purposes. Such information does not constitute veterinary advice, diagnosis, or treatment and should not replace a consultation with a licensed veterinarian.",
                            color: "red"
                        },
                        {
                            title: "No Guarantees",
                            content: "We do not guarantee the accuracy, completeness, or currency of any medical content. Reliance on any information provided through Snoutiq is solely at your own risk.",
                            color: "yellow"
                        },
                        {
                            title: "No Doctor-Patient Relationship",
                            content: "Viewing or using medical content does not create a doctor-patient (veterinarian-pet) relationship between you (or your pet) and Thinktail or any Providers on Snoutiq. Only a direct, real-time consultation (video or in-person) with a licensed Veterinarian establishes such a relationship.",
                            color: "blue"
                        },
                        {
                            title: "Limitations of Liability",
                            content: "Thinktail and its affiliates, officers, directors, employees, and agents disclaim all liability for any outcomes—adverse or otherwise—arising from your use of medical information on Snoutiq. Always seek the care of a qualified veterinarian for questions about your pet's health, diagnoses, or treatments.",
                            color: "red"
                        }
                    ].map((section, index) => (
                        <div key={index} className={`bg-${section.color}-50 border-l-4 border-${section.color}-400 p-4 rounded-r-lg`}>
                            <h3 className={`font-semibold text-${section.color}-900 mb-2`}>{section.title}</h3>
                            <p className={`text-${section.color}-800 text-sm`}>{section.content}</p>
                        </div>
                    ))}
                </div>
            </section>

            {/* Continue with remaining sections in condensed form... */}
            <section className="mb-8">
                <h2 className="text-2xl font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                    7. Fees, Payments, Cancellations & Refunds
                </h2>
                <div className="bg-indigo-50 border border-indigo-200 p-4 rounded-lg">
                    <h3 className="font-semibold text-indigo-900 mb-3">Key Points:</h3>
                    <ul className="space-y-2 text-indigo-800 text-sm">
                        <li>• <strong>Product Sales:</strong> 20% commission on retail items</li>
                        <li>• <strong>Veterinarian Consultations:</strong> 7% commission (Early Adopters, first 2 months), 10% thereafter</li>
                        <li>• <strong>Other Services:</strong> 20% commission on grooming, training, boarding</li>
                        <li>• <strong>Premium Provider Plan:</strong> ₹5,999/month (retained in full by Thinktail)</li>
                        <li>• <strong>Refund Processing:</strong> 7-10 business days to original payment method</li>
                        <li>• All refunds subject to Cancellation & Refund Policy terms</li>
                    </ul>
                </div>
            </section>

            {/* Contact Section */}
            <section className="mt-12 bg-gray-50 border border-gray-200 p-6 rounded-lg">
                <h2 className="text-2xl font-semibold text-gray-900 mb-4">Contact Information</h2>
                <div className="bg-white p-4 rounded border">
                    <h3 className="font-semibold text-gray-900 mb-2">Thinktail Global Pvt. Ltd.</h3>
                    <p className="text-gray-700 text-sm">
                        Plot No. 20, Block H-1/A, Sector-63<br/>
                        Noida-201301, Uttar Pradesh, India<br/>
                        Email: <a href="mailto:snoutiq@gmail.com" className="text-blue-600 hover:text-blue-800 underline">snoutiq@gmail.com</a> or <a href="mailto:info@snoutiq.com" className="text-blue-600 hover:text-blue-800 underline">info@snoutiq.com</a><br/>
                        Attention: Legal / Compliance Department
                    </p>
                </div>
            </section>

            {/* Acknowledgment Footer */}
            <div className="mt-8 pt-6 border-t border-gray-200">
                <div className="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-lg">
                    <h3 className="font-semibold text-blue-900 mb-2">Acknowledgment</h3>
                    <p className="text-blue-800 text-sm mb-2">
                        By clicking "I Agree," continuing to access, or using Snoutiq, you acknowledge that:
                    </p>
                    <ul className="list-disc pl-5 space-y-1 text-blue-800 text-sm">
                        <li>You have read, understood, and agree to be bound by these Terms;</li>
                        <li>You have the capacity to enter into legal agreements under applicable law; and</li>
                        <li>You accept Snoutiq's policies, procedures, and guidelines.</li>
                    </ul>
                    <p className="text-blue-800 text-sm mt-3">
                        If you have any questions about these Terms, please contact us at <a href="mailto:snoutiq@gmail.com" className="underline hover:text-blue-600">snoutiq@gmail.com</a> or <a href="mailto:info@snoutiq.com" className="underline hover:text-blue-600">info@snoutiq.com</a>.
                    </p>
                </div>
            </div>
        </div>
    );
}