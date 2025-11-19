import React, { useState, useMemo } from "react";
import { Link } from "react-router-dom";
import {
  Calendar,
  Clock,
  ArrowRight,
  User,
  Search,
  BookOpen,
  TrendingUp,
} from "lucide-react";
import Header from "../components/Header";
import Footer from "../components/Footer";
import img1 from '../assets/images/dog winter.png';
import img2 from '../assets/images/pawproduction.png';
import img3 from '../assets/images/tickfever.png';
// --------------------------------------------------------
import img4 from '../assets/images/first_aid_tips.jpeg';
import img5 from '../assets/images/how_to_boost.jpeg';
import img6 from '../assets/images/how_vets_can.jpeg';
import img7 from '../assets/images/vaccination_schedule.jpeg';
import img8 from '../assets/images/what_should_your.jpeg';

export const metadata = {
  title: "Blog - SnoutIQ Veterinary Insights",
  description:
    "Expert advice, industry insights, and best practices for modern veterinary practices.",
};

const featuredPost = {
  title: "How to Reduce No-Shows in Your Veterinary Practice",
  excerpt:
    "Discover proven strategies to cut no-show rates by up to 70% and increase your clinic revenue through automated reminders and smart scheduling systems.",
  author: "Dr. Priya Sharma",
  date: "November 5, 2024",
  readTime: "8 min read",
  category: "Practice Management",
  slug: "how-to-reduce-no-shows",
  image: "/blog/no-shows.jpg",
  featured: true,
};

const posts = [
  {
    image:img1,
    title: "Dog Winter Care Guide – How to Take Care of Dogs in Winter & Keep Them Warm",
    excerpt:
      "Learn the best dog winter care guide with practical tips for taking care of dogs’ paws in winter, grooming, diet, and keeping your dog warm and healthy all season long.",
    author: "Snoutiq Editer",
    date: "November 11, 2025",
    readTime: "6 min read",
    category: "Telemedicine",
    slug: "dog-winter-care-guide",
    trending: true,
  },
  {
     image:img3,
    title: "Tick Fever in Dogs – Symptoms, Causes & Treatment | Complete Guide",
    excerpt:
      "Learn about tick fever in dogs — common symptoms, causes, prevention tips, and treatment options. Understand how to detect tick fever early and keep your dog safe.",
    author: "Snoutiq Editer",
    date: "November 11, 2025",
    readTime: "7 min read",
    category: "AI & Technology",
    slug: "symptoms-of-tick-fever-in-dogs",
    trending: true,
  },
  {
     image:img2,
    title: "Protecting Pet Paws in Winter – Tips, Products & Care Guide for Pet Parents",
    excerpt:
      "Learn how to protect your pet’s paws in winter with easy home care tips, natural remedies, and prevention hacks. Discover safe paw care, winter boots, and DIY protection methods for dogs and cats.",
    author: "Snoutiq Editer",
    date: "November 11, 2025",
    readTime: "5 min read",
    category: "Client Relations",
    slug: "protecting-pet-paws-in-winter-tips-guide",
  },
// -----------------------------------------------------------------------------------------
  {
    image:img4,
    title: "First Aid Tips Every Pet Parent Should Know – Essential Pet Emergency Guide",
    excerpt:
      "Learn life-saving first aid tips every pet parent should know. Discover how to handle pet injuries, choking, burns, bleeding, and other emergencies confidently at home.",
    author: "Snoutiq Editer",
    date: "November 19, 2025",
    readTime: "6 min read",
    category: "Telemedicine",
    slug: "first-aid-tips-every-pet-parent-should-know",
    trending: true,
  },
  {
     image:img5,
    title: "Natural Ways to Strengthen Your Dog’s Immune System – Complete Guide",
    excerpt:
      "Discover natural ways to strengthen your dog’s immune system. Learn foods that boost dog immunity, effective routines, and natural immune boosters for dogs you can try at home.",
    author: "Snoutiq Editer",
    date: "November 19, 2025",
    readTime: "7 min read",
    category: "Client Relations",
    slug: "boost-your-dogs-immunity-naturally",
    trending: true,
  },
  {
     image:img7,
    title: "Vaccination Schedule for Pets in India – Complete Vet Approved Guide",
    excerpt:
      ": Learn the complete vaccination schedule for pets in India. Know which vaccines your dog or cat needs, timelines, costs, and why vaccination is vital for long, healthy lives.",
    author: "Snoutiq Editer",
    date: "November 19, 2025",
    readTime: "5 min read",
    category: "Client Relations",
    slug: "vaccination-schedule-for-pets-in-india",
  },

   {
     image:img8,
    title: "Best Food for Dogs in Winter Complete Nutrition Guide 2025",
    excerpt:
      "Discover the best food for dogs in winter including warming meals, immunity boosting foods and a complete winter diet plan to keep your dog healthy and active.",
    author: "Snoutiq Editer",
    date: "November 19, 2025",
    readTime: "7 min read",
    category: "Client Relations",
    slug: "best-food-for-dogs-in-winter",
    trending: true,
  },
  {
     image:img6,
    title: "How Vets Can Grow Their Practice with Online Consultations in India",
    excerpt:
      "Discover how veterinarians can grow their practice using online vet consultation. Learn benefits, tools, pricing, marketing strategies and much more for scaling digital veterinary services in India.",
    author: "Snoutiq Editer",
    date: "November 19, 2025",
    readTime: "5 min read",
    category: "Client Relations",
    slug: "how-vets-grow-with-online-consultations",
  },
 
];

const categories = [
  "All Posts",
  "Practice Management",
  "Telemedicine",
  "AI & Technology",
  "Client Relations",
  "Emergency Care",
];

function Container({ children, className = "" }) {
  return (
    <div
      className={`container-custom mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 ${className}`}
    >
      {children}
    </div>
  );
}

/* -------------------- HERO -------------------- */

function Hero({ searchQuery, onSearchChange, onSearchSubmit }) {
  return (
    <section className="relative py-20 md:py-28 lg:py-36 bg-gradient-to-br from-blue-50 via-sky-50 to-cyan-50 overflow-hidden">
      {/* Background */}
      <div className="absolute inset-0 bg-grid-blue-100 [mask-image:linear-gradient(0deg,white,rgba(255,255,255,0.6))]" />
      <div className="absolute top-10 right-10 w-72 h-72 bg-blue-200/30 rounded-full blur-3xl" />
      <div className="absolute bottom-10 left-10 w-96 h-96 bg-cyan-200/30 rounded-full blur-3xl" />

      <Container className="relative text-center">
        <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/80 backdrop-blur-sm border border-blue-200/80 shadow-sm mb-6">
          <BookOpen className="w-4 h-4 text-blue-600" />
          <span className="text-sm font-medium text-blue-800">
            Expert Veterinary Insights
          </span>
        </div>

        <h1 className="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-bold leading-tight text-gray-900 mb-6">
          SnoutIQ{" "}
          <span className="bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
            Insights
          </span>
        </h1>

        <p className="text-lg sm:text-xl md:text-2xl leading-relaxed text-gray-600 max-w-4xl mx-auto font-light">
          Expert insights, growth strategies, and technology trends for
          forward-thinking{" "}
          <span className="font-semibold text-gray-700">
            veterinary practices
          </span>
          .
        </p>

        <div className="mt-12 flex flex-col sm:flex-row items-center justify-center gap-4 max-w-2xl mx-auto">
          <div className="relative flex-1 w-full sm:max-w-md">
            <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => onSearchChange(e.target.value)}
              placeholder="Search articles, topics, authors..."
              className="w-full pl-12 pr-4 py-4 bg-white/80 backdrop-blur-sm border border-blue-200/80 rounded-2xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500/30 transition-all placeholder-gray-400"
            />
          </div>
          <button
            type="button"
            onClick={onSearchSubmit}
            className="px-8 py-4 bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-semibold rounded-2xl shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:-translate-y-0.5 transition-all duration-200 whitespace-nowrap"
          >
            Search Articles
          </button>
        </div>
      </Container>
    </section>
  );
}

/* -------------------- CATEGORY BAR -------------------- */

function CategoryBar({ activeCategory, onCategoryChange }) {
  return (
    <section className="sticky top-20 z-40 bg-white/90 backdrop-blur-md border-b border-blue-100 supports-backdrop-blur:bg-white/80">
      <Container>
        <div className="flex flex-wrap gap-2 py-4 md:py-6 justify-start md:justify-center">
          {categories.map((category) => {
            const isActive = activeCategory === category;
            return (
              <button
                key={category}
                type="button"
                onClick={() => onCategoryChange(category)}
                className={`px-6 py-3 rounded-2xl text-sm font-medium transition-all duration-200 border focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:ring-offset-1
                  ${
                    isActive
                      ? "bg-gradient-to-r from-blue-600 to-cyan-600 text-white shadow-lg shadow-blue-500/25 border-transparent"
                      : "bg-white text-gray-700 border-blue-200 hover:border-blue-300 hover:shadow-md hover:-translate-y-0.5"
                  }`}
              >
                {category}
              </button>
            );
          })}
        </div>
      </Container>
    </section>
  );
}

/* -------------------- FEATURED ARTICLE -------------------- */

function FeaturedArticle() {
  return (
    <section className="py-16 md:py-24 bg-white relative overflow-hidden">
      <Container>
        <div className="flex items-center justify-between mb-10 md:mb-12">
          <div className="flex items-center gap-4">
            <div className="w-1.5 h-8 bg-gradient-to-b from-blue-600 to-cyan-600 rounded-full" />
            <div>
              <span className="text-xs md:text-sm font-semibold text-blue-600 uppercase tracking-[0.2em]">
                Featured Article
              </span>
              <h2 className="text-xl md:text-2xl font-bold text-gray-900 mt-1">
                Editor&apos;s Pick
              </h2>
            </div>
          </div>
          <div className="hidden md:flex items-center gap-2 text-gray-400 text-xs">
            <div className="w-2 h-2 bg-blue-500 rounded-full animate-pulse" />
            <span>Updated weekly</span>
          </div>
        </div>

        <Link
          to={`/blog/${featuredPost.slug}`}
          className="group block bg-gradient-to-br from-white to-blue-50/50 rounded-3xl overflow-hidden hover:shadow-2xl transition-all duration-500 border border-blue-100 hover:border-blue-200/80"
        >
          <div className="grid lg:grid-cols-2 gap-8 p-6 md:p-8 lg:p-12">
            {/* Text */}
            <div className="flex flex-col justify-center space-y-5">
              <div className="flex items-center gap-3">
                <span className="px-4 py-2 bg-blue-100 text-blue-700 rounded-full text-xs md:text-sm font-semibold">
                  {featuredPost.category}
                </span>
                <span className="flex items-center gap-1 text-xs text-gray-500">
                  <Clock className="w-3 h-3" />
                  {featuredPost.readTime}
                </span>
              </div>

              <h2 className="text-2xl md:text-3xl lg:text-4xl font-bold leading-tight text-gray-900 group-hover:text-blue-700 transition-colors duration-300">
                {featuredPost.title}
              </h2>

              <p className="text-base md:text-lg leading-relaxed text-gray-600">
                {featuredPost.excerpt}
              </p>

              <div className="flex flex-wrap items-center gap-6 text-xs md:text-sm text-gray-500">
                <div className="flex items-center gap-2">
                  <div className="w-8 h-8 bg-gradient-to-br from-blue-600 to-cyan-600 rounded-full flex items-center justify-center">
                    <User className="w-4 h-4 text-white" />
                  </div>
                  <span className="font-medium">{featuredPost.author}</span>
                </div>
                <div className="flex items-center gap-2">
                  <Calendar className="w-4 h-4" />
                  <span>{featuredPost.date}</span>
                </div>
              </div>

              <div className="flex items-center text-blue-600 font-semibold group-hover:translate-x-1.5 transition-transform duration-300 pt-2">
                <span>Read full article</span>
                <ArrowRight className="ml-2 w-4 h-4" />
              </div>
            </div>

            {/* Visual */}
            <div className="relative">
              <div className="relative bg-gradient-to-br from-blue-100 via-sky-100 to-cyan-100 rounded-2xl aspect-[4/3] overflow-hidden group-hover:scale-[1.02] transition-transform duration-500">
                {/* eslint-disable-next-line jsx-a11y/alt-text */}
                {featuredPost.image && (
                  <img
                    src={featuredPost.image}
                    alt={featuredPost.title}
                    className="absolute inset-0 w-full h-full object-cover opacity-80"
                  />
                )}
                <div className="absolute inset-0 bg-gradient-to-t from-gray-900/40 via-gray-900/5 to-transparent" />
                <div className="absolute inset-0 flex flex-col items-center justify-center p-6 text-center text-white">
                  <div className="text-5xl md:text-6xl mb-4">📊</div>
                  <p className="text-xs md:text-sm max-w-xs">
                    Data-backed strategies to reduce no-shows and unlock hidden
                    revenue in your clinic.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </Link>
      </Container>
    </section>
  );
}

/* -------------------- BLOG CARD -------------------- */

function BlogCard({ post }) {
  return (
    <Link
      to={`/blog/${post.slug}`}
      className="group bg-white rounded-3xl overflow-hidden shadow-lg hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 flex flex-col border border-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:ring-offset-2"
    >
      <div
        className="relative bg-gradient-to-br from-blue-50 to-cyan-50 aspect-[16/9] flex items-center justify-center overflow-hidden"
        style={{
          backgroundImage: `url(${post.image})`,   // ✅ fixed
          backgroundSize: "cover",
          backgroundPosition: "center",
        }}
      >
        <div className="absolute inset-0 bg-pattern opacity-5" />
        {/* <div className="relative text-4xl transform group-hover:scale-110 transition-transform duration-300">
          {post.trending ? "🚀" : "📝"}
        </div> */}

        {/* {post.trending && (
          <div className="absolute top-4 left-4 flex items-center gap-1 px-3 py-1 bg-gradient-to-r from-orange-500 to-amber-500 text-white text-[10px] font-bold rounded-full shadow-lg">
            <TrendingUp className="w-3 h-3" />
            TRENDING
          </div>
        )} */}
      </div>

      <div className="p-6 lg:p-8 flex flex-col flex-grow space-y-3">
        <h3 className="text-lg lg:text-xl font-bold text-gray-900 group-hover:text-blue-700 transition-colors duration-300 line-clamp-2 leading-snug">
          {post.title}
        </h3>

        <p className="text-sm text-gray-600 leading-relaxed line-clamp-3 flex-grow">
          {post.excerpt}
        </p>

        <div className="space-y-2 pt-4 border-t border-blue-100">
          <div className="flex items-center justify-between text-xs text-gray-500">
            <span className="font-medium truncate">{post.author}</span>
            <div className="flex items-center gap-1">
              <Clock className="w-3.5 h-3.5" />
              <span>{post.readTime}</span>
            </div>
          </div>
          <div className="flex items-center justify-between text-[10px] text-gray-400">
            <span>{post.date}</span>
            <div className="flex items-center text-blue-600 font-semibold text-[10px] group-hover:translate-x-1 transition-transform duration-300">
              Read more
              <ArrowRight className="ml-1 w-3.5 h-3.5" />
            </div>
          </div>
        </div>
      </div>
    </Link>
  );
}


/* -------------------- LATEST ARTICLES -------------------- */

function LatestArticles({ posts, activeCategory, searchQuery }) {
  const filteredPosts = useMemo(() => {
    const query = searchQuery.trim().toLowerCase();
    return posts.filter((post) => {
      const matchesCategory =
        activeCategory === "All Posts" ||
        post.category === activeCategory;

      if (!query) return matchesCategory;

      const haystack = (
        post.title +
        post.excerpt +
        post.author +
        post.category
      ).toLowerCase();

      return matchesCategory && haystack.includes(query);
    });
  }, [posts, activeCategory, searchQuery]);

  return (
    <section
      id="latest-articles"
      className="py-16 md:py-24 bg-blue-50/60 relative overflow-hidden"
    >
      <div className="absolute inset-0 bg-grid-blue-100/40 [mask-image:linear-gradient(0deg,transparent,white)]" />
      <Container className="relative">
        <div className="flex flex-col lg:flex-row lg:items-end justify-between gap-4 mb-10">
          <div>
            <div className="flex items-center gap-3 mb-3">
              <div className="w-1.5 h-8 bg-gradient-to-b from-blue-600 to-cyan-600 rounded-full" />
              <div>
                <span className="text-xs font-semibold text-blue-600 uppercase tracking-[0.2em]">
                  Latest Articles
                </span>
                <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mt-1">
                  Fresh Insights for Your Practice
                </h2>
              </div>
            </div>
            <p className="text-sm md:text-base text-gray-600 max-w-2xl">
              Curated, practical content for growth-focused clinics, telemedicine
              adopters, and modern veterinary teams.
            </p>
          </div>

          <div className="text-right text-xs md:text-sm text-gray-500">
            <div>
              Showing{" "}
              <span className="font-semibold text-gray-800">
                {filteredPosts.length}
              </span>{" "}
              {filteredPosts.length === 1 ? "article" : "articles"}
            </div>
            {(activeCategory !== "All Posts" || searchQuery.trim()) && (
              <div className="mt-1">
                Filters:
                {activeCategory !== "All Posts" && (
                  <span className="ml-1 px-2 py-0.5 rounded-full bg-white border border-blue-200 text-[10px] text-blue-700">
                    {activeCategory}
                  </span>
                )}
                {searchQuery.trim() && (
                  <span className="ml-1 px-2 py-0.5 rounded-full bg-white border border-blue-200 text-[10px] text-blue-700">
                    “{searchQuery.trim()}”
                  </span>
                )}
              </div>
            )}
          </div>
        </div>

        {filteredPosts.length === 0 ? (
          <div className="py-16 text-center">
            <p className="text-gray-500 mb-3">
              No articles match your search yet.
            </p>
            <p className="text-gray-400 text-sm">
              Try a different keyword or category like{" "}
              <span className="font-semibold text-blue-600">
                Practice Management
              </span>{" "}
              or{" "}
              <span className="font-semibold text-blue-600">
                Telemedicine
              </span>
              .
            </p>
          </div>
        ) : (
          <>
            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 md:gap-8">
              {filteredPosts.map((post) => (
                <BlogCard key={post.slug} post={post} />
              ))}
            </div>

            {/* <div className="text-center mt-14">
              <button
                type="button"
                className="px-8 py-3 bg-white text-gray-700 font-semibold rounded-2xl border border-blue-200 shadow-md hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200 hover:border-blue-300"
              >
                Load More Articles
              </button>
            </div> */}
          </>
        )}
      </Container>
    </section>
  );
}

/* -------------------- NEWSLETTER -------------------- */

function NewsletterSection() {
  return (
    <section className="relative py-20 md:py-24 bg-gradient-to-br from-gray-900 via-blue-950 to-cyan-900 text-white overflow-hidden">
      <div className="absolute inset-0 bg-grid-gray-800/25 [mask-image:linear-gradient(0deg,transparent,black)]" />
      <div className="absolute top-1/4 -left-32 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl" />
      <div className="absolute bottom-1/4 -right-32 w-64 h-64 bg-cyan-500/10 rounded-full blur-3xl" />

      <Container className="relative">
        <div className="max-w-4xl mx-auto text-center">
          <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur-sm border border-white/20 mb-6">
            <span className="text-xs md:text-sm font-medium text-gray-200">
              Stay Ahead
            </span>
          </div>

          <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold mb-4">
            Never Miss an{" "}
            <span className="bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent">
              Update
            </span>
          </h2>

          <p className="text-sm md:text-lg text-gray-300 mb-10 max-w-2xl mx-auto leading-relaxed">
            Get actionable playbooks on telemedicine, client retention, AI tools,
            workflow automation, and revenue growth—crafted for veterinary leaders.
          </p>

          <form
            onSubmit={(e) => e.preventDefault()}
            className="max-w-2xl mx-auto flex flex-col sm:flex-row gap-4"
          >
            <input
              type="email"
              required
              placeholder="Enter your work email"
              className="flex-1 px-6 py-4 bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-900 transition-all"
            />
            <button
              type="submit"
              className="px-8 py-4 bg-gradient-to-r from-blue-500 to-cyan-500 text-white font-semibold rounded-2xl shadow-2xl shadow-blue-500/30 hover:shadow-blue-500/40 hover:-translate-y-0.5 transition-all duration-200 whitespace-nowrap"
            >
              Subscribe Now
            </button>
          </form>

          <p className="mt-4 text-[10px] md:text-xs text-gray-500">
            Join 2,500+ veterinary professionals. No spam. Unsubscribe anytime.
          </p>
        </div>
      </Container>
    </section>
  );
}

/* -------------------- PAGE ROOT -------------------- */

export default function Blog() {
  const [activeCategory, setActiveCategory] = useState("All Posts");
  const [searchQuery, setSearchQuery] = useState("");

  const handleSearchSubmit = () => {
    const section = document.getElementById("latest-articles");
    if (section) {
      section.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  };

  return (
    <div className="min-h-screen bg-white text-gray-900">
      <Header />
      <main>
        <Hero
          searchQuery={searchQuery}
          onSearchChange={setSearchQuery}
          onSearchSubmit={handleSearchSubmit}
        />
        {/* <CategoryBar
          activeCategory={activeCategory}
          onCategoryChange={setActiveCategory}
        /> */}
        {/* <FeaturedArticle /> */}
        <LatestArticles
          posts={posts}
          activeCategory={activeCategory}
          searchQuery={searchQuery}
        />
        <NewsletterSection />
      </main>
      <Footer />
    </div>
  );
}