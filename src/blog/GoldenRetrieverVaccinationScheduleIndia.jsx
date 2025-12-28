import React, { useEffect, useState } from "react";
import Footer from "../components/Footer";
import Header from "../components/Header";
import { Helmet, HelmetProvider } from "react-helmet-async";
import { Link } from "react-router-dom";

const GoldenRetrieverVaccinationScheduleIndia = () => {
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
            Golden Retriever Vaccination Schedule India Complete Guide
          </title>
          <meta
            name="title"
            content="Golden Retriever Vaccination Schedule India Complete Guide"
          />
          <meta
            name="description"
            content="Complete Golden Retriever vaccination schedule for puppies, adults and seniors with rabies, deworming, price chart and FAQs for Indian pet parents."
          />
          <meta
            name="keywords"
            content="golden retriever vaccination schedule, puppy DP vaccine, dog vaccination schedule, dog rabies vaccine schedule, dog vaccination and deworming schedule, dog vaccines list, dog vaccination schedule India with price"
          />
          <meta name="author" content="SnoutIQ" />
          <meta name="robots" content="index, follow" />
          <meta
            property="og:title"
            content="Golden Retriever Vaccination Schedule India Complete Guide"
          />
          <meta
            property="og:description"
            content="Complete Golden Retriever vaccination schedule for puppies, adults and seniors with rabies, deworming, price chart and FAQs for Indian pet parents."
          />
          <meta property="og:type" content="article" />
          <meta
            property="og:image"
            content="https://snoutiq.com/images/vaccination-schedule.jpg"
          />
          <meta property="og:url" content="https://snoutiq.com/blog/golden-retriever-vaccination-schedule-india" />
          <link
            rel="canonical"
            href="https://snoutiq.com/blog/golden-retriever-vaccination-schedule-india"
          />

          {/* Twitter Card */}
          <meta property="twitter:card" content="summary_large_image" />
          <meta property="twitter:url" content="https://snoutiq.com/blog/golden-retriever-vaccination-schedule-india" />
          <meta
            property="twitter:title"
            content="Golden Retriever Vaccination Schedule India Complete Guide"
          />
          <meta
            property="twitter:description"
            content="Complete Golden Retriever vaccination schedule for puppies, adults and seniors with rabies, deworming, price chart and FAQs for Indian pet parents."
          />
          <meta
            property="twitter:image"
            content="https://snoutiq.com/images/vaccination-schedule.jpg"
          />

          {/* Schema.org Markup */}
          <script type="application/ld+json">
            {JSON.stringify({
              "@context": "https://schema.org",
              "@type": "Article",
              headline: "Golden Retriever Vaccination Schedule (Complete Guide for Puppies, Adults & Seniors)",
              description:
                "Complete guide on Golden Retriever vaccination schedule including puppy vaccines, rabies schedule, deworming timeline, and price chart for India.",
              image: "https://snoutiq.com/images/vaccination-schedule.jpg",
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
              datePublished: "2024-12-22",
              dateModified: "2024-12-22",
              mainEntityOfPage: {
                "@type": "WebPage",
                "@id": "https://snoutiq.com/blog/golden-retriever-vaccination-schedule-india",
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
                  name: "When should Golden Retriever vaccination start?",
                  acceptedAnswer: {
                    "@type": "Answer",
                    text: "Golden Retriever vaccination should start at 6 weeks of age with the puppy DP vaccine.",
                  },
                },
                {
                  "@type": "Question",
                  name: "Is rabies vaccine compulsory in India?",
                  acceptedAnswer: {
                    "@type": "Answer",
                    text: "Yes, the rabies vaccine is legally mandatory in India for all dogs.",
                  },
                },
                {
                  "@type": "Question",
                  name: "How often are boosters needed?",
                  acceptedAnswer: {
                    "@type": "Answer",
                    text: "Booster vaccinations are needed once every year to maintain immunity.",
                  },
                },
                {
                  "@type": "Question",
                  name: "Should deworming be done with vaccines?",
                  acceptedAnswer: {
                    "@type": "Answer",
                    text: "Deworm first, then vaccinate after 7 days as part of the proper vaccination schedule.",
                  },
                },
              ],
            })}
          </script>
        </Helmet>

        <div className="font-sans text-gray-800 bg-gradient-to-br from-indigo-50 to-purple-50 min-h-screen mt-20">
          <Header />
          
          {/* Breadcrumb */}
          <nav className="max-w-7xl mx-auto px-8 py-4 text-sm" aria-label="Breadcrumb">
            <ol className="flex items-center space-x-2">
              <li>
                <Link to="/" className="text-indigo-600 hover:underline">
                  Home
                </Link>
              </li>
              <li className="text-gray-500">»</li>
              <li>
                <Link to="/blog" className="text-indigo-600 hover:underline">
                  Blog
                </Link>
              </li>
              <li className="text-gray-500">»</li>
              <li className="text-gray-700">Golden Retriever Vaccination Schedule</li>
            </ol>
          </nav>

          <main className="max-w-4xl mx-auto px-8 py-8">
            <article
              className="bg-white rounded-xl shadow-lg overflow-hidden"
              itemScope
              itemType="http://schema.org/Article"
            >
              {/* Header */}
              <header className="bg-gradient-to-r from-indigo-600 to-purple-600 text-white relative overflow-hidden py-16 px-8 text-center">
                <div className="absolute text-9xl opacity-10 -top-10 -right-10 transform -rotate-12">
                  💉
                </div>
                <div className="absolute text-7xl opacity-10 -bottom-10 -left-10 transform rotate-12">
                  🐕
                </div>

                <div className="relative z-10">
                  <h1
                    className="text-3xl md:text-4xl lg:text-5xl font-black mb-6 drop-shadow-lg"
                    itemProp="headline"
                  >
                    Golden Retriever Vaccination Schedule (Complete Guide for Puppies, Adults & Seniors)
                  </h1>
                  <p className="text-xl md:text-2xl opacity-95 font-light mb-8 leading-relaxed">
                    Complete Golden Retriever vaccination schedule for puppies, adults and seniors with rabies, deworming, price chart and FAQs
                  </p>
                </div>
              </header>

              {/* Main Content */}
              <section className="px-6 md:px-10 py-8" itemProp="articleBody">
                {/* Introduction */}
                <div className="bg-gradient-to-r from-indigo-100 to-purple-100 border-l-4 border-indigo-500 p-6 rounded-lg mb-8">
                  <p className="text-lg font-medium">
                    The <strong>Golden Retriever vaccination schedule</strong> is the most important health routine every pet parent must follow to protect their dog from deadly diseases like distemper, parvovirus, hepatitis, and rabies. Golden Retrievers should start vaccination at 6 weeks of age, complete core vaccines by 14 weeks, and continue yearly boosters for lifelong protection.
                  </p>
                </div>

                <p className="text-lg mb-8 leading-relaxed">
                  This guide explains the <strong>Golden Retriever puppy vaccination</strong>, <strong>dog vaccination schedule India</strong>, <strong>dog rabies vaccine schedule</strong>, <strong>dog vaccination and deworming schedule</strong>, prices, charts, and FAQs in simple, vet-approved language.
                </p>

                {/* Why Vaccination Is Important */}
                <section id="why-important" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-6 pb-4 border-b-2 border-indigo-400 relative">
                    <span className="text-2xl mr-2">💉</span> Why Vaccination Is Important for Golden Retrievers
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    Golden Retrievers are active, social, and friendly dogs. Because they interact more with people, dogs, parks, and open environments, they are at higher risk of infections.
                  </p>

                  <div className="bg-green-50 border-l-4 border-green-500 p-6 rounded-lg mb-6">
                    <p className="text-lg font-semibold mb-4">
                      Following a proper <strong>dog vaccination schedule</strong> helps to:
                    </p>
                    <ul className="list-disc pl-6 space-y-2">
                      <li>Prevent fatal viral and bacterial diseases</li>
                      <li>Build strong lifelong immunity</li>
                      <li>Reduce emergency medical costs</li>
                      <li>Keep your dog legally protected under Indian pet laws</li>
                    </ul>
                  </div>

                  <p className="text-lg mb-4">
                    Vaccines work best when immunity is strong. You can support your dog naturally by following this guide:
                  </p>
                  <Link
                    to="/blog/boost-your-dogs-immunity-naturally"
                    className="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-colors"
                  >
                    👉 Boost Your Dog's Immunity Naturally
                  </Link>
                </section>

                {/* Dog Vaccines List */}
                <section id="vaccines-list" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-6 pb-4 border-b-2 border-indigo-400 relative">
                    <span className="text-2xl mr-2">📋</span> Dog Vaccines List for Golden Retrievers
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
                  </h2>

                  <div className="overflow-x-auto rounded-xl shadow-lg mb-8">
                    <table className="min-w-full divide-y divide-gray-200">
                      <thead className="bg-gradient-to-r from-indigo-600 to-purple-600">
                        <tr>
                          <th className="px-6 py-4 text-left text-sm font-semibold text-white">
                            Vaccine
                          </th>
                          <th className="px-6 py-4 text-left text-sm font-semibold text-white">
                            Disease Protected
                          </th>
                          <th className="px-6 py-4 text-left text-sm font-semibold text-white">
                            Category
                          </th>
                        </tr>
                      </thead>
                      <tbody className="bg-white divide-y divide-gray-200">
                        {[
                          ["Puppy DP Vaccine", "Distemper, Parvovirus", "Core"],
                          ["DHPPi", "Distemper, Hepatitis, Parvo, Parainfluenza", "Core"],
                          ["Leptospirosis", "Bacterial infection", "Core"],
                          ["Rabies", "Fatal viral disease", "Mandatory"],
                          ["Kennel Cough", "Respiratory infection", "Optional"],
                        ].map((row, index) => (
                          <tr
                            key={index}
                            className={
                              index % 2 === 0
                                ? "bg-indigo-50 hover:bg-indigo-100"
                                : "bg-white hover:bg-indigo-100"
                            }
                          >
                            <td className="px-6 py-4 font-semibold text-gray-900">{row[0]}</td>
                            <td className="px-6 py-4 text-gray-700">{row[1]}</td>
                            <td className="px-6 py-4 text-gray-700">{row[2]}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>

                  <p className="text-lg">
                    This <strong>dog vaccines list</strong> applies to all Golden Retrievers in India.
                  </p>
                </section>

                {/* Complete Chart */}
                <section id="complete-chart" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-6 pb-4 border-b-2 border-indigo-400 relative">
                    <span className="text-2xl mr-2">📅</span> Golden Retriever Vaccination Schedule Chart (All Ages)
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
                  </h2>

                  <h3 className="text-2xl font-bold text-gray-800 mb-4">Complete Golden Retriever Vaccine Chart</h3>

                  <div className="overflow-x-auto rounded-xl shadow-lg mb-8">
                    <table className="min-w-full divide-y divide-gray-200">
                      <thead className="bg-gradient-to-r from-indigo-600 to-purple-600">
                        <tr>
                          <th className="px-6 py-4 text-left text-sm font-semibold text-white">
                            Age
                          </th>
                          <th className="px-6 py-4 text-left text-sm font-semibold text-white">
                            Vaccine
                          </th>
                          <th className="px-6 py-4 text-left text-sm font-semibold text-white">
                            Purpose
                          </th>
                        </tr>
                      </thead>
                      <tbody className="bg-white divide-y divide-gray-200">
                        {[
                          ["6 Weeks", "Puppy DP Vaccine", "First immunity"],
                          ["8 Weeks", "DHPPi", "Core protection"],
                          ["10 Weeks", "DHPPi + Lepto", "Stronger immunity"],
                          ["12 Weeks", "DHPPi Booster", "Long-term defense"],
                          ["12–14 Weeks", "Rabies", "Mandatory"],
                          ["6 Months", "Optional boosters", "Lifestyle-based"],
                          ["1 Year", "Annual Booster", "Immunity maintenance"],
                          ["Every Year", "Rabies + Core", "Lifelong protection"],
                        ].map((row, index) => (
                          <tr
                            key={index}
                            className={
                              index % 2 === 0
                                ? "bg-indigo-50 hover:bg-indigo-100"
                                : "bg-white hover:bg-indigo-100"
                            }
                          >
                            <td className="px-6 py-4 font-semibold text-gray-900">{row[0]}</td>
                            <td className="px-6 py-4 text-gray-700">{row[1]}</td>
                            <td className="px-6 py-4 text-gray-700">{row[2]}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>

                  <p className="text-lg">
                    This <strong>Golden Retriever vaccine chart</strong> ensures no dose is missed.
                  </p>
                </section>

                {/* Puppy Vaccination */}
                <section id="puppy-vaccination" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-6 pb-4 border-b-2 border-indigo-400 relative">
                    <span className="text-2xl mr-2">🐶</span> Golden Retriever Puppy Vaccination (0–4 Months)
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    The <strong>Golden Retriever puppy vaccination</strong> phase is the most critical.
                  </p>

                  <h3 className="text-2xl font-bold text-gray-800 mb-4">Puppy Vaccines Explained</h3>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div className="bg-white border-2 border-gray-200 rounded-lg p-6 hover:border-indigo-500 hover:shadow-lg transition-all">
                      <h4 className="text-xl font-bold text-indigo-600 mb-3">Puppy DP Vaccine</h4>
                      <p className="text-gray-700">
                        Protects against distemper and parvovirus
                      </p>
                    </div>

                    <div className="bg-white border-2 border-gray-200 rounded-lg p-6 hover:border-indigo-500 hover:shadow-lg transition-all">
                      <h4 className="text-xl font-bold text-indigo-600 mb-3">DHPPi</h4>
                      <p className="text-gray-700">
                        Covers multiple deadly viral infections
                      </p>
                    </div>

                    <div className="bg-white border-2 border-gray-200 rounded-lg p-6 hover:border-indigo-500 hover:shadow-lg transition-all">
                      <h4 className="text-xl font-bold text-indigo-600 mb-3">Leptospirosis</h4>
                      <p className="text-gray-700">
                        Prevents bacterial infections spread by water and urine
                      </p>
                    </div>

                    <div className="bg-white border-2 border-gray-200 rounded-lg p-6 hover:border-indigo-500 hover:shadow-lg transition-all">
                      <h4 className="text-xl font-bold text-indigo-600 mb-3">Rabies</h4>
                      <p className="text-gray-700">
                        Mandatory by law
                      </p>
                    </div>
                  </div>

                  <div className="bg-yellow-50 border-l-4 border-yellow-500 p-6 rounded-lg mb-6">
                    <p className="text-lg font-semibold text-yellow-800">
                      <strong>⚠️ Important:</strong> Avoid public places until the puppy completes this stage.
                    </p>
                  </div>

                  <p className="text-lg mb-4">
                    A strong diet during this phase is explained here:
                  </p>
                  <Link
                    to="/blog/best-dog-food-for-golden-retrievers"
                    className="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-colors"
                  >
                    👉 Best Dog Food for Golden Retrievers
                  </Link>
                </section>

                {/* Mid-Age Vaccination */}
                <section id="mid-age" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-6 pb-4 border-b-2 border-indigo-400 relative">
                    <span className="text-2xl mr-2">🦴</span> Mid-Age Golden Retriever Vaccination (6 Months to 1 Year)
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    During this stage, immunity is strengthened and stabilized.
                  </p>

                  <h3 className="text-2xl font-bold text-gray-800 mb-4">Vaccines Required</h3>

                  <div className="bg-gray-50 border-l-4 border-indigo-500 p-6 rounded-lg mb-6">
                    <ul className="list-disc pl-6 space-y-2">
                      <li>DHPPi booster if advised by vet</li>
                      <li>Leptospirosis booster</li>
                      <li>Kennel Cough if the dog socializes often</li>
                    </ul>
                  </div>

                  <p className="text-lg mb-4">
                    Winter care is very important for mid-age dogs. Read:
                  </p>
                  <Link
                    to="/blog/dog-winter-care-guide"
                    className="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-colors"
                  >
                    👉 Dog Winter Care Guide
                  </Link>
                </section>

                {/* Adult Vaccination */}
                <section id="adult-vaccination" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-6 pb-4 border-b-2 border-indigo-400 relative">
                    <span className="text-2xl mr-2">🐕</span> Adult Golden Retriever Vaccination Schedule (1 Year and Above)
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    Adult dogs need yearly boosters to maintain protection.
                  </p>

                  <h3 className="text-2xl font-bold text-gray-800 mb-4">Adult Dog Vaccines</h3>

                  <div className="bg-green-50 border-l-4 border-green-500 p-6 rounded-lg mb-6">
                    <ul className="list-disc pl-6 space-y-2">
                      <li>DHPPi annual booster</li>
                      <li>Rabies booster</li>
                      <li>Leptospirosis</li>
                      <li>Kennel Cough if required</li>
                    </ul>
                  </div>

                  <p className="text-lg mb-4">
                    Seasonal nutrition helps adult dogs respond better to vaccines:
                  </p>
                  <Link
                    to="/blog/best-food-for-dogs-in-winter"
                    className="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-colors"
                  >
                    👉 Best Food for Dogs in Winter
                  </Link>
                </section>

                {/* Rabies Schedule */}
                <section id="rabies-schedule" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-6 pb-4 border-b-2 border-indigo-400 relative">
                    <span className="text-2xl mr-2">🏥</span> Dog Rabies Vaccine Schedule
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    Rabies is 100% fatal but fully preventable.
                  </p>

                  <h3 className="text-2xl font-bold text-gray-800 mb-4">Anti-Rabies Vaccine Schedule for Dogs</h3>

                  <div className="space-y-4 mb-6">
                    <div className="bg-gray-50 border-l-4 border-indigo-500 p-6 rounded-lg">
                      <div className="font-bold text-indigo-600 text-xl mb-2">First Dose</div>
                      <p className="text-gray-700">At 12–14 weeks</p>
                    </div>

                    <div className="bg-gray-50 border-l-4 border-indigo-500 p-6 rounded-lg">
                      <div className="font-bold text-indigo-600 text-xl mb-2">First Booster</div>
                      <p className="text-gray-700">After 1 year</p>
                    </div>

                    <div className="bg-gray-50 border-l-4 border-indigo-500 p-6 rounded-lg">
                      <div className="font-bold text-indigo-600 text-xl mb-2">Ongoing</div>
                      <p className="text-gray-700">Then every year for life</p>
                    </div>
                  </div>

                  <p className="text-lg">
                    The <strong>dog rabies vaccine schedule</strong> protects both pets and humans and is legally required in India.
                  </p>
                </section>

                {/* Deworming */}
                <section id="deworming" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-6 pb-4 border-b-2 border-indigo-400 relative">
                    <span className="text-2xl mr-2">🐛</span> Dog Vaccination and Deworming Schedule
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
                  </h2>

                  <h3 className="text-2xl font-bold text-gray-800 mb-4">Deworming Timeline</h3>

                  <div className="overflow-x-auto rounded-xl shadow-lg mb-6">
                    <table className="min-w-full divide-y divide-gray-200">
                      <thead className="bg-gradient-to-r from-indigo-600 to-purple-600">
                        <tr>
                          <th className="px-6 py-4 text-left text-sm font-semibold text-white">
                            Age
                          </th>
                          <th className="px-6 py-4 text-left text-sm font-semibold text-white">
                            Frequency
                          </th>
                        </tr>
                      </thead>
                      <tbody className="bg-white divide-y divide-gray-200">
                        {[
                          ["2 weeks to 3 months", "Every 15 days"],
                          ["3 to 6 months", "Monthly"],
                          ["Above 6 months", "Every 3 months"],
                        ].map((row, index) => (
                          <tr
                            key={index}
                            className={
                              index % 2 === 0
                                ? "bg-indigo-50 hover:bg-indigo-100"
                                : "bg-white hover:bg-indigo-100"
                            }
                          >
                            <td className="px-6 py-4 font-semibold text-gray-900">{row[0]}</td>
                            <td className="px-6 py-4 text-gray-700">{row[1]}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>

                  <div className="bg-yellow-50 border-l-4 border-yellow-500 p-6 rounded-lg">
                    <p className="text-lg font-semibold text-yellow-800">
                      <strong>Always deworm 7 days before vaccination</strong> as part of the complete <strong>dog vaccination and deworming schedule</strong>.
                    </p>
                  </div>
                </section>

                {/* Price Chart */}
                <section id="price-chart" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-6 pb-4 border-b-2 border-indigo-400 relative">
                    <span className="text-2xl mr-2">💰</span> Dog Vaccination Schedule India With Price
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
                  </h2>

                  <div className="overflow-x-auto rounded-xl shadow-lg mb-8">
                    <table className="min-w-full divide-y divide-gray-200">
                      <thead className="bg-gradient-to-r from-indigo-600 to-purple-600">
                        <tr>
                          <th className="px-6 py-4 text-left text-sm font-semibold text-white">
                            Vaccine
                          </th>
                          <th className="px-6 py-4 text-left text-sm font-semibold text-white">
                            Price Range (INR)
                          </th>
                        </tr>
                      </thead>
                      <tbody className="bg-white divide-y divide-gray-200">
                        {[
                          ["Puppy DP Vaccine", "₹500 – ₹800"],
                          ["DHPPi", "₹700 – ₹1,200"],
                          ["Leptospirosis", "₹600 – ₹1,000"],
                          ["Rabies", "₹300 – ₹600"],
                          ["Kennel Cough", "₹800 – ₹1,500"],
                        ].map((row, index) => (
                          <tr
                            key={index}
                            className={
                              index % 2 === 0
                                ? "bg-indigo-50 hover:bg-indigo-100"
                                : "bg-white hover:bg-indigo-100"
                            }
                          >
                            <td className="px-6 py-4 font-semibold text-gray-900">{row[0]}</td>
                            <td className="px-6 py-4 text-gray-700">{row[1]}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>

                  <p className="text-lg">
                    Prices may vary by city and clinic.
                  </p>
                </section>

                {/* Care Tips */}
                <section id="care-tips" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-6 pb-4 border-b-2 border-indigo-400 relative">
                    <span className="text-2xl mr-2">🩺</span> Pre and Post Vaccination Care
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
                  </h2>

                  <div className="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mb-6">
                    <h3 className="text-2xl font-bold text-blue-600 mb-4">Before Vaccination</h3>
                    <ul className="list-disc pl-6 space-y-2">
                      <li>Dog should be healthy</li>
                      <li>Deworming completed</li>
                      <li>Avoid stress</li>
                    </ul>
                  </div>

                  <div className="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mb-6">
                    <h3 className="text-2xl font-bold text-blue-600 mb-4">After Vaccination</h3>
                    <ul className="list-disc pl-6 space-y-2">
                      <li>Mild fever or sleepiness is normal</li>
                      <li>No bathing for 3 days</li>
                      <li>Contact vet if swelling or vomiting occurs</li>
                    </ul>
                  </div>

                  <p className="text-lg mb-4">
                    Emergency handling tips:
                  </p>
                  <Link
                    to="/blog/first-aid-tips-every-pet-parent-should-know"
                    className="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-colors"
                  >
                    👉 First Aid Tips Every Pet Parent Should Know
                  </Link>
                </section>

                {/* Nutrition Connection */}
                <section id="nutrition-connection" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-6 pb-4 border-b-2 border-indigo-400 relative">
                    <span className="text-2xl mr-2">🍖</span> Nutrition and Vaccination Connection
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
                  </h2>

                  <p className="text-lg mb-6 leading-relaxed">
                    Vaccines work best with proper nutrition. Avoid harmful foods listed here:
                  </p>

                  <Link
                    to="/blog/foods-golden-retrievers-should-never-eat"
                    className="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-colors"
                  >
                    👉 Foods Golden Retrievers Should Never Eat
                  </Link>
                </section>

                {/* FAQ Section */}
                <section id="faqs" className="scroll-mt-20 mb-12">
                  <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-6 pb-4 border-b-2 border-indigo-400 relative">
                    <span className="text-2xl mr-2">❓</span> Frequently Asked Questions
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
                  </h2>

                  <div className="space-y-4">
                    {[
                      {
                        q: "When should Golden Retriever vaccination start?",
                        a: "At 6 weeks of age.",
                      },
                      {
                        q: "Is rabies vaccine compulsory in India?",
                        a: "Yes, legally mandatory.",
                      },
                      {
                        q: "Can vaccination be delayed?",
                        a: "Delay increases disease risk.",
                      },
                      {
                        q: "Are vaccines safe for Golden Retrievers?",
                        a: "Yes, when given by certified vets.",
                      },
                      {
                        q: "How often are boosters needed?",
                        a: "Once every year.",
                      },
                      {
                        q: "Can adult dogs skip vaccines?",
                        a: "No, yearly boosters are essential.",
                      },
                      {
                        q: "Should deworming be done with vaccines?",
                        a: "Deworm first, vaccinate after 7 days.",
                      },
                      {
                        q: "Does food affect vaccine effectiveness?",
                        a: "Yes, good nutrition improves immunity.",
                      },
                    ].map((faq, index) => (
                      <div
                        key={index}
                        className="bg-gray-50 border-l-4 border-indigo-500 p-6 rounded-lg hover:bg-gray-100 transition-all"
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
                  <h2 className="text-3xl md:text-4xl font-bold text-indigo-600 mb-6 pb-4 border-b-2 border-indigo-400 relative">
                    <span className="text-2xl mr-2">✅</span> Final Conclusion
                    <div className="absolute bottom-0 left-0 w-24 h-1 bg-purple-500"></div>
                  </h2>

                  <p className="text-lg leading-relaxed mb-8">
                    Following a complete <strong>Golden Retriever vaccination schedule</strong> from puppyhood to adulthood is the best way to ensure a long, healthy, and disease-free life. Vaccination, deworming, proper diet, and seasonal care together form the foundation of responsible pet parenting.
                  </p>

                  {/* CTA Box */}
                  <div className="bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl p-8 text-center shadow-2xl">
                    <h3 className="text-2xl md:text-3xl font-bold mb-4">
                      Need Expert Pet Care Guidance?
                    </h3>
                    <p className="text-xl opacity-95 mb-6">
                      For trusted pet care resources and vet support, visit SnoutIQ
                    </p>
                    <Link
                      to="/"
                      className="inline-block bg-white text-indigo-600 px-8 py-3 rounded-lg font-bold hover:bg-gray-100 transition-colors"
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
            className={`fixed bottom-8 right-8 bg-gradient-to-r from-indigo-600 to-purple-600 text-white w-14 h-14 rounded-full flex items-center justify-center shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 ${
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

export default GoldenRetrieverVaccinationScheduleIndia;

