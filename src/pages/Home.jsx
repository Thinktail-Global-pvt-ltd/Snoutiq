import React, { useState, useEffect, useMemo, lazy, Suspense } from "react";
import { useNavigate } from "react-router-dom";
import Header from "../components/Header";
import "./DiwaliHome.css";

// Lazy load components
const ChatInput = lazy(() => import("../components/ChatInput"));
const Footer = lazy(() => import("../components/Footer"));
const StatsSection = lazy(() => import("./StatsSection"));

const Home = () => {
  const navigate = useNavigate();
  const [message, setMessage] = useState("");
  const [isVisible, setIsVisible] = useState(false);

  useEffect(() => {
    const stored = localStorage.getItem("messageIntended");
    if (stored) setMessage(stored);
    
    // Trigger animations
    setTimeout(() => setIsVisible(true), 100);
  }, []);

  const handleSendMessage = (msg) => {
    if (msg?.trim()) {
      localStorage.setItem("messageIntended", msg);
      navigate("/register");
    }
  };

  // Diwali-themed features
  const features = useMemo(
    () => [
      {
        icon: "🪔",
        title: "24/7 Diwali Care",
        description: "Round-the-clock pet safety support during festivities",
        gradient: "from-orange-400 via-red-400 to-pink-500"
      },
      {
        icon: "🎆",
        title: "Firework Anxiety",
        description: "Expert tips to keep pets calm during celebrations",
        gradient: "from-yellow-400 via-orange-400 to-red-500"
      },
      {
        icon: "🥮",
        title: "Festive Diet Guide",
        description: "Safe treats and foods for your furry friends",
        gradient: "from-pink-400 via-purple-400 to-indigo-500"
      },
    ],
    []
  );

  // Firework particles
  const Fireworks = () => (
    <div className="fireworks-container">
      {[...Array(15)].map((_, i) => (
        <div key={i} className={`firework firework-${i % 5}`} />
      ))}
    </div>
  );

  // Floating diyas animation
  const FloatingDiyas = () => (
    <div className="floating-diyas">
      {[...Array(8)].map((_, i) => (
        <div key={i} className={`diya diya-${i + 1}`}>🪔</div>
      ))}
    </div>
  );

  return (
    <>
      <Header />
      <Fireworks />
      <FloatingDiyas />
      
      <main className="diwali-home min-h-screen bg-gradient-to-b from-orange-50 via-yellow-50 to-red-50 flex flex-col overflow-hidden">
        <div className="flex-1 flex flex-col px-4 py-8 max-w-6xl mx-auto w-full relative z-10">
          {/* Hero Section with Diwali Theme */}
          <section className="text-center py-8 md:py-16 relative">
            <div className="relative">
              {/* Rangoli pattern background */}
              <div className="rangoli-bg"></div>
              
              <div className={`fade-in-up ${isVisible ? 'animate-in' : ''} inline-flex items-center justify-center mb-4 bg-gradient-to-r from-orange-500 via-yellow-400 to-pink-500 text-white rounded-full px-5 py-2 text-xs md:text-sm font-semibold shadow-lg`}>
                <span className="animate-pulse mr-2">🪔</span>
                Diwali Special Offer
                <span className="animate-pulse ml-2">🪔</span>
              </div>
              
              <h1 className={`text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-extrabold mb-4 fade-in-up ${isVisible ? 'animate-in' : ''}`} style={{animationDelay: '0.15s'}}>
                <span className="diwali-gradient-text">SnoutIQ</span>
                <br />
                <span className="text-gray-800">Pet Care This</span>{" "}
                <span className="text-transparent bg-clip-text bg-gradient-to-r from-orange-500 via-red-500 to-pink-500">Diwali</span>
              </h1>
              
              <p className={`text-base sm:text-lg md:text-xl text-gray-600 max-w-2xl mx-auto mb-8 leading-relaxed fade-in-up ${isVisible ? 'animate-in' : ''}`} style={{animationDelay: '0.3s'}}>
                AI-powered pet safety during festivals 🎆
              </p>

              {/* HERO CHAT INPUT - Main Focus */}
              <div className={`max-w-2xl mx-auto mb-12 fade-in-up ${isVisible ? 'animate-in' : ''}`} style={{animationDelay: '0.45s'}}>
                {/* Glowing Border Effect */}
                <div className="hero-chat-spotlight relative">
                  <div className="absolute -inset-1 bg-gradient-to-r from-orange-400 via-red-400 to-pink-500 rounded-3xl blur opacity-75 animate-pulse"></div>
                  
                  <div className="relative bg-white rounded-3xl shadow-2xl p-2 border-4 border-transparent bg-clip-padding backdrop-blur-sm transform transition-all duration-500 hover:scale-[1.02]">
                    {/* Decorative Diya Icons */}
                    <div className="absolute -top-4 -left-4 text-3xl animate-bounce">🪔</div>
                    <div className="absolute -top-4 -right-4 text-3xl animate-bounce" style={{animationDelay: '0.3s'}}>🪔</div>
                    
                    <Suspense
                      fallback={
                        <div className="p-6 text-gray-400 text-center min-h-[80px] flex items-center justify-center">
                          <div className="diya-loader text-4xl">🪔</div>
                        </div>
                      }
                    >
                      <div className="bg-gradient-to-br from-orange-50 to-pink-50 rounded-2xl p-1">
                        <ChatInput onSendMessage={handleSendMessage} />
                      </div>
                    </Suspense>
                    
                    {/* Sparkle Effects */}
                    <div className="absolute top-0 right-0 w-full h-full pointer-events-none overflow-hidden rounded-3xl">
                      <span className="sparkle sparkle-1">✨</span>
                      <span className="sparkle sparkle-2">✨</span>
                      <span className="sparkle sparkle-3">✨</span>
                    </div>
                  </div>
                </div>
                
                <div className="mt-4 flex items-center justify-center gap-4 text-xs md:text-sm text-gray-600">
                  <span className="flex items-center gap-1">
                    <span className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    24/7 Available
                  </span>
                  <span className="text-gray-300">•</span>
                  <span className="flex items-center gap-1">
                    ⚡ Instant Response
                  </span>
                  <span className="text-gray-300">•</span>
                  <span className="flex items-center gap-1">
                    🎯 AI-Powered
                  </span>
                </div>
                
                <p className="text-sm md:text-base text-gray-700 mt-3 font-medium px-4">
                  💬 Ask: "How to keep my pet calm during Diwali fireworks?"
                </p>
              </div>
            </div>
          </section>
           
          {/* Stats Section */}
          <Suspense
            fallback={
              <div className="mb-20 min-h-[200px] flex items-center justify-center">
                <div className="diwali-loading text-orange-600 font-semibold">
                  Loading festive statistics...
                </div>
              </div>
            }
          >
            <StatsSection />
          </Suspense>

          {/* Diwali Features */}
          <section className="w-full mb-20 relative">
            <div className="text-center mb-12">
              <h2 className="text-2xl md:text-3xl lg:text-4xl font-extrabold mb-3">
                <span className="text-gray-800">Festive</span>{" "}
                <span className="diwali-gradient-text">Pet Care</span>{" "}
                <span className="text-gray-800">Features</span>
              </h2>
              <p className="text-sm md:text-lg text-gray-600 max-w-2xl mx-auto">
                Everything you need for a safe & joyful Diwali with your pets 🐾
              </p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
              {features.map((f, idx) => (
                <div
                  key={idx}
                  className={`diwali-feature-card bg-gradient-to-br ${f.gradient} text-white rounded-2xl p-6 md:p-8 shadow-2xl transform transition-all duration-500 hover:-translate-y-3 hover:rotate-1 cursor-pointer group`}
                  style={{animationDelay: `${0.6 + idx * 0.15}s`}}
                >
                  <div className="w-16 h-16 md:w-20 md:h-20 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center mb-5 text-3xl md:text-4xl group-hover:scale-110 transition-transform duration-300">
                    <span className="animate-pulse">{f.icon}</span>
                  </div>
                  <h3 className="text-xl md:text-2xl font-bold mb-3">{f.title}</h3>
                  <p className="text-white text-opacity-90 text-sm md:text-base">{f.description}</p>
                  
                  {/* Decorative corner */}
                  <div className="absolute top-4 right-4 text-white text-opacity-20 text-2xl">✨</div>
                </div>
              ))}
            </div>
          </section>

          {/* Trust Badges */}
          <section className="mb-16 relative">
            <div className="bg-white rounded-2xl shadow-xl p-8 border-2 border-orange-100">
              <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div className="text-center">
                  <div className="text-3xl mb-2">🏆</div>
                  <div className="text-2xl font-bold text-orange-600">50K+</div>
                  <div className="text-sm text-gray-600">Happy Pet Parents</div>
                </div>
                <div className="text-center">
                  <div className="text-3xl mb-2">⚡</div>
                  <div className="text-2xl font-bold text-orange-600">&lt;1min</div>
                  <div className="text-sm text-gray-600">Response Time</div>
                </div>
                <div className="text-center">
                  <div className="text-3xl mb-2">🎯</div>
                  <div className="text-2xl font-bold text-orange-600">98%</div>
                  <div className="text-sm text-gray-600">Accuracy Rate</div>
                </div>
                <div className="text-center">
                  <div className="text-3xl mb-2">🔒</div>
                  <div className="text-2xl font-bold text-orange-600">100%</div>
                  <div className="text-sm text-gray-600">Secure & Private</div>
                </div>
              </div>
            </div>
          </section>

          {/* Diwali CTA Section */}
          <section className="text-center py-12 md:py-16 px-4 bg-gradient-to-r from-orange-500 via-yellow-500 to-red-500 rounded-3xl text-white mb-16 relative overflow-hidden shadow-2xl">
            <div className="sparkle-overlay"></div>
            <div className="relative z-10">
              <div className="inline-block mb-4">
                <div className="flex items-center gap-2 text-5xl md:text-6xl animate-bounce">
                  🪔 🎆 🪔
                </div>
              </div>
              <h2 className="text-2xl md:text-3xl lg:text-4xl font-extrabold mb-4">
                Celebrate Diwali with <span className="text-yellow-200">Happy Pets</span>!
              </h2>
              <p className="text-base md:text-lg text-orange-100 max-w-2xl mx-auto mb-8">
                Get personalized pet care guidance for a stress-free festive season
              </p>
              <button
                onClick={() => navigate("/register")}
                className="diwali-cta-btn bg-white text-orange-600 font-bold py-4 px-8 md:px-12 rounded-full hover:bg-gray-50 transition-all duration-300 shadow-2xl transform hover:scale-110 text-base md:text-lg"
              >
                🪔 Start Your Free Consultation
              </button>
              <p className="text-xs md:text-sm text-orange-100 mt-4 opacity-80">
                No credit card required • Instant access • AI-powered insights
              </p>
            </div>
          </section>
        </div>

        <Suspense fallback={null}>
          <Footer />
        </Suspense>
      </main>
    </>
  );
};

export default Home;