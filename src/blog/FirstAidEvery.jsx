import React, { useState } from 'react';

const PetFirstAidGuide = () => {
  const [activeSection, setActiveSection] = useState('intro');

  const sections = {
    intro: {
      title: "First Aid Tips Every Pet Parent Should Know",
      content: (
        <div className="space-y-4">
          <p className="text-lg text-gray-700">
            Emergencies can happen anytime, and when they involve your pet, every second matters. 
            Knowing the right first aid tips can save your pet's life before you reach the vet.
          </p>
          <p className="text-gray-600">
            In this guide, you'll learn practical first aid tips for pets including handling bleeding, 
            burns, choking, heatstroke, and fractures.
          </p>
        </div>
      )
    },
    importance: {
      title: "Why Pet First Aid Matters",
      content: (
        <div className="space-y-4">
          <p className="text-gray-600">
            Accidents are unpredictable. Basic pet first aid knowledge bridges the gap between home care and professional help.
          </p>
          <div className="bg-blue-50 p-4 rounded-lg">
            <h3 className="font-semibold mb-2">Key Benefits:</h3>
            <ul className="space-y-1">
              <li>• Stabilize your pet before professional care</li>
              <li>• Prevent worsening of injuries during transport</li>
              <li>• Recognize emergencies faster</li>
              <li>• Stay calm and confident in emergencies</li>
            </ul>
          </div>
        </div>
      )
    },
    kit: {
      title: "Pet First Aid Kit Essentials",
      content: (
        <div className="space-y-4">
          <p className="text-gray-600">
            Every pet parent should keep a well-stocked first aid kit at home and in the car.
          </p>
          <div className="grid md:grid-cols-2 gap-4">
            <div className="bg-gray-50 p-4 rounded-lg">
              <h3 className="font-semibold mb-2">Basic Supplies:</h3>
              <ul className="space-y-1 text-sm">
                <li>• Sterile gauze pads</li>
                <li>• Antiseptic wipes</li>
                <li>• Adhesive bandages</li>
                <li>• Digital thermometer</li>
                <li>• Saline solution</li>
              </ul>
            </div>
            <div className="bg-gray-50 p-4 rounded-lg">
              <h3 className="font-semibold mb-2">Additional Items:</h3>
              <ul className="space-y-1 text-sm">
                <li>• Tweezers and scissors</li>
                <li>• Disposable gloves</li>
                <li>• Emergency blanket</li>
                <li>• Muzzle</li>
                <li>• Vet contact numbers</li>
              </ul>
            </div>
          </div>
        </div>
      )
    },
    bleeding: {
      title: "Handling Bleeding or Cuts",
      content: (
        <div className="space-y-4">
          <p className="text-gray-600">
            One of the most common pet emergencies is bleeding from paws, ears, or skin wounds.
          </p>
          <div className="bg-red-50 p-4 rounded-lg">
            <h3 className="font-semibold mb-2">Steps to Follow:</h3>
            <ol className="space-y-2">
              <li>1. Stay calm and restrain your pet gently</li>
              <li>2. Apply direct pressure with sterile gauze</li>
              <li>3. Don't remove soaked gauze - add more layers</li>
              <li>4. Elevate the injured area if possible</li>
              <li>5. Wrap with clean bandage once bleeding slows</li>
              <li>6. Seek vet care if bleeding continues</li>
            </ol>
          </div>
        </div>
      )
    },
    choking: {
      title: "Treating Choking",
      content: (
        <div className="space-y-4">
          <p className="text-gray-600">
            Pets often choke on toys, bones, or food pieces.
          </p>
          <div className="bg-yellow-50 p-4 rounded-lg">
            <h3 className="font-semibold mb-2">Signs of Choking:</h3>
            <ul className="space-y-1">
              <li>• Pawing at the mouth</li>
              <li>• Gagging or coughing</li>
              <li>• Difficulty breathing</li>
              <li>• Bluish gums</li>
            </ul>
          </div>
          <div className="bg-green-50 p-4 rounded-lg">
            <h3 className="font-semibold mb-2">What to Do:</h3>
            <ul className="space-y-1">
              <li>• Open mouth and look for objects</li>
              <li>• Remove visible objects gently</li>
              <li>• Perform Heimlich maneuver if needed</li>
              <li>• Rush to vet if breathing doesn't resume</li>
            </ul>
          </div>
        </div>
      )
    },
    heatstroke: {
      title: "Heatstroke Emergency",
      content: (
        <div className="space-y-4">
          <p className="text-gray-600">
            Overheating is dangerous, especially in summer.
          </p>
          <div className="bg-orange-50 p-4 rounded-lg">
            <h3 className="font-semibold mb-2">Symptoms:</h3>
            <ul className="space-y-1">
              <li>• Excessive panting</li>
              <li>• Drooling and weakness</li>
              <li>• Vomiting or collapse</li>
            </ul>
          </div>
          <div className="bg-blue-50 p-4 rounded-lg">
            <h3 className="font-semibold mb-2">Immediate Action:</h3>
            <ul className="space-y-1">
              <li>• Move to cool, shaded area</li>
              <li>• Apply cool water to paws and belly</li>
              <li>• Offer small sips of water</li>
              <li>• Avoid ice baths</li>
              <li>• Go to vet immediately</li>
            </ul>
          </div>
        </div>
      )
    },
    poisoning: {
      title: "Poisoning Emergency",
      content: (
        <div className="space-y-4">
          <p className="text-gray-600">
            Common toxins include chocolate, xylitol, onions, grapes, and medicines.
          </p>
          <div className="bg-red-50 p-4 rounded-lg">
            <h3 className="font-semibold mb-2">What to Do:</h3>
            <ul className="space-y-1">
              <li>• Call vet or pet poison helpline immediately</li>
              <li>• Induce vomiting only if advised</li>
              <li>• Don't induce vomiting for corrosive substances</li>
              <li>• Keep packaging to show the vet</li>
            </ul>
          </div>
        </div>
      )
    },
    reference: {
      title: "Quick Emergency Reference",
      content: (
        <div className="space-y-4">
          <div className="bg-gray-50 p-4 rounded-lg">
            <div className="space-y-3">
              <div className="flex justify-between border-b pb-2">
                <span className="font-semibold">Bleeding</span>
                <span>Apply pressure → See vet</span>
              </div>
              <div className="flex justify-between border-b pb-2">
                <span className="font-semibold">Choking</span>
                <span>Clear airway → See vet</span>
              </div>
              <div className="flex justify-between border-b pb-2">
                <span className="font-semibold">Heatstroke</span>
                <span>Cool down → See vet</span>
              </div>
              <div className="flex justify-between border-b pb-2">
                <span className="font-semibold">Poisoning</span>
                <span>Call vet → See vet</span>
              </div>
              <div className="flex justify-between">
                <span className="font-semibold">Fracture</span>
                <span>Immobilize → See vet</span>
              </div>
            </div>
          </div>
        </div>
      )
    },
    faq: {
      title: "Frequently Asked Questions",
      content: (
        <div className="space-y-4">
          <div className="border-l-4 border-blue-500 pl-4">
            <h3 className="font-semibold">Most important first aid tips?</h3>
            <p className="text-sm text-gray-600 mt-1">Stopping bleeding, handling choking, and stabilizing fractures.</p>
          </div>
          <div className="border-l-4 border-blue-500 pl-4">
            <h3 className="font-semibold">Human antiseptics on pets?</h3>
            <p className="text-sm text-gray-600 mt-1">No. Use pet-safe antiseptics like chlorhexidine.</p>
          </div>
          <div className="border-l-4 border-blue-500 pl-4">
            <h3 className="font-semibold">Check first aid kit frequency?</h3>
            <p className="text-sm text-gray-600 mt-1">Every 3-6 months; replace expired items.</p>
          </div>
          <div className="border-l-4 border-blue-500 pl-4">
            <h3 className="font-semibold">Prevent pet emergencies?</h3>
            <p className="text-sm text-gray-600 mt-1">Pet-proof your home and supervise outdoor play.</p>
          </div>
        </div>
      )
    }
  };

  const sectionKeys = Object.keys(sections);

  return (
    <div className="min-h-screen bg-gray-50 py-8 px-4">
      <div className="max-w-4xl mx-auto bg-white rounded-lg shadow-sm">
        
        {/* Simple Header */}
        <header className="bg-white border-b border-gray-200 p-6 text-center">
          <h1 className="text-3xl font-bold text-gray-800 mb-2">
            Pet First Aid Guide
          </h1>
          <p className="text-gray-600">
            Essential first aid tips for pet emergencies
          </p>
        </header>

        <div className="p-6">
          {/* Simple Navigation */}
          <div className="flex overflow-x-auto gap-2 mb-6 pb-2">
            {sectionKeys.map((key) => (
              <button
                key={key}
                onClick={() => setActiveSection(key)}
                className={`whitespace-nowrap px-4 py-2 rounded-full text-sm font-medium transition-colors ${
                  activeSection === key
                    ? 'bg-blue-500 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                }`}
              >
                {sections[key].title.split(' ').slice(0, 3).join(' ')}
              </button>
            ))}
          </div>

          {/* Main Content */}
          <div className="mb-8">
            <h2 className="text-2xl font-bold text-gray-800 mb-4">
              {sections[activeSection].title}
            </h2>
            <div className="text-gray-700">
              {sections[activeSection].content}
            </div>
          </div>

          {/* Simple Navigation Buttons */}
          <div className="flex justify-between border-t border-gray-200 pt-6">
            <button
              onClick={() => {
                const currentIndex = sectionKeys.indexOf(activeSection);
                const prevKey = currentIndex > 0 ? sectionKeys[currentIndex - 1] : sectionKeys[sectionKeys.length - 1];
                setActiveSection(prevKey);
              }}
              className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
            >
              ← Previous
            </button>
            <button
              onClick={() => {
                const currentIndex = sectionKeys.indexOf(activeSection);
                const nextKey = currentIndex < sectionKeys.length - 1 ? sectionKeys[currentIndex + 1] : sectionKeys[0];
                setActiveSection(nextKey);
              }}
              className="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
            >
              Next →
            </button>
          </div>
        </div>

        {/* Simple Footer */}
        <footer className="bg-gray-800 text-white p-6 text-center">
          <p className="text-sm">
            This information is for educational purposes only. Always consult with a veterinarian for professional medical advice.
          </p>
        </footer>
      </div>
    </div>
  );
};

export default PetFirstAidGuide;