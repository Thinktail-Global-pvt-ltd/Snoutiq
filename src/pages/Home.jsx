// import React,{useState} from "react";
// import ChatInput from "../components/ChatInput";
// import FeatureCard from "../components/FeatureCard";
// import { useNavigate } from "react-router-dom";
// import Footer from "../components/Footer";
// import Header from "../components/Header";

// const Home = () => {
//   const navigate = useNavigate();
//   const [message, setMessage] = useState(localStorage.getItem("messageIntended") || "");

//   const handleSendMessage = (message) => {
//     if (message && message.trim() !== "") {
//       localStorage.setItem("messageIntended", message);
//       console.log("User intended message:", message);
//       navigate("/register");
//     }
//   };

//   const features = [
//     {
//       icon: (
//         <svg
//           className="w-6 h-6 text-white"
//           fill="none"
//           stroke="currentColor"
//           viewBox="0 0 24 24"
//         >
//           <path
//             strokeLinecap="round"
//             strokeLinejoin="round"
//             strokeWidth={2}
//             d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
//           />
//         </svg>
//       ),
//       title: "24/7 Pet Care Support",
//       description: "Get instant answers to your pet care questions anytime, anywhere",
//     },
//     {
//       icon: (
//         <svg
//           className="w-6 h-6 text-white"
//           fill="none"
//           stroke="currentColor"
//           viewBox="0 0 24 24"
//         >
//           <path
//             strokeLinecap="round"
//             strokeLinejoin="round"
//             strokeWidth={2}
//             d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
//           />
//         </svg>
//       ),
//       title: "Personalized Pet Plans",
//       description: "Receive tailored advice based on your pet's unique needs",
//     },
//     {
//       icon: (
//         <svg
//           className="w-6 h-6 text-white"
//           fill="none"
//           stroke="currentColor"
//           viewBox="0 0 24 24"
//         >
//           <path
//             strokeLinecap="round"
//             strokeLinejoin="round"
//             strokeWidth={2}
//             d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
//           />
//         </svg>
//       ),
//       title: "Behavior Training Tips",
//       description: "Learn effective techniques to train and bond with your pet",
//     },
//   ];

//   return (
//     <>
//     <Header/>
//     <div className="bg-white flex flex-col justify-between mt-12">
//       {/* Main Content */}
//       <div className="flex flex-col px-4 py-8">
//         {/* Hero Section */}
//         <div className="text-center">
//           <h1 className="text-3xl sm:text-4xl md:text-5xl font-bold text-gray-800 mb-4 mt-12 sm:mt-20">
//             Your AI Pet Companion
//           </h1>
//           <p className="text-base sm:text-lg md:text-xl text-[#828282] max-w-xl mx-auto mb-8 px-4 sm:px-14">
//             Your AI-powered pet companion for expert care, guidance, and endless
//             tail wags
//           </p>

//           {/* Chat Input */}
//           <div className="px-4 sm:px-0">
//             <ChatInput onSendMessage={handleSendMessage} />
//           </div>
//         </div>
//       </div>

//       {/* Feature Cards */}
//       <div className="w-full px-4 mb-36">
//         <div className="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6 w-full mt-20 sm:mt-40">
//           {features.map((feature, index) => (
//             <FeatureCard
//               key={index}
//               icon={feature.icon}
//               title={feature.title}
//               description={feature.description}
//             />
//           ))}
//         </div>
//       </div>

//       <Footer />
//     </div>
//     </>
//   );
// };

// export default Home;

import React, { useState } from "react";
import { useNavigate } from "react-router-dom";
import ChatInput from "../components/ChatInput";
import FeatureCard from "../components/FeatureCard";
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
          className="w-8 h-8 text-white"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          xmlns="http://www.w3.org/2000/svg"
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
          className="w-8 h-8 text-white"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
          />
        </svg>
      ),
      title: "Personalized Pet Plans",
      description: "Receive tailored advice based on your pet's unique needs",
    },
    {
      icon: (
        <svg
          className="w-8 h-8 text-white"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
          />
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
      ),
      title: "Behavior Training Tips",
      description: "Learn effective techniques to train and bond with your pet",
    },
  ];

  return (
    <>
      <Header />
      <div className="min-h-screen bg-gradient-to-b from-white to-blue-50 flex flex-col">
        {/* Main Content */}
        <div className="flex-1 flex flex-col px-4 py-8 max-w-6xl mx-auto w-full">
          {/* Hero Section */}
          <div className="text-center py-12 md:py-20">
            <div className="inline-flex items-center justify-center mb-4 bg-blue-100 text-blue-800 rounded-full px-4 py-2 text-sm font-medium">
              <svg className="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                <path fillRule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clipRule="evenodd" />
              </svg>
              AI-Powered Pet Care Assistant
            </div>
            
            <h1 className="text-3xl sm:text-5xl md:text-6xl font-bold text-gray-900 mb-6">
              SnoutIQ - Your AI Pet Companion for 
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600"> Smart Pet Care</span>
            </h1>
            
            <p className="text-lg sm:text-xl md:text-2xl text-gray-600 max-w-2xl mx-auto mb-10 leading-relaxed">
              Intelligent pet care guidance, health advice, and training tips powered by advanced AI technology
            </p>

            {/* Chat Input */}
            <div className="max-w-xl mx-auto mb-16">
              <div className="bg-white rounded-2xl shadow-lg p-1 border border-gray-200">
                <ChatInput onSendMessage={handleSendMessage} />
              </div>
              <p className="text-sm text-gray-500 mt-3">
                Ask anything about your pet's health, behavior, or training
              </p>
            </div>
          </div>

          {/* Stats Section */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-6 mb-20">
            <div className="text-center p-6 bg-white rounded-xl shadow-sm border border-gray-100">
              <div className="text-3xl font-bold text-blue-600 mb-2">10K+</div>
              <div className="text-gray-600">Happy Pets</div>
            </div>
            <div className="text-center p-6 bg-white rounded-xl shadow-sm border border-gray-100">
              <div className="text-3xl font-bold text-blue-600 mb-2">24/7</div>
              <div className="text-gray-600">Support</div>
            </div>
            <div className="text-center p-6 bg-white rounded-xl shadow-sm border border-gray-100">
              <div className="text-3xl font-bold text-blue-600 mb-2">98%</div>
              <div className="text-gray-600">Accuracy</div>
            </div>
            <div className="text-center p-6 bg-white rounded-xl shadow-sm border border-gray-100">
              <div className="text-3xl font-bold text-blue-600 mb-2">500+</div>
              <div className="text-gray-600">Pet Experts</div>
            </div>
          </div>

          {/* Feature Cards */}
          <div className="w-full mb-20">
            <div className="text-center mb-16">
              <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                Why Pet Owners Love Us
              </h2>
              <p className="text-lg text-gray-600 max-w-2xl mx-auto">
                Advanced AI technology combined with veterinary expertise to give your pet the best care
              </p>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
              {features.map((feature, index) => (
                <div 
                  key={index} 
                  className="bg-gradient-to-br from-blue-600 to-purple-600 text-white rounded-2xl p-6 shadow-lg transform transition-all duration-300 hover:-translate-y-2"
                >
                  <div className="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mb-5">
                    {feature.icon}
                  </div>
                  <h3 className="text-xl font-bold mb-3">{feature.title}</h3>
                  <p className="text-blue-100 opacity-90">{feature.description}</p>
                </div>
              ))}
            </div>
          </div>

          {/* CTA Section */}
          <div className="text-center py-12 px-4 bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl text-white mb-16">
            <h2 className="text-3xl md:text-4xl font-bold mb-6">
              Ready to Transform Your Pet's Life?
            </h2>
            <p className="text-lg text-blue-100 max-w-2xl mx-auto mb-8">
              Join thousands of pet owners who trust our AI assistant for their pet's health and happiness
            </p>
            <button 
              onClick={() => navigate('/register')}
              className="bg-white text-blue-600 font-semibold py-3 px-8 rounded-full hover:bg-gray-100 transition-colors duration-300 shadow-lg"
            >
              Get Started Now
            </button>
          </div>
        </div>

        <Footer />
      </div>
    </>
  );
};

export default Home;