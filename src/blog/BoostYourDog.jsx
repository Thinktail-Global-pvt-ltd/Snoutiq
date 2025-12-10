import React from "react";
import { Helmet, HelmetProvider } from "react-helmet-async";
import Footer from "../components/Footer";
import Header from "../components/Header";
import img5 from "../assets/images/how_to_boost.jpeg";

const seo = {
  title: "How to Boost Your Dog's Immunity Naturally | Holistic Pet Health Guide",
  description:
    "Boost your dog's immunity naturally with balanced nutrition, supplements, exercise, and simple hygiene habits that keep infections away.",
  keywords:
    "boost dog immunity naturally, dog immune system, dog supplements, dog gut health, dog exercise, dog immunity diet",
  url: "https://snoutiq.com/blog/boost-your-dogs-immunity-naturally",
  image: "https://snoutiq.com/images/boost-dog-immunity.jpg",
};

const structuredData = {
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  headline: seo.title,
  description: seo.description,
  image: seo.image,
  author: {
    "@type": "Organization",
    name: "SnoutIQ",
  },
  publisher: {
    "@type": "Organization",
    name: "SnoutIQ",
  },
  mainEntityOfPage: {
    "@type": "WebPage",
    "@id": seo.url,
  },
};

const DogImmunityBlog = () => {
  return (
    <HelmetProvider>
      <Helmet>
        <title>{seo.title}</title>
        <meta name="description" content={seo.description} />
        <meta name="keywords" content={seo.keywords} />
        <link rel="canonical" href={seo.url} />

        <meta property="og:title" content={seo.title} />
        <meta property="og:description" content={seo.description} />
        <meta property="og:type" content="article" />
        <meta property="og:url" content={seo.url} />
        <meta property="og:image" content={seo.image} />

        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content={seo.title} />
        <meta name="twitter:description" content={seo.description} />
        <meta name="twitter:image" content={seo.image} />

        <script type="application/ld+json">
          {JSON.stringify(structuredData)}
        </script>
      </Helmet>
      <Header />
      <div className="min-h-screen bg-gray-50 py-8 px-4 mt-20">
        <div className="max-w-4xl mx-auto bg-white rounded-lg shadow-sm">
          {/* Blog Header */}
          <header className="bg-white border-b border-gray-200 p-6 text-center">
            <h1 className="text-4xl font-bold text-gray-800 mb-4">
              How to Boost Your Dog's Immunity Naturally
            </h1>
            <p className="text-lg text-gray-600 max-w-2xl mx-auto">
              Complete guide to natural canine health through diet, exercise,
              and simple care practices
            </p>
            <div className="mt-4 text-sm text-gray-500">
              Published on {new Date().toLocaleDateString()} • 10 min read
            </div>
          </header>
          <section>
            <img src={img5} alt="image" />
          </section>
          {/* Blog Content */}
          <main className="p-6 md:p-8">
            <article className="prose prose-lg max-w-none">
              {/* Introduction */}
              <section className="mb-8">
                <p className="text-xl text-gray-700 leading-relaxed mb-4">
                  If you want to boost your dog's immunity naturally, you're
                  already taking the best step toward long-term canine health.
                </p>
                <p className="text-gray-600 mb-4">
                  In simple terms, the stronger your dog's immune system, the
                  fewer infections, allergies, or fatigue issues they'll face.
                  You can boost your dog's immune system at home easily by
                  improving diet, reducing stress, and maintaining an active
                  lifestyle.
                </p>
              </section>

              {/* Why Immunity Matters */}
              <section className="mb-8">
                <h2 className="text-2xl font-bold text-gray-800 mb-4">
                  Why Immunity Matters for Dogs
                </h2>
                <p className="text-gray-600 mb-4">
                  Your dog's immune system is its natural armor, protecting
                  against viruses, bacteria, and toxins. When you boost your
                  dog's immunity naturally, you're strengthening those defenses.
                </p>

                <div className="bg-gray-50 p-4 rounded-lg mb-4">
                  <h3 className="font-semibold text-gray-800 mb-3">
                    Key Immune System Components:
                  </h3>
                  <ul className="space-y-2">
                    <li className="flex items-start">
                      <span className="text-green-500 mr-2">•</span>
                      <span>
                        <strong>Gut Health:</strong> Houses nearly 80% of immune
                        cells
                      </span>
                    </li>
                    <li className="flex items-start">
                      <span className="text-green-500 mr-2">•</span>
                      <span>
                        <strong>Nutrition:</strong> Feeds and repairs immune
                        tissue
                      </span>
                    </li>
                    <li className="flex items-start">
                      <span className="text-green-500 mr-2">•</span>
                      <span>
                        <strong>Exercise:</strong> Reduces inflammation and
                        improves circulation
                      </span>
                    </li>
                  </ul>
                </div>
              </section>

              {/* Foods Section */}
              <section className="mb-8">
                <h2 className="text-2xl font-bold text-gray-800 mb-4">
                  Foods That Boost Dog Immunity
                </h2>
                <p className="text-gray-600 mb-4">
                  Diet is the most powerful tool to boost your dog's immunity
                  naturally. A balanced diet rich in antioxidants, vitamins, and
                  lean proteins helps immune cells fight off disease.
                </p>

                <div className="grid md:grid-cols-2 gap-4 mb-4">
                  <div className="bg-green-50 p-4 rounded-lg">
                    <h3 className="font-semibold text-green-800 mb-2">
                      Best Immune-Boosting Foods:
                    </h3>
                    <ul className="space-y-1 text-sm">
                      <li>• Lean proteins (chicken, turkey, fish)</li>
                      <li>• Fresh vegetables (broccoli, spinach, carrots)</li>
                      <li>• Fruits (blueberries, pumpkin, apples)</li>
                      <li>• Omega-3 fatty acids (fish oil)</li>
                    </ul>
                  </div>

                  <div className="bg-yellow-50 p-4 rounded-lg">
                    <h3 className="font-semibold text-yellow-800 mb-2">
                      Foods to Avoid:
                    </h3>
                    <ul className="space-y-1 text-sm">
                      <li>• Excess carbs and fillers</li>
                      <li>• Artificial preservatives</li>
                      <li>• Processed treats</li>
                      <li>• High-sugar foods</li>
                    </ul>
                  </div>
                </div>
              </section>

              {/* Gut Health */}
              <section className="mb-8">
                <h2 className="text-2xl font-bold text-gray-800 mb-4">
                  Gut Health: The Core of Strong Immunity
                </h2>
                <p className="text-xl text-green-700 font-semibold mb-4">
                  A healthy gut = a healthy immune system
                </p>

                <div className="bg-blue-50 p-4 rounded-lg">
                  <h3 className="font-semibold text-blue-800 mb-3">
                    Gut-Friendly Tips:
                  </h3>
                  <ul className="space-y-2">
                    <li className="flex items-start">
                      <span className="text-blue-500 mr-2">•</span>
                      <span>
                        Add probiotics and prebiotics (pumpkin, banana, oats)
                      </span>
                    </li>
                    <li className="flex items-start">
                      <span className="text-blue-500 mr-2">•</span>
                      <span>
                        Include fermented foods like unsweetened yogurt
                      </span>
                    </li>
                    <li className="flex items-start">
                      <span className="text-blue-500 mr-2">•</span>
                      <span>Avoid overuse of antibiotics</span>
                    </li>
                    <li className="flex items-start">
                      <span className="text-blue-500 mr-2">•</span>
                      <span>Maintain consistent feeding times</span>
                    </li>
                  </ul>
                </div>
              </section>

              {/* Exercise */}
              <section className="mb-8">
                <h2 className="text-2xl font-bold text-gray-800 mb-4">
                  Exercise & Playtime: The Natural Shield
                </h2>
                <p className="text-gray-600 mb-4">
                  Regular movement helps boost your dog's immunity naturally by
                  improving circulation and reducing inflammation.
                </p>

                <div className="bg-purple-50 p-4 rounded-lg">
                  <h3 className="font-semibold text-purple-800 mb-3">
                    Recommended Activity by Age:
                  </h3>
                  <div className="space-y-2">
                    <div className="flex justify-between items-center border-b border-purple-100 pb-2">
                      <span className="font-medium">Puppies</span>
                      <span className="text-purple-600">
                        20-30 mins of play daily
                      </span>
                    </div>
                    <div className="flex justify-between items-center border-b border-purple-100 pb-2">
                      <span className="font-medium">Adult Dogs</span>
                      <span className="text-purple-600">
                        45-60 mins of walks + games
                      </span>
                    </div>
                    <div className="flex justify-between items-center">
                      <span className="font-medium">Senior Dogs</span>
                      <span className="text-purple-600">
                        Gentle exercise, 20-30 mins
                      </span>
                    </div>
                  </div>
                </div>
              </section>

              {/* Supplements */}
              <section className="mb-8">
                <h2 className="text-2xl font-bold text-gray-800 mb-4">
                  Natural Supplements for Immunity
                </h2>
                <p className="text-gray-600 mb-4">
                  Supplements can bridge nutritional gaps and help boost your
                  dog's immunity naturally.
                </p>

                <div className="grid gap-3">
                  <div className="flex items-start p-3 bg-orange-50 rounded-lg">
                    <div className="bg-orange-100 p-2 rounded mr-3">
                      <span className="text-orange-600 font-semibold">1</span>
                    </div>
                    <div>
                      <h4 className="font-semibold text-orange-800">
                        Probiotics
                      </h4>
                      <p className="text-sm text-orange-700">
                        Improves gut bacteria balance
                      </p>
                    </div>
                  </div>

                  <div className="flex items-start p-3 bg-blue-50 rounded-lg">
                    <div className="bg-blue-100 p-2 rounded mr-3">
                      <span className="text-blue-600 font-semibold">2</span>
                    </div>
                    <div>
                      <h4 className="font-semibold text-blue-800">
                        Fish Oil (Omega-3)
                      </h4>
                      <p className="text-sm text-blue-700">
                        Reduces inflammation, enhances coat
                      </p>
                    </div>
                  </div>

                  <div className="flex items-start p-3 bg-green-50 rounded-lg">
                    <div className="bg-green-100 p-2 rounded mr-3">
                      <span className="text-green-600 font-semibold">3</span>
                    </div>
                    <div>
                      <h4 className="font-semibold text-green-800">Turmeric</h4>
                      <p className="text-sm text-green-700">
                        Anti-inflammatory & antioxidant
                      </p>
                    </div>
                  </div>
                </div>
              </section>

              {/* Quick Routine */}
              <section className="mb-8">
                <h2 className="text-2xl font-bold text-gray-800 mb-4">
                  Simple Daily Immune-Boosting Routine
                </h2>

                <div className="bg-gradient-to-r from-green-500 to-blue-500 text-white p-6 rounded-lg">
                  <h3 className="text-xl font-bold mb-4 text-center">
                    Daily Checklist
                  </h3>
                  <div className="grid md:grid-cols-2 gap-4">
                    <div>
                      <h4 className="font-semibold mb-2">Morning</h4>
                      <ul className="space-y-1 text-sm">
                        <li>✓ Balanced breakfast with protein</li>
                        <li>✓ Fresh water change</li>
                        <li>✓ 15-30 min morning walk</li>
                      </ul>
                    </div>
                    <div>
                      <h4 className="font-semibold mb-2">Evening</h4>
                      <ul className="space-y-1 text-sm">
                        <li>✓ Healthy dinner with veggies</li>
                        <li>✓ Playtime and bonding</li>
                        <li>✓ Quiet rest time</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </section>

              {/* FAQ */}
              <section className="mb-8">
                <h2 className="text-2xl font-bold text-gray-800 mb-4">
                  Frequently Asked Questions
                </h2>

                <div className="space-y-4">
                  <div className="border-l-4 border-green-400 pl-4">
                    <h3 className="font-semibold text-gray-800 mb-1">
                      What are the best natural immune boosters for dogs?
                    </h3>
                    <p className="text-gray-600 text-sm">
                      Probiotics, fish oil, turmeric, spirulina, and vitamins C
                      & E are proven dog immune system boosters.
                    </p>
                  </div>

                  <div className="border-l-4 border-green-400 pl-4">
                    <h3 className="font-semibold text-gray-800 mb-1">
                      Can I boost my dog's immune system at home?
                    </h3>
                    <p className="text-gray-600 text-sm">
                      Absolutely! Through daily care, hygiene, diet, and natural
                      ingredients.
                    </p>
                  </div>

                  <div className="border-l-4 border-green-400 pl-4">
                    <h3 className="font-semibold text-gray-800 mb-1">
                      How often should I give supplements?
                    </h3>
                    <p className="text-gray-600 text-sm">
                      2–3 times a week is enough for maintenance (as advised by
                      your vet).
                    </p>
                  </div>
                </div>
              </section>

              {/* Conclusion */}
              <section className="bg-gray-50 p-6 rounded-lg">
                <h2 className="text-2xl font-bold text-gray-800 mb-4">
                  Final Thoughts
                </h2>
                <p className="text-gray-600 mb-4">
                  When you boost your dog's immunity naturally, you give your
                  pet the best defense against illness and fatigue. The secret
                  lies in consistency, not complexity.
                </p>
                <div className="bg-white p-4 rounded border-l-4 border-green-500">
                  <p className="font-semibold text-green-700 text-center">
                    Feed well, play daily, rest peacefully, and love
                    unconditionally. That's the real formula for lifelong canine
                    health.
                  </p>
                </div>
              </section>
            </article>
          </main>
        </div>
      </div>
      <Footer />
    </HelmetProvider>
  );
};

export default DogImmunityBlog;
