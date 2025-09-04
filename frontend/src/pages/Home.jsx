import React,{useState} from "react";
import ChatInput from "../components/ChatInput";
import FeatureCard from "../components/FeatureCard";
import { useNavigate } from "react-router-dom";
import Footer from "../components/Footer";
import Header from "../components/Header";

const Home = () => {
  const navigate = useNavigate();
  const [message, setMessage] = useState(localStorage.getItem("messageIntended") || "");

  const handleSendMessage = (message) => {
    if (message && message.trim() !== "") {
      localStorage.setItem("messageIntended", message);
      console.log("User intended message:", message);
      navigate("/register");
    }
  };

  const features = [
    {
      icon: (
        <svg
          className="w-6 h-6 text-white"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
          />
        </svg>
      ),
      title: "24/7 Pet Care Support",
      description: "Get instant answers to your pet care questions anytime, anywhere",
    },
    {
      icon: (
        <svg
          className="w-6 h-6 text-white"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
          />
        </svg>
      ),
      title: "Personalized Pet Plans",
      description: "Receive tailored advice based on your pet's unique needs",
    },
    {
      icon: (
        <svg
          className="w-6 h-6 text-white"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
          />
        </svg>
      ),
      title: "Behavior Training Tips",
      description: "Learn effective techniques to train and bond with your pet",
    },
  ];

  return (
    <>
    <Header/>
    <div className="bg-white flex flex-col justify-between mt-12">
      {/* Main Content */}
      <div className="flex flex-col px-4 py-8">
        {/* Hero Section */}
        <div className="text-center">
          <h1 className="text-3xl sm:text-4xl md:text-5xl font-bold text-gray-800 mb-4 mt-12 sm:mt-20">
            Your AI Pet Companion tttt
          </h1>
          <p className="text-base sm:text-lg md:text-xl text-[#828282] max-w-xl mx-auto mb-8 px-4 sm:px-14">
            Your AI-powered pet companion for expert care, guidance, and endless
            tail wags
          </p>

          {/* Chat Input */}
          <div className="px-4 sm:px-0">
            <ChatInput onSendMessage={handleSendMessage} />
          </div>
        </div>
      </div>

      {/* Feature Cards */}
      <div className="w-full px-4 mb-36">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6 w-full mt-20 sm:mt-40">
          {features.map((feature, index) => (
            <FeatureCard
              key={index}
              icon={feature.icon}
              title={feature.title}
              description={feature.description}
            />
          ))}
        </div>
      </div>

      <Footer />
    </div>
    </>
  );
};

export default Home;
