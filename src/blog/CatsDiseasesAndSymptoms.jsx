import React, { useEffect, useState } from "react";
import Footer from "../components/Footer";
import Header from "../components/Header";
import { Helmet, HelmetProvider } from "react-helmet-async";
import { Link } from "react-router-dom";

const CatsDiseasesAndSymptoms = () => {
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
        const targetId = target.getAttribute("href");
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
          targetElement.scrollIntoView({
            behavior: "smooth",
            block: "start",
          });
        }
      }
    };

    window.addEventListener("scroll", handleScroll);
    document.addEventListener("click", handleAnchorClick);

    return () => {
      window.removeEventListener("scroll", handleScroll);
      document.removeEventListener("click", handleAnchorClick);
    };
  }, []);

  const scrollToTop = () => {
    window.scrollTo({
      top: 0,
      behavior: "smooth",
    });
  };

  return (
    <>
      <HelmetProvider>
        <Helmet>
          {/* SEO Meta Tags */}
          <title>
            Cats Diseases and Symptoms | Common Cat Infections & Health Guide
          </title>
          <meta
            name="title"
            content="Cats Diseases and Symptoms | Common Cat Infections & Health Guide"
          />
          <meta
            name="description"
            content="Learn about cats diseases and symptoms including common cat infections, ailments, causes, treatment options, medicines and prevention tips every cat parent should know."
          />
          <meta
            name="keywords"
            content="cats diseases and symptoms, cat infection, cat infection symptoms, diseases caused by cats, cat medical care, cat infection medicine, common cat diseases, common cat ailments"
          />
          <meta name="author" content="SnoutIQ" />
          <meta name="robots" content="index, follow" />
          <meta
            property="og:title"
            content="Cats Diseases and Symptoms | Common Cat Infections & Health Guide"
          />
          <meta
            property="og:description"
            content="Learn about cats diseases and symptoms including common cat infections, ailments, causes, treatment options, medicines and prevention tips every cat parent should know."
          />
          <meta property="og:type" content="article" />
          <meta
            property="og:image"
            content="https://snoutiq.com/images/cat-diseases-symptoms.jpg"
          />
          <meta property="og:url" content="https://snoutiq.com/blog/cats-diseases-and-symptoms" />
          <link
            rel="canonical"
            href="https://snoutiq.com/blog/cats-diseases-and-symptoms"
          />

          {/* Twitter Card */}
          <meta property="twitter:card" content="summary_large_image" />
          <meta property="twitter:url" content="https://snoutiq.com/blog/cats-diseases-and-symptoms" />
          <meta
            property="twitter:title"
            content="Cats Diseases and Symptoms | Common Cat Infections & Health Guide"
          />
          <meta
            property="twitter:description"
            content="Learn about cats diseases and symptoms including common cat infections, ailments, causes, treatment options, medicines and prevention tips every cat parent should know."
          />
          <meta
            property="twitter:image"
            content="https://snoutiq.com/images/cat-diseases-symptoms.jpg"
          />

          {/* Schema.org Markup */}
          <script type="application/ld+json">
            {JSON.stringify({
              "@context": "https://schema.org",
              "@type": "Article",
              headline: "Cats Diseases and Symptoms: Complete Guide for Cat Parents",
              description:
                "Complete guide on cats diseases and symptoms including common cat infections, treatment options, prevention tips and medical care for healthy cats.",
              image: "https://snoutiq.com/images/cat-diseases-symptoms.jpg",
              author: {
                "@type": "Organization",
                name: "SnoutIQ",
              },
              publisher: {
                "@type": "Organization",
                name: "SnoutIQ",
                logo: {
                  "@type": "ImageObject",
                  url: "https://snoutiq.com/logo.png",
                },
              },
              datePublished: "2025-01-15",
              dateModified: "2025-01-15",
              mainEntityOfPage: {
                "@type": "WebPage",
                "@id": "https://snoutiq.com/blog/cats-diseases-and-symptoms",
              },
            })}
          </script>

          {/* FAQ Schema */}
          <script type="application/ld+json">
            {JSON.stringify({
              "@context": "https://schema.org",
              "@type": "FAQPage",
              mainEntity: [
                {
                  "@type": "Question",
                  name: "What are the most common cats diseases and symptoms?",
                  acceptedAnswer: {
                    "@type": "Answer",
                    text: "The most common cats diseases and symptoms include respiratory infections, skin problems, digestive issues and ear infections.",
                  },
                },
                {
                  "@type": "Question",
                  name: "How can I tell if my cat has an infection?",
                  acceptedAnswer: {
                    "@type": "Answer",
                    text: "Common cat infection symptoms include fever, lethargy, appetite loss, vomiting and abnormal discharge.",
                  },
                },
                {
                  "@type": "Question",
                  name: "Are cat diseases contagious to humans?",
                  acceptedAnswer: {
                    "@type": "Answer",
                    text: "Some diseases caused by cats can affect humans, but proper hygiene and vet care reduce risk significantly.",
                  },
                },
                {
                  "@type": "Question",
                  name: "Can cat infections be treated at home?",
                  acceptedAnswer: {
                    "@type": "Answer",
                    text: "Mild issues may improve with care, but proper cat infection medicine should always be prescribed by a vet.",
                  },
                },
              ],
            })}
          </script>
        </Helmet>

        <div className="font-sans text-gray-800 bg-gradient-to-br from-blue-50 to-indigo-50 min-h-screen mt-20">
          <Header />
          
          {/* Breadcrumb */}
          <nav className="max-w-7xl mx-auto px-8 py-4 text-sm" aria-label="Breadcrumb">
            <ol className="flex items-center space-x-2">
              <li>
                <Link to="/" className="text-blue-600 hover:underline">
                  Home
                </Link>
              </li>
              <li className="text-gray-500">»</li>
              <li>
                <Link to="/blog" className="text-blue-600 hover:underline">
                  Blog
                </Link>
              </li>
              <li className="text-gray-500">»</li>
              <li className="text-gray-700">Cats Diseases and Symptoms</li>
            </ol>
          </nav>

          <main className="max-w-4xl mx-auto px-8 py-8">
            <article
              className="bg-white rounded-xl shadow-lg overflow-hidden"
              itemScope
              itemType="http://schema.org/Article"
            >
              {/* Header */}
              <header className="bg-gradient-to-r from-blue-600 to-indigo-600 text-white relative overflow-hidden py-16 px-8 text-center">
                <div className="absolute text-9xl opacity-10 -top-10 -right-10 transform -rotate-12">
                  🏥
                </div>
                <div className="absolute text-7xl opacity-10 -bottom-10 -left-10 transform rotate-12">
                  🐱
                </div>

                <div className="relative z-10">
                  <h1
                    className="text-3xl md:text-4xl lg:text-5xl font-black mb-6 drop-shadow-lg"
                    itemProp="headline"
                  >
                    Cats Diseases and Symptoms: Complete Guide for Cat Parents
                  </h1>
                  <p className="text-xl md:text-2xl opacity-95 font-light mb-8 leading-relaxed">
                    Learn about cats diseases and symptoms including common cat infections, ailments, causes, treatment options, medicines and prevention tips
                  </p>
                </div>
              </header>

              {/* Main Content */}
              <section className="px-6 md:px-10 py-8" itemProp="articleBody">
                {/* Introduction */}
                <div className="bg-gradient-to-r from-blue-100 to-indigo-100 border-l-4 border-blue-500 p-6 rounded-lg mb-8">
                  <p className="text-lg font-medium">
                    Understanding <strong>cats diseases and symptoms</strong> is essential for every cat parent to protect their pet's health and prevent serious complications. Most cat diseases start with mild signs like lethargy, appetite loss or skin issues, but early identification and timely treatment can save your cat's life.
                  </p>
                </div>

                <p className="text-lg mb-8 leading-relaxed">
                  This complete guide explains <strong>common cat diseases</strong>, infections, symptoms, causes, treatment options and prevention tips in simple language so you can take quick and informed action.
                </p>

                {/* Why Knowing Cats Diseases and Symptoms Is Important */}
                <section id="why-important" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">🏥</span> Why Knowing Cats Diseases and Symptoms Is Important
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    Cats are very good at hiding pain. By the time visible symptoms appear, the disease may already be advanced. Knowing <strong>cats diseases and symptoms</strong> helps you:
                  </p>

                  {/* Disease Grid */}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div className="bg-white border-2 border-gray-200 rounded-lg p-6 hover:border-blue-500 hover:shadow-lg transition-all">
                      <h4 className="text-xl font-bold text-blue-600 mb-3">Early Detection</h4>
                      <p className="text-gray-700">
                        Detect illness early before it becomes severe and harder to treat.
                      </p>
                    </div>

                    <div className="bg-white border-2 border-gray-200 rounded-lg p-6 hover:border-blue-500 hover:shadow-lg transition-all">
                      <h4 className="text-xl font-bold text-blue-600 mb-3">Prevent Spreading</h4>
                      <p className="text-gray-700">
                        Prevent spreading of <strong>cat infection</strong> to other pets in your household.
                      </p>
                    </div>

                    <div className="bg-white border-2 border-gray-200 rounded-lg p-6 hover:border-blue-500 hover:shadow-lg transition-all">
                      <h4 className="text-xl font-bold text-blue-600 mb-3">Cost Savings</h4>
                      <p className="text-gray-700">
                        Avoid costly treatments later by addressing issues promptly.
                      </p>
                    </div>

                    <div className="bg-white border-2 border-gray-200 rounded-lg p-6 hover:border-blue-500 hover:shadow-lg transition-all">
                      <h4 className="text-xl font-bold text-blue-600 mb-3">Better Quality of Life</h4>
                      <p className="text-gray-700">
                        Improve your cat's overall quality of life and longevity.
                      </p>
                    </div>
                  </div>

                  <p className="text-lg">
                    Good nutrition also plays a major role in disease prevention. Feeding high quality food strengthens immunity. You can read this detailed guide on{" "}
                    <Link to="/blog/best-cat-food-in-india" className="text-blue-600 hover:underline">
                      the best cat food in India
                    </Link>{" "}
                    to support your cat's health.
                  </p>
                </section>

                {/* Common Cat Diseases and Their Symptoms */}
                <section id="common-diseases" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">🩺</span> Common Cat Diseases and Their Symptoms
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    Below are the most <strong>common cat diseases</strong> seen in India along with early warning signs.
                  </p>

                  {/* Disease 1 */}
                  <div className="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mb-6">
                    <h3 className="text-2xl font-bold text-blue-600 mb-4">1. Upper Respiratory Infection</h3>
                    <p className="text-lg mb-4">
                      This is one of the most common forms of <strong>cat infection</strong>.
                    </p>
                    
                    <div className="bg-purple-50 border-l-4 border-purple-500 p-4 rounded-lg mb-4">
                      <p className="font-semibold mb-2"><strong>Symptoms:</strong></p>
                      <ul className="list-disc pl-6 space-y-1">
                        <li>Sneezing and nasal discharge</li>
                        <li>Watery eyes</li>
                        <li>Fever</li>
                        <li>Loss of appetite</li>
                      </ul>
                    </div>
                    
                    <p className="text-lg">
                      This infection spreads easily between cats, especially in multi cat households.
                    </p>
                  </div>

                  {/* Disease 2 */}
                  <div className="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mb-6">
                    <h3 className="text-2xl font-bold text-blue-600 mb-4">2. Feline Panleukopenia</h3>
                    <p className="text-lg mb-4">
                      A serious viral disease mostly affecting kittens.
                    </p>
                    
                    <div className="bg-purple-50 border-l-4 border-purple-500 p-4 rounded-lg mb-4">
                      <p className="font-semibold mb-2"><strong>Symptoms:</strong></p>
                      <ul className="list-disc pl-6 space-y-1">
                        <li>Severe vomiting and diarrhea</li>
                        <li>Sudden weakness</li>
                        <li>Dehydration</li>
                        <li>Weight loss</li>
                      </ul>
                    </div>
                    
                    <p className="text-lg">
                      Timely vaccination is critical. Refer to this{" "}
                      <Link
                        to="/blog/vaccination-schedule-for-pets-in-india"
                        className="text-blue-600 hover:underline"
                      >
                        vaccination schedule for pets in India
                      </Link>{" "}
                      for prevention.
                    </p>
                  </div>

                  {/* Disease 3 */}
                  <div className="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mb-6">
                    <h3 className="text-2xl font-bold text-blue-600 mb-4">3. Skin Infections</h3>
                    <p className="text-lg mb-4">
                      Skin related <strong>cat infection symptoms</strong> are common in Indian weather.
                    </p>
                    
                    <div className="bg-purple-50 border-l-4 border-purple-500 p-4 rounded-lg mb-4">
                      <p className="font-semibold mb-2"><strong>Symptoms:</strong></p>
                      <ul className="list-disc pl-6 space-y-1">
                        <li>Hair loss</li>
                        <li>Redness and itching</li>
                        <li>Scabs and dandruff</li>
                        <li>Excessive scratching</li>
                      </ul>
                    </div>
                    
                    <p className="text-lg">
                      Poor grooming and nutrition often worsen skin conditions. Winter grooming is especially important.{" "}
                      <Link
                        to="/blog/why-winter-grooming-is-important-for-cats"
                        className="text-blue-600 hover:underline"
                      >
                        Learn more here
                      </Link>
                      .
                    </p>
                  </div>

                  {/* Disease 4 */}
                  <div className="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mb-6">
                    <h3 className="text-2xl font-bold text-blue-600 mb-4">4. Ear Infections</h3>
                    <p className="text-lg mb-4">
                      Ear mites and bacterial growth cause painful infections.
                    </p>
                    
                    <div className="bg-purple-50 border-l-4 border-purple-500 p-4 rounded-lg mb-4">
                      <p className="font-semibold mb-2"><strong>Symptoms:</strong></p>
                      <ul className="list-disc pl-6 space-y-1">
                        <li>Head shaking</li>
                        <li>Bad odor from ears</li>
                        <li>Redness and swelling</li>
                        <li>Discharge</li>
                      </ul>
                    </div>
                    
                    <p className="text-lg">
                      Untreated ear infections can affect balance and hearing.
                    </p>
                  </div>

                  {/* Disease 5 */}
                  <div className="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mb-6">
                    <h3 className="text-2xl font-bold text-blue-600 mb-4">5. Gastrointestinal Diseases</h3>
                    <p className="text-lg mb-4">
                      Digestive issues fall under <strong>common cat ailments</strong>.
                    </p>
                    
                    <div className="bg-purple-50 border-l-4 border-purple-500 p-4 rounded-lg mb-4">
                      <p className="font-semibold mb-2"><strong>Symptoms:</strong></p>
                      <ul className="list-disc pl-6 space-y-1">
                        <li>Vomiting</li>
                        <li>Diarrhea</li>
                        <li>Constipation</li>
                        <li>Bloated abdomen</li>
                      </ul>
                    </div>
                    
                    <p className="text-lg">
                      Dietary imbalance is a major cause. Feeding poor quality food increases risk.
                    </p>
                  </div>
                </section>

                {/* Emergency Symptoms */}
                <section id="emergency-symptoms" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">🚨</span> Cat Infection Symptoms You Should Never Ignore
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <div className="bg-red-50 border-l-4 border-red-500 p-6 rounded-lg mb-6">
                    <p className="text-lg font-semibold mb-4">
                      <strong>Some cat infection symptoms indicate emergencies:</strong>
                    </p>
                    <ul className="list-disc pl-6 space-y-2">
                      <li>Continuous vomiting</li>
                      <li>High fever</li>
                      <li>Difficulty breathing</li>
                      <li>Sudden collapse</li>
                      <li>Blood in urine or stool</li>
                    </ul>
                  </div>

                  <p className="text-lg">
                    In such cases, immediate vet consultation is required. If visiting a clinic is not possible, consider{" "}
                    <Link
                      to="/blog/online-vet-consultation"
                      className="text-blue-600 hover:underline"
                    >
                      online vet consultation
                    </Link>{" "}
                    for quick guidance.
                  </p>
                </section>

                {/* Zoonotic Diseases */}
                <section id="zoonotic" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">👥</span> Diseases Caused by Cats: Can Cats Make Humans Sick?
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    Many pet parents worry about <strong>diseases caused by cats</strong>.
                  </p>

                  <div className="bg-yellow-50 border-l-4 border-yellow-500 p-6 rounded-lg mb-6">
                    <p className="text-lg font-semibold mb-4">
                      <strong>Common zoonotic concerns include:</strong>
                    </p>
                    <ul className="list-disc pl-6 space-y-2">
                      <li>Ringworm</li>
                      <li>Toxoplasmosis</li>
                      <li>Cat scratch disease</li>
                    </ul>
                  </div>

                  <p className="text-lg">
                    These diseases are rare and mostly affect people with weak immunity. Proper hygiene, grooming and regular vet care significantly reduce risk.
                  </p>
                </section>

                {/* Cat Hair Diseases */}
                <section id="cat-hair" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">💇</span> Diseases Caused by Cats Hair: Myth vs Reality
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    There is a common misconception about <strong>diseases caused by cats hair</strong>. Cat hair itself does not cause disease, but it can carry:
                  </p>

                  <div className="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mb-6">
                    <ul className="list-disc pl-6 space-y-2">
                      <li>Allergens</li>
                      <li>Fungal spores</li>
                      <li>Parasites</li>
                    </ul>
                  </div>

                  <p className="text-lg">
                    Regular brushing, bathing when required and clean living spaces prevent related issues.
                  </p>
                </section>

                {/* Medical Care */}
                <section id="medical-care" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">⚕️</span> Cat Medical Care and Diagnosis
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    Proper <strong>cat medical care</strong> involves routine checkups, vaccinations and parasite control.
                  </p>

                  <div className="bg-green-50 border-l-4 border-green-500 p-6 rounded-lg mb-6">
                    <p className="text-lg font-semibold mb-4">
                      <strong>Basic medical care includes:</strong>
                    </p>
                    <ul className="list-disc pl-6 space-y-2">
                      <li>Annual health checkups</li>
                      <li>Deworming</li>
                      <li>Vaccination</li>
                      <li>Dental care</li>
                    </ul>
                  </div>

                  <p className="text-lg">
                    If your cat shows recurring symptoms, blood tests and imaging may be needed for diagnosis.
                  </p>
                </section>

                {/* Treatment */}
                <section id="treatment" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">💊</span> Cat Infection Medicine and Treatment
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <div className="bg-yellow-50 border-l-4 border-yellow-500 p-6 rounded-lg mb-6">
                    <p className="text-lg font-semibold">
                      <strong>Never give human medicines to cats.</strong> <strong>Cat infection medicine</strong> depends on the cause.
                    </p>
                  </div>

                  <div className="overflow-x-auto rounded-xl shadow-lg mb-8">
                    <table className="min-w-full divide-y divide-gray-200">
                      <thead className="bg-gradient-to-r from-blue-600 to-indigo-600">
                        <tr>
                          <th className="px-6 py-4 text-left text-sm font-semibold text-white">
                            Type of Infection
                          </th>
                          <th className="px-6 py-4 text-left text-sm font-semibold text-white">
                            Common Treatment
                          </th>
                        </tr>
                      </thead>
                      <tbody className="bg-white divide-y divide-gray-200">
                        {[
                          ["Bacterial", "Antibiotics prescribed by vet"],
                          ["Fungal", "Antifungal creams or oral medicine"],
                          ["Viral", "Supportive care and immunity boosters"],
                          ["Parasitic", "Deworming and anti parasite drugs"],
                        ].map((row, index) => (
                          <tr
                            key={index}
                            className={
                              index % 2 === 0
                                ? "bg-blue-50 hover:bg-blue-100"
                                : "bg-white hover:bg-blue-100"
                            }
                          >
                            <td className="px-6 py-4 font-semibold text-gray-900">
                              {row[0]}
                            </td>
                            <td className="px-6 py-4 text-gray-700">{row[1]}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>

                  <p className="text-lg">
                    Always consult a vet before starting any medication.
                  </p>
                </section>

                {/* Prevention */}
                <section id="prevention" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">🛡️</span> Prevention Tips for Common Cat Diseases
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <div className="bg-teal-50 border-l-4 border-teal-500 p-6 rounded-lg mb-6">
                    <p className="text-lg font-semibold mb-4">
                      <strong>Prevention is always better than treatment:</strong>
                    </p>
                    <ul className="list-disc pl-6 space-y-2">
                      <li>Feed a balanced diet</li>
                      <li>Keep vaccinations up to date</li>
                      <li>Maintain hygiene and grooming</li>
                      <li>Avoid contact with infected animals</li>
                      <li>Schedule regular vet visits</li>
                    </ul>
                  </div>

                  <p className="text-lg">
                    If you are a new cat parent, understanding breeds can help predict health risks. Read about{" "}
                    <Link
                      to="/blog/best-cat-breeds-in-india"
                      className="text-blue-600 hover:underline"
                    >
                      best cat breeds in India here
                    </Link>
                    .
                  </p>
                </section>

                {/* Nutrition */}
                <section id="nutrition" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">🍖</span> Role of Nutrition in Disease Prevention
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    A strong immune system reduces the risk of <strong>common cat diseases</strong>. Poor nutrition weakens immunity and increases infection risk.
                  </p>

                  <div className="bg-green-50 border-l-4 border-green-500 p-6 rounded-lg">
                    <p className="text-lg font-semibold mb-4">
                      <strong>High quality food helps:</strong>
                    </p>
                    <ul className="list-disc pl-6 space-y-2">
                      <li>Improve gut health</li>
                      <li>Strengthen immunity</li>
                      <li>Maintain healthy skin and coat</li>
                    </ul>
                  </div>
                </section>

                {/* First Aid */}
                <section id="first-aid" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">🩹</span> Emergency Situations and First Aid
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    Knowing basic first aid can save your cat's life before reaching the vet. This detailed guide on{" "}
                    <Link
                      to="/blog/first-aid-tips-every-pet-parent-should-know"
                      className="text-blue-600 hover:underline"
                    >
                      first aid tips every pet parent should know
                    </Link>{" "}
                    is highly recommended.
                  </p>
                </section>

                {/* FAQ Section */}
                <section id="faq" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">❓</span> Frequently Asked Questions
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <div className="space-y-4">
                    {[
                      {
                        q: "What are the most common cats diseases and symptoms?",
                        a: "The most common cats diseases and symptoms include respiratory infections, skin problems, digestive issues and ear infections.",
                      },
                      {
                        q: "How can I tell if my cat has an infection?",
                        a: "Common cat infection symptoms include fever, lethargy, appetite loss, vomiting and abnormal discharge.",
                      },
                      {
                        q: "Are cat diseases contagious to humans?",
                        a: "Some diseases caused by cats can affect humans, but proper hygiene and vet care reduce risk significantly.",
                      },
                      {
                        q: "Is hair fall a sign of disease?",
                        a: "Hair fall can indicate skin infection, stress or nutritional deficiency. It is not directly a disease caused by cats hair.",
                      },
                      {
                        q: "Can cat infections be treated at home?",
                        a: "Mild issues may improve with care, but proper cat infection medicine should always be prescribed by a vet.",
                      },
                      {
                        q: "How often should cats visit a vet?",
                        a: "Healthy adult cats should visit a vet at least once a year. Kittens and senior cats need more frequent visits.",
                      },
                      {
                        q: "Does diet affect cat diseases?",
                        a: "Yes, poor diet increases risk of infections. Feeding quality food reduces chances of common cat ailments.",
                      },
                      {
                        q: "When should I seek emergency care?",
                        a: "If your cat shows breathing difficulty, seizures, bleeding or collapse, seek immediate medical help.",
                      },
                    ].map((faq, index) => (
                      <div
                        key={index}
                        className="bg-gray-50 border-l-4 border-blue-500 p-6 rounded-lg hover:bg-gray-100 transition-all"
                      >
                        <div className="font-bold text-lg text-gray-800 mb-2">
                          {index + 1}. {faq.q}
                        </div>
                        <div className="text-gray-700">{faq.a}</div>
                      </div>
                    ))}
                  </div>
                </section>

                {/* Conclusion */}
                <section id="conclusion" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">✅</span> Conclusion
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <p className="text-lg leading-relaxed mb-8">
                    Understanding <strong>cats diseases and symptoms</strong> empowers cat parents to act early and protect their pet's health. From infections and skin problems to myths around cat hair diseases, awareness is the key. Combine timely medical care, proper nutrition, grooming and vaccinations to ensure your cat lives a long, healthy and happy life.
                  </p>

                  <div className="bg-teal-50 border-l-4 border-teal-500 p-6 rounded-lg mb-8">
                    <p className="text-lg font-semibold mb-4">
                      <strong>Key Takeaways:</strong>
                    </p>
                    <ul className="list-disc pl-6 space-y-2">
                      <li>Monitor your cat regularly for early signs of illness</li>
                      <li>Never delay vet consultation for serious symptoms</li>
                      <li>Focus on preventive care through nutrition and vaccination</li>
                      <li>Maintain proper hygiene and grooming habits</li>
                      <li>Stay informed about common cat diseases in your region</li>
                    </ul>
                  </div>

                  {/* CTA Box */}
                  <div className="bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl p-8 text-center shadow-2xl">
                    <h3 className="text-2xl md:text-3xl font-bold mb-4">
                      Trusted Pet Care Support
                    </h3>
                    <p className="text-xl opacity-95 mb-6">
                      For reliable information, expert guidance and veterinary services, visit SnoutIQ. We support pet parents with trusted content and professional veterinary access.
                    </p>
                    <Link
                      to="/"
                      className="inline-block bg-white text-blue-600 px-8 py-3 rounded-lg font-bold hover:bg-gray-100 transition-colors"
                    >
                      Visit SnoutIQ
                    </Link>
                  </div>
                </section>
              </section>
            </article>
          </main>

          {/* Back to Top Button */}
          <button
            onClick={scrollToTop}
            className={`fixed bottom-8 right-8 bg-gradient-to-r from-blue-600 to-indigo-600 text-white w-14 h-14 rounded-full flex items-center justify-center shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 ${
              showBackToTop ? "opacity-100" : "opacity-0 pointer-events-none"
            }`}
            aria-label="Back to top"
          >
            <span className="text-2xl">↑</span>
          </button>

          <Footer />
        </div>
      </HelmetProvider>
    </>
  );
};

export default CatsDiseasesAndSymptoms;

