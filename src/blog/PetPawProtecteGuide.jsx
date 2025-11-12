import React, { useState } from 'react';
import Footer from '../components/Footer';
import Header from '../components/Header';

const PetPawProtectionGuide = () => {
  const [activeSection, setActiveSection] = useState(null);

  const toggleSection = (section) => {
    setActiveSection(activeSection === section ? null : section);
  };

  const preWalkTips = [
    {
      icon: "🛡️",
      title: "Apply Paw Balm or Wax",
      description: "A winter paw balm for dogs or cats creates a protective barrier against ice and salt. Look for natural ingredients like shea butter, coconut oil, or beeswax.",
      proTip: "Apply balm 5–10 minutes before walks to allow it to absorb fully."
    },
    {
      icon: "✂️",
      title: "Trim Excess Fur Between Toes",
      description: "Snow can get stuck between the pads, forming ice balls. Regularly trim fur between the toes to reduce buildup."
    },
    {
      icon: "💅",
      title: "Inspect Nails",
      description: "Long nails can cause pets to slip on ice. Keep them neatly trimmed for better traction."
    }
  ];

  const duringWalkTips = [
    {
      icon: "👢",
      title: "Use Dog Booties or Paw Covers",
      description: "Invest in the best winter boots for dogs that are waterproof, non-slip, and snug. They prevent cold exposure and protect against salt and sharp ice."
    },
    {
      icon: "🚫",
      title: "Avoid Treated Areas",
      description: "Stay clear of heavily salted sidewalks. Walk your dog on grassy areas whenever possible."
    },
    {
      icon: "⏱️",
      title: "Keep Walks Short and Sweet",
      description: "Limit exposure to extreme cold. Instead of one long walk, go for 2–3 short ones throughout the day."
    }
  ];

  const afterWalkTips = [
    {
      icon: "🧼",
      title: "Wipe the Paws Immediately",
      description: "Use a damp towel or pet-safe wipes to remove salt, ice, and debris. Focus between toes and under pads."
    },
    {
      icon: "💧",
      title: "Rinse with Lukewarm Water",
      description: "If your pet walked on salted roads, rinse paws in warm (not hot) water to dissolve chemicals completely."
    },
    {
      icon: "🌿",
      title: "Moisturize Naturally",
      description: "Dryness can cause cracks, so apply pet paw moisturizers made with coconut oil, shea butter, or olive oil. Avoid human creams — they may contain toxic ingredients."
    }
  ];

  const naturalRemedies = [
    {
      icon: "🥥",
      title: "Coconut Oil Massage",
      description: "Softens pads and adds a natural layer of protection."
    },
    {
      icon: "🌱",
      title: "Aloe Vera Gel",
      description: "Soothes inflammation and redness."
    },
    {
      icon: "🐝",
      title: "Beeswax Barrier",
      description: "Creates a light protective layer against salt and snow."
    },
    {
      icon: "🛁",
      title: "Epsom Salt Soak",
      description: "For healing minor cuts and improving circulation (only if no open wounds)."
    }
  ];

  const warningSigns = [
    { symptom: "Limping or avoiding walks", severity: "high" },
    { symptom: "Licking or biting at paws frequently", severity: "medium" },
    { symptom: "Redness, swelling, or bleeding", severity: "high" },
    { symptom: "Flaky, peeling skin", severity: "medium" },
    { symptom: "Cracks that won't heal", severity: "high" }
  ];

  const quickChecklist = [
    "Apply balm before every walk",
    "Trim fur between toes",
    "Use booties on icy days",
    "Wipe paws after walks",
    "Moisturize daily",
    "Watch for cracks and redness",
    "Visit the vet for persistent irritation"
  ];

  return (
    <>
    <Header/>
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-8 px-4 mt-12">
      <div className="max-w-6xl mx-auto">
        
        {/* Header */}
        <header className="text-center mb-12">
          <div className="bg-white rounded-2xl shadow-xl p-8 mb-8 border-l-4 border-r-4 border-blue-500">
            <div className="flex justify-center mb-4">
              <span className="text-4xl">🐾</span>
            </div>
            <h1 className="text-4xl md:text-5xl font-bold text-gray-800 mb-4">
              Protecting Pet Paws in Winter
            </h1>
            <p className="text-xl text-gray-600 mb-6">
              Expert Tips, Care Guide & Prevention for Cold Weather
            </p>
            <div className="w-24 h-1 bg-gradient-to-r from-blue-500 to-purple-500 mx-auto rounded-full"></div>
          </div>
        </header>

        {/* Introduction */}
        <section className="bg-white rounded-2xl shadow-lg p-8 mb-8">
          <div className="flex items-start">
            <div className="bg-blue-100 p-3 rounded-lg mr-4">
              <span className="text-2xl">❄️</span>
            </div>
            <div>
              <h2 className="text-2xl font-bold text-gray-800 mb-4">
                Why Paw Protection Matters in Winter
              </h2>
              <div className="text-gray-700 space-y-4">
                <p>
                  As temperatures drop and sidewalks freeze, your pet's paws take the hardest hit. 
                  Snow, ice, and salt can cause cracking, irritation, and even infections. While 
                  dogs and cats have tougher pads than humans, they still need protection from harsh winter elements.
                </p>
                <p className="font-semibold text-blue-600">
                  In this guide on protecting pet paws in winter, you'll learn how to keep those 
                  little paws soft, healthy, and injury-free using simple home tips, paw balms, 
                  and preventive care strategies.
                </p>
              </div>
            </div>
          </div>
        </section>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          
          {/* Main Content */}
          <div className="lg:col-span-2 space-y-8">
            
            {/* Why Winter Hurts Paws */}
            <section className="bg-white rounded-2xl shadow-lg p-8">
              <h2 className="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <span className="text-blue-500 mr-3">🔍</span>
                1. Understanding Why Winter Hurts Pet Paws
              </h2>
              <div className="text-gray-700 space-y-4">
                <p>
                  Just like human skin, pet paws are sensitive to cold and dryness. During winter, 
                  three main culprits cause damage:
                </p>
                
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                  <div className="bg-red-50 p-4 rounded-lg border-l-4 border-red-400">
                    <div className="text-red-500 text-xl mb-2">🧂</div>
                    <h3 className="font-bold text-gray-800 mb-2">Salt and Chemicals</h3>
                    <p className="text-sm text-gray-600">
                      Road salt and de-icers are harsh on paw pads, leading to burns and cracking.
                    </p>
                  </div>
                  <div className="bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-400">
                    <div className="text-yellow-500 text-xl mb-2">🌬️</div>
                    <h3 className="font-bold text-gray-800 mb-2">Dry Air</h3>
                    <p className="text-sm text-gray-600">
                      Low humidity makes paws brittle and prone to peeling.
                    </p>
                  </div>
                  <div className="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-400">
                    <div className="text-blue-500 text-xl mb-2">❄️</div>
                    <h3 className="font-bold text-gray-800 mb-2">Cold Surfaces</h3>
                    <p className="text-sm text-gray-600">
                      Snow and ice draw out natural moisture, resulting in rough, painful pads.
                    </p>
                  </div>
                </div>

                <div className="mt-6 p-4 bg-green-50 rounded-lg border-l-4 border-green-500">
                  <p className="text-green-700 font-semibold">
                    By following these dog paw protection tips, you can easily minimize irritation 
                    and keep your pet's paws healthy all season long.
                  </p>
                </div>
              </div>
            </section>

            {/* Pre-Walk Routine */}
            <section className="bg-white rounded-2xl shadow-lg p-8">
              <h2 className="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <span className="text-green-500 mr-3">⏰</span>
                2. Pre-Walk Routine: Preparing the Paws
              </h2>
              <p className="text-gray-700 mb-6">
                Before heading outdoors, a little prep goes a long way.
              </p>

              <div className="space-y-6">
                {preWalkTips.map((tip, index) => (
                  <div key={index} className="flex items-start p-4 bg-green-50 rounded-lg border-l-4 border-green-400">
                    <div className="text-2xl mr-4 mt-1">{tip.icon}</div>
                    <div className="flex-1">
                      <h3 className="font-bold text-gray-800 text-lg mb-2">{tip.title}</h3>
                      <p className="text-gray-600 mb-2">{tip.description}</p>
                      {tip.proTip && (
                        <div className="bg-white p-3 rounded-lg border border-green-200">
                          <p className="text-green-700 text-sm font-semibold">
                            💡 Pro Tip: {tip.proTip}
                          </p>
                        </div>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </section>

            {/* During Walk */}
            <section className="bg-white rounded-2xl shadow-lg p-8">
              <h2 className="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <span className="text-purple-500 mr-3">🚶‍♂️</span>
                3. During the Walk: Smart Paw Protection
              </h2>
              <p className="text-gray-700 mb-6">
                When outside, your goal is to prevent direct contact with harmful surfaces.
              </p>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {duringWalkTips.map((tip, index) => (
                  <div key={index} className="bg-purple-50 p-6 rounded-lg border-l-4 border-purple-400 text-center hover:shadow-md transition-shadow">
                    <div className="text-3xl mb-4">{tip.icon}</div>
                    <h3 className="font-bold text-gray-800 mb-3">{tip.title}</h3>
                    <p className="text-gray-600 text-sm">{tip.description}</p>
                  </div>
                ))}
              </div>
            </section>

            {/* After Walk */}
            <section className="bg-white rounded-2xl shadow-lg p-8">
              <h2 className="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <span className="text-blue-500 mr-3">🏠</span>
                4. After the Walk: Cleaning and Moisturizing
              </h2>
              <p className="text-gray-700 mb-6">
                Once home, dog paw cleaning after walk is crucial for maintaining healthy paws.
              </p>

              <div className="space-y-4">
                {afterWalkTips.map((tip, index) => (
                  <div key={index} className="flex items-start p-4 bg-blue-50 rounded-lg">
                    <div className="text-2xl mr-4 mt-1">{tip.icon}</div>
                    <div>
                      <h3 className="font-bold text-gray-800 text-lg mb-2">{tip.title}</h3>
                      <p className="text-gray-600">{tip.description}</p>
                    </div>
                  </div>
                ))}
              </div>
            </section>

            {/* Cracked Paws Care */}
            <section className="bg-white rounded-2xl shadow-lg p-8">
              <h2 className="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <span className="text-red-500 mr-3">🩹</span>
                5. Cracked Dog Paws Winter Care
              </h2>
              <div className="bg-red-50 p-6 rounded-lg border-l-4 border-red-400">
                <p className="text-gray-700 mb-4">
                  Even with precautions, cracks can still form. Here's what you can do for cracked dog paws winter care:
                </p>
                <ul className="list-disc pl-5 text-gray-700 space-y-2">
                  <li>Clean paws gently with antiseptic water</li>
                  <li>Apply an antibiotic ointment (vet-approved)</li>
                  <li>Cover with soft gauze or socks to prevent licking</li>
                  <li>Allow healing time before resuming long walks</li>
                </ul>
                <div className="mt-4 p-3 bg-white rounded-lg border border-red-200">
                  <p className="text-red-700 font-semibold text-center">
                    ⚠️ If cracks deepen or bleeding occurs, consult your vet for professional care.
                  </p>
                </div>
              </div>
            </section>
          </div>

          {/* Sidebar */}
          <div className="space-y-8">
            
            {/* Natural Remedies */}
            <section className="bg-white rounded-2xl shadow-lg p-6">
              <h2 className="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <span className="text-green-500 mr-3">🌿</span>
                6. Natural Remedies
              </h2>
              <p className="text-gray-600 text-sm mb-4">
                Chemical-free approach to winter paw care for dogs
              </p>
              
              <div className="space-y-3">
                {naturalRemedies.map((remedy, index) => (
                  <div key={index} className="flex items-start p-3 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                    <span className="text-xl mr-3 mt-1">{remedy.icon}</span>
                    <div>
                      <h3 className="font-semibold text-gray-800 text-sm mb-1">{remedy.title}</h3>
                      <p className="text-gray-600 text-xs">{remedy.description}</p>
                    </div>
                  </div>
                ))}
              </div>
            </section>

            {/* Warning Signs */}
            <section className="bg-white rounded-2xl shadow-lg p-6">
              <h2 className="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <span className="text-red-500 mr-3">🚨</span>
                7. Warning Signs of Paw Damage
              </h2>
              
              <div className="space-y-2">
                {warningSigns.map((sign, index) => (
                  <div key={index} className={`flex items-center p-3 rounded-lg ${
                    sign.severity === 'high' ? 'bg-red-50 border-l-4 border-red-400' : 'bg-yellow-50 border-l-4 border-yellow-400'
                  }`}>
                    <span className={`mr-3 ${
                      sign.severity === 'high' ? 'text-red-500' : 'text-yellow-500'
                    }`}>
                      {sign.severity === 'high' ? '🔴' : '🟡'}
                    </span>
                    <span className="text-sm text-gray-800">{sign.symptom}</span>
                  </div>
                ))}
              </div>

              <div className="mt-4 p-3 bg-red-100 rounded-lg">
                <p className="text-red-700 text-sm font-semibold text-center">
                  If you notice multiple symptoms, seek immediate veterinary attention!
                </p>
              </div>
            </section>

            {/* Quick Checklist */}
            <section className="bg-white rounded-2xl shadow-lg p-6 sticky top-16">
              <h2 className="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <span className="text-blue-500 mr-3">✅</span>
                Quick Paw Protection Checklist
              </h2>
              
              <div className="space-y-2">
                {quickChecklist.map((item, index) => (
                  <div key={index} className="flex items-center p-2 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                    <div className="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs mr-3">
                      {index + 1}
                    </div>
                    <span className="text-sm text-gray-800">{item}</span>
                  </div>
                ))}
              </div>

              <div className="mt-4 p-3 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg text-white text-center">
                <p className="font-bold text-sm">Follow this checklist for happy winter paws! 🐾</p>
              </div>
            </section>

            {/* Indoor Care Tips */}
            <section className="bg-white rounded-2xl shadow-lg p-6">
              <h2 className="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <span className="text-purple-500 mr-3">🏡</span>
                Indoor Winter Paw Care
              </h2>
              <ul className="text-gray-600 text-sm space-y-2">
                <li className="flex items-start">
                  <span className="text-purple-500 mr-2">•</span>
                  Keep floors clean from salt residue
                </li>
                <li className="flex items-start">
                  <span className="text-purple-500 mr-2">•</span>
                  Use soft rugs for warm resting spots
                </li>
                <li className="flex items-start">
                  <span className="text-purple-500 mr-2">•</span>
                  Maintain humidity with a humidifier
                </li>
                <li className="flex items-start">
                  <span className="text-purple-500 mr-2">•</span>
                  Regular massage for blood flow
                </li>
              </ul>
            </section>
          </div>
        </div>

        {/* Beyond Paws Section */}
        <section className="bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl shadow-lg p-8 mt-8 text-white">
          <h2 className="text-2xl font-bold mb-4 text-center">9. Winter Safety for Pets – Beyond the Paws</h2>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
            <div className="bg-white bg-opacity-20 p-4 rounded-lg">
              <div className="text-2xl mb-2">🍎</div>
              <h3 className="font-bold mb-2">Nutrient-Rich Diet</h3>
              <p className="text-sm opacity-90">Feed omega fatty acids for better skin health</p>
            </div>
            <div className="bg-white bg-opacity-20 p-4 rounded-lg">
              <div className="text-2xl mb-2">🧥</div>
              <h3 className="font-bold mb-2">Proper Clothing</h3>
              <p className="text-sm opacity-90">Use sweaters for short walks in extreme cold</p>
            </div>
            <div className="bg-white bg-opacity-20 p-4 rounded-lg">
              <div className="text-2xl mb-2">🏠</div>
              <h3 className="font-bold mb-2">Indoor Safety</h3>
              <p className="text-sm opacity-90">Avoid leaving pets outside unattended</p>
            </div>
          </div>
        </section>

        {/* Conclusion */}
        <section className="bg-white rounded-2xl shadow-lg p-8 mt-8 text-center">
          <h2 className="text-2xl font-bold text-gray-800 mb-4">Conclusion: Keep Your Pet's Paws Happy All Winter</h2>
          <div className="max-w-3xl mx-auto">
            <p className="text-gray-700 mb-6 text-lg">
              Caring for your pet's paws in winter doesn't have to be complicated — it just takes awareness and consistency. 
              By using winter paw balm for dogs, regular cleaning, and protective gear, you can prevent cracks, burns, and infections.
            </p>
            <div className="bg-gradient-to-r from-blue-100 to-purple-100 p-6 rounded-lg border-2 border-blue-200">
              <p className="text-blue-700 font-bold text-lg">
                This season, make paw care part of your daily routine. Your pet will thank you with happy steps! 🐾
              </p>
            </div>
          </div>
        </section>

        {/* Footer */}
        <footer className="text-center mt-12 text-gray-600">
          <p>© {new Date().getFullYear()} Pet Care Guide. All rights reserved.</p>
        </footer>
      </div>
    </div>
    <Footer/>
    </>
  );
};

export default PetPawProtectionGuide;