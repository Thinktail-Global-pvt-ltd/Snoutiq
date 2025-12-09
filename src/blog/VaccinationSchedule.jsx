import React, { useEffect, useState } from 'react';
import Footer from '../components/Footer';
import Header from '../components/Header';
import img7 from '../assets/images/vaccination_schedule.jpeg';

const VaccinationScheduleForPets = () => {
  const [showBackToTop, setShowBackToTop] = useState(false);

  useEffect(() => {
    // Back to top functionality
    const handleScroll = () => {
      setShowBackToTop(window.pageYOffset > 300);
    };

    // Smooth scrolling for anchor links
    const handleAnchorClick = (e) => {
      const target = e.target.closest('a[href^="#"]');
      if (target) {
        e.preventDefault();
        const targetId = target.getAttribute('href');
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
          targetElement.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      }
    };

    window.addEventListener('scroll', handleScroll);
    document.addEventListener('click', handleAnchorClick);

    return () => {
      window.removeEventListener('scroll', handleScroll);
      document.removeEventListener('click', handleAnchorClick);
    };
  }, []);

  const scrollToTop = () => {
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  };

  return (
    <>
      {/* SEO Meta Tags */}
      <head>
        <title>Vaccination Schedule for Pets in India 2025 | Complete Guide for Dogs & Cats</title>
        <meta name="title" content="Vaccination Schedule for Pets in India - Complete Guide 2025" />
        <meta name="description" content="Complete vaccination schedule for pets in India 2025. Learn about dog and cat vaccination timeline, costs, core vaccines, booster shots and expert pet care tips." />
        <meta name="keywords" content="vaccination schedule for pets in india, pet vaccination timeline india, dog vaccination schedule india, cat vaccination schedule india, pet vaccines cost india, rabies vaccine pets india" />
        <meta name="author" content="Snoutiq - Pet Care Experts" />
        <meta name="robots" content="index, follow" />
        <meta property="og:title" content="Vaccination Schedule for Pets in India - Complete Guide 2025" />
        <meta property="og:description" content="Comprehensive guide on pet vaccination in India with age-wise charts, costs, and expert recommendations for dogs and cats." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://snoutiq.com/vaccination-schedule-pets-india" />
        <meta property="og:image" content="https://snoutiq.com/images/pet-vaccination-guide.jpg" />
        <link rel="canonical" href="https://snoutiq.com/vaccination-schedule-pets-india" />
        
        {/* Schema.org Markup */}
        <script type="application/ld+json">
          {JSON.stringify({
            "@context": "https://schema.org",
            "@type": "Article",
            "headline": "Vaccination Schedule for Pets in India - Complete Guide 2025",
            "description": "Complete vaccination schedule for pets in India 2025. Learn about dog and cat vaccination timeline, costs, core vaccines, booster shots and expert pet care tips.",
            "image": "https://snoutiq.com/images/pet-vaccination-guide.jpg",
            "author": {
              "@type": "Organization",
              "name": "Snoutiq"
            },
            "publisher": {
              "@type": "Organization",
              "name": "Snoutiq",
              "logo": {
                "@type": "ImageObject",
                "url": "https://snoutiq.com/logo.png"
              }
            },
            "datePublished": "2024-12-01",
            "dateModified": "2024-12-04",
            "mainEntityOfPage": {
              "@type": "WebPage",
              "@id": "https://snoutiq.com/vaccination-schedule-pets-india"
            }
          })}
        </script>

        {/* FAQ Schema */}
        <script type="application/ld+json">
          {JSON.stringify({
            "@context": "https://schema.org",
            "@type": "FAQPage",
            "mainEntity": [
              {
                "@type": "Question",
                "name": "What is the correct vaccination schedule for pets in India?",
                "acceptedAnswer": {
                  "@type": "Answer",
                  "text": "It starts at 6 to 8 weeks of age, followed by booster doses at 10-12 weeks and 14-16 weeks, and then yearly revaccinations to maintain immunity throughout your pet's life."
                }
              },
              {
                "@type": "Question",
                "name": "When should puppies and kittens get their first vaccine?",
                "acceptedAnswer": {
                  "@type": "Answer",
                  "text": "Puppies and kittens should receive their first vaccine at 6 to 8 weeks of age. This is when maternal antibodies start declining and vaccination becomes most effective."
                }
              },
              {
                "@type": "Question",
                "name": "Are Rabies vaccines mandatory in India?",
                "acceptedAnswer": {
                  "@type": "Answer",
                  "text": "Yes, Rabies vaccination is legally required for both dogs and cats in India. It protects not only your pet but also humans from this fatal disease."
                }
              },
              {
                "@type": "Question",
                "name": "Do indoor pets also need vaccination?",
                "acceptedAnswer": {
                  "@type": "Answer",
                  "text": "Yes, even indoor pets need vaccination because viruses can enter homes through shoes, hands, clothing, or visiting animals."
                }
              },
              {
                "@type": "Question",
                "name": "What if I miss a vaccine date?",
                "acceptedAnswer": {
                  "@type": "Answer",
                  "text": "Consult a vet immediately if you miss a vaccine date. Depending on the gap, your vet may restart the schedule or adjust the timeline."
                }
              }
            ]
          })}
        </script>
      </head>

      <div className="font-sans text-gray-800 bg-gradient-to-br from-indigo-50 to-purple-50 min-h-screen">
<Header/>
        <article className="max-w-4xl mx-auto bg-white shadow-xl shadow-indigo-100 rounded-xl overflow-hidden my-8 mt-20" itemScope itemType="http://schema.org/BlogPosting">
          
          {/* Blog Header */}
          <header className="bg-gradient-to-r from-indigo-500 to-purple-600 text-white relative overflow-hidden py-16 px-8 text-center">
            {/* Decorative Emojis */}
            <div className="absolute text-9xl opacity-10 -top-10 -right-10 transform -rotate-12">💉</div>
            <div className="absolute text-7xl opacity-10 -bottom-10 -left-10 transform rotate-12">🐾</div>
            
            <div className="relative z-10">
              <h1 className="text-3xl md:text-4xl lg:text-5xl font-black mb-6 drop-shadow-lg" itemProp="headline">
                Vaccination Schedule for Pets in India
              </h1>
              <p className="text-xl md:text-2xl opacity-95 font-light mb-8 leading-relaxed">
                Complete Guide 2025 - Protect Your Dog & Cat with Timely Vaccines
              </p>
              
              <div className="flex flex-wrap justify-center gap-4 mt-8">
                <span className="bg-white/20 backdrop-blur-sm px-5 py-2 rounded-full flex items-center gap-2">
                  📅 Updated: December 2024
                </span>
                <span className="bg-white/20 backdrop-blur-sm px-5 py-2 rounded-full flex items-center gap-2">
                  ⏱️ Reading Time: 8 minutes
                </span>
                <span className="bg-white/20 backdrop-blur-sm px-5 py-2 rounded-full flex items-center gap-2">
                  🏥 Vet Approved
                </span>
              </div>
            </div>
          </header>
<section>
            <img src={img7} alt="image" />
          </section>
          {/* Main Content */}
          <div className="px-6 md:px-10 py-8" itemProp="articleBody">
            <p className="text-gray-700 text-lg mb-6 leading-relaxed">
              Vaccines play a crucial role in protecting your pets from dangerous diseases. Following the correct <strong className="font-bold text-indigo-600">vaccination schedule for pets in India</strong> ensures your dog or cat lives a healthy and disease-free life.
            </p>

            <p className="text-gray-700 text-lg mb-8 leading-relaxed">
              This comprehensive guide covers the complete pet vaccination timeline in India, including core and optional vaccines, age-wise charts, booster shots, and costs. Whether you are a new pet parent or updating your pet's medical record, this guide makes vaccination simple and reliable.
            </p>

            {/* Table of Contents */}
            <nav className="bg-gradient-to-r from-indigo-50 to-purple-100 border-l-4 border-indigo-500 p-6 md:p-8 rounded-xl shadow-sm mb-12" aria-label="Table of Contents">
              <h3 className="text-indigo-600 text-2xl font-bold mb-6 flex items-center gap-2">
                📑 Table of Contents
              </h3>
              <ol className="list-decimal pl-5 space-y-3">
                {[
                  "Why Vaccination Is Important for Your Pet",
                  "Vaccination Schedule for Dogs in India",
                  "Vaccination Schedule for Cats in India",
                  "Cost of Pet Vaccination in India",
                  "Annual Booster Shots",
                  "What to Do If You Miss a Vaccine",
                  "Core vs Non-Core Vaccines",
                  "After Vaccination Care",
                  "Quick Reference Vaccination Chart",
                  "Frequently Asked Questions"
                ].map((item, index) => (
                  <li key={index}>
                    <a href={`#${item.toLowerCase().replace(/\s+/g, '-').replace(/[&]/g, '').replace(/vs/, 'vs-')}`} className="text-gray-800 hover:text-indigo-600 font-medium transition-colors duration-300">
                      {item}
                    </a>
                  </li>
                ))}
              </ol>
            </nav>

            {/* Section 1: Why Vaccination Is Important */}
            <section id="why-vaccination" className="scroll-mt-20 mb-12">
              <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-8 pb-4 border-b-2 border-indigo-500 relative">
                Why Vaccination Is Important for Your Pet
                <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
              </h2>
              
              <p className="text-gray-700 text-lg mb-6 leading-relaxed">
                Vaccines prepare your pet's immune system to detect and fight harmful viruses and bacteria. Without timely vaccination, pets are highly vulnerable to fatal infections such as rabies, parvovirus, distemper, and feline panleukopenia.
              </p>

              {/* Benefits Grid */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                {[
                  { icon: "🛡️", title: "Disease Protection", desc: "Protection from severe and deadly diseases that can be fatal" },
                  { icon: "🚫", title: "Transmission Prevention", desc: "Reduces risk of transmission to other pets and humans" },
                  { icon: "💰", title: "Cost Savings", desc: "Saves long-term veterinary treatment costs" },
                  { icon: "✈️", title: "Travel Ready", desc: "Required for travel, grooming, and pet boarding facilities" },
                  { icon: "❤️", title: "Responsible Parenting", desc: "Ensures responsible and safe pet ownership" },
                  { icon: "📋", title: "Legal Compliance", desc: "Meets legal requirements for pet ownership" }
                ].map((benefit, index) => (
                  <div key={index} className="bg-white border-t-4 border-indigo-500 rounded-xl p-6 text-center shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                    <div className="text-5xl mb-4">{benefit.icon}</div>
                    <h4 className="text-xl font-bold text-indigo-600 mb-3">{benefit.title}</h4>
                    <p className="text-gray-600">{benefit.desc}</p>
                  </div>
                ))}
              </div>

              <p className="text-gray-700 text-lg leading-relaxed">
                Following the correct <strong className="font-bold text-indigo-600">vaccination schedule for pets in India</strong> is essential for your pet's long-term health and wellbeing.
              </p>
            </section>

            {/* Section 2: Dog Schedule */}
            <section id="vaccination-schedule-for-dogs-in-india" className="scroll-mt-20 mb-12">
              <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-8 pb-4 border-b-2 border-indigo-500 relative">
                Vaccination Schedule for Dogs in India
                <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
              </h2>
              
              <p className="text-gray-700 text-lg mb-6 leading-relaxed">
                Here is the recommended <strong className="font-bold text-indigo-600">vaccination schedule for pets in India</strong> for dogs:
              </p>

              <div className="overflow-x-auto rounded-xl shadow-lg mb-8">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gradient-to-r from-indigo-500 to-purple-600">
                    <tr>
                      <th className="px-6 py-4 text-left text-sm font-semibold text-white">Age</th>
                      <th className="px-6 py-4 text-left text-sm font-semibold text-white">Core Vaccines</th>
                      <th className="px-6 py-4 text-left text-sm font-semibold text-white">Optional Vaccines</th>
                      <th className="px-6 py-4 text-left text-sm font-semibold text-white">Remarks</th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {[
                      ["6 to 8 weeks", "DHPPi", "Kennel Cough", "Primary vaccination begins"],
                      ["10 to 12 weeks", "DHPPi (2nd dose)", "Leptospirosis", "Booster for immunity"],
                      ["14 to 16 weeks", "DHPPi (3rd dose) + Rabies", "Coronavirus", "Rabies is legally required"],
                      ["12 months", "DHPPi + Rabies", "Lyme Disease", "Annual booster"],
                      ["Every year", "DHPPi + Rabies", "Tick Fever Vaccine", "Repeat yearly"]
                    ].map((row, index) => (
                      <tr key={index} className={index % 2 === 0 ? "bg-indigo-50 hover:bg-indigo-100" : "bg-white hover:bg-indigo-100"}>
                        <td className="px-6 py-4 font-semibold text-gray-900">{row[0]}</td>
                        <td className="px-6 py-4 text-gray-700">{row[1]}</td>
                        <td className="px-6 py-4 text-gray-700">{row[2]}</td>
                        <td className="px-6 py-4 text-gray-700">{row[3]}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              <div className="bg-gradient-to-r from-blue-50 to-sky-50 border-l-4 border-blue-500 rounded-xl p-6 mb-6 shadow-sm">
                <p className="text-gray-800 text-lg flex items-start gap-2">
                  💡 <strong className="text-blue-600">Tip:</strong> If your pet has no vaccination history, consult a vet to restart the cycle safely and create a proper immunization plan.
                </p>
              </div>

              <div className="bg-gradient-to-r from-indigo-50 to-purple-50 border-l-4 border-indigo-500 rounded-xl p-6 shadow-sm">
                <h4 className="text-xl font-bold text-indigo-600 mb-4 flex items-center gap-2">
                  📌 What is DHPPi Vaccine?
                </h4>
                <p className="text-gray-700 mb-4">DHPPi is a combination vaccine that protects against:</p>
                <ul className="list-disc pl-6 space-y-3">
                  <li className="text-gray-700"><strong>D</strong> - Distemper (viral disease affecting multiple organs)</li>
                  <li className="text-gray-700"><strong>H</strong> - Hepatitis (liver infection)</li>
                  <li className="text-gray-700"><strong>P</strong> - Parvovirus (severe gastrointestinal disease)</li>
                  <li className="text-gray-700"><strong>P</strong> - Parainfluenza (respiratory infection)</li>
                  <li className="text-gray-700"><strong>i</strong> - Infectious diseases protection</li>
                </ul>
              </div>
            </section>

            {/* Section 3: Cat Schedule */}
            <section id="vaccination-schedule-for-cats-in-india" className="scroll-mt-20 mb-12">
              <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-8 pb-4 border-b-2 border-indigo-500 relative">
                Vaccination Schedule for Cats in India
                <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
              </h2>
              
              <p className="text-gray-700 text-lg mb-6 leading-relaxed">
                Here is the <strong className="font-bold text-indigo-600">vaccination schedule for pets in India</strong> for cats:
              </p>

              <div className="overflow-x-auto rounded-xl shadow-lg mb-8">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gradient-to-r from-indigo-500 to-purple-600">
                    <tr>
                      <th className="px-6 py-4 text-left text-sm font-semibold text-white">Age</th>
                      <th className="px-6 py-4 text-left text-sm font-semibold text-white">Core Vaccines</th>
                      <th className="px-6 py-4 text-left text-sm font-semibold text-white">Optional Vaccines</th>
                      <th className="px-6 py-4 text-left text-sm font-semibold text-white">Remarks</th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {[
                      ["6 to 8 weeks", "FVRCP", "Feline Chlamydia", "First dose"],
                      ["10 to 12 weeks", "FVRCP (2nd dose)", "FeLV", "Booster dose"],
                      ["14 to 16 weeks", "FVRCP (3rd dose) + Rabies", "FIP", "Rabies is mandatory"],
                      ["12 months", "FVRCP + Rabies", "FeLV Booster", "Annual booster"],
                      ["Every year", "FVRCP + Rabies", "Optional boosters", "Recommended yearly"]
                    ].map((row, index) => (
                      <tr key={index} className={index % 2 === 0 ? "bg-indigo-50 hover:bg-indigo-100" : "bg-white hover:bg-indigo-100"}>
                        <td className="px-6 py-4 font-semibold text-gray-900">{row[0]}</td>
                        <td className="px-6 py-4 text-gray-700">{row[1]}</td>
                        <td className="px-6 py-4 text-gray-700">{row[2]}</td>
                        <td className="px-6 py-4 text-gray-700">{row[3]}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              <div className="bg-gradient-to-r from-yellow-50 to-amber-50 border-l-4 border-amber-500 rounded-xl p-6 mb-6 shadow-sm">
                <p className="text-gray-800 text-lg font-semibold">
                  ⚠️ <span className="text-amber-600">Important:</span> Even indoor cats need vaccinations because infections can enter homes through shoes, hands, clothing, or visiting animals. Never assume indoor cats are completely safe from diseases.
                </p>
              </div>

              <div className="bg-gradient-to-r from-indigo-50 to-purple-50 border-l-4 border-indigo-500 rounded-xl p-6 shadow-sm">
                <h4 className="text-xl font-bold text-indigo-600 mb-4 flex items-center gap-2">
                  📌 What is FVRCP Vaccine?
                </h4>
                <p className="text-gray-700 mb-4">FVRCP is a core combination vaccine for cats protecting against:</p>
                <ul className="list-disc pl-6 space-y-3">
                  <li className="text-gray-700"><strong>F</strong> - Feline Viral Rhinotracheitis (respiratory infection)</li>
                  <li className="text-gray-700"><strong>V</strong> - Viral infections</li>
                  <li className="text-gray-700"><strong>R</strong> - Respiratory diseases</li>
                  <li className="text-gray-700"><strong>C</strong> - Calicivirus (respiratory and oral disease)</li>
                  <li className="text-gray-700"><strong>P</strong> - Panleukopenia (feline distemper)</li>
                </ul>
              </div>
            </section>

            {/* Section 4: Cost */}
            <section id="cost-of-pet-vaccination-in-india" className="scroll-mt-20 mb-12">
              <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-8 pb-4 border-b-2 border-indigo-500 relative">
                Cost of Pet Vaccination in India
                <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
              </h2>

              <p className="text-gray-700 text-lg mb-6 leading-relaxed">
                Vaccination costs vary based on city, clinic reputation, and vaccine brand. Here's an approximate price guide:
              </p>

              <div className="overflow-x-auto rounded-xl shadow-lg mb-8">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gradient-to-r from-indigo-500 to-purple-600">
                    <tr>
                      <th className="px-6 py-4 text-left text-sm font-semibold text-white">Vaccine</th>
                      <th className="px-6 py-4 text-left text-sm font-semibold text-white">Dog (₹)</th>
                      <th className="px-6 py-4 text-left text-sm font-semibold text-white">Cat (₹)</th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {[
                      ["DHPPi", "600 to 900", "–"],
                      ["Rabies", "300 to 600", "300 to 600"],
                      ["Leptospirosis", "400 to 700", "–"],
                      ["Coronavirus", "600 to 1000", "–"],
                      ["FVRCP", "–", "600 to 900"],
                      ["FeLV", "–", "700 to 1000"],
                      ["Annual Booster Package", "1000 to 1500", "900 to 1300"]
                    ].map((row, index) => (
                      <tr key={index} className={index % 2 === 0 ? "bg-indigo-50 hover:bg-indigo-100" : "bg-white hover:bg-indigo-100"}>
                        <td className="px-6 py-4 font-semibold text-gray-900">{row[0]}</td>
                        <td className="px-6 py-4 text-gray-700">{row[1]}</td>
                        <td className="px-6 py-4 text-gray-700">{row[2]}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              <div className="bg-gradient-to-r from-blue-50 to-sky-50 border-l-4 border-blue-500 rounded-xl p-6 shadow-sm">
                <p className="text-gray-800 text-lg">
                  💡 <strong className="text-blue-600">Note:</strong> Prices vary based on city, clinic, and vaccine brand. Metro cities typically charge higher rates.
                </p>
              </div>
            </section>

            {/* Section 5: Boosters */}
            <section id="annual-booster-shots" className="scroll-mt-20 mb-12">
              <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-8 pb-4 border-b-2 border-indigo-500 relative">
                Annual Booster Shots
                <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
              </h2>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div className="bg-white border-l-4 border-indigo-500 rounded-xl p-6 shadow-sm">
                  <h3 className="text-xl font-bold text-indigo-600 mb-4">Dogs</h3>
                  <ul className="list-disc pl-6 space-y-2">
                    <li className="text-gray-700">DHPPi and Rabies once every 12 months</li>
                  </ul>
                </div>
                <div className="bg-white border-l-4 border-purple-500 rounded-xl p-6 shadow-sm">
                  <h3 className="text-xl font-bold text-purple-600 mb-4">Cats</h3>
                  <ul className="list-disc pl-6 space-y-2">
                    <li className="text-gray-700">FVRCP and Rabies annually</li>
                    <li className="text-gray-700">FeLV as required based on exposure</li>
                  </ul>
                </div>
              </div>

              <p className="text-gray-700 text-lg">
                Annual boosters help maintain strong immunity and are essential for long-term protection against diseases.
              </p>
            </section>

            {/* Section 6: Missed Vaccine */}
            <section id="what-to-do-if-you-miss-a-vaccine" className="scroll-mt-20 mb-12">
              <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-8 pb-4 border-b-2 border-indigo-500 relative">
                What to Do If You Miss a Vaccine
                <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
              </h2>

              <p className="text-gray-700 text-lg mb-6 leading-relaxed">
                Missing a vaccine is common and can be managed with proper veterinary guidance.
              </p>

              <ul className="list-disc pl-6 space-y-3 mb-6">
                <li className="text-gray-700">Visit a vet immediately for assessment</li>
                <li className="text-gray-700">Restart the schedule if needed based on gap duration</li>
                <li className="text-gray-700">Avoid giving vaccines at home without supervision</li>
                <li className="text-gray-700">Older pets may need antibody titer tests to check immunity levels</li>
              </ul>

              <div className="bg-gradient-to-r from-yellow-50 to-amber-50 border-l-4 border-amber-500 rounded-xl p-6 shadow-sm">
                <p className="text-gray-800 text-lg font-semibold">
                  ⚠️ <span className="text-amber-600">Important:</span> Timely management is crucial to maintain immunity. Extended gaps may require restarting the entire vaccination series.
                </p>
              </div>
            </section>

            {/* Section 7: Core vs Non-Core */}
            <section id="core-vs-non-core-vaccines" className="scroll-mt-20 mb-12">
              <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-8 pb-4 border-b-2 border-indigo-500 relative">
                Core vs Non-Core Vaccines
                <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
              </h2>

              <div className="overflow-x-auto rounded-xl shadow-lg mb-8">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gradient-to-r from-indigo-500 to-purple-600">
                    <tr>
                      <th className="px-6 py-4 text-left text-sm font-semibold text-white">Type</th>
                      <th className="px-6 py-4 text-left text-sm font-semibold text-white">Covers</th>
                      <th className="px-6 py-4 text-left text-sm font-semibold text-white">Examples</th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    <tr className="bg-indigo-50 hover:bg-indigo-100">
                      <td className="px-6 py-4 font-semibold text-gray-900">Core</td>
                      <td className="px-6 py-4 text-gray-700">Essential for all pets</td>
                      <td className="px-6 py-4 text-gray-700">DHPPi, FVRCP, Rabies</td>
                    </tr>
                    <tr className="bg-white hover:bg-indigo-100">
                      <td className="px-6 py-4 font-semibold text-gray-900">Non-Core</td>
                      <td className="px-6 py-4 text-gray-700">Based on lifestyle and risk</td>
                      <td className="px-6 py-4 text-gray-700">FeLV, Leptospirosis, Kennel Cough</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <p className="text-gray-700 text-lg">
                Your vet will recommend non-core vaccines based on exposure, environment, and your pet's daily routine. Factors like outdoor access, interaction with other animals, and geographic location play important roles.
              </p>
            </section>

            {/* Section 8: After Care */}
            <section id="after-vaccination-care" className="scroll-mt-20 mb-12">
              <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-8 pb-4 border-b-2 border-indigo-500 relative">
                After Vaccination Care
                <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
              </h2>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div className="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-emerald-500 rounded-xl p-6 shadow-sm">
                  <h4 className="text-xl font-bold text-emerald-600 mb-4 flex items-center gap-2">
                    ✅ Do's
                  </h4>
                  <ul className="list-disc pl-6 space-y-3">
                    <li className="text-gray-700">Allow your pet to rest and recover</li>
                    <li className="text-gray-700">Keep the pet well hydrated</li>
                    <li className="text-gray-700">Monitor for mild reactions like slight fever</li>
                    <li className="text-gray-700">Maintain a comfortable temperature</li>
                    <li className="text-gray-700">Keep vaccination records updated</li>
                  </ul>
                </div>
                <div className="bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-500 rounded-xl p-6 shadow-sm">
                  <h4 className="text-xl font-bold text-red-600 mb-4 flex items-center gap-2">
                    ❌ Don'ts
                  </h4>
                  <ul className="list-disc pl-6 space-y-3">
                    <li className="text-gray-700">No bathing for 48 hours after vaccination</li>
                    <li className="text-gray-700">No heavy exercise or strenuous activity</li>
                    <li className="text-gray-700">Do not repeat the dose yourself</li>
                    <li className="text-gray-700">Avoid exposing to other sick animals</li>
                    <li className="text-gray-700">Don't skip follow-up appointments</li>
                  </ul>
                </div>
              </div>

              <div className="bg-gradient-to-r from-yellow-50 to-amber-50 border-l-4 border-amber-500 rounded-xl p-6 shadow-sm">
                <p className="text-gray-800 text-lg font-semibold">
                  ⚠️ <span className="text-amber-600">Emergency Signs:</span> If your pet shows severe swelling, vomiting, difficulty breathing, or continuous high fever, visit a vet immediately. These could indicate serious allergic reactions.
                </p>
              </div>
            </section>

            {/* Section 9: Quick Reference */}
            <section id="quick-reference-vaccination-chart" className="scroll-mt-20 mb-12">
              <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-8 pb-4 border-b-2 border-indigo-500 relative">
                Quick Reference Vaccination Chart
                <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
              </h2>

              <div className="overflow-x-auto rounded-xl shadow-lg mb-8">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gradient-to-r from-indigo-500 to-purple-600">
                    <tr>
                      <th className="px-6 py-4 text-left text-sm font-semibold text-white">Pet</th>
                      <th className="px-6 py-4 text-left text-sm font-semibold text-white">Core Vaccines</th>
                      <th className="px-6 py-4 text-left text-sm font-semibold text-white">Start Age</th>
                      <th className="px-6 py-4 text-left text-sm font-semibold text-white">Booster Frequency</th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    <tr className="bg-indigo-50 hover:bg-indigo-100">
                      <td className="px-6 py-4 font-semibold text-gray-900">Dog</td>
                      <td className="px-6 py-4 text-gray-700">DHPPi + Rabies</td>
                      <td className="px-6 py-4 text-gray-700">6 to 8 weeks</td>
                      <td className="px-6 py-4 text-gray-700">Annually</td>
                    </tr>
                    <tr className="bg-white hover:bg-indigo-100">
                      <td className="px-6 py-4 font-semibold text-gray-900">Cat</td>
                      <td className="px-6 py-4 text-gray-700">FVRCP + Rabies</td>
                      <td className="px-6 py-4 text-gray-700">6 to 8 weeks</td>
                      <td className="px-6 py-4 text-gray-700">Annually</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div className="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-emerald-500 rounded-xl p-6 shadow-sm">
                <p className="text-gray-800 text-lg flex items-start gap-2">
                  ✅ <strong className="text-emerald-600">Pro Tip:</strong> Keeping a printed or digital vaccination record helps avoid missed doses and is required for boarding, travel, and emergencies.
                </p>
              </div>
            </section>

            {/* CTA Box */}
            <div className="bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl p-8 md:p-10 text-center shadow-2xl mb-12">
              <h3 className="text-2xl md:text-3xl font-bold mb-6">🏠 Visit Snoutiq for More Pet Care Tips</h3>
              <p className="text-xl opacity-95 mb-8">
                Explore comprehensive guides on pet nutrition, training, grooming, and emergency care.
              </p>
              <div className="flex flex-wrap justify-center gap-4">
                <a href="https://snoutiq.com/" className="bg-white text-indigo-600 font-bold py-3 px-6 rounded-full hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                  Visit Homepage
                </a>
                <a href="https://snoutiq.com/first-aid-tips-pet-parents" className="bg-purple-500 text-white font-bold py-3 px-6 rounded-full hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                  Read First Aid Guide
                </a>
              </div>
            </div>

            {/* FAQ Section */}
            <section id="faq" className="scroll-mt-20 bg-gradient-to-r from-indigo-50 to-purple-50 px-6 md:px-10 py-12 rounded-xl">
              <div className="max-w-4xl mx-auto">
                <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 text-center mb-12">
                  ❓ Frequently Asked Questions
                </h2>

                <div className="space-y-6">
                  {[
                    {
                      q: "What is the correct vaccination schedule for pets in India?",
                      a: "It starts at 6 to 8 weeks of age, followed by booster doses at 10-12 weeks and 14-16 weeks, and then yearly revaccinations to maintain immunity throughout your pet's life."
                    },
                    {
                      q: "When should puppies and kittens get their first vaccine?",
                      a: "Puppies and kittens should receive their first vaccine at 6 to 8 weeks of age. This is when maternal antibodies start declining and vaccination becomes most effective."
                    },
                    {
                      q: "Are Rabies vaccines mandatory in India?",
                      a: "Yes, Rabies vaccination is legally required for both dogs and cats in India. It protects not only your pet but also humans from this fatal disease."
                    },
                    {
                      q: "Do indoor pets also need vaccination?",
                      a: "Yes, even indoor pets need vaccination because viruses can enter homes through shoes, hands, clothing, or visiting animals. Indoor cats and dogs remain vulnerable to airborne and contact-based diseases."
                    },
                    {
                      q: "What if I miss a vaccine date?",
                      a: "Consult a vet immediately if you miss a vaccine date. Depending on the gap, your vet may restart the schedule or adjust the timeline to ensure proper immunity."
                    },
                    {
                      q: "Can I give vaccines at home?",
                      a: "No, only a licensed veterinarian should administer vaccines. Proper storage, dosage, and administration techniques are critical for vaccine effectiveness and pet safety."
                    },
                    {
                      q: "Are there any side effects of vaccines?",
                      a: "Mild side effects like slight fever, swelling at injection site, or tiredness may occur and usually resolve within 24-48 hours. Severe reactions are rare but require immediate veterinary attention."
                    },
                    {
                      q: "Do pets need yearly boosters?",
                      a: "Yes, yearly booster shots are essential to maintain your pet's immunity against diseases. Annual vaccination ensures continued protection throughout their life."
                    }
                  ].map((faq, index) => (
                    <div key={index} className="bg-white border-l-4 border-indigo-500 rounded-xl p-6 shadow-lg hover:shadow-xl hover:translate-x-2 transition-all duration-300" itemScope itemType="https://schema.org/Question">
                      <div className="faq-question text-xl font-bold text-indigo-600 mb-4" itemProp="name">
                        {index + 1}. {faq.q}
                      </div>
                      <div className="faq-answer text-gray-700" itemScope itemType="https://schema.org/Answer" itemProp="acceptedAnswer">
                        <p itemProp="text">{faq.a}</p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </section>
          </div>

          {/* Footer */}
          <footer className="bg-gradient-to-r from-gray-800 to-gray-900 text-white px-6 md:px-10 py-12">
            <div className="max-w-4xl mx-auto text-center">
              <p className="text-2xl font-bold mb-6">Final Thoughts</p>
              <p className="text-lg mb-6 text-gray-200">
                Following the correct <strong className="text-indigo-300">vaccination schedule for pets in India</strong> is the most effective way to protect your pet from dangerous diseases. With timely vaccines, annual boosters, a healthy diet, and regular vet visits, your pet can enjoy a long and healthy life.
              </p>
              <p className="text-lg mb-6 text-gray-200">
                For more trusted pet care information, visit <a href="https://snoutiq.com/" className="text-purple-300 hover:text-purple-200 font-medium">Snoutiq</a> and also read the <a href="https://snoutiq.com/first-aid-tips-pet-parents" className="text-purple-300 hover:text-purple-200 font-medium">First Aid Tips Every Pet Parent Should Know</a> guide to stay prepared for emergencies.
              </p>
              <p className="text-gray-400 mt-8 text-sm">
                © 2024 Snoutiq. All rights reserved.
              </p>
            </div>
          </footer>
        </article>
<Footer/>
        {/* Back to Top Button */}
        <button
          onClick={scrollToTop}
          className={`fixed bottom-8 right-8 bg-gradient-to-r from-indigo-500 to-purple-600 text-white w-14 h-14 rounded-full flex items-center justify-center shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 ${showBackToTop ? 'opacity-100' : 'opacity-0 pointer-events-none'}`}
          aria-label="Back to top"
        >
          <span className="text-2xl">↑</span>
        </button>
      </div>
    </>
  );
};

export default VaccinationScheduleForPets;