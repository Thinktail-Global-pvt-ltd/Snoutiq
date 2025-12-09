import React from 'react';
import Footer from '../components/Footer';
import Header from '../components/Header';
import img3 from '../assets/images/tickfever.png';

const TickFeverGuide = () => {
  return (
    <>
      <Header />
      <div className="min-h-screen bg-gray-50 py-8 px-4 mt-12">
        <div className="max-w-4xl mx-auto">
          
          {/* Blog Header */}
          <header className="text-center mb-8">
            <h1 className="text-3xl font-bold text-gray-800 mb-4">
              Tick Fever in Dogs: Symptoms, Prevention & Treatment
            </h1>
            <p className="text-gray-600 text-lg">
              A comprehensive guide for pet parents to protect their furry friends
            </p>
            <div className="w-20 h-1 bg-red-500 mx-auto mt-4"></div>
          </header>
<section>
            <img src={img3} alt="image" />
          </section>
          {/* Introduction */}
          <section className="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 className="text-2xl font-bold text-gray-800 mb-4">Understanding Tick Fever</h2>
            <p className="text-gray-700 mb-4">
              Tick fever is a serious bacterial infection transmitted through tick bites that can be life-threatening 
              for your dog if not treated promptly. Ticks may be small, but the diseases they carry can be dangerous.
            </p>
            <div className="bg-red-50 p-4 rounded-lg border-l-4 border-red-400">
              <p className="text-red-800 font-semibold">
                Early detection of symptoms can save your dog's life. Learn what to watch for.
              </p>
            </div>
          </section>

          {/* What is Tick Fever */}
          <section className="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 className="text-2xl font-bold text-gray-800 mb-4">What Is Tick Fever?</h2>
            <p className="text-gray-700 mb-4">
              Tick fever, also called canine ehrlichiosis or anaplasmosis, is caused by bacteria transmitted when an infected tick bites a dog.
            </p>
            
            <div className="bg-red-50 p-4 rounded-lg mb-4">
              <h3 className="font-bold text-gray-800 mb-3">The two most common bacteria responsible are:</h3>
              <ul className="list-disc pl-5 text-gray-700 space-y-1">
                <li><span className="font-semibold">Ehrlichia canis</span> — transmitted mainly by the brown dog tick</li>
                <li><span className="font-semibold">Anaplasma platys</span> — spread by the deer tick</li>
              </ul>
            </div>

            <p className="text-gray-700">
              These bacteria enter your dog's bloodstream and attack white blood cells and platelets, leading to symptoms like fever, fatigue, and bleeding problems.
            </p>
          </section>

          {/* Symptoms Section */}
          <section className="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 className="text-2xl font-bold text-gray-800 mb-4">Common Symptoms of Tick Fever</h2>
            <p className="text-gray-700 mb-4">
              Early detection is critical. The symptoms can vary depending on the stage of infection and your pet's overall health.
            </p>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              {[
                "Persistent high fever (above 103°F or 39.5°C)",
                "Lethargy and weakness",
                "Loss of appetite",
                "Weight loss",
                "Pale gums and nose (signs of anemia)",
                "Nosebleeds or bleeding issues",
                "Swollen lymph nodes",
                "Joint pain and lameness",
                "Eye problems (redness, cloudiness)",
                "Vomiting and diarrhea"
              ].map((symptom, index) => (
                <div key={index} className="flex items-start">
                  <span className="text-red-500 mr-2">•</span>
                  <span className="text-gray-700">{symptom}</span>
                </div>
              ))}
            </div>

            <div className="bg-red-50 p-4 rounded-lg border-l-4 border-red-400">
              <p className="text-red-700 font-semibold text-center">
                If you notice any of these symptoms, take your pet to the vet immediately!
              </p>
            </div>
          </section>

          {/* Diagnosis & Treatment */}
          <section className="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 className="text-2xl font-bold text-gray-800 mb-4">Diagnosis & Treatment</h2>

            <div className="space-y-6">
              <div>
                <h3 className="text-xl font-semibold text-gray-800 mb-3">How Is Tick Fever Diagnosed?</h3>
                <p className="text-gray-700 mb-3">
                  The vet will typically perform a physical examination and run blood tests like:
                </p>
                <ul className="list-disc pl-5 text-gray-700 space-y-1">
                  <li><span className="font-semibold">CBC (Complete Blood Count)</span> – checks red/white blood cells and platelets</li>
                  <li><span className="font-semibold">PCR test</span> – detects Ehrlichia or Anaplasma DNA</li>
                  <li><span className="font-semibold">ELISA test</span> – identifies antibodies against the bacteria</li>
                </ul>
                <p className="text-blue-600 font-semibold mt-3">
                  Early diagnosis ensures faster recovery and prevents chronic infection.
                </p>
              </div>

              <div>
                <h3 className="text-xl font-semibold text-gray-800 mb-3">Treatment for Tick Fever</h3>
                <div className="space-y-3">
                  <div>
                    <h4 className="font-semibold text-gray-800 mb-1">Antibiotic Therapy</h4>
                    <p className="text-gray-700">
                      The most common medication is Doxycycline, usually given for 3–4 weeks.
                    </p>
                  </div>
                  <div>
                    <h4 className="font-semibold text-gray-800 mb-1">Supportive Care</h4>
                    <p className="text-gray-700">
                      IV fluids for dehydration, pain relief for joint discomfort, and blood transfusions in severe cases.
                    </p>
                  </div>
                  <div>
                    <h4 className="font-semibold text-gray-800 mb-1">Nutrition and Rest</h4>
                    <p className="text-gray-700">
                      Ensure your dog eats well-balanced meals and rests in a calm, warm place.
                    </p>
                  </div>
                </div>
                <p className="text-green-600 font-semibold mt-3">
                  Most dogs recover fully with early and proper treatment.
                </p>
              </div>
            </div>
          </section>

          {/* Prevention Tips */}
          <section className="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 className="text-2xl font-bold text-gray-800 mb-4">Prevention Tips</h2>
            <p className="text-gray-700 mb-4">
              Prevention is far easier than treatment! Here are essential tips to protect your dog:
            </p>
            
            <div className="space-y-3">
              {[
                "Regular tick checks after outdoor activities",
                "Use vet-approved tick prevention products",
                "Maintain clean living areas and yard",
                "Provide balanced diet for immune support",
                "Schedule routine vet checkups every 6 months"
              ].map((tip, index) => (
                <div key={index} className="flex items-start">
                  <span className="text-green-500 mr-2">✓</span>
                  <span className="text-gray-700">{tip}</span>
                </div>
              ))}
            </div>
          </section>

          {/* FAQ Section */}
          <section className="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 className="text-2xl font-bold text-gray-800 mb-4">Frequently Asked Questions</h2>
            
            <div className="space-y-4">
              <div>
                <h3 className="font-semibold text-gray-800 mb-2">How long after a tick bite do symptoms appear?</h3>
                <p className="text-gray-700">Usually 1–3 weeks after the bite, but it can vary depending on the bacteria type and your dog's immunity.</p>
              </div>
              
              <div>
                <h3 className="font-semibold text-gray-800 mb-2">Can tick fever in dogs be cured completely?</h3>
                <p className="text-gray-700">Yes. With early diagnosis and antibiotic treatment, most dogs recover fully.</p>
              </div>
              
              <div>
                <h3 className="font-semibold text-gray-800 mb-2">Is tick fever contagious to humans or other dogs?</h3>
                <p className="text-gray-700">No direct dog-to-dog or dog-to-human transmission occurs. However, ticks can bite multiple hosts, so tick control is crucial.</p>
              </div>
            </div>
          </section>

          {/* Key Takeaways */}
          <section className="bg-gray-100 p-6 rounded-lg mb-8">
            <h2 className="text-xl font-bold text-gray-800 mb-4">Key Takeaways</h2>
            <ul className="space-y-2 text-gray-700">
              <li>• Regular tick prevention is essential for your dog's health</li>
              <li>• Early detection and treatment lead to better outcomes</li>
              <li>• Consult your veterinarian for proper diagnosis and treatment</li>
              <li>• Keep your dog's environment clean and tick-free</li>
              <li>• Monitor your dog for any unusual symptoms after outdoor activities</li>
            </ul>
          </section>

          {/* Emergency Notice */}
          <div className="bg-red-100 border-l-4 border-red-500 p-4 rounded-lg">
            <div className="flex items-start">
              <div className="flex-shrink-0">
                <span className="text-red-500 text-lg">⚠️</span>
              </div>
              <div className="ml-3">
                <h3 className="text-lg font-semibold text-red-800 mb-2">Emergency Notice</h3>
                <p className="text-red-700">
                  If your dog shows multiple symptoms like high fever, pale gums, or bleeding, 
                  seek veterinary care immediately. Early treatment can save your dog's life.
                </p>
              </div>
            </div>
          </div>

        </div>
      </div>
      <Footer />
    </>
  );
};

export default TickFeverGuide;