import React, { useState, useEffect, useMemo, lazy, Suspense } from "react";
import { useNavigate } from "react-router-dom";
import Header from "../components/Header";

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

  return (
    <>
      <Header />
      <main className="min-h-screen bg-gradient-to-b from-white to-blue-50 flex flex-col">
        <div className="flex-1 flex flex-col px-4 py-8 max-w-6xl mx-auto w-full">
          {/* Hero */}
          <section className="text-center py-12 md:py-20">
            <div className="inline-flex items-center justify-center mb-4 bg-blue-100 text-blue-800 rounded-full px-4 py-2 text-sm font-medium">
              🐶 AI-Powered Pet Care Assistant
            </div>
            <h1
              ref={mainHeadingRef}
              className="text-4xl sm:text-5xl md:text-6xl font-bold text-gray-900 mb-6"
            >
              SnoutIQ - Your AI Pet Companion for{" "}
              <span className="text-blue-600">Smart Pet Care</span>
            </h1>
            <p className="text-lg sm:text-xl md:text-2xl text-gray-600 max-w-2xl mx-auto mb-10 leading-relaxed">
              Intelligent pet care guidance, health advice, and training tips
              powered by advanced AI technology
            </p>

            {/* Chat Input */}
            <div className="max-w-xl mx-auto mb-16">
              <div className="bg-white rounded-2xl shadow-lg p-1 border border-gray-200">
                <Suspense
                  fallback={
                    <div className="p-4 text-gray-400">Loading chat...</div>
                  }
                >
                  <ChatInput onSendMessage={handleSendMessage} />
                </Suspense>
              </div>
              <p className="text-sm text-gray-500 mt-3">
                Ask anything about your pet's health, behavior, or training
              </p>
            </div>
          </section>

          {/* CTA */}
          <section className="text-center py-12 px-4 bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl text-white mb-16">
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

        <Suspense fallback={null}>
          <Footer />
        </Suspense>
      </main>
    </>
  );
};

export default Home;
