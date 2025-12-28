import React, { useEffect, useState } from "react";
import Footer from "../components/Footer";
import Header from "../components/Header";
import { Helmet, HelmetProvider } from "react-helmet-async";
import { Link } from "react-router-dom";

const CatVaccinationScheduleIndia = () => {
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
            Cat Vaccination Schedule India | Kitten, Rabies & Feligen Vaccine Guide
          </title>
          <meta
            name="title"
            content="Cat Vaccination Schedule India | Kitten, Rabies & Feligen Vaccine Guide"
          />
          <meta
            name="description"
            content="Complete cat vaccination schedule India with kitten vaccine timeline, Feligen CRP, rabies injection for cats, booster doses and expert vet tips."
          />
          <meta
            name="keywords"
            content="cat vaccination schedule india, kitten vaccine schedule, cat rabies vaccine schedule, feligen vaccine, anti rabies vaccine for cats, persian cat vaccination schedule, cat vaccine name"
          />
          <meta name="author" content="SnoutIQ" />
          <meta name="robots" content="index, follow" />
          <meta
            property="og:title"
            content="Cat Vaccination Schedule India | Kitten, Rabies & Feligen Vaccine Guide"
          />
          <meta
            property="og:description"
            content="Complete cat vaccination schedule India with kitten vaccine timeline, Feligen CRP, rabies injection for cats, booster doses and expert vet tips."
          />
          <meta property="og:type" content="article" />
          <meta
            property="og:image"
            content="https://snoutiq.com/images/cat-vaccination-schedule.jpg"
          />
          <meta property="og:url" content="https://snoutiq.com/blog/cat-vaccination-schedule-india" />
          <link
            rel="canonical"
            href="https://snoutiq.com/blog/cat-vaccination-schedule-india"
          />

          {/* Twitter Card */}
          <meta property="twitter:card" content="summary_large_image" />
          <meta property="twitter:url" content="https://snoutiq.com/blog/cat-vaccination-schedule-india" />
          <meta
            property="twitter:title"
            content="Cat Vaccination Schedule India | Kitten, Rabies & Feligen Vaccine Guide"
          />
          <meta
            property="twitter:description"
            content="Complete cat vaccination schedule India with kitten vaccine timeline, Feligen CRP, rabies injection for cats, booster doses and expert vet tips."
          />
          <meta
            property="twitter:image"
            content="https://snoutiq.com/images/cat-vaccination-schedule.jpg"
          />

          {/* Schema.org Markup */}
          <script type="application/ld+json">
            {JSON.stringify({
              "@context": "https://schema.org",
              "@type": "Article",
              headline: "Cat Vaccination Schedule India: Complete Guide for Kittens and Adult Cats",
              description:
                "Complete cat vaccination schedule India with kitten vaccine timeline, Feligen CRP, rabies injection for cats, booster doses and expert vet tips.",
              image: "https://snoutiq.com/images/cat-vaccination-schedule.jpg",
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
                "@id": "https://snoutiq.com/blog/cat-vaccination-schedule-india",
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
                  name: "What is the correct cat vaccination schedule India?",
                  acceptedAnswer: {
                    "@type": "Answer",
                    text: "The correct cat vaccination schedule India starts at 6 to 8 weeks with Feligen CRP and includes rabies at 3 to 4 months, followed by annual boosters.",
                  },
                },
                {
                  "@type": "Question",
                  name: "Is rabies vaccine mandatory for cats?",
                  acceptedAnswer: {
                    "@type": "Answer",
                    text: "Yes, the anti rabies vaccine for cats is mandatory in India.",
                  },
                },
                {
                  "@type": "Question",
                  name: "Can indoor cats skip vaccination?",
                  acceptedAnswer: {
                    "@type": "Answer",
                    text: "No, indoor cats also need full vaccination as per cat vaccination schedule India.",
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
              <li className="text-gray-700">Cat Vaccination Schedule India</li>
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
                  💉
                </div>
                <div className="absolute text-7xl opacity-10 -bottom-10 -left-10 transform rotate-12">
                  🐱
                </div>

                <div className="relative z-10">
                  <h1
                    className="text-3xl md:text-4xl lg:text-5xl font-black mb-6 drop-shadow-lg"
                    itemProp="headline"
                  >
                    Cat Vaccination Schedule India: Complete Guide for Kittens and Adult Cats
                  </h1>
                  <p className="text-xl md:text-2xl opacity-95 font-light mb-8 leading-relaxed">
                    Complete cat vaccination schedule India with kitten vaccine timeline, Feligen CRP, rabies injection for cats, booster doses and expert vet tips
                  </p>
                </div>
              </header>

              {/* Main Content */}
              <section className="px-6 md:px-10 py-8" itemProp="articleBody">
                {/* Introduction */}
                <div className="bg-gradient-to-r from-blue-100 to-indigo-100 border-l-4 border-blue-500 p-6 rounded-lg mb-8">
                  <p className="text-lg font-medium">
                    Following the correct <strong>cat vaccination schedule India</strong> is one of the most important responsibilities of a cat parent. Cats in India must receive core vaccines like FVRCP and rabies at the right age to stay protected from life threatening viral and bacterial infections.
                  </p>
                </div>

                <p className="text-lg mb-8 leading-relaxed">
                  This detailed guide explains the <strong>cat vaccination schedule</strong>, vaccine names, rabies injections, kitten timelines, Persian cat vaccination schedule and booster doses in simple, vet approved language.
                </p>

                {/* Why Cat Vaccination Is Important */}
                <section id="why-important" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">💉</span> Why Cat Vaccination Is Important
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    Vaccination protects cats from deadly diseases such as panleukopenia, calicivirus, herpesvirus and rabies. Following the <strong>cat vaccination schedule India</strong> helps to:
                  </p>

                  {/* Vaccine Grid */}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div className="bg-white border-2 border-gray-200 rounded-lg p-6 hover:border-blue-500 hover:shadow-lg transition-all">
                      <h4 className="text-xl font-bold text-blue-600 mb-3">Prevent Infections</h4>
                      <p className="text-gray-700">
                        Protect your cat from serious viral and bacterial infections that can be fatal.
                      </p>
                    </div>

                    <div className="bg-white border-2 border-gray-200 rounded-lg p-6 hover:border-blue-500 hover:shadow-lg transition-all">
                      <h4 className="text-xl font-bold text-blue-600 mb-3">Reduce Costs</h4>
                      <p className="text-gray-700">
                        Prevention through vaccination is far cheaper than treating severe illnesses.
                      </p>
                    </div>

                    <div className="bg-white border-2 border-gray-200 rounded-lg p-6 hover:border-blue-500 hover:shadow-lg transition-all">
                      <h4 className="text-xl font-bold text-blue-600 mb-3">Protect Humans</h4>
                      <p className="text-gray-700">
                        Prevent zoonotic diseases like rabies that can spread from cats to humans.
                      </p>
                    </div>

                    <div className="bg-white border-2 border-gray-200 rounded-lg p-6 hover:border-blue-500 hover:shadow-lg transition-all">
                      <h4 className="text-xl font-bold text-blue-600 mb-3">Longer Lifespan</h4>
                      <p className="text-gray-700">
                        Vaccinated cats live longer, healthier lives with better quality of life.
                      </p>
                    </div>
                  </div>

                  <p className="text-lg">
                    To understand health risks better, you can also read this guide on{" "}
                    <Link to="/blog/cats-diseases-and-symptoms" className="text-blue-600 hover:underline">
                      cats diseases and symptoms
                    </Link>
                    .
                  </p>
                </section>

                {/* Core Vaccines */}
                <section id="core-vaccines" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">🛡️</span> Core Vaccines for Cats in India
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    Every cat must receive these core vaccines:
                  </p>

                  <div className="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mb-6">
                    <h3 className="text-2xl font-bold text-blue-600 mb-4">FVRCP Vaccine</h3>
                    <p className="text-lg mb-4">
                      Also known as the <strong>Feligen vaccine</strong> or <strong>Feligen CRP vaccine</strong>, this protects against:
                    </p>
                    <ul className="list-disc pl-6 space-y-2">
                      <li>
                        <strong>Feline Viral Rhinotracheitis</strong> - Upper respiratory infection
                      </li>
                      <li>
                        <strong>Calicivirus</strong> - Respiratory disease and oral disease
                      </li>
                      <li>
                        <strong>Panleukopenia</strong> - Highly contagious and often fatal viral disease
                      </li>
                    </ul>
                  </div>

                  <div className="bg-red-50 border-l-4 border-red-500 p-6 rounded-lg mb-6">
                    <h3 className="text-2xl font-bold text-red-600 mb-4">Rabies Vaccine</h3>
                    <p className="text-lg">
                      The <strong>anti rabies vaccine for cats</strong> is mandatory in India and protects both pets and humans from this deadly disease.
                    </p>
                  </div>
                </section>

                {/* Cat Vaccine Name List */}
                <section id="vaccine-names" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">📋</span> Cat Vaccine Name List
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    Below are commonly used <strong>cat vaccine names</strong> in India:
                  </p>

                  <div className="overflow-x-auto rounded-xl shadow-lg mb-8">
                    <table className="min-w-full divide-y divide-gray-200">
                      <thead className="bg-gradient-to-r from-blue-600 to-indigo-600">
                        <tr>
                          <th className="px-6 py-4 text-left text-sm font-semibold text-white">
                            Vaccine Name
                          </th>
                          <th className="px-6 py-4 text-left text-sm font-semibold text-white">
                            Protects Against
                          </th>
                        </tr>
                      </thead>
                      <tbody className="bg-white divide-y divide-gray-200">
                        {[
                          ["Feligen CRP", "Viral infections (FVRCP)"],
                          ["Nobivac Tricat", "Panleukopenia, Calicivirus, Herpesvirus"],
                          ["Purevax", "Viral diseases"],
                          ["Nobivac Rabies", "Rabies"],
                          ["Defensor Rabies", "Rabies"],
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
                </section>

                {/* Kitten Vaccine Schedule */}
                <section id="kitten-schedule" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">🐱</span> Kitten Vaccine Schedule
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    The <strong>kitten vaccine schedule</strong> starts early because kittens have weak immunity.
                  </p>

                  <div className="bg-purple-50 border-l-4 border-purple-500 p-6 rounded-lg mb-8">
                    <div className="overflow-x-auto rounded-xl shadow-lg">
                      <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gradient-to-r from-purple-600 to-indigo-600">
                          <tr>
                            <th className="px-6 py-4 text-left text-sm font-semibold text-white">
                              Age of Kitten
                            </th>
                            <th className="px-6 py-4 text-left text-sm font-semibold text-white">
                              Vaccine
                            </th>
                          </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                          {[
                            ["6 to 8 weeks", "Feligen CRP first dose"],
                            ["9 to 11 weeks", "Feligen CRP second dose"],
                            ["12 to 13 weeks", "Feligen CRP third dose"],
                            ["14 to 16 weeks", "Rabies injection for cats"],
                          ].map((row, index) => (
                            <tr
                              key={index}
                              className={
                                index % 2 === 0
                                  ? "bg-purple-50 hover:bg-purple-100"
                                  : "bg-white hover:bg-purple-100"
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
                  </div>

                  <p className="text-lg">
                    Following this <strong>cat vaccination schedule India</strong> ensures complete early protection for your kitten.
                  </p>
                </section>

                {/* Adult Cat Vaccination Schedule */}
                <section id="adult-schedule" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">🐈</span> Adult Cat Vaccination Schedule
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    Adult cats also need regular boosters to maintain immunity.
                  </p>

                  <div className="bg-purple-50 border-l-4 border-purple-500 p-6 rounded-lg mb-6">
                    <div className="overflow-x-auto rounded-xl shadow-lg">
                      <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gradient-to-r from-purple-600 to-indigo-600">
                          <tr>
                            <th className="px-6 py-4 text-left text-sm font-semibold text-white">
                              Age
                            </th>
                            <th className="px-6 py-4 text-left text-sm font-semibold text-white">
                              Vaccine
                            </th>
                          </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                          {[
                            ["1 year", "FVRCP booster"],
                            ["1 year", "Rabies booster"],
                            ["Every year", "Annual boosters"],
                          ].map((row, index) => (
                            <tr
                              key={index}
                              className={
                                index % 2 === 0
                                  ? "bg-purple-50 hover:bg-purple-100"
                                  : "bg-white hover:bg-purple-100"
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
                  </div>

                  <div className="bg-yellow-50 border-l-4 border-yellow-500 p-6 rounded-lg">
                    <p className="text-lg font-semibold">
                      <strong>Important:</strong> Missing boosters can reduce immunity and increase infection risk significantly.
                    </p>
                  </div>
                </section>

                {/* Cat Rabies Vaccine Schedule */}
                <section id="rabies-schedule" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">🦠</span> Cat Rabies Vaccine Schedule
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    The <strong>cat rabies vaccine schedule</strong> in India is strictly regulated:
                  </p>

                  <div className="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mb-6">
                    <ul className="list-disc pl-6 space-y-2">
                      <li>
                        <strong>First rabies injection</strong> at 3 to 4 months
                      </li>
                      <li>
                        <strong>First booster</strong> after 1 year
                      </li>
                      <li>
                        <strong>Annual boosters</strong> thereafter
                      </li>
                    </ul>
                  </div>

                  <p className="text-lg">
                    The <strong>anti rabies vaccine schedule in India</strong> applies to both indoor and outdoor cats. Even indoor cats can be exposed to rabies through contact with infected animals that enter the home.
                  </p>
                </section>

                {/* Persian Cat Vaccination Schedule */}
                <section id="persian-schedule" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">👑</span> Persian Cat Vaccination Schedule
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    The <strong>Persian cat vaccination schedule</strong> is the same as other breeds, but Persian cats are more prone to respiratory issues.
                  </p>

                  <div className="bg-teal-50 border-l-4 border-teal-500 p-6 rounded-lg mb-6">
                    <p className="text-lg font-semibold mb-4">
                      <strong>Extra care tips for Persian cats:</strong>
                    </p>
                    <ul className="list-disc pl-6 space-y-2">
                      <li>Never delay vaccines</li>
                      <li>Avoid crowded places until vaccination is complete</li>
                      <li>Maintain proper grooming to prevent respiratory issues</li>
                      <li>Monitor for any adverse reactions more carefully</li>
                    </ul>
                  </div>

                  <p className="text-lg">
                    Learn more about grooming needs here:{" "}
                    <Link
                      to="/blog/why-winter-grooming-is-important-for-cats"
                      className="text-blue-600 hover:underline"
                    >
                      why winter grooming is important for cats
                    </Link>
                    .
                  </p>
                </section>

                {/* Side Effects */}
                <section id="side-effects" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">⚠️</span> Side Effects of Cat Vaccines
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    Most vaccines are safe. Mild side effects may include:
                  </p>

                  <div className="bg-yellow-50 border-l-4 border-yellow-500 p-6 rounded-lg mb-6">
                    <ul className="list-disc pl-6 space-y-2 mb-4">
                      <li>Slight fever</li>
                      <li>Lethargy for 24-48 hours</li>
                      <li>Mild swelling at injection site</li>
                      <li>Reduced appetite temporarily</li>
                    </ul>
                    <p className="text-lg font-semibold">
                      <strong>These usually resolve within 24 to 48 hours.</strong> Contact a vet if symptoms persist or worsen.
                    </p>
                  </div>
                </section>

                {/* Missed Vaccine */}
                <section id="missed-vaccine" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">⏰</span> What Happens If Vaccination Is Missed
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    Missing vaccines can lead to serious illness and higher treatment costs. In such cases, vets may restart the <strong>cat vaccination schedule India</strong> depending on age and health status.
                  </p>

                  <div className="bg-red-50 border-l-4 border-red-500 p-6 rounded-lg">
                    <p className="text-lg font-semibold">
                      <strong>Important:</strong> If you've missed a vaccine appointment, consult your veterinarian immediately to determine the best course of action. Don't try to compensate by giving multiple vaccines at once.
                    </p>
                  </div>
                </section>

                {/* Nutrition */}
                <section id="nutrition" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">🍖</span> Role of Nutrition During Vaccination
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    Good nutrition helps the immune system respond better to vaccines. Feeding high quality food before and after vaccination is recommended.
                  </p>

                  <div className="bg-green-50 border-l-4 border-green-500 p-6 rounded-lg mb-6">
                    <p className="text-lg font-semibold mb-4">
                      <strong>Nutritional support during vaccination:</strong>
                    </p>
                    <ul className="list-disc pl-6 space-y-2">
                      <li>Ensures strong immune response to vaccines</li>
                      <li>Helps body recover from mild side effects</li>
                      <li>Supports overall health and vitality</li>
                      <li>Reduces risk of complications</li>
                    </ul>
                  </div>

                  <p className="text-lg">
                    Read this detailed guide on{" "}
                    <Link
                      to="/blog/best-cat-food-in-india"
                      className="text-blue-600 hover:underline"
                    >
                      the best cat food in India
                    </Link>{" "}
                    for optimal nutrition.
                  </p>
                </section>

                {/* Emergency Care */}
                <section id="emergency-care" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">🚑</span> Emergency Care and First Aid
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    Some cats may show allergic reactions after vaccination, though rare. Knowing first aid can help in emergency situations.
                  </p>

                  <div className="bg-teal-50 border-l-4 border-teal-500 p-6 rounded-lg mb-6">
                    <p className="text-lg font-semibold mb-4">
                      <strong>Signs of vaccine reaction requiring immediate attention:</strong>
                    </p>
                    <ul className="list-disc pl-6 space-y-2">
                      <li>Difficulty breathing</li>
                      <li>Facial swelling</li>
                      <li>Severe vomiting or diarrhea</li>
                      <li>Collapse or loss of consciousness</li>
                    </ul>
                  </div>

                  <p className="text-lg">
                    Refer to{" "}
                    <Link
                      to="/blog/first-aid-tips-every-pet-parent-should-know"
                      className="text-blue-600 hover:underline"
                    >
                      first aid tips every pet parent should know
                    </Link>{" "}
                    for comprehensive guidance.
                  </p>
                </section>

                {/* Online Support */}
                <section id="online-support" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">💻</span> Online Vet Support
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    If you are unsure about vaccine timing or side effects, expert help is available through{" "}
                    <Link
                      to="/blog/online-vet-consultation"
                      className="text-blue-600 hover:underline"
                    >
                      online vet consultation
                    </Link>
                    .
                  </p>

                  <div className="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg">
                    <p className="text-lg font-semibold mb-4">
                      <strong>Benefits of online consultation:</strong>
                    </p>
                    <ul className="list-disc pl-6 space-y-2">
                      <li>Quick answers to vaccination questions</li>
                      <li>Guidance on side effect management</li>
                      <li>Schedule planning assistance</li>
                      <li>Emergency support when needed</li>
                    </ul>
                  </div>
                </section>

                {/* Related Guides */}
                <section id="related-guides" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-blue-600 mb-6 pb-4 border-b-2 border-blue-400 relative">
                    <span className="text-2xl mr-2">📚</span> Related Vaccination Guides
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-indigo-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    For a broader understanding of pet vaccines in India, explore these resources:
                  </p>

                  <div className="bg-green-50 border-l-4 border-green-500 p-6 rounded-lg">
                    <ul className="list-disc pl-6 space-y-2">
                      <li>
                        <Link
                          to="/blog/vaccination-schedule-for-pets-in-india"
                          className="text-blue-600 hover:underline"
                        >
                          Vaccination Schedule for Pets in India
                        </Link>{" "}
                        - Complete guide for all pets
                      </li>
                      <li>
                        <Link
                          to="/blog/golden-retriever-vaccination-schedule-india"
                          className="text-blue-600 hover:underline"
                        >
                          Golden Retriever Vaccination Schedule
                        </Link>{" "}
                        - For dog owners
                      </li>
                      <li>
                        <Link
                          to="/blog/cats-diseases-and-symptoms"
                          className="text-blue-600 hover:underline"
                        >
                          Cats Diseases and Symptoms
                        </Link>{" "}
                        - Understanding what vaccines prevent
                      </li>
                    </ul>
                  </div>
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
                        q: "What is the correct cat vaccination schedule India?",
                        a: "The correct cat vaccination schedule India starts at 6 to 8 weeks with Feligen CRP and includes rabies at 3 to 4 months, followed by annual boosters.",
                      },
                      {
                        q: "Is rabies vaccine mandatory for cats?",
                        a: "Yes, the anti rabies vaccine for cats is mandatory in India to protect both pets and humans from this deadly disease.",
                      },
                      {
                        q: "Which is the most common cat vaccine name?",
                        a: "Feligen CRP is one of the most commonly used vaccines in India, providing protection against multiple viral diseases.",
                      },
                      {
                        q: "Can indoor cats skip vaccination?",
                        a: "No, indoor cats also need full vaccination as per cat vaccination schedule India. Even indoor cats can be exposed to diseases through various means.",
                      },
                      {
                        q: "Are Persian cats vaccinated differently?",
                        a: "No, the Persian cat vaccination schedule follows the same timeline as other breeds, though extra care should be taken due to their respiratory sensitivity.",
                      },
                      {
                        q: "How long does vaccine immunity last?",
                        a: "Most vaccines require yearly boosters to maintain protection. Some vaccines may provide longer immunity, but annual checkups are recommended.",
                      },
                      {
                        q: "What if my kitten misses a vaccine?",
                        a: "Consult a vet immediately to restart the kitten vaccine schedule safely. Don't attempt to catch up on your own.",
                      },
                      {
                        q: "Can I vaccinate my cat at home?",
                        a: "Vaccination should always be done by a licensed veterinarian to ensure proper administration, storage, and immediate care in case of reactions.",
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
                    Following the correct <strong>cat vaccination schedule India</strong> is essential for protecting your cat and your family from serious diseases. From kitten vaccines and Feligen CRP to rabies boosters, timely vaccination combined with proper nutrition, grooming and vet care ensures a healthy and happy life for your cat.
                  </p>

                  <div className="bg-teal-50 border-l-4 border-teal-500 p-6 rounded-lg mb-8">
                    <p className="text-lg font-semibold mb-4">
                      <strong>Key Takeaways:</strong>
                    </p>
                    <ul className="list-disc pl-6 space-y-2">
                      <li>Start kitten vaccinations at 6-8 weeks with Feligen CRP</li>
                      <li>Rabies vaccination is mandatory in India for all cats</li>
                      <li>Annual boosters are essential to maintain immunity</li>
                      <li>Even indoor cats need complete vaccination</li>
                      <li>Consult a vet immediately if vaccines are missed</li>
                      <li>Good nutrition supports better vaccine response</li>
                    </ul>
                  </div>

                  {/* CTA Box */}
                  <div className="bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl p-8 text-center shadow-2xl">
                    <h3 className="text-2xl md:text-3xl font-bold mb-4">
                      Trusted Pet Care Platform
                    </h3>
                    <p className="text-xl opacity-95 mb-6">
                      For reliable pet health information and veterinary services, visit SnoutIQ. We provide expert guidance and professional veterinary access for all your pet care needs.
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

export default CatVaccinationScheduleIndia;

