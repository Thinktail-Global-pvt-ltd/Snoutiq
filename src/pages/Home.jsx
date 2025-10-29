import React, { useState, useEffect, useMemo, lazy, Suspense } from "react";
import { useNavigate } from "react-router-dom";
import Header from "../components/Header";
import HeroSection from "./HeroSection";

const ChatInput = lazy(() => import("../components/ChatInput"));
const Footer = lazy(() => import("../components/Footer"));

const Home = () => {
  const navigate = useNavigate();
  const [message, setMessage] = useState("");
  const mainHeadingRef = React.useRef(null);

  useEffect(() => {
    const stored = localStorage.getItem("messageIntended");
    if (stored) setMessage(stored);
    if (mainHeadingRef.current) {
      mainHeadingRef.current.style.contentVisibility = "auto";
    }
  }, []);

  const handleSendMessage = (msg) => {
    if (msg?.trim()) {
      localStorage.setItem("messageIntended", msg);
      navigate("/register");
    }
  };

  const features = useMemo(
    () => [
      {
        icon: "✅",
        title: "24/7 Pet Care Support",
        description: "Instant answers anytime, anywhere",
      },
      {
        icon: "👩‍⚕️",
        title: "Personalized Pet Plans",
        description: "Tailored advice for your pet",
      },
      {
        icon: "🐾",
        title: "Behavior Training Tips",
        description: "Train and bond with your pet",
      },
    ],
    []
  );

  return (
    <>
      <Header />
      <HeroSection />
      <main className="min-h-screen bg-gradient-to-b from-white to-blue-50 flex flex-col">
        <div className="flex-1 flex flex-col px-4 py-8 max-w-6xl mx-auto w-full">
          {/* Hero */}
          {/* <section className="text-center py-12 md:py-20">
            <div className="inline-flex items-center justify-center mb-4 bg-blue-100 text-blue-800 rounded-full px-4 py-2 text-sm font-medium">
              🐶 AI-Powered Pet Care Assistant
            </div>
            <h1
              ref={mainHeadingRef}
              className="text-4xl sm:text-5xl md:text-6xl font-bold text-gray-900 mb-6 leading-tight min-h-[3.5rem] sm:min-h-[4rem] md:min-h-[5rem]"
            >
              SnoutIQ - Your AI Pet Companion for{" "}
              <span className="text-blue-600">Smart Pet Care</span>
            </h1>

            <p className="text-lg sm:text-xl md:text-2xl text-gray-600 max-w-2xl mx-auto mb-10 leading-relaxed">
              Intelligent pet care guidance, health advice, and training tips
              powered by advanced AI technology
            </p>

       
            <div className="max-w-xl mx-auto mb-16">
              <div className="bg-white rounded-2xl shadow-lg p-1 border border-gray-200">
                <Suspense
                  fallback={
                    <div className="bg-white rounded-2xl shadow-lg p-4 border border-gray-200 h-20">
                      <div className="animate-pulse text-gray-300">
                        Loading chat…
                      </div>
                    </div>
                  }
                >
                  <ChatInput onSendMessage={handleSendMessage} />
                </Suspense>
              </div>
              <p className="text-sm text-gray-500 mt-3">
                Ask anything about your pet's health, behavior, or training
              </p>
            </div>
          </section> */}

          <section
            className="grid grid-cols-2 md:grid-cols-4 gap-6 mb-20"
            style={{ minHeight: "200px" }}
          >
            {[
              { value: "50+", label: "Registered Vets" },
              { value: "24/7", label: "Fast, Easy, Available" },
              { value: "Under 10", label: "Min Connect" },
              { value: "AI System", label: "Check" },
            ].map((stat, i) => (
              <div
                key={i}
                className="text-center p-6 bg-[#DBEAFE] rounded-xl shadow-sm border border-gray-100 flex flex-col justify-center min-w-[80px]"
                style={{ minHeight: "160px" }}
              >
                <div className="text-3xl font-bold text-blue-600 mb-2 h-8 flex items-center justify-center">
                  {stat.value}
                </div>
                <div className="text-gray-600 h-6 flex items-center justify-center">
                  {stat.label}
                </div>
              </div>
            ))}
          </section>

          {/* Features */}
          <section className="w-full mb-20">
            <div className="text-center mb-16">
              <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                Why Pet Owners Love Us
              </h2>
              <p className="text-lg text-gray-600 max-w-2xl mx-auto">
                Advanced AI + veterinary expertise for the best pet care
              </p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
              {features.map((f, idx) => (
                <div
                  key={idx}
                  className="bg-blue-600 text-white rounded-2xl p-6 shadow-lg transform transition-all duration-300 hover:-translate-y-2"
                >
                  <div className="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mb-5 text-2xl">
                    {f.icon}
                  </div>
                  <h3 className="text-xl font-bold mb-3">{f.title}</h3>
                  <p className="text-blue-100 opacity-90">{f.description}</p>
                </div>
              ))}
            </div>
          </section>

          {/* CTA */}
          <section className="text-center py-12 px-4 bg-blue-600 rounded-2xl text-white mb-16">
            <h2 className="text-3xl md:text-4xl font-bold mb-6">
              Ready to Transform Your Pet's Life?
            </h2>
            <p className="text-lg text-blue-100 max-w-2xl mx-auto mb-8">
              Join thousands of pet owners who trust our AI assistant
            </p>
            <button
              onClick={() => navigate("/register")}
              className="bg-white text-blue-600 font-semibold py-3 px-8 rounded-full hover:bg-gray-100 transition-colors duration-300 shadow-lg"
            >
              Get Started Now
            </button>
          </section>
        </div>

        <Suspense
          fallback={
            <div className="h-40 bg-gray-50 border-t border-gray-200 animate-pulse" />
          }
        >
          <Footer />
        </Suspense>
      </main>
    </>
  );
};

export default Home;
