import React, { useEffect, useState } from "react";
import Footer from "../components/Footer";
import Header from "../components/Header";
import img8 from "../assets/images/what_should_your.jpeg";

const BestFoodForDogsInWinter = () => {
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
      {/* SEO Meta Tags */}
      <head>
        <title>
          Best Food for Dogs in Winter: Complete Seasonal Nutrition Guide 2025
        </title>
        <meta
          name="title"
          content="Best Food for Dogs in Winter: Complete Seasonal Nutrition Guide 2025"
        />
        <meta
          name="description"
          content="Complete guide on best food for dogs in winter. Learn about nutritious winter diet for dogs, immunity boosting foods, seasonal nutrition tips and weekly meal plans for your pet."
        />
        <meta
          name="keywords"
          content="best food for dogs in winter, winter diet for dogs, nutritious dog food for cold weather, healthy winter foods for dogs, foods to boost dog immunity in winter, winter care tips for dogs diet"
        />
        <meta name="author" content="Pet Nutrition Expert" />
        <meta name="robots" content="index, follow" />
        <meta
          property="og:title"
          content="Best Food for Dogs in Winter: Complete Seasonal Nutrition Guide 2025"
        />
        <meta
          property="og:description"
          content="Discover the best winter foods for your dog. Complete guide with nutrition tips, meal plans, and safety guidelines for cold weather."
        />
        <meta property="og:type" content="article" />
        <meta
          property="og:image"
          content="https://yourwebsite.com/images/dog-winter-food.jpg"
        />
        <link
          rel="canonical"
          href="https://yourwebsite.com/best-food-for-dogs-in-winter"
        />

        {/* Schema.org Markup */}
        <script type="application/ld+json">
          {JSON.stringify({
            "@context": "https://schema.org",
            "@type": "BlogPosting",
            headline:
              "Best Food for Dogs in Winter: Complete Seasonal Nutrition Guide 2025",
            description:
              "Complete guide on best food for dogs in winter. Learn about nutritious winter diet for dogs, immunity boosting foods, seasonal nutrition tips and weekly meal plans for your pet.",
            image: "https://yourwebsite.com/images/dog-winter-food.jpg",
            author: {
              "@type": "Organization",
              name: "Pet Nutrition Expert",
            },
            publisher: {
              "@type": "Organization",
              name: "Your Website",
              logo: {
                "@type": "ImageObject",
                url: "https://yourwebsite.com/logo.png",
              },
            },
            datePublished: "2025-12-01",
            dateModified: "2025-12-01",
            mainEntityOfPage: {
              "@type": "WebPage",
              "@id": "https://yourwebsite.com/best-food-for-dogs-in-winter",
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
                name: "What is the best food for dogs in winter?",
                acceptedAnswer: {
                  "@type": "Answer",
                  text: "Chicken soup, eggs, fish, bone broth and sweet potatoes are some of the best food for dogs in winter because they provide warmth, energy and immunity.",
                },
              },
              {
                "@type": "Question",
                name: "Should I increase my dog's food intake in winter?",
                acceptedAnswer: {
                  "@type": "Answer",
                  text: "Active dogs and those who spend significant time outdoors may need slightly more calories during cold weather (10-15% increase).",
                },
              },
              {
                "@type": "Question",
                name: "Are fruits safe for dogs in winter?",
                acceptedAnswer: {
                  "@type": "Answer",
                  text: "Yes, apples and bananas are safe winter fruits for dogs when served in moderation. Always remove apple seeds and cores.",
                },
              },
              {
                "@type": "Question",
                name: "How often should dogs eat fish in winter?",
                acceptedAnswer: {
                  "@type": "Answer",
                  text: "One to two times a week is ideal for most dogs. Fish like salmon and sardines are excellent sources of Omega-3 fatty acids.",
                },
              },
              {
                "@type": "Question",
                name: "Is bone broth safe to give daily?",
                acceptedAnswer: {
                  "@type": "Answer",
                  text: "Yes, bone broth supports joints and immunity. It can be given daily in moderate amounts (1/4 to 1 cup depending on dog size).",
                },
              },
              {
                "@type": "Question",
                name: "Can puppies follow this winter diet?",
                acceptedAnswer: {
                  "@type": "Answer",
                  text: "Yes, but portions should be smaller and adjusted according to their age, size and growth stage.",
                },
              },
              {
                "@type": "Question",
                name: "Are carrots good for dogs in winter?",
                acceptedAnswer: {
                  "@type": "Answer",
                  text: "Carrots are vitamin-rich and support immunity, making them excellent healthy winter foods for dogs.",
                },
              },
              {
                "@type": "Question",
                name: "Should meals always be warm in winter?",
                acceptedAnswer: {
                  "@type": "Answer",
                  text: "Warm (not hot) meals aid digestion and help maintain body temperature. Room temperature food is also acceptable.",
                },
              },
            ],
          })}
        </script>
      </head>

      <div className="font-sans text-gray-800 bg-gradient-to-br from-red-50 to-pink-50 min-h-screen mt-20">
        <Header />
        <article
          className="max-w-4xl mx-auto bg-white shadow-xl shadow-red-100 rounded-xl overflow-hidden my-8"
          itemScope
          itemType="http://schema.org/BlogPosting"
        >
          {/* Blog Header */}
          <header className="bg-gradient-to-r from-red-400 to-pink-500 text-white relative overflow-hidden py-16 px-8 text-center">
            {/* Decorative Emojis */}
            <div className="absolute text-9xl opacity-10 -top-10 -right-10 transform -rotate-12">
              🐕
            </div>
            <div className="absolute text-7xl opacity-10 -bottom-10 -left-10 transform rotate-12">
              ❄️
            </div>

            <div className="relative z-10">
              <h1
                className="text-3xl md:text-4xl lg:text-5xl font-black mb-6 drop-shadow-lg"
                itemProp="headline"
              >
                Best Food for Dogs in Winter: Complete Seasonal Nutrition Guide
              </h1>
              <p className="text-xl md:text-2xl opacity-95 font-light mb-8 leading-relaxed">
                Keep your furry friend healthy, warm and active throughout the
                cold season with proper winter nutrition
              </p>

              <div className="flex flex-wrap justify-center gap-4 mt-8">
                <span className="bg-white/20 backdrop-blur-sm px-5 py-2 rounded-full flex items-center gap-2">
                  📅 Updated: December 2025
                </span>
                <span className="bg-white/20 backdrop-blur-sm px-5 py-2 rounded-full flex items-center gap-2">
                  ⏱️ Reading Time: 10 minutes
                </span>
                <span className="bg-white/20 backdrop-blur-sm px-5 py-2 rounded-full flex items-center gap-2">
                  🐕 Expert Reviewed
                </span>
              </div>
            </div>
          </header>
          <section>
            <img src={img8} alt="image" />
          </section>
          {/* Quick Answer Section */}
          <div className="bg-gradient-to-r from-yellow-100 to-yellow-50 border-l-4 border-yellow-500 p-6 md:p-8 mx-8 my-8 rounded-xl shadow-lg">
            <h3 className="text-yellow-600 text-2xl font-bold mb-4 flex items-center gap-2">
              ⚡ Quick Answer
            </h3>
            <p className="text-gray-800 text-lg leading-relaxed">
              The simplest and most effective winter feeding approach is to give
              your dog warm meals, protein-rich foods, healthy fats and
              immunity-boosting ingredients like{" "}
              <strong className="font-bold text-gray-900">
                chicken soup, bone broth, eggs and fish
              </strong>
              . These foods help maintain body temperature, boost immunity and
              provide extra energy your dog needs during cold months.
            </p>
          </div>

          {/* Main Content */}
          <div className="px-6 md:px-10 py-8" itemProp="articleBody">
            <p className="text-gray-700 text-lg mb-6 leading-relaxed">
              Dogs need extra nutrients, warmth and immune support in cold
              months, and choosing the{" "}
              <strong className="font-bold text-red-600">
                best food for dogs in winter
              </strong>{" "}
              is the easiest way to keep them healthy, active and protected from
              infections. Winter can slow digestion, weaken immunity and reduce
              your dog's overall energy. A balanced winter diet plays a major
              role in supporting joint health, skin quality, warmth and gut
              strength.
            </p>

            <p className="text-gray-700 text-lg mb-8 leading-relaxed">
              This complete guide explains every winter food your dog needs, how
              to feed it safely, serving sizes, a weekly chart and expert-backed
              nutrition tips.
            </p>

            {/* Table of Contents */}
            <nav
              className="bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-400 p-6 md:p-8 rounded-xl shadow-sm mb-12"
              aria-label="Table of Contents"
            >
              <h3 className="text-red-600 text-2xl font-bold mb-6 flex items-center gap-2">
                📑 Table of Contents
              </h3>
              <ol className="list-decimal pl-5 space-y-3">
                <li>
                  <a
                    href="#why-winter-diet"
                    className="text-gray-800 hover:text-red-600 font-medium transition-colors duration-300"
                  >
                    Why Winter Diet Needs Special Care
                  </a>
                </li>
                <li>
                  <a
                    href="#top-foods"
                    className="text-gray-800 hover:text-red-600 font-medium transition-colors duration-300"
                  >
                    Top 12 Best Food for Dogs in Winter
                  </a>
                </li>
                <li>
                  <a
                    href="#nutrition-table"
                    className="text-gray-800 hover:text-red-600 font-medium transition-colors duration-300"
                  >
                    Nutritional Table for Winter Foods
                  </a>
                </li>
                <li>
                  <a
                    href="#health-support"
                    className="text-gray-800 hover:text-red-600 font-medium transition-colors duration-300"
                  >
                    How These Winter Foods Support Your Dog
                  </a>
                </li>
                <li>
                  <a
                    href="#meal-plan"
                    className="text-gray-800 hover:text-red-600 font-medium transition-colors duration-300"
                  >
                    Weekly Winter Diet Plan
                  </a>
                </li>
                <li>
                  <a
                    href="#safety-tips"
                    className="text-gray-800 hover:text-red-600 font-medium transition-colors duration-300"
                  >
                    Winter Feeding Safety Tips
                  </a>
                </li>
                <li>
                  <a
                    href="#faq"
                    className="text-gray-800 hover:text-red-600 font-medium transition-colors duration-300"
                  >
                    Frequently Asked Questions
                  </a>
                </li>
              </ol>
            </nav>

            {/* Why Winter Diet Needs Special Care */}
            <section id="why-winter-diet" className="scroll-mt-20">
              <h2 className="text-3xl md:text-4xl font-bold text-red-600 mb-8 pb-4 border-b-2 border-red-400 relative">
                Why Winter Diet Needs Special Care
                <div className="absolute bottom-0 left-0 w-24 h-1 bg-pink-500"></div>
              </h2>

              <p className="text-gray-700 text-lg mb-8 leading-relaxed">
                Dogs burn more calories to stay warm during winter, which
                significantly increases their nutritional needs. When you choose
                the{" "}
                <strong className="font-bold text-red-600">
                  best food for dogs in winter
                </strong>
                , you help them maintain body temperature, energy levels and
                strong immunity.
              </p>

              {/* Benefits Grid */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                {[
                  {
                    icon: "🛡️",
                    title: "Infection Protection",
                    desc: "Protects against viral infections and seasonal illnesses",
                  },
                  {
                    icon: "🔥",
                    title: "Body Warmth",
                    desc: "Enhances natural body heat generation",
                  },
                  {
                    icon: "🦴",
                    title: "Joint Health",
                    desc: "Keeps joints flexible and reduces stiffness",
                  },
                  {
                    icon: "💪",
                    title: "Digestive Strength",
                    desc: "Strengthens digestive health and gut function",
                  },
                  {
                    icon: "✨",
                    title: "Skin Hydration",
                    desc: "Boosts skin hydration and coat shine",
                  },
                  {
                    icon: "😊",
                    title: "Mood Stability",
                    desc: "Stabilises mood and activity levels",
                  },
                ].map((benefit, index) => (
                  <div
                    key={index}
                    className="bg-white border-t-4 border-red-400 rounded-xl p-6 text-center shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-300"
                  >
                    <div className="text-5xl mb-4">{benefit.icon}</div>
                    <h4 className="text-xl font-bold text-red-600 mb-3">
                      {benefit.title}
                    </h4>
                    <p className="text-gray-600">{benefit.desc}</p>
                  </div>
                ))}
              </div>

              <p className="text-gray-700 text-lg leading-relaxed">
                A well-balanced{" "}
                <strong className="font-bold text-red-600">
                  winter diet for dogs
                </strong>{" "}
                improves their overall winter wellbeing and helps them thrive
                during cold weather.
              </p>
            </section>

            {/* Top 12 Best Food for Dogs in Winter */}
            <section id="top-foods" className="scroll-mt-20 mt-16">
              <h2 className="text-3xl md:text-4xl font-bold text-red-600 mb-8 pb-4 border-b-2 border-red-400 relative">
                Top 12 Best Food for Dogs in Winter
                <div className="absolute bottom-0 left-0 w-24 h-1 bg-pink-500"></div>
              </h2>

              <p className="text-gray-700 text-lg mb-8 leading-relaxed">
                Here are the most effective foods to add to your dog's winter
                routine:
              </p>

              {/* Food Cards */}
              <div className="space-y-8">
                {[
                  {
                    icon: "🍗",
                    title: "Chicken Soup",
                    desc: "Chicken soup is a warm, soothing meal that supports digestion and hydration.",
                    benefits: [
                      "Improves immunity and fights infections",
                      "Increases body warmth naturally",
                      "Supports gut health and digestion",
                      "Hydrates naturally without forcing water intake",
                    ],
                    note: "This is also one of the best nutritious dog food for cold weather choices.",
                  },

                  {
                    icon: "🥚",
                    title: "Eggs",
                    desc: "Eggs provide high-quality protein that supports coat shine and muscle strength.",
                    benefits: [
                      "Helps in coat formation and reduces shedding",
                      "Improves immunity with essential amino acids",
                      "Supports energy levels throughout the day",
                      "Rich in biotin for healthy skin",
                    ],
                    note: "A perfect example of healthy winter foods for dogs.",
                  },

                  {
                    icon: "🍠",
                    title: "Sweet Potatoes",
                    desc: "Rich in fibre and energy-giving carbohydrates that keep dogs active in winter.",
                    benefits: [
                      "Improves digestion and prevents constipation",
                      "Great for steady, long-lasting energy",
                      "Supports gut health with natural fiber",
                      "Packed with beta-carotene for immunity",
                    ],
                    note: "Sweet potatoes are easy to include in a winter diet for dogs.",
                  },

                  {
                    icon: "🎃",
                    title: "Pumpkin",
                    desc: "Pumpkin is a powerful digestive superfood for winter months.",
                    benefits: [
                      "Prevents constipation and diarrhea",
                      "Strengthens immunity with antioxidants",
                      "Keeps gut balanced and healthy",
                      "Low in calories, high in nutrition",
                    ],
                    note: "Pumpkin is also one of the top foods to boost dog immunity in winter.",
                  },

                  {
                    icon: "🧀",
                    title: "Cottage Cheese",
                    desc: "Cottage cheese is a calcium-rich, soft protein source that's gentle on the stomach.",
                    benefits: [
                      "Strengthens bones and teeth",
                      "Enhances muscle growth and repair",
                      "Light on stomach, easy to digest",
                      "Rich in probiotics for gut health",
                    ],
                    note: "Ideal for inclusion while planning winter care tips for dogs diet.",
                  },

                  {
                    icon: "🐟",
                    title: "Fish (Salmon or Sardines)",
                    desc: "Fish supports coat health, joint strength and immunity during cold weather.",
                    benefits: [
                      "Rich in Omega-3 fatty acids",
                      "Reduces inflammation in joints",
                      "Strengthens skin and coat quality",
                      "Boosts brain function and heart health",
                    ],
                    note: "This makes fish a powerful option within nutritious dog food for cold weather.",
                  },
                ].map((food, index) => (
                  <div
                    key={index}
                    className="bg-white border-2 border-red-100 rounded-xl p-6 shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all duration-300"
                  >
                    <h3 className="text-2xl font-bold text-red-600 mb-4 flex items-center gap-3">
                      <span className="text-3xl">{food.icon}</span> {index + 1}.{" "}
                      {food.title}
                    </h3>
                    <p className="text-gray-700 mb-6 text-lg">{food.desc}</p>

                    <h4 className="text-xl font-semibold text-gray-800 mb-3">
                      Benefits:
                    </h4>
                    <ul className="list-disc pl-6 mb-6 space-y-2">
                      {food.benefits.map((benefit, i) => (
                        <li key={i} className="text-gray-600">
                          {benefit}
                        </li>
                      ))}
                    </ul>

                    <p className="text-gray-700 italic">{food.note}</p>
                  </div>
                ))}
              </div>
            </section>

            {/* Nutritional Table */}
            <section id="nutrition-table" className="scroll-mt-20 mt-16">
              <h2 className="text-3xl md:text-4xl font-bold text-red-600 mb-8 pb-4 border-b-2 border-red-400 relative">
                Nutritional Table for Winter Foods
                <div className="absolute bottom-0 left-0 w-24 h-1 bg-pink-500"></div>
              </h2>

              <div className="overflow-x-auto rounded-xl shadow-lg mb-12">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gradient-to-r from-red-400 to-pink-500">
                    <tr>
                      <th className="px-6 py-4 text-left text-sm font-semibold text-white">
                        Food
                      </th>
                      <th className="px-6 py-4 text-left text-sm font-semibold text-white">
                        Key Nutrients
                      </th>
                      <th className="px-6 py-4 text-left text-sm font-semibold text-white">
                        Winter Benefit
                      </th>
                      <th className="px 6 py-4 text-left text-sm font-semibold text-white">
                        Serving Advice
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {[
                      [
                        "Chicken Soup",
                        "Amino acids, minerals",
                        "Warmth and immunity",
                        "2 to 4 tbsp",
                      ],
                      [
                        "Eggs",
                        "Protein, biotin",
                        "Coat and muscle health",
                        "3 to 5 weekly",
                      ],
                      [
                        "Sweet Potato",
                        "Beta carotene",
                        "Energy and gut health",
                        "Small cubes",
                      ],
                      [
                        "Pumpkin",
                        "Fiber",
                        "Digestion and immunity",
                        "1 to 2 tbsp",
                      ],
                      [
                        "Fish",
                        "Omega-3",
                        "Skin and joint support",
                        "1 to 2 meals weekly",
                      ],
                      ["Bone Broth", "Collagen", "Gut repair", "1 cup daily"],
                      ["Oatmeal", "Fiber", "Steady energy", "1 to 2 tbsp"],
                      ["Cottage Cheese", "Calcium", "Bone strength", "1 tbsp"],
                      [
                        "Carrots",
                        "Vitamin A",
                        "Eye and skin health",
                        "Few slices",
                      ],
                      ["Apples", "Vit A and C", "Light snack", "Small cubes"],
                    ].map((row, index) => (
                      <tr
                        key={index}
                        className={
                          index % 2 === 0
                            ? "bg-red-50 hover:bg-red-100"
                            : "bg-white hover:bg-red-100"
                        }
                      >
                        <td className="px-6 py-4 font-semibold text-gray-900">
                          {row[0]}
                        </td>
                        <td className="px-6 py-4 text-gray-700">{row[1]}</td>
                        <td className="px-6 py-4 text-gray-700">{row[2]}</td>
                        <td className="px-6 py-4 text-gray-700">{row[3]}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </section>

            {/* How These Winter Foods Support Your Dog */}
            <section id="health-support" className="scroll-mt-20 mt-16">
              <h2 className="text-3xl md:text-4xl font-bold text-red-600 mb-8 pb-4 border-b-2 border-red-400 relative">
                How These Winter Foods Support Your Dog
                <div className="absolute bottom-0 left-0 w-24 h-1 bg-pink-500"></div>
              </h2>

              <p className="text-gray-700 text-lg mb-8 leading-relaxed">
                Including the{" "}
                <strong className="font-bold text-red-600">
                  best food for dogs in winter
                </strong>{" "}
                results in several significant health improvements:
              </p>

              <div className="space-y-8">
                {[
                  {
                    title: "Stronger Immunity",
                    content:
                      "Bone broth, pumpkin, eggs and fish are top foods to boost dog immunity in winter because they contain antioxidants, vitamins and natural immune-supporting compounds that help your dog fight off seasonal infections.",
                  },
                  {
                    title: "Improved Coat and Skin",
                    content:
                      "Fish oil and eggs nourish the coat from within and reduce winter dryness. The Omega-3 fatty acids help maintain skin moisture and prevent flaking, itching and dullness.",
                  },
                  {
                    title: "Better Digestion",
                    content:
                      "Pumpkin, oatmeal and sweet potatoes maintain stable digestive function even when cold weather slows down the digestive process. These fiber-rich foods keep everything moving smoothly.",
                  },
                  {
                    title: "Warmth and Energy",
                    content:
                      "Chicken soup and healthy fats provide the heat and energy essential for cold months. These calorie-dense foods help your dog maintain body temperature naturally.",
                  },
                  {
                    title: "Joint Support",
                    content:
                      "Bone broth strengthens joints with collagen, especially beneficial for older dogs who may experience stiffness in winter. Regular consumption can improve mobility and reduce discomfort.",
                  },
                ].map((item, index) => (
                  <div key={index}>
                    <h3 className="text-2xl font-bold text-pink-600 mb-4">
                      {index + 1}. {item.title}
                    </h3>
                    <p className="text-gray-700 text-lg leading-relaxed mb-6">
                      {item.content}
                    </p>
                  </div>
                ))}
              </div>

              <div className="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-emerald-500 rounded-xl p-6 mt-8 shadow-lg">
                <p className="text-gray-800 text-lg font-semibold flex items-center gap-2">
                  ✅ <span className="text-emerald-600">Expert Tip:</span>{" "}
                  Consistency is key! Include at least 3-4 of these winter
                  superfoods in your dog's weekly diet for optimal results.
                </p>
              </div>
            </section>

            {/* Weekly Winter Diet Plan */}
            <section id="meal-plan" className="scroll-mt-20 mt-16">
              <h2 className="text-3xl md:text-4xl font-bold text-red-600 mb-8 pb-4 border-b-2 border-red-400 relative">
                Weekly Winter Diet Plan
                <div className="absolute bottom-0 left-0 w-24 h-1 bg-pink-500"></div>
              </h2>

              <div className="bg-white rounded-xl p-6 md:p-8 shadow-lg mb-8">
                <h3 className="text-2xl font-bold text-red-600 text-center mb-8 flex items-center justify-center gap-2">
                  🗓️ 7-Day Winter Meal Schedule
                </h3>

                <div className="space-y-4">
                  {[
                    {
                      day: "Monday",
                      morning: "Oatmeal + Egg",
                      evening: "Chicken Soup + Rice",
                    },
                    {
                      day: "Tuesday",
                      morning: "Pumpkin Mix",
                      evening: "Fish with Rice",
                    },
                    {
                      day: "Wednesday",
                      morning: "Sweet Potato Bowl",
                      evening: "Bone Broth Meal",
                    },
                    {
                      day: "Thursday",
                      morning: "Cottage Cheese and Carrots",
                      evening: "Chicken and Carrots",
                    },
                    {
                      day: "Friday",
                      morning: "Egg and Rice Bowl",
                      evening: "Pumpkin with Kibble",
                    },
                    {
                      day: "Saturday",
                      morning: "Salmon Meal",
                      evening: "Oatmeal with Warm Water",
                    },
                    {
                      day: "Sunday",
                      morning: "Chicken Soup",
                      evening: "Light Rice Meal + Carrots",
                    },
                  ].map((meal, index) => (
                    <div
                      key={index}
                      className="bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-400 rounded-lg p-6 grid grid-cols-1 md:grid-cols-3 gap-4 items-center"
                    >
                      <div className="font-bold text-red-600 text-lg">
                        {meal.day}
                      </div>
                      <div className="text-gray-700">
                        <strong className="block text-gray-900 mb-1">
                          Morning:
                        </strong>{" "}
                        {meal.morning}
                      </div>
                      <div className="text-gray-700">
                        <strong className="block text-gray-900 mb-1">
                          Evening:
                        </strong>{" "}
                        {meal.evening}
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              <div className="bg-gradient-to-r from-blue-50 to-sky-50 border-l-4 border-blue-500 rounded-xl p-6 shadow-lg">
                <p className="text-gray-800 text-lg">
                  <strong className="text-blue-600">💡 Note:</strong> This chart
                  includes multiple items from the{" "}
                  <strong className="font-semibold text-gray-900">
                    best food for dogs in winter
                  </strong>{" "}
                  category and ensures a balanced{" "}
                  <strong className="font-semibold text-gray-900">
                    winter diet for dogs
                  </strong>
                  . Adjust portions based on your dog's size and activity level.
                </p>
              </div>
            </section>

            {/* Winter Feeding Safety Tips */}
            <section id="safety-tips" className="scroll-mt-20 mt-16">
              <h2 className="text-3xl md:text-4xl font-bold text-red-600 mb-8 pb-4 border-b-2 border-red-400 relative">
                Winter Feeding Safety Tips
                <div className="absolute bottom-0 left-0 w-24 h-1 bg-pink-500"></div>
              </h2>

              <div className="bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-400 rounded-xl p-6 md:p-8 shadow-lg mb-8">
                <h3 className="text-2xl font-bold text-red-600 mb-6 flex items-center gap-2">
                  🔒 Important Safety Guidelines
                </h3>

                <div className="space-y-6">
                  {[
                    {
                      title: "Avoid Salt and Spices",
                      content:
                        "Only plain, cooked foods should be served. Salt, onions, garlic, and heavy spices can be toxic to dogs.",
                    },
                    {
                      title: "Serve Warm Meals",
                      content:
                        "Warm (not hot) meals help digestion and support nutritious dog food for cold weather patterns. Test temperature before serving.",
                    },
                    {
                      title: "Introduce Slowly",
                      content:
                        "New foods should be given in small amounts over 3-5 days to avoid digestive upset. Watch for any allergic reactions.",
                    },
                    {
                      title: "Keep Water Lukewarm",
                      content:
                        "Cold water can reduce appetite and slow digestion. Room temperature or slightly warm water is ideal in winter.",
                    },
                    {
                      title: "Follow Natural Ingredients",
                      content:
                        "Helps maintain a clean and healthy winter diet for dogs. Avoid processed foods with artificial additives.",
                    },
                    {
                      title: "Monitor Portion Sizes",
                      content:
                        "Even healthy foods can cause weight gain if overfed. Adjust portions based on your dog's activity level and metabolism.",
                    },
                    {
                      title: "Remove Apple Seeds",
                      content:
                        "Always remove apple cores and seeds before feeding, as they contain trace amounts of cyanide.",
                    },
                    {
                      title: "Check Food Freshness",
                      content:
                        "Store homemade meals properly and use within 2-3 days. Discard any food that smells off or looks spoiled.",
                    },
                  ].map((tip, index) => (
                    <div key={index}>
                      <h4 className="text-xl font-semibold text-gray-800 mb-2">
                        {tip.title}
                      </h4>
                      <p className="text-gray-700">{tip.content}</p>
                    </div>
                  ))}
                </div>
              </div>

              <div className="bg-gradient-to-r from-yellow-50 to-amber-50 border-l-4 border-yellow-500 rounded-xl p-6 shadow-lg">
                <p className="text-gray-800 text-lg font-semibold">
                  ⚠️ <span className="text-amber-600">Warning:</span> Never feed
                  your dog chocolate, grapes, raisins, xylitol, macadamia nuts,
                  alcohol, or cooked bones. These are toxic and can be fatal.
                </p>
              </div>
            </section>

            {/* CTA Box */}
            <div className="bg-gradient-to-r from-red-500 to-pink-600 text-white rounded-xl p-8 md:p-10 text-center mt-16 shadow-2xl">
              <h3 className="text-2xl md:text-3xl font-bold mb-6">
                🎯 Start Your Dog's Winter Wellness Journey Today!
              </h3>
              <p className="text-xl opacity-95 mb-8">
                Give your furry friend the nutrition they deserve this winter.
                Implement these dietary changes gradually and watch your dog
                thrive with improved energy, immunity and happiness throughout
                the cold season!
              </p>
            </div>
          </div>

          {/* FAQ Section */}
          <section
            id="faq"
            className="scroll-mt-20 bg-gradient-to-r from-red-50 to-pink-50 px-6 md:px-10 py-12"
          >
            <div className="max-w-4xl mx-auto">
              <h2 className="text-3xl md:text-4xl font-bold text-red-600 text-center mb-12">
                ❓ Frequently Asked Questions
              </h2>

              <div className="space-y-6">
                {[
                  {
                    q: "What is the best food for dogs in winter?",
                    a: "Chicken soup, eggs, fish, bone broth and sweet potatoes are some of the best food for dogs in winter because they provide warmth, energy and immunity. These nutrient-dense foods help dogs maintain body temperature and fight off seasonal infections effectively.",
                  },
                  {
                    q: "Should I increase my dog's food intake in winter?",
                    a: "Active dogs and those who spend significant time outdoors may need slightly more calories during cold weather (10-15% increase). However, indoor dogs with less activity should maintain their regular portions to avoid winter weight gain. Consult your vet for personalized recommendations.",
                  },
                  {
                    q: "Are fruits safe for dogs in winter?",
                    a: "Yes, apples and bananas are safe winter fruits for dogs when served in moderation. Always remove apple seeds and cores. These fruits provide natural vitamins, fiber and make excellent low-calorie treats. Avoid grapes and raisins as they are toxic to dogs.",
                  },
                  {
                    q: "How often should dogs eat fish in winter?",
                    a: "One to two times a week is ideal for most dogs. Fish like salmon and sardines are excellent sources of Omega-3 fatty acids that support coat health, reduce inflammation and boost immunity. Always cook fish thoroughly and remove all bones before serving.",
                  },
                  {
                    q: "Is bone broth safe to give daily?",
                    a: "Yes, bone broth supports joints and immunity as part of foods to boost dog immunity in winter. It can be given daily in moderate amounts (1/4 to 1 cup depending on dog size). Homemade bone broth without salt, onions or garlic is the safest option.",
                  },
                  {
                    q: "Can puppies follow this winter diet?",
                    a: "Yes, but portions should be smaller and adjusted according to their age, size and growth stage. Puppies have different nutritional needs, so consult your veterinarian before making significant dietary changes. Introduce new foods gradually to avoid digestive issues.",
                  },
                  {
                    q: "Are carrots good for dogs in winter?",
                    a: "Carrots are vitamin-rich and support immunity, making them excellent healthy winter foods for dogs. They can be served raw as crunchy treats or cooked for easier digestion. Carrots are also low in calories and help with dental health.",
                  },
                  {
                    q: "Should meals always be warm in winter?",
                    a: "Warm (not hot) meals aid digestion and follow the principles of winter care tips for dogs diet. They also help maintain body temperature. However, room temperature food is also acceptable. The key is avoiding very cold food straight from the refrigerator during winter months.",
                  },
                ].map((faq, index) => (
                  <div
                    key={index}
                    className="bg-white border-l-4 border-red-400 rounded-xl p-6 shadow-lg hover:shadow-xl hover:translate-x-2 transition-all duration-300"
                    itemScope
                    itemType="https://schema.org/Question"
                  >
                    <div
                      className="faq-question text-xl font-bold text-red-600 mb-4"
                      itemProp="name"
                    >
                      {index + 1}. {faq.q}
                    </div>
                    <div
                      className="faq-answer text-gray-700"
                      itemScope
                      itemType="https://schema.org/Answer"
                      itemProp="acceptedAnswer"
                    >
                      <p itemProp="text">{faq.a}</p>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </section>
        </article>

        {/* Back to Top Button */}
        <button
          onClick={scrollToTop}
          className={`fixed bottom-8 right-8 bg-gradient-to-r from-red-400 to-pink-500 text-white w-14 h-14 rounded-full flex items-center justify-center shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 ${
            showBackToTop ? "opacity-100" : "opacity-0 pointer-events-none"
          }`}
          aria-label="Back to top"
        >
          <span className="text-2xl">↑</span>
        </button>
        <Footer />
      </div>
    </>
  );
};

export default BestFoodForDogsInWinter;
