import React, { useEffect, useRef } from "react";
import { Link } from "react-router-dom";
import { gsap } from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
import Header from './Header';
import headerlogo from '../assets/images/Snoutiq-mascot.png';
import Footer from "./Footer";

// Register ScrollTrigger plugin
gsap.registerPlugin(ScrollTrigger);

export default function Home() {

  // Refs for GSAP animations
  const heroRef = useRef(null);
  const problemSectionRef = useRef(null);
  const solutionSectionRef = useRef(null);
  const offerSectionRef = useRef(null);
  const howItWorksRef = useRef(null);
  const marketSectionRef = useRef(null);
  const testimonialsRef = useRef(null);
  const scarcityRef = useRef(null);
  const faqRef = useRef(null);

  // GSAP Animations
  useEffect(() => {
    // Hero section
    if (heroRef.current) {
      gsap.fromTo(heroRef.current,
        { opacity: 0, y: 50 },
        {
          opacity: 1,
          y: 0,
          duration: 1.2,
          scrollTrigger: {
            trigger: heroRef.current,
            start: "top 80%",
            toggleActions: "play none none none"
          }
        }
      );
    }

    // Problem section
    if (problemSectionRef.current) {
      gsap.fromTo(problemSectionRef.current.querySelectorAll('.problem-card'),
        { opacity: 0, x: -50 },
        {
          opacity: 1,
          x: 0,
          duration: 0.8,
          stagger: 0.2,
          scrollTrigger: {
            trigger: problemSectionRef.current,
            start: "top 80%",
            toggleActions: "play none none none"
          }
        }
      );
    }

    // Solution section
    if (solutionSectionRef.current) {
      gsap.fromTo(solutionSectionRef.current.querySelectorAll('.solution-card'),
        { opacity: 0, y: 50 },
        {
          opacity: 1,
          y: 0,
          duration: 0.8,
          stagger: 0.2,
          scrollTrigger: {
            trigger: solutionSectionRef.current,
            start: "top 80%",
            toggleActions: "play none none none"
          }
        }
      );
    }

    // Offer section
    if (offerSectionRef.current) {
      gsap.fromTo(offerSectionRef.current.querySelectorAll('.offer-card'),
        { opacity: 0, scale: 0.8 },
        {
          opacity: 1,
          scale: 1,
          duration: 0.8,
          stagger: 0.15,
          scrollTrigger: {
            trigger: offerSectionRef.current,
            start: "top 80%",
            toggleActions: "play none none none"
          }
        }
      );
    }

    // How it works section
    if (howItWorksRef.current) {
      gsap.fromTo(howItWorksRef.current.querySelectorAll('.step-card'),
        { opacity: 0, rotationY: 90 },
        {
          opacity: 1,
          rotationY: 0,
          duration: 0.8,
          stagger: 0.2,
          scrollTrigger: {
            trigger: howItWorksRef.current,
            start: "top 80%",
            toggleActions: "play none none none"
          }
        }
      );
    }

    // Market section
    if (marketSectionRef.current) {
      gsap.fromTo(marketSectionRef.current.querySelectorAll('div > div'),
        { opacity: 0, x: 100 },
        {
          opacity: 1,
          x: 0,
          duration: 0.8,
          stagger: 0.2,
          scrollTrigger: {
            trigger: marketSectionRef.current,
            start: "top 80%",
            toggleActions: "play none none none"
          }
        }
      );
    }

    // Testimonials section
    if (testimonialsRef.current) {
      gsap.fromTo(testimonialsRef.current.querySelectorAll('.testimonial-card'),
        { opacity: 0, y: 50 },
        {
          opacity: 1,
          y: 0,
          duration: 0.8,
          stagger: 0.2,
          scrollTrigger: {
            trigger: testimonialsRef.current,
            start: "top 80%",
            toggleActions: "play none none none"
          }
        }
      );
    }

    // Scarcity section
    if (scarcityRef.current) {
      gsap.fromTo(scarcityRef.current,
        { opacity: 0, scale: 0.9 },
        {
          opacity: 1,
          scale: 1,
          duration: 1,
          scrollTrigger: {
            trigger: scarcityRef.current,
            start: "top 80%",
            toggleActions: "play none none none"
          }
        }
      );
    }

    // FAQ section
    if (faqRef.current) {
      gsap.fromTo(faqRef.current.querySelectorAll('div > div'),
        { opacity: 0, x: -50 },
        {
          opacity: 1,
          x: 0,
          duration: 0.6,
          stagger: 0.1,
          scrollTrigger: {
            trigger: faqRef.current,
            start: "top 80%",
            toggleActions: "play none none none"
          }
        }
      );
    }

    // Cleanup
    return () => {
      ScrollTrigger.getAll().forEach(trigger => trigger.kill());
    };
  }, []);
  return (
    <>
      <Header />
      <div className="font-sans antialiased text-gray-800 overflow-hidden">
        {/* Hero Section */}
        <section className="relative bg-[#FFF8F5] py-20">
          <div className="container mx-auto px-6 lg:px-16 flex flex-col-reverse lg:flex-row items-center gap-12">
            <div className="flex-1 text-left">
              <h1 className="text-3xl md:text-5xl font-bold mb-6 leading-snug text-[#3E2723]">
                🐾 Get Your First 10 Clients Absolutely FREE!
              </h1>
              <p className="text-lg md:text-2xl mb-8 max-w-xl text-[#4E342E]">
                Join Snoutiq as a Founding Partner and Launch Your Pet Grooming Business
                to New Heights with ZERO Risk.
              </p>
              <a
                href="/frontend/files/contact us"
                className="inline-block bg-[#3E2723] text-white font-bold text-lg px-8 py-4 rounded-lg hover:bg-[#4E342E] transition-all duration-300 shadow-lg hover:shadow-xl"
              >
                Claim My FREE Clients Now!
              </a>
              <p className="mt-6 text-[#3E2723] flex items-center">
                <span className="inline-block bg-yellow-400 text-[#3E2723] px-3 py-1 rounded-full text-sm font-semibold mr-2">
                  ⚡
                </span>
                Only 25 Founding Partner Spots Available - 18 Already Claimed!
              </p>
            </div>

            {/* Right Image Section */}
            <div className="flex-1">
              <img
                src={headerlogo}
                alt="Pet Grooming"
                className="w-full h-auto rounded-2xl shadow-xl object-cover"
              />
            </div>

          </div>
        </section>


        {/* Problem Section */}
        <section ref={problemSectionRef} className="py-20 bg-white">
          <div className="container mx-auto px-4 max-w-6xl">
            <h2 className="text-4xl md:text-5xl font-bold text-center mb-8 text-gray-800">
              Are You Tired of the Endless Struggle to Find Clients?
            </h2>
            <p className="text-xl text-center text-gray-600 mb-16 max-w-3xl mx-auto">
              Every day, talented pet groomers face the same frustrating challenges...
            </p>

            <div className="grid md:grid-cols-2 gap-8">
              {[
                { icon: "💸", title: "Wasted Ad Spend", desc: "You're pouring thousands into Facebook ads, Google ads, and local marketing that barely brings in quality leads." },
                { icon: "📉", title: "Inconsistent Bookings", desc: "One week you're swamped, the next week you're sitting idle. The feast-or-famine cycle is killing your cash flow." },
                { icon: "🎯", title: "Wrong Customers", desc: "When leads do come in, they're price shoppers, no-shows, or people who don't value quality grooming." },
                { icon: "⏰", title: "Time Drain", desc: "You're spending more time marketing than grooming. You became a groomer to work with pets, not to be a full-time marketer." }
              ].map((item, index) => (
                <div key={index} className="problem-card bg-gradient-to-br from-gray-50 to-white p-8 rounded-2xl border border-gray-100 shadow-lg hover:shadow-xl transition-shadow duration-300">
                  <div className="text-4xl mb-4">{item.icon}</div>
                  <h4 className="text-xl font-semibold mb-3 text-gray-800">{item.title}</h4>
                  <p className="text-gray-600">{item.desc}</p>
                </div>
              ))}
            </div>

            <div className="mt-20 bg-orange-50 border border-orange-200 rounded-2xl p-10 text-center transform hover:scale-105 transition-transform duration-300">
              <h3 className="text-2xl font-bold text-orange-700 mb-4">
                What if there was a way to get premium clients delivered to you automatically?
              </h3>
              <p className="text-lg text-orange-600">
                Imagine never worrying about your next booking again...
              </p>
            </div>
          </div>
        </section>

        {/* Solution Section */}
        <section ref={solutionSectionRef} className="py-20 bg-gradient-to-br from-blue-50 to-indigo-50">
          <div className="container mx-auto px-4 max-w-6xl">
            <h2 className="text-4xl md:text-5xl font-bold text-center mb-8 text-gray-800">
              Introducing Snoutiq: The Game-Changing Pet Grooming Marketplace
            </h2>
            <p className="text-xl text-center text-gray-600 mb-16 max-w-3xl mx-auto">
              We're building the premier platform that connects premium pet owners with top-tier groomers like you.
            </p>

            <div className="grid md:grid-cols-3 gap-8">
              {[
                { icon: "🎯", title: "Pre-Qualified Clients", desc: "Every client values quality and is ready to pay premium prices for expert grooming services." },
                { icon: "📱", title: "Seamless Booking System", desc: "Automated scheduling, payments, and customer management. Focus on grooming, we handle the rest." },
                { icon: "🚀", title: "Built for Growth", desc: "Our platform is designed to scale your business systematically with tools and insights that drive success." }
              ].map((item, index) => (
                <div key={index} className="solution-card bg-white p-8 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2">
                  <div className="text-5xl mb-6">{item.icon}</div>
                  <h3 className="text-xl font-semibold mb-4 text-gray-800">{item.title}</h3>
                  <p className="text-gray-600">{item.desc}</p>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* Market Section */}
        <section ref={marketSectionRef} className="py-20 bg-[#FFF8F5] text-[#3E2723]">
          <div className="container mx-auto px-4 max-w-6xl">
            <h2 className="text-4xl md:text-5xl font-bold text-center mb-16">
              Why Snoutiq Will Dominate the Pet Care Market
            </h2>

            <div className="grid md:grid-cols-2 gap-12">
              {[
                { icon: "🐾", title: "Market-Leading Technology", desc: "Built by tech experts who understand both the pet industry and digital marketing." },
                { icon: "🐾", title: "Premium Client Base", desc: "We're targeting affluent pet owners who view their pets as family members." },
                { icon: "🐾", title: "Comprehensive Support", desc: "From business coaching to technical support, we provide everything you need." },
                { icon: "🐾", title: "Data-Driven Growth", desc: "Advanced analytics show you exactly what's working for maximum profitability." }
              ].map((item, index) => (
                <div key={index} className="flex items-start">
                  <span className="text-3xl mr-4">{item.icon}</span>
                  <div>
                    <h3 className="text-xl font-semibold mb-4">{item.title}</h3>
                    <p className="opacity-90">{item.desc}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* Offer Section */}
        <section ref={offerSectionRef} className="py-20 bg-white">
          <div className="container mx-auto px-4 max-w-6xl">
            <h2 className="text-4xl md:text-5xl font-bold text-center mb-8 text-gray-800">
              The Exclusive Founding Partner Program
            </h2>
            <p className="text-xl text-center text-gray-600 mb-16 max-w-3xl mx-auto">
              We're looking for 25 exceptional groomers to partner with us from day one. Here's what you get:
            </p>

            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
              {[
                { icon: "🎁", title: "First 10 Clients FREE", value: "₹25,000+", desc: "We guarantee to deliver your first 10 premium clients at zero cost to you." },
                {
                  icon: "💎",
                  title: (
                    <>
                      <span className="line-through text-red-500">6 Months</span>{" "}
                      <span className="text-green-600 font-bold">Lifetime FREE</span> Premium Access
                    </>
                  ),
                  value: "₹23,994",
                  desc: "Full platform access, premium listing, and all pro features completely free."
                },
                {
                  icon: "🔒",
                  title: (
                    <>
                    <span>Founder's Rate</span>
                      {/* <span className="line-through text-red-500"> Locked FOREVER</span>{" "} */}
                      <span className="text-green-600 font-bold"> Commision 15%</span> Locked FOREVER
                    </>
                  ),
                  value: "Save: ₹36,000/Year",
                  desc: "After your free period, pay just ₹999/month (regular price ₹3,999/month)."
                },

                // { icon: "🔒", title: "Founder's Rate Locked FOREVER", value: "Save: ₹36,000/Year", desc: "After your free period, pay just ₹999/month (regular price ₹3,999/month)." },
                { icon: "⭐", title: "Priority Placement & Founder Badge", value: "Priceless Visibility", desc: "Top search results, special Founding Partner badge for your first year." },
                { icon: "🗣️", title: "Shape Snoutiq's Future", value: "Direct Influence", desc: "Your feedback directly influences our platform development." },
                { icon: "🛡️", title: "Zero Risk Guarantee", value: "100% Risk-Free", desc: "Don't see value? Walk away with no obligations. We're that confident." }
              ].map((item, index) => (
                <div key={index} className="offer-card bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-2xl border border-blue-200 shadow-md hover:shadow-lg transition-all duration-300">
                  <div className="text-4xl mb-4">{item.icon}</div>
                  <h4 className="text-lg font-semibold mb-2 text-blue-800">{item.title}</h4>
                  <div className="text-blue-700 font-bold mb-3">{item.value}</div>
                  <p className="text-gray-700 text-sm">{item.desc}</p>
                </div>
              ))}
            </div>

            <div className="mt-20 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-2xl p-10 text-center shadow-2xl transform hover:scale-105 transition-transform duration-300">
              <h3 className="text-3xl font-bold mb-4">
                Total Founding Partner Value: Over ₹85,000
              </h3>
              <p className="text-xl mb-8">
                But you get it all FREE when you join as a Founding Partner
              </p>
              <div>
                <Link
                  to="/contact us"
                  className="inline-block bg-white text-green-600 font-bold text-lg px-10 py-4 rounded-full hover:bg-green-50 transition-colors duration-300 shadow-lg"
                >
                  Secure My Founding Partner Spot!
                </Link>
              </div>
            </div>
          </div>
        </section>

        {/* How It Works */}
        <section ref={howItWorksRef} className="py-20 bg-gradient-to-br from-purple-50 to-pink-50">
          <div className="container mx-auto px-4 max-w-6xl">
            <h2 className="text-4xl md:text-5xl font-bold text-center mb-8 text-gray-800">
              How to Become a Founding Partner
            </h2>
            <p className="text-xl text-center text-gray-600 mb-16 max-w-3xl mx-auto">
              It's incredibly simple to get started:
            </p>

            <div className="grid md:grid-cols-4 gap-8">
              {[
                { step: "1", title: "Fill Out the Form", desc: "Share your basic business details and best time to connect. Takes less than 2 minutes." },
                { step: "2", title: "Quick Qualification Call", desc: "We'll have a brief chat to ensure mutual fit and answer any questions." },
                { step: "3", title: "Platform Onboarding", desc: "We'll help you set up your premium profile and get ready for clients." },
                { step: "4", title: "Start Receiving Clients", desc: "Watch as pre-qualified, premium clients start booking automatically!" }
              ].map((item, index) => (
                <div key={index} className="step-card bg-white p-8 rounded-2xl shadow-lg text-center hover:shadow-xl transition-all duration-300">
                  <div className="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-full flex items-center justify-center mx-auto text-2xl font-bold mb-6">
                    {item.step}
                  </div>
                  <h4 className="text-lg font-semibold mb-4 text-gray-800">{item.title}</h4>
                  <p className="text-gray-600 text-sm">{item.desc}</p>
                </div>
              ))}
            </div>
          </div>
        </section>


        {/* Testimonials */}
        {/* <section ref={testimonialsRef} className="py-20 bg-white">
          <div className="container mx-auto px-4 max-w-6xl">
            <h2 className="text-4xl md:text-5xl font-bold text-center mb-16 text-gray-800">
              What Future Founding Partners Are Saying
            </h2>

            <div className="grid md:grid-cols-3 gap-8">
              {[
                { text: "I've been struggling to find consistent clients for months. The Snoutiq opportunity sounds exactly like what I need to finally scale my business properly.", author: "Sarah K., Pet Groomer" },
                { text: "The Founding Partner benefits are incredible. Getting 10 free clients plus the locked-in rate forever? This could completely change my business trajectory.", author: "Raj M., Mobile Grooming Service" },
                { text: "Finally, a platform that understands what professional groomers need. The zero-risk guarantee gives me complete confidence to try this.", author: "Priya S., Luxury Pet Spa Owner" }
              ].map((testimonial, index) => (
                <div key={index} className="testimonial-card bg-gradient-to-br from-gray-50 to-white p-8 rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300">
                  <p className="text-gray-600 italic mb-6">"{testimonial.text}"</p>
                  <div className="text-blue-600 font-semibold">- {testimonial.author}</div>
                </div>
              ))}
            </div>
          </div>
        </section> */}

        {/* Scarcity Section */}
        <section ref={scarcityRef} className="py-20 bg-gradient-to-r from-red-500 to-red-600 text-white">
          <div className="container mx-auto px-4 max-w-6xl text-center">
            <h2 className="text-4xl md:text-5xl font-bold mb-8">
              ⚠️ Only 13 Founding Partner Spots Remaining!
            </h2>
            <p className="text-xl mb-12 max-w-3xl mx-auto">
              Applications are pouring in from top groomers. Don't miss your chance to be part of this exclusive program.
            </p>

            {/* <div className="bg-red-400 bg-opacity-20 p-10 rounded-2xl mb-12 max-w-4xl mx-auto">
              <h3 className="text-2xl font-semibold mb-8">What Happens When All 25 Spots Are Filled?</h3>
              <ul className="text-left space-y-4 max-w-3xl mx-auto">
                {[
                  "The free 10 clients offer disappears forever",
                  "The ₹999/month founder's rate becomes unavailable",
                  "New groomers pay full price (₹3,999/month) with no guarantees",
                  "Priority placement goes to Founding Partners only",
                  "You'll have to compete with 25 established partners for clients"
                ].map((item, index) => (
                  <li key={index} className="flex items-start text-lg">
                    <span className="mr-3">•</span> {item}
                  </li>
                ))}
              </ul>
            </div> */}

            <Link
              to="/contact us"
              className="inline-block bg-white text-red-600 font-bold text-xl px-12 py-5 rounded-full hover:bg-gray-100 transition-colors duration-300 shadow-2xl transform hover:-translate-y-1"
            >
              Don't Miss Out - Apply Now!
            </Link>
          </div>
        </section>

        {/* FAQ Section */}
        <section ref={faqRef} className="py-20 bg-gray-50">
          <div className="container mx-auto px-4 max-w-4xl">
            <h2 className="text-4xl md:text-5xl font-bold text-center mb-16 text-gray-800">
              Frequently Asked Questions
            </h2>

            <div className="space-y-8">
              {[
                {
                  q: "What is the Snoutiq Founding Partner Program?",
                  a: "The Founding Partner Program is an exclusive, limited-time opportunity for the first 20 veterinarians in Gurgaon to join our platform with special benefits. These include lifetime free access, a special founder's rate on commissions, and an early-mover badge that provides enhanced visibility."
                },
                {
                  q: "How does Snoutiq help me get new clients?",
                  a: "Snoutiq's AI platform proactively matches your services with nearby pet parents who are actively seeking professional veterinary care. This eliminates your marketing costs and provides you with pre-qualified clients who are ready to book a consultation."
                },
                {
                  q: "How quickly will I start receiving clients?",
                  a: "After you fill out the form, we'll have a quick qualification call. Once you're onboarded and your profile is set up, you can expect to start receiving your first clients within 7-14 days."
                },
                {
                  q: "What does the Lifetime Free Access offer mean?",
                  a: "As a founding partner, you will never pay any platform fees or subscription charges. You'll have complete access to our features and dashboard for life, at no cost."
                },
                {
                  q: "How much does it cost to use Snoutiq?",
                  a: "As a founding partner, you'll benefit from a special commission rate of only 15% per consultation. This is our lowest rate, ensuring you keep more of your earnings. This rate applies only when you successfully complete a paid consultation. There are no upfront fees or hidden charges."
                },
                {
                  q: "What is the Zero Risk guarantee? ",
                  a: "We are confident in our ability to help you grow. If you don't receive any clients within the first 90 days of joining, you can cancel your partnership with zero obligations."
                },
                {
                  q: "How do you ensure the quality of clients?",
                  a: "Our platform finds and connects you with pet parents who value high-quality veterinary care. The system helps to ensure they are serious about their pet's health and are willing to pay for your expertise."
                },
                {
                  q: "I’m not in Gurgaon, can I still join?",
                  a: "Currently, the Founding Partner Program is focused on veterinarians in Gurgaon. However, we are expanding to new cities soon. Please contact us to stay updated on our launch plans."
                },
                 {
                  q: "How long does the onboarding process take?",
                  a: "After you fill out the form, we'll have a quick qualification call to ensure a good fit. We then help you set up your profile, and you can start receiving clients within 7-14 days."
                }
              ].map((faq, index) => (
                <div key={index} className="bg-white p-8 rounded-2xl shadow-md hover:shadow-lg transition-shadow duration-300">
                  <h3 className="text-xl font-semibold mb-4 text-blue-700">{faq.q}</h3>
                  <p className="text-gray-600">{faq.a}</p>
                </div>
              ))}
            </div>
          </div>
        </section>
      </div>
      <Footer />
    </>
  );
}