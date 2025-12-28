import React from "react";
import { Helmet, HelmetProvider } from "react-helmet-async";
import { Link } from "react-router-dom";
import Footer from "../components/Footer";
import Header from "../components/Header";

const seo = {
  title: "Top Friendly Dog Breeds in India for Home and Family",
  description:
    "Discover the top friendly dog breeds in India for home and family. Compare dog breeds in India with price, temperament, care tips, and expert guidance for choosing the best dog.",
  keywords:
    "top friendly dog breeds in India, best dog for home in India, dog breeds in India with price, which dog is best for home, best family dog in India, most friendly dog breeds in India, cute dog breeds in India",
  url: "https://snoutiq.com/blog/top-friendly-dog-breeds-in-india",
  image: "https://snoutiq.com/images/friendly-dog-breeds-india.jpg",
};

const structuredData = {
  "@context": "https://schema.org",
  "@type": "Article",
  headline: seo.title,
  description:
    "Complete guide on the top friendly dog breeds in India including Golden Retriever, Labrador, Beagle, Pug, Indian Pariah Dog and more with price comparisons.",
  image: seo.image,
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
    "@id": seo.url,
  },
};

const faqStructuredData = {
  "@context": "https://schema.org",
  "@type": "FAQPage",
  mainEntity: [
    {
      "@type": "Question",
      name: "Which is the best family dog in India?",
      acceptedAnswer: {
        "@type": "Answer",
        text: "Golden Retriever and Labrador Retriever are considered the best family dog in India due to their gentle nature.",
      },
    },
    {
      "@type": "Question",
      name: "Which dog is best for home and kids?",
      acceptedAnswer: {
        "@type": "Answer",
        text: "Labrador, Golden Retriever, and Pug are ideal for homes with children.",
      },
    },
    {
      "@type": "Question",
      name: "Are Indian dogs friendly?",
      acceptedAnswer: {
        "@type": "Answer",
        text: "Yes, Indian Pariah Dogs are among the most friendly dog breeds in India.",
      },
    },
    {
      "@type": "Question",
      name: "What is the best dog for apartment living in India?",
      acceptedAnswer: {
        "@type": "Answer",
        text: "Pug, Shih Tzu, and Beagle are excellent apartment friendly dogs.",
      },
    },
  ],
};

const TopFriendlyDogBreeds = () => {
  return (
    <HelmetProvider>
      <Helmet>
        <title>{seo.title}</title>
        <meta name="description" content={seo.description} />
        <meta name="keywords" content={seo.keywords} />
        <link rel="canonical" href={seo.url} />

        <meta property="og:type" content="article" />
        <meta property="og:url" content={seo.url} />
        <meta property="og:title" content={seo.title} />
        <meta property="og:description" content={seo.description} />
        <meta property="og:image" content={seo.image} />

        <meta property="twitter:card" content="summary_large_image" />
        <meta property="twitter:url" content={seo.url} />
        <meta property="twitter:title" content={seo.title} />
        <meta property="twitter:description" content={seo.description} />
        <meta property="twitter:image" content={seo.image} />

        <script type="application/ld+json">
          {JSON.stringify(structuredData)}
        </script>
        <script type="application/ld+json">
          {JSON.stringify(faqStructuredData)}
        </script>
      </Helmet>
      <Header />
      <div className="min-h-screen bg-gray-50">
        {/* Breadcrumb */}
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-4">
          <nav className="text-sm text-gray-600" aria-label="Breadcrumb">
            <Link to="/" className="hover:text-blue-600 transition-colors">
              Home
            </Link>
            <span className="mx-2">»</span>
            <Link to="/blog" className="hover:text-blue-600 transition-colors">
              Blog
            </Link>
            <span className="mx-2">»</span>
            <span className="text-gray-800">Top Friendly Dog Breeds in India</span>
          </nav>
        </div>

        <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
          <article className="bg-white rounded-xl shadow-lg p-6 sm:p-8 lg:p-12">
            <h1 className="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-6 leading-tight">
              Top Friendly Dog Breeds in India for Home and Family
            </h1>

            {/* Introduction */}
            <section className="mb-8">
              <div className="bg-gradient-to-r from-yellow-50 to-amber-50 border-l-4 border-amber-500 p-6 rounded-lg mb-6">
                <p className="text-lg text-gray-800 leading-relaxed">
                  Choosing the <strong>top friendly dog breeds in India</strong> is
                  one of the most important decisions for a family or first-time pet
                  parent. The <strong>best dog for home in India</strong> is one that
                  is friendly, adaptable to Indian weather, safe for children, easy
                  to train, and comfortable in apartments or independent houses.
                </p>
              </div>

              <p className="text-lg text-gray-700 leading-relaxed mb-4">
                India has a wide dog variety, from popular foreign breeds to hardy
                Indian dogs. This guide explains the{" "}
                <strong>best dog breeds in India for home</strong>, their
                temperament, price range, care needs, and suitability for families
                so you can make the right choice with confidence.
              </p>
            </section>

            {/* Why Choosing a Friendly Dog Breed Matters */}
            <section id="why-important" className="mb-10">
              <h2 className="text-2xl sm:text-3xl font-bold text-gray-800 mb-4 border-l-4 border-blue-600 pl-4">
                🐕 Why Choosing a Friendly Dog Breed Matters in India
              </h2>
              <p className="text-lg text-gray-700 mb-4 leading-relaxed">
                A friendly dog adjusts easily to people, children, guests, and daily
                routines. In Indian households, dogs often live close to family
                members, elders, and kids. Selecting from{" "}
                <strong>most friendly dog breeds in India</strong> helps avoid
                behavior issues and ensures a stress-free bonding experience.
              </p>

              <div className="bg-green-50 border-l-4 border-green-500 p-6 rounded-lg mb-6">
                <h3 className="text-xl font-bold text-gray-800 mb-4">
                  Benefits of Choosing a Friendly Dog for Home
                </h3>
                <ul className="list-disc list-inside space-y-2 text-gray-700">
                  <li>Better bonding with family members</li>
                  <li>Safer environment for children</li>
                  <li>Easier training and socialization</li>
                  <li>Lower aggression and anxiety</li>
                  <li>Ideal for apartments and gated societies</li>
                </ul>
              </div>

              <p className="text-lg text-gray-700">
                When searching for <strong>which dog is best for home</strong>,
                temperament should matter more than looks.
              </p>
            </section>

            {/* Top Breeds */}
            <section id="top-breeds" className="mb-10">
              <h2 className="text-2xl sm:text-3xl font-bold text-gray-800 mb-6 border-l-4 border-blue-600 pl-4">
                ⭐ Top Friendly Dog Breeds in India
              </h2>
              <p className="text-lg text-gray-700 mb-6">
                Below is a carefully curated list of{" "}
                <strong>top dog breeds in India</strong> that are friendly,
                family-oriented, and suitable for Indian homes.
              </p>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                {/* Golden Retriever */}
                <div className="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-blue-500 hover:shadow-xl transition-all duration-300">
                  <div className="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mb-4">
                    1
                  </div>
                  <h3 className="text-2xl font-bold text-blue-600 mb-3">
                    Golden Retriever
                  </h3>
                  <p className="text-gray-700 mb-4">
                    Golden Retrievers are widely considered the{" "}
                    <strong>best family dog in India</strong> due to their gentle
                    and loving nature.
                  </p>
                  <h4 className="text-lg font-semibold text-gray-800 mb-2">
                    Why they are ideal
                  </h4>
                  <ul className="list-disc list-inside space-y-1 text-gray-700 mb-4">
                    <li>Extremely friendly and social</li>
                    <li>Great with children and elders</li>
                    <li>Easy to train and obedient</li>
                  </ul>
                  <div className="bg-yellow-100 text-yellow-800 px-4 py-2 rounded-lg inline-block font-bold mb-4">
                    ₹25,000 – ₹45,000
                  </div>
                  <p className="text-gray-700 mb-3">
                    Golden Retrievers need proper vaccination and preventive care.
                  </p>
                  <div className="flex flex-wrap gap-2">
                    <Link
                      to="/blog/vaccination-schedule-for-pets-in-india"
                      className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium"
                    >
                      Vaccination Guide
                    </Link>
                  </div>
                </div>

                {/* Labrador Retriever */}
                <div className="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-blue-500 hover:shadow-xl transition-all duration-300">
                  <div className="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mb-4">
                    2
                  </div>
                  <h3 className="text-2xl font-bold text-blue-600 mb-3">
                    Labrador Retriever
                  </h3>
                  <p className="text-gray-700 mb-4">
                    Labradors are among the{" "}
                    <strong>most friendly dog breeds in India</strong> and are
                    perfect for both apartments and houses.
                  </p>
                  <h4 className="text-lg font-semibold text-gray-800 mb-2">
                    Key traits
                  </h4>
                  <ul className="list-disc list-inside space-y-1 text-gray-700 mb-4">
                    <li>Loyal and affectionate</li>
                    <li>Excellent with kids</li>
                    <li>Highly adaptable</li>
                  </ul>
                  <div className="bg-yellow-100 text-yellow-800 px-4 py-2 rounded-lg inline-block font-bold mb-4">
                    ₹20,000 – ₹40,000
                  </div>
                  <p className="text-gray-700 mb-3">
                    Labradors require balanced nutrition, especially in winter.
                  </p>
                  <Link
                    to="/blog/best-food-for-dogs-in-winter"
                    className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium inline-block"
                  >
                    Winter Nutrition
                  </Link>
                </div>

                {/* Beagle */}
                <div className="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-blue-500 hover:shadow-xl transition-all duration-300">
                  <div className="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mb-4">
                    3
                  </div>
                  <h3 className="text-2xl font-bold text-blue-600 mb-3">Beagle</h3>
                  <p className="text-gray-700 mb-4">
                    Beagles are playful, cheerful, and make excellent home-friendly
                    dogs.
                  </p>
                  <h4 className="text-lg font-semibold text-gray-800 mb-2">
                    Why Beagles are popular
                  </h4>
                  <ul className="list-disc list-inside space-y-1 text-gray-700 mb-4">
                    <li>Small to medium size</li>
                    <li>Friendly with families</li>
                    <li>Low aggression</li>
                  </ul>
                  <div className="bg-yellow-100 text-yellow-800 px-4 py-2 rounded-lg inline-block font-bold mb-4">
                    ₹30,000 – ₹50,000
                  </div>
                  <p className="text-gray-700 mb-3">
                    They require proper exercise and seasonal care.
                  </p>
                  <Link
                    to="/blog/dog-winter-care-guide"
                    className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium inline-block"
                  >
                    Winter Care Guide
                  </Link>
                </div>

                {/* Pug */}
                <div className="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-blue-500 hover:shadow-xl transition-all duration-300">
                  <div className="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mb-4">
                    4
                  </div>
                  <h3 className="text-2xl font-bold text-blue-600 mb-3">Pug</h3>
                  <p className="text-gray-700 mb-4">
                    Pugs are one of the <strong>cute dog breeds in India</strong>{" "}
                    and ideal for apartment living.
                  </p>
                  <h4 className="text-lg font-semibold text-gray-800 mb-2">
                    Best features
                  </h4>
                  <ul className="list-disc list-inside space-y-1 text-gray-700 mb-4">
                    <li>Loving and calm nature</li>
                    <li>Low exercise requirement</li>
                    <li>Strong family bonding</li>
                  </ul>
                  <div className="bg-yellow-100 text-yellow-800 px-4 py-2 rounded-lg inline-block font-bold mb-4">
                    ₹18,000 – ₹35,000
                  </div>
                  <p className="text-gray-700">
                    Pugs need careful health monitoring due to breathing sensitivity.
                  </p>
                </div>

                {/* Indian Pariah Dog */}
                <div className="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-blue-500 hover:shadow-xl transition-all duration-300">
                  <div className="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mb-4">
                    5
                  </div>
                  <h3 className="text-2xl font-bold text-blue-600 mb-3">
                    Indian Pariah Dog (Indie)
                  </h3>
                  <p className="text-gray-700 mb-4">
                    The Indian Pariah Dog is one of the{" "}
                    <strong>best dogs in India</strong> for families seeking low
                    maintenance and high intelligence.
                  </p>
                  <h4 className="text-lg font-semibold text-gray-800 mb-2">
                    Why choose an Indie
                  </h4>
                  <ul className="list-disc list-inside space-y-1 text-gray-700 mb-4">
                    <li>Naturally adapted to Indian climate</li>
                    <li>Extremely loyal and friendly</li>
                    <li>Low grooming and medical cost</li>
                  </ul>
                  <div className="bg-yellow-100 text-yellow-800 px-4 py-2 rounded-lg inline-block font-bold mb-4">
                    Free / Adoption Charges
                  </div>
                  <p className="text-gray-700 mb-3">
                    Indies respond well to basic first aid knowledge.
                  </p>
                  <Link
                    to="/blog/first-aid-tips-every-pet-parent-should-know"
                    className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium inline-block"
                  >
                    First Aid Tips
                  </Link>
                </div>

                {/* Shih Tzu */}
                <div className="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-blue-500 hover:shadow-xl transition-all duration-300">
                  <div className="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mb-4">
                    6
                  </div>
                  <h3 className="text-2xl font-bold text-blue-600 mb-3">Shih Tzu</h3>
                  <p className="text-gray-700 mb-4">
                    Shih Tzus are gentle lap dogs and popular among{" "}
                    <strong>family dog breeds in India</strong>.
                  </p>
                  <h4 className="text-lg font-semibold text-gray-800 mb-2">
                    Highlights
                  </h4>
                  <ul className="list-disc list-inside space-y-1 text-gray-700 mb-4">
                    <li>Calm and affectionate</li>
                    <li>Ideal for apartments</li>
                    <li>Good with kids</li>
                  </ul>
                  <div className="bg-yellow-100 text-yellow-800 px-4 py-2 rounded-lg inline-block font-bold">
                    ₹35,000 – ₹70,000
                  </div>
                </div>

                {/* Cocker Spaniel */}
                <div className="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-blue-500 hover:shadow-xl transition-all duration-300">
                  <div className="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mb-4">
                    7
                  </div>
                  <h3 className="text-2xl font-bold text-blue-600 mb-3">
                    Cocker Spaniel
                  </h3>
                  <p className="text-gray-700 mb-4">
                    Cocker Spaniels are loving and emotionally connected dogs.
                  </p>
                  <h4 className="text-lg font-semibold text-gray-800 mb-2">
                    Why families love them
                  </h4>
                  <ul className="list-disc list-inside space-y-1 text-gray-700 mb-4">
                    <li>Friendly temperament</li>
                    <li>Moderate activity needs</li>
                    <li>Excellent companion dogs</li>
                  </ul>
                  <div className="bg-yellow-100 text-yellow-800 px-4 py-2 rounded-lg inline-block font-bold">
                    ₹30,000 – ₹55,000
                  </div>
                </div>

                {/* German Shepherd */}
                <div className="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-blue-500 hover:shadow-xl transition-all duration-300">
                  <div className="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mb-4">
                    8
                  </div>
                  <h3 className="text-2xl font-bold text-blue-600 mb-3">
                    German Shepherd (Well Trained)
                  </h3>
                  <p className="text-gray-700 mb-4">
                    When properly trained, German Shepherds are loyal and protective
                    family dogs.
                  </p>
                  <h4 className="text-lg font-semibold text-gray-800 mb-2">
                    Why they work
                  </h4>
                  <ul className="list-disc list-inside space-y-1 text-gray-700 mb-4">
                    <li>Intelligent and disciplined</li>
                    <li>Protective yet affectionate</li>
                    <li>Great for active families</li>
                  </ul>
                  <div className="bg-yellow-100 text-yellow-800 px-4 py-2 rounded-lg inline-block font-bold">
                    ₹18,000 – ₹40,000
                  </div>
                </div>
              </div>
            </section>

            {/* Comparison Table */}
            <section id="comparison" className="mb-10">
              <h2 className="text-2xl sm:text-3xl font-bold text-gray-800 mb-6 border-l-4 border-blue-600 pl-4">
                📊 Comparison Table: Dog Breeds in India with Price
              </h2>
              <div className="overflow-x-auto">
                <table className="w-full border-collapse border border-gray-300 shadow-md">
                  <thead>
                    <tr className="bg-blue-600 text-white">
                      <th className="border border-gray-300 px-4 py-3 text-left">
                        Dog Breed
                      </th>
                      <th className="border border-gray-300 px-4 py-3 text-left">
                        Friendliness
                      </th>
                      <th className="border border-gray-300 px-4 py-3 text-left">
                        Family Suitable
                      </th>
                      <th className="border border-gray-300 px-4 py-3 text-left">
                        Price Range
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr className="bg-gray-50 hover:bg-gray-100">
                      <td className="border border-gray-300 px-4 py-3">
                        Golden Retriever
                      </td>
                      <td className="border border-gray-300 px-4 py-3">
                        Very High
                      </td>
                      <td className="border border-gray-300 px-4 py-3">Yes</td>
                      <td className="border border-gray-300 px-4 py-3">
                        ₹25k–45k
                      </td>
                    </tr>
                    <tr className="hover:bg-gray-100">
                      <td className="border border-gray-300 px-4 py-3">
                        Labrador Retriever
                      </td>
                      <td className="border border-gray-300 px-4 py-3">
                        Very High
                      </td>
                      <td className="border border-gray-300 px-4 py-3">Yes</td>
                      <td className="border border-gray-300 px-4 py-3">
                        ₹20k–40k
                      </td>
                    </tr>
                    <tr className="bg-gray-50 hover:bg-gray-100">
                      <td className="border border-gray-300 px-4 py-3">Beagle</td>
                      <td className="border border-gray-300 px-4 py-3">High</td>
                      <td className="border border-gray-300 px-4 py-3">Yes</td>
                      <td className="border border-gray-300 px-4 py-3">
                        ₹30k–50k
                      </td>
                    </tr>
                    <tr className="hover:bg-gray-100">
                      <td className="border border-gray-300 px-4 py-3">Pug</td>
                      <td className="border border-gray-300 px-4 py-3">High</td>
                      <td className="border border-gray-300 px-4 py-3">Yes</td>
                      <td className="border border-gray-300 px-4 py-3">
                        ₹18k–35k
                      </td>
                    </tr>
                    <tr className="bg-gray-50 hover:bg-gray-100">
                      <td className="border border-gray-300 px-4 py-3">
                        Indian Pariah
                      </td>
                      <td className="border border-gray-300 px-4 py-3">
                        Very High
                      </td>
                      <td className="border border-gray-300 px-4 py-3">Yes</td>
                      <td className="border border-gray-300 px-4 py-3">
                        Adoption
                      </td>
                    </tr>
                    <tr className="hover:bg-gray-100">
                      <td className="border border-gray-300 px-4 py-3">Shih Tzu</td>
                      <td className="border border-gray-300 px-4 py-3">High</td>
                      <td className="border border-gray-300 px-4 py-3">Yes</td>
                      <td className="border border-gray-300 px-4 py-3">
                        ₹35k–70k
                      </td>
                    </tr>
                    <tr className="bg-gray-50 hover:bg-gray-100">
                      <td className="border border-gray-300 px-4 py-3">
                        Cocker Spaniel
                      </td>
                      <td className="border border-gray-300 px-4 py-3">High</td>
                      <td className="border border-gray-300 px-4 py-3">Yes</td>
                      <td className="border border-gray-300 px-4 py-3">
                        ₹30k–55k
                      </td>
                    </tr>
                    <tr className="hover:bg-gray-100">
                      <td className="border border-gray-300 px-4 py-3">
                        German Shepherd
                      </td>
                      <td className="border border-gray-300 px-4 py-3">
                        Medium-High
                      </td>
                      <td className="border border-gray-300 px-4 py-3">Yes</td>
                      <td className="border border-gray-300 px-4 py-3">
                        ₹18k–40k
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </section>

            {/* Which Dog Is Best for Home */}
            <section id="best-choice" className="mb-10">
              <h2 className="text-2xl sm:text-3xl font-bold text-gray-800 mb-6 border-l-4 border-blue-600 pl-4">
                🏠 Which Dog Is Best for Home in India
              </h2>
              <p className="text-lg text-gray-700 mb-4">
                The answer depends on your lifestyle.
              </p>
              <div className="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mb-6">
                <ul className="list-disc list-inside space-y-2 text-gray-700">
                  <li>
                    <strong>For families with kids:</strong> Golden Retriever,
                    Labrador
                  </li>
                  <li>
                    <strong>For apartments:</strong> Pug, Shih Tzu, Beagle
                  </li>
                  <li>
                    <strong>For low maintenance:</strong> Indian Pariah Dog
                  </li>
                </ul>
              </div>
              <p className="text-lg text-gray-700 mb-4">
                If you are confused about <strong>which dog is best</strong>, online
                veterinary guidance can help.
              </p>
              <Link
                to="/blog/online-vet-consultation"
                className="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors inline-block font-medium"
              >
                👉 Online Vet Consultation
              </Link>
            </section>

            {/* Health and Safety Tips */}
            <section id="health-safety" className="mb-10">
              <h2 className="text-2xl sm:text-3xl font-bold text-gray-800 mb-6 border-l-4 border-blue-600 pl-4">
                🩺 Health and Safety Tips for Friendly Dogs
              </h2>
              <p className="text-lg text-gray-700 mb-4">
                Friendly dogs still need preventive care and awareness.
              </p>
              <div className="bg-yellow-50 border-l-4 border-yellow-500 p-6 rounded-lg mb-6">
                <p className="font-bold text-gray-800 mb-3">Essential Resources:</p>
                <ul className="list-disc list-inside space-y-2 text-gray-700">
                  <li>Follow proper vaccination schedule</li>
                  <li>Watch for common diseases like tick fever</li>
                  <li>Ensure proper nutrition and seasonal care</li>
                </ul>
              </div>
              <div className="flex flex-wrap gap-3">
                <Link
                  to="/blog/vaccination-schedule-for-pets-in-india"
                  className="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium"
                >
                  Vaccination Schedule
                </Link>
                <Link
                  to="/blog/symptoms-of-tick-fever-in-dogs"
                  className="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium"
                >
                  Tick Fever Symptoms
                </Link>
              </div>
            </section>

            {/* FAQs */}
            <section id="faqs" className="mb-10">
              <h2 className="text-2xl sm:text-3xl font-bold text-gray-800 mb-6 border-l-4 border-blue-600 pl-4">
                ❓ Frequently Asked Questions
              </h2>
              <div className="space-y-4">
                <div className="bg-gray-50 border-l-4 border-blue-500 p-6 rounded-lg hover:bg-gray-100 transition-colors">
                  <div className="font-bold text-gray-800 mb-2 text-lg">
                    1. Which is the best family dog in India?
                  </div>
                  <div className="text-gray-700 leading-relaxed">
                    Golden Retriever and Labrador Retriever are considered the best
                    family dog in India due to their gentle nature.
                  </div>
                </div>
                <div className="bg-gray-50 border-l-4 border-blue-500 p-6 rounded-lg hover:bg-gray-100 transition-colors">
                  <div className="font-bold text-gray-800 mb-2 text-lg">
                    2. Which dog is best for home and kids?
                  </div>
                  <div className="text-gray-700 leading-relaxed">
                    Labrador, Golden Retriever, and Pug are ideal for homes with
                    children.
                  </div>
                </div>
                <div className="bg-gray-50 border-l-4 border-blue-500 p-6 rounded-lg hover:bg-gray-100 transition-colors">
                  <div className="font-bold text-gray-800 mb-2 text-lg">
                    3. Are Indian dogs friendly?
                  </div>
                  <div className="text-gray-700 leading-relaxed">
                    Yes, Indian Pariah Dogs are among the most friendly dog breeds in
                    India.
                  </div>
                </div>
                <div className="bg-gray-50 border-l-4 border-blue-500 p-6 rounded-lg hover:bg-gray-100 transition-colors">
                  <div className="font-bold text-gray-800 mb-2 text-lg">
                    4. What is the best dog for apartment living in India?
                  </div>
                  <div className="text-gray-700 leading-relaxed">
                    Pug, Shih Tzu, and Beagle are excellent apartment-friendly dogs.
                  </div>
                </div>
                <div className="bg-gray-50 border-l-4 border-blue-500 p-6 rounded-lg hover:bg-gray-100 transition-colors">
                  <div className="font-bold text-gray-800 mb-2 text-lg">
                    5. Which dog breed is low maintenance?
                  </div>
                  <div className="text-gray-700 leading-relaxed">
                    Indian Pariah Dog and Pug require minimal grooming and care.
                  </div>
                </div>
                <div className="bg-gray-50 border-l-4 border-blue-500 p-6 rounded-lg hover:bg-gray-100 transition-colors">
                  <div className="font-bold text-gray-800 mb-2 text-lg">
                    6. Are expensive dog breeds more friendly?
                  </div>
                  <div className="text-gray-700 leading-relaxed">
                    No, friendliness depends on temperament and training, not price.
                  </div>
                </div>
                <div className="bg-gray-50 border-l-4 border-blue-500 p-6 rounded-lg hover:bg-gray-100 transition-colors">
                  <div className="font-bold text-gray-800 mb-2 text-lg">
                    7. Which dog variety in India is best for first-time owners?
                  </div>
                  <div className="text-gray-700 leading-relaxed">
                    Labrador, Golden Retriever, and Indie dogs are ideal for
                    beginners.
                  </div>
                </div>
                <div className="bg-gray-50 border-l-4 border-blue-500 p-6 rounded-lg hover:bg-gray-100 transition-colors">
                  <div className="font-bold text-gray-800 mb-2 text-lg">
                    8. How do I choose the right dog breed?
                  </div>
                  <div className="text-gray-700 leading-relaxed">
                    Consider space, time, budget, and family members before choosing.
                  </div>
                </div>
              </div>
            </section>

            {/* Conclusion */}
            <section id="conclusion" className="mb-10">
              <h2 className="text-2xl sm:text-3xl font-bold text-gray-800 mb-6 border-l-4 border-blue-600 pl-4">
                ✅ Conclusion
              </h2>
              <p className="text-lg text-gray-700 leading-relaxed mb-6">
                Choosing from the <strong>top friendly dog breeds in India</strong>{" "}
                ensures a happy, safe, and emotionally fulfilling pet parenting
                experience. Whether you prefer popular foreign breeds or native
                Indian dogs, the right choice depends on lifestyle, care ability, and
                family needs. With proper nutrition, healthcare, and love, any
                friendly dog can become the perfect companion for your home.
              </p>
            </section>

            {/* CTA Box */}
            <div className="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-8 rounded-xl text-center shadow-xl mb-8">
              <h3 className="text-2xl sm:text-3xl font-bold mb-4">
                Need Expert Pet Care Guidance?
              </h3>
              <p className="text-lg mb-6">For reliable pet care services and resources</p>
              <a
                href="https://snoutiq.com"
                className="bg-white text-blue-600 px-8 py-3 rounded-lg font-bold hover:shadow-lg transition-all inline-block hover:-translate-y-1"
                target="_blank"
                rel="noopener noreferrer"
              >
                Visit SnoutIQ
              </a>
            </div>
          </article>
        </main>
      </div>
      <Footer />
    </HelmetProvider>
  );
};

export default TopFriendlyDogBreeds;

