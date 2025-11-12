import React, { useState } from 'react';
import Footer from '../components/Footer';
import Header from '../components/Header';

const TickFeverGuide = () => {
  const [activeSection, setActiveSection] = useState(null);
  const [showFaq, setShowFaq] = useState(false);

  const toggleSection = (section) => {
    setActiveSection(activeSection === section ? null : section);
  };

  const symptoms = [
    {
      icon: "🌡️",
      title: "Persistent High Fever",
      description: "One of the first signs of tick fever in dogs is a consistent fever (above 103°F or 39.5°C). The fever may come and go but tends to persist as the infection progresses."
    },
    {
      icon: "😴",
      title: "Lethargy and Weakness",
      description: "Dogs with tick fever often appear unusually tired, less playful, and may prefer lying down all day. You'll notice reduced interest in walks or playtime."
    },
    {
      icon: "🍽️",
      title: "Loss of Appetite",
      description: "A clear early symptom — your dog might refuse food or eat significantly less than usual."
    },
    {
      icon: "⚖️",
      title: "Weight Loss",
      description: "Due to appetite loss and metabolic stress, gradual weight loss can occur."
    },
    {
      icon: "🦷",
      title: "Pale Gums and Nose",
      description: "Tick fever affects red blood cell production, which can cause anemia. Check your dog's gums — if they look pale instead of pink, it's a red flag."
    },
    {
      icon: "🩸",
      title: "Nosebleeds or Bleeding Issues",
      description: "Some dogs develop spontaneous nosebleeds or bleed easily from minor injuries due to low platelet counts (a condition called thrombocytopenia)."
    },
    {
      icon: "🔍",
      title: "Swollen Lymph Nodes",
      description: "You may feel small lumps near your dog's neck, groin, or underarms — these are inflamed lymph nodes fighting infection."
    },
    {
      icon: "🦵",
      title: "Joint Pain and Lameness",
      description: "Tick fever often affects the joints, leading to stiffness, limping, or reluctance to move."
    },
    {
      icon: "👁️",
      title: "Eye Problems",
      description: "You may notice redness, cloudy eyes, or discharge. In some cases, tick fever causes inflammation inside the eyes (uveitis)."
    },
    {
      icon: "🤢",
      title: "Vomiting and Diarrhea",
      description: "As the infection spreads, the digestive system may be affected, causing vomiting, diarrhea, or both."
    }
  ];

  const preventionTips = [
    {
      icon: "🔍",
      title: "Regular Tick Checks",
      description: "After walks or outdoor play, thoroughly inspect your dog's fur — especially under ears, between toes, and around the neck."
    },
    {
      icon: "🛡️",
      title: "Tick Prevention Products",
      description: "Use vet-approved tick repellents, spot-on treatments, or medicated collars. These reduce tick bites drastically."
    },
    {
      icon: "🧼",
      title: "Maintain Hygiene",
      description: "Keep your dog's sleeping area clean and wash bedding regularly. Trim grass and shrubs in your yard where ticks thrive."
    },
    {
      icon: "🍎",
      title: "Balanced Diet & Immune Support",
      description: "A healthy immune system helps fight infections better. Provide balanced nutrition and fresh water daily."
    },
    {
      icon: "🏥",
      title: "Routine Vet Visits",
      description: "Regular health checkups (every 6 months) help detect infections early and ensure vaccinations are up-to-date."
    }
  ];

  const faqItems = [
    {
      question: "How long after a tick bite do symptoms appear?",
      answer: "Usually 1–3 weeks after the bite, but it can vary depending on the bacteria type and your dog's immunity."
    },
    {
      question: "Can tick fever in dogs be cured completely?",
      answer: "Yes. With early diagnosis and antibiotic treatment, most dogs recover fully."
    },
    {
      question: "Is tick fever contagious to humans or other dogs?",
      answer: "No direct dog-to-dog or dog-to-human transmission occurs. However, ticks can bite multiple hosts, so tick control is crucial."
    },
    {
      question: "What breeds are most at risk?",
      answer: "All dogs can get tick fever, but those that spend time outdoors or in rural areas are more vulnerable."
    },
    {
      question: "How can I remove a tick safely?",
      answer: "Use fine-tipped tweezers, grasp close to the skin, and pull steadily. Disinfect the area and wash your hands afterward."
    }
  ];

  return (
    <>
    <Header/>
    <div className="min-h-screen bg-gradient-to-br from-amber-50 to-red-50 py-8 px-4 mt-12">
      <div className="max-w-6xl mx-auto">
        
        {/* Header */}
        <header className="text-center mb-12">
          <div className="bg-white rounded-2xl shadow-lg p-8 mb-8 border-l-4 border-r-4 border-red-500">
            <h1 className="text-4xl md:text-5xl font-bold text-gray-800 mb-4 bg-gradient-to-r from-red-600 to-amber-600 bg-clip-text text-transparent">
              Symptoms of Tick Fever in Dogs
            </h1>
            <p className="text-xl text-gray-600 mb-6">
              Causes, Prevention & Treatment Guide for Pet Parents
            </p>
            <div className="w-24 h-1 bg-gradient-to-r from-red-500 to-amber-500 mx-auto rounded-full"></div>
          </div>
        </header>

        {/* Introduction */}
        <section className="bg-white rounded-2xl shadow-lg p-8 mb-8 border-l-4 border-red-500">
          <h2 className="text-2xl font-bold text-gray-800 mb-4 flex items-center">
            <span className="text-red-500 mr-3">⚠️</span>
            Why Tick Fever in Dogs Is a Serious Concern
          </h2>
          <div className="text-gray-700 space-y-4">
            <p>
              Ticks may be small, but the diseases they carry can be life-threatening for your dog. 
              One of the most common and dangerous is tick fever — a bacterial or parasitic infection 
              transmitted through tick bites.
            </p>
            <p>
              In this post, we'll discuss the symptoms of tick fever in dogs, what causes it, how to 
              prevent it, and the right steps to take if your pet shows signs of illness.
            </p>
            <p className="font-semibold text-red-600">
              Understanding these symptoms early can make all the difference in keeping your furry friend safe and healthy.
            </p>
          </div>
        </section>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          
          {/* Main Content */}
          <div className="lg:col-span-2 space-y-8">
            
            {/* What is Tick Fever */}
            <section className="bg-white rounded-2xl shadow-lg p-8">
              <h2 className="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <span className="text-red-500 mr-3">🔬</span>
                What Is Tick Fever in Dogs?
              </h2>
              <div className="text-gray-700 space-y-4">
                <p>
                  Tick fever, also called canine ehrlichiosis or anaplasmosis, is caused by bacteria 
                  transmitted when an infected tick bites a dog.
                </p>
                
                <div className="bg-red-50 p-6 rounded-lg border-l-4 border-red-400">
                  <h3 className="font-bold text-gray-800 mb-3">The two most common bacteria responsible are:</h3>
                  <ul className="list-disc pl-5 space-y-2">
                    <li><span className="font-semibold">Ehrlichia canis</span> — transmitted mainly by the brown dog tick.</li>
                    <li><span className="font-semibold">Anaplasma platys</span> — spread by the deer tick.</li>
                  </ul>
                </div>

                <p>
                  These bacteria enter your dog's bloodstream and attack white blood cells and platelets, 
                  leading to symptoms like fever, fatigue, and bleeding problems.
                </p>

                <div className="bg-amber-50 p-6 rounded-lg mt-6">
                  <h3 className="font-bold text-gray-800 mb-4 text-center">Tick Fever Progresses in Three Stages:</h3>
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div className="text-center p-4 bg-white rounded-lg shadow">
                      <div className="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-3 text-xl font-bold">1</div>
                      <h4 className="font-bold text-gray-800 mb-2">Acute Stage</h4>
                      <p className="text-sm text-gray-600">First 2–4 weeks: Early infection with mild symptoms</p>
                    </div>
                    <div className="text-center p-4 bg-white rounded-lg shadow">
                      <div className="w-12 h-12 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-3 text-xl font-bold">2</div>
                      <h4 className="font-bold text-gray-800 mb-2">Subclinical Stage</h4>
                      <p className="text-sm text-gray-600">No visible signs, but bacteria remain in the bloodstream</p>
                    </div>
                    <div className="text-center p-4 bg-white rounded-lg shadow">
                      <div className="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-3 text-xl font-bold">3</div>
                      <h4 className="font-bold text-gray-800 mb-2">Chronic Stage</h4>
                      <p className="text-sm text-gray-600">Severe illness affecting organs like liver, spleen, and bone marrow</p>
                    </div>
                  </div>
                </div>
              </div>
            </section>

            {/* Symptoms Section */}
            <section className="bg-white rounded-2xl shadow-lg p-8">
              <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800 flex items-center">
                  <span className="text-red-500 mr-3">🚨</span>
                  Symptoms of Tick Fever in Dogs
                </h2>
                <span className="bg-red-100 text-red-600 px-3 py-1 rounded-full text-sm font-semibold">
                  10 Symptoms
                </span>
              </div>
              
              <p className="text-gray-700 mb-6">
                Early detection is critical. The symptoms of tick fever in dogs can vary depending on 
                the stage of infection and your pet's overall health.
              </p>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {symptoms.map((symptom, index) => (
                  <div key={index} className="bg-gradient-to-br from-white to-red-50 p-6 rounded-xl border border-red-100 hover:shadow-md transition-shadow">
                    <div className="flex items-start mb-3">
                      <span className="text-2xl mr-3">{symptom.icon}</span>
                      <h3 className="font-bold text-gray-800 text-lg">{symptom.title}</h3>
                    </div>
                    <p className="text-gray-600 text-sm">{symptom.description}</p>
                  </div>
                ))}
              </div>

              <div className="mt-6 p-4 bg-red-50 rounded-lg border-l-4 border-red-500">
                <p className="text-red-700 font-semibold text-center">
                  👇 If you notice any of these symptoms, take your pet to the vet immediately!
                </p>
              </div>
            </section>

            {/* Diagnosis & Treatment */}
            <section className="bg-white rounded-2xl shadow-lg p-8">
              <h2 className="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <span className="text-red-500 mr-3">🏥</span>
                Diagnosis & Treatment
              </h2>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                {/* Diagnosis */}
                <div className="bg-blue-50 p-6 rounded-xl border-l-4 border-blue-500">
                  <h3 className="font-bold text-gray-800 mb-4 text-lg">How Is Tick Fever Diagnosed?</h3>
                  <p className="text-gray-700 mb-4">
                    The vet will typically perform a physical examination and run blood tests like:
                  </p>
                  <ul className="list-disc pl-5 space-y-2 text-gray-700">
                    <li><span className="font-semibold">CBC (Complete Blood Count)</span> – checks red/white blood cells and platelets</li>
                    <li><span className="font-semibold">PCR test</span> – detects Ehrlichia or Anaplasma DNA</li>
                    <li><span className="font-semibold">ELISA test</span> – identifies antibodies against the bacteria</li>
                  </ul>
                  <p className="text-blue-700 font-semibold mt-4">
                    Early diagnosis ensures faster recovery and prevents chronic infection.
                  </p>
                </div>

                {/* Treatment */}
                <div className="bg-green-50 p-6 rounded-xl border-l-4 border-green-500">
                  <h3 className="font-bold text-gray-800 mb-4 text-lg">Treatment for Tick Fever</h3>
                  <div className="space-y-4">
                    <div>
                      <h4 className="font-semibold text-gray-800 mb-2">Antibiotic Therapy</h4>
                      <p className="text-gray-700 text-sm">
                        The most common medication is Doxycycline, usually given for 3–4 weeks.
                      </p>
                    </div>
                    <div>
                      <h4 className="font-semibold text-gray-800 mb-2">Supportive Care</h4>
                      <p className="text-gray-700 text-sm">
                        IV fluids for dehydration, pain relief for joint discomfort, and blood transfusions in severe cases.
                      </p>
                    </div>
                    <div>
                      <h4 className="font-semibold text-gray-800 mb-2">Nutrition and Rest</h4>
                      <p className="text-gray-700 text-sm">
                        Ensure your dog eats well-balanced meals and rests in a calm, warm place.
                      </p>
                    </div>
                  </div>
                  <p className="text-green-700 font-semibold mt-4">
                    Most dogs recover fully with early and proper treatment.
                  </p>
                </div>
              </div>
            </section>
          </div>

          {/* Sidebar */}
          <div className="space-y-8">
            
            {/* Prevention Tips */}
            <section className="bg-white rounded-2xl shadow-lg p-6 sticky top-16">
              <h2 className="text-xl font-bold text-gray-800 mb-6 flex items-center">
                <span className="text-green-500 mr-3">🛡️</span>
                Prevention Tips
              </h2>
              
              <div className="space-y-4">
                {preventionTips.map((tip, index) => (
                  <div key={index} className="flex items-start p-3 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                    <span className="text-xl mr-3 mt-1">{tip.icon}</span>
                    <div>
                      <h3 className="font-semibold text-gray-800 text-sm mb-1">{tip.title}</h3>
                      <p className="text-gray-600 text-xs">{tip.description}</p>
                    </div>
                  </div>
                ))}
              </div>

              <div className="mt-6 p-4 bg-gradient-to-r from-green-500 to-emerald-500 rounded-lg text-white text-center">
                <p className="font-bold">Prevention is far easier than treatment!</p>
              </div>
            </section>

            {/* Bonus Tip */}
            <section className="bg-white rounded-2xl shadow-lg p-6">
              <h2 className="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <span className="text-purple-500 mr-3">💡</span>
                Bonus Tip
              </h2>
              <div className="bg-purple-50 p-4 rounded-lg border-l-4 border-purple-400">
                <h3 className="font-bold text-gray-800 mb-2">Difference Between Tick Fever & Regular Fever</h3>
                <p className="text-gray-700 text-sm">
                  Not all fevers are tick-related. A normal fever can result from infections or inflammation. 
                  However, if your dog also shows signs like <span className="font-semibold">pale gums, nosebleeds, or joint pain</span> — 
                  it's more likely tick fever.
                </p>
                <p className="text-purple-700 font-semibold text-sm mt-2">
                  Always consult your vet rather than self-medicating.
                </p>
              </div>
            </section>

            {/* Emergency Card */}
            <section className="bg-red-500 rounded-2xl shadow-lg p-6 text-white">
              <h2 className="text-xl font-bold mb-4 flex items-center">
                <span className="mr-3">🚑</span>
                Emergency Alert
              </h2>
              <p className="text-sm mb-4">
                If your dog shows multiple symptoms, especially <span className="font-bold">nosebleeds, pale gums, or high fever</span>, 
                seek veterinary care immediately!
              </p>
              <div className="bg-white text-red-600 p-3 rounded-lg text-center font-bold">
                Don't Wait - Early Treatment Saves Lives!
              </div>
            </section>
          </div>
        </div>

        {/* FAQ Section */}
        <section className="bg-white rounded-2xl shadow-lg p-8 mt-8">
          <div className="text-center mb-8">
            <h2 className="text-2xl font-bold text-gray-800 mb-2">Frequently Asked Questions</h2>
            <p className="text-gray-600">Common concerns about tick fever in dogs</p>
          </div>

          <div className="space-y-4">
            {faqItems.map((faq, index) => (
              <div key={index} className="border border-gray-200 rounded-lg hover:border-red-200 transition-colors">
                <button
                  className="w-full p-4 text-left flex justify-between items-center font-semibold text-gray-800"
                  onClick={() => toggleSection(`faq-${index}`)}
                >
                  <span>{faq.question}</span>
                  <span className="text-red-500">
                    {activeSection === `faq-${index}` ? '−' : '+'}
                  </span>
                </button>
                {activeSection === `faq-${index}` && (
                  <div className="p-4 bg-gray-50 border-t border-gray-200">
                    <p className="text-gray-700">{faq.answer}</p>
                  </div>
                )}
              </div>
            ))}
          </div>
        </section>

        {/* Conclusion */}
        <section className="bg-gradient-to-r from-red-500 to-amber-500 rounded-2xl shadow-lg p-8 mt-8 text-white text-center">
          <h2 className="text-2xl font-bold mb-4">Conclusion</h2>
          <p className="mb-4 text-lg">
            Tick fever is dangerous, but with awareness and early action, it's completely treatable.
          </p>
          <p className="mb-6">
            As a responsible pet parent, knowing the symptoms of tick fever in dogs — such as fever, 
            lethargy, pale gums, and bleeding — can help you save your pet's life.
          </p>
          <div className="bg-white text-red-600 p-4 rounded-lg font-bold text-sm max-w-2xl mx-auto">
            Combine vigilance with preventive care, regular tick checks, and timely veterinary attention. 
            Your dog depends on you to protect them — and a little care goes a long way toward a healthy, tick-free life.
          </div>
        </section>

        {/* Footer */}
        <footer className="text-center mt-12 text-gray-600">
          <p>© {new Date().getFullYear()} Dog Health Guide. All rights reserved.</p>
        </footer>
      </div>
    </div>
    <Footer/>
    </>
  );
};

export default TickFeverGuide;