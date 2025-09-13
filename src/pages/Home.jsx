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
import React, { useState, useEffect, useMemo, lazy, Suspense } from "react";
import { useNavigate } from "react-router-dom";
import Header from "../components/Header"; // header ko normal import rakha hai

const ChatInput = lazy(() => import("../components/ChatInput"));
const Footer = lazy(() => import("../components/Footer"));

const Home = () => {
  const navigate = useNavigate();
  const [message, setMessage] = useState("");

  // localStorage read only once on mount
  useEffect(() => {
    const stored = localStorage.getItem("messageIntended");
    if (stored) setMessage(stored);
  }, []);

  const handleSendMessage = (msg) => {
    if (msg?.trim()) {
      localStorage.setItem("messageIntended", msg);
      console.log("User intended message:", msg);
      navigate("/register");
    }
  };

  // memoize static features
  const features = useMemo(
    () => [
      {
        icon: "✅",
        title: "24/7 Pet Care Support",
        description:
          "Get instant answers to your pet care questions anytime, anywhere",
      },
      {
        icon: "👩‍⚕️",
        title: "Personalized Pet Plans",
        description: "Receive tailored advice based on your pet's unique needs",
      },
      {
        icon: "🐾",
        title: "Behavior Training Tips",
        description:
          "Learn effective techniques to train and bond with your pet",
      },
    ],
    []
  );

  return (
    <>
      <Header />
      <main className="min-h-screen bg-gradient-to-b from-white to-blue-50 flex flex-col">
        <div className="flex-1 flex flex-col px-4 py-8 max-w-6xl mx-auto w-full">
          {/* Hero Section */}
          <section className="text-center py-12 md:py-20">
            <div className="inline-flex items-center justify-center mb-4 bg-blue-100 text-blue-800 rounded-full px-4 py-2 text-sm font-medium">
              🐶 AI-Powered Pet Care Assistant
            </div>

            <Header />
            <h1 >
              SnoutIQ - Your AI Pet Companion for{" "}
              {/* <span className="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent whitespace-nowrap"> */}
                Smart Pet Care
              {/* </span> */}
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

          {/* Stats Section */}
          <section className="grid grid-cols-2 md:grid-cols-4 gap-6 mb-20">
            {[
              { value: "10K+", label: "Happy Pets" },
              { value: "24/7", label: "Support" },
              { value: "98%", label: "Accuracy" },
              { value: "500+", label: "Pet Experts" },
            ].map((stat, i) => (
              <div
                key={i}
                className="text-center p-6 bg-white rounded-xl shadow-sm border border-gray-100"
              >
                <div className="text-3xl font-bold text-blue-600 mb-2">
                  {stat.value}
                </div>
                <div className="text-gray-600">{stat.label}</div>
              </div>
            ))}
          </section>

          {/* Feature Section */}
          <section className="w-full mb-20">
            <div className="text-center mb-16">
              <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                Why Pet Owners Love Us
              </h2>
              <p className="text-lg text-gray-600 max-w-2xl mx-auto">
                Advanced AI technology combined with veterinary expertise to
                give your pet the best care
              </p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
              {features.map((f, idx) => (
                <div
                  key={idx}
                  className="bg-gradient-to-br from-blue-600 to-purple-600 text-white rounded-2xl p-6 shadow-lg transform transition-all duration-300 hover:-translate-y-2"
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

          {/* CTA Section */}
          <section className="text-center py-12 px-4 bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl text-white mb-16">
            <h2 className="text-3xl md:text-4xl font-bold mb-6">
              Ready to Transform Your Pet's Life?
            </h2>
            <p className="text-lg text-blue-100 max-w-2xl mx-auto mb-8">
              Join thousands of pet owners who trust our AI assistant for their
              pet's health and happiness
            </p>
            <button
              onClick={() => navigate("/register")}
              className="bg-white text-blue-600 font-semibold py-3 px-8 rounded-full hover:bg-gray-100 transition-colors duration-300 shadow-lg"
            >
              Get Started Now
            </button>
          </section>
        </div>

        {/* Footer lazy load */}
        <Suspense fallback={null}>
          <Footer />
        </Suspense>
      </main>
    </>
  );
};

export default Home;
