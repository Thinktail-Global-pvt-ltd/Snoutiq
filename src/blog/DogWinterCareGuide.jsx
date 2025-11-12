import React, { useState } from 'react';
import Footer from '../components/Footer';
import Header from '../components/Header';

const DogWinterCareGuide = () => {
  const [activeSection, setActiveSection] = useState(null);

  const toggleSection = (section) => {
    setActiveSection(activeSection === section ? null : section);
  };

  return (
    <>        <Header/>
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 py-8 px-4 mt-10">

      <div className="max-w-4xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden">
        {/* Header */}
        <header className="bg-gradient-to-r from-blue-600 to-indigo-700 text-white p-8">
          <h1 className="text-3xl md:text-4xl font-bold mb-4">Dog Winter Care Guide</h1>
          <p className="text-lg md:text-xl opacity-90">
            How to Take Care of Dogs in Winter and Keep Them Safe & Warm
          </p>
        </header>

        {/* Introduction */}
        <section className="p-6 md:p-8 bg-white">
          <p className="text-gray-700 mb-4">
            When the chilly winds start blowing, your dog feels the cold just like you do. Winter can be tough on pets — dry air, icy roads, less sunlight, and limited outdoor time. That's why it's important to know how to take care of dogs in winter so they stay warm, healthy, and happy.
          </p>
          <p className="text-gray-700">
            In this comprehensive dog winter care guide, we'll cover everything from taking care of dogs' paws in winter to grooming, diet adjustments, and essential winter health tips.
          </p>
        </section>

        {/* Table of Contents */}
        <section className="p-6 md:p-8 bg-blue-50">
          <h2 className="text-2xl font-bold text-gray-800 mb-4">Table of Contents</h2>
          <ul className="grid grid-cols-1 md:grid-cols-2 gap-2">
            {[
              "Understanding Why Winter Care Matters",
              "How to Keep Dogs Warm in Winter",
              "Taking Care of Dogs' Paws in Winter",
              "Dog Winter Diet Tips",
              "Dog Grooming in Winter",
              "Winter Skin Care for Dogs",
              "Common Dog Winter Health Problems",
              "Winter Essentials for Dogs",
              "Love and Comfort Matter Most"
            ].map((item, index) => (
              <li key={index} className="flex items-start">
                <span className="text-blue-600 mr-2">•</span>
                <a 
                  href={`#section-${index + 1}`} 
                  className="text-blue-700 hover:text-blue-900 hover:underline transition-colors"
                >
                  {item}
                </a>
              </li>
            ))}
          </ul>
        </section>

        {/* Main Content */}
        <main className="p-6 md:p-8">
          {/* Section 1 */}
          <section id="section-1" className="mb-10">
            <div 
              className="flex justify-between items-center cursor-pointer"
              onClick={() => toggleSection(1)}
            >
              <h2 className="text-2xl font-bold text-gray-800">1. Understanding Why Winter Care Matters</h2>
              <span className="text-blue-600 text-xl">
                {activeSection === 1 ? '−' : '+'}
              </span>
            </div>
            {activeSection === 1 && (
              <div className="mt-4 p-4 bg-blue-50 rounded-lg">
                <p className="text-gray-700 mb-4">
                  Many dog owners assume that their pet's fur is enough protection during cold weather — but that's not always true. Some breeds have thin coats, and even thick-furred dogs can struggle with cold air and dry indoor heat.
                </p>
                <p className="text-gray-700">
                  Winter brings risks like cracked paws, dry skin, and joint stiffness. Preparing early and following a structured dog winter care guide helps you prevent these problems and keep your pet comfortable.
                </p>
              </div>
            )}
          </section>

          {/* Section 2 */}
          <section id="section-2" className="mb-10">
            <div 
              className="flex justify-between items-center cursor-pointer"
              onClick={() => toggleSection(2)}
            >
              <h2 className="text-2xl font-bold text-gray-800">2. How to Keep Dogs Warm in Winter</h2>
              <span className="text-blue-600 text-xl">
                {activeSection === 2 ? '−' : '+'}
              </span>
            </div>
            {activeSection === 2 && (
              <div className="mt-4 p-4 bg-blue-50 rounded-lg">
                <p className="text-gray-700 mb-4">
                  Keeping your dog warm doesn't mean just putting on a sweater — it's about maintaining a cozy environment and adjusting your daily routine.
                </p>
                <ul className="list-disc pl-5 text-gray-700 space-y-2">
                  <li><span className="font-semibold">Create a warm sleeping space:</span> Keep your dog's bed away from cold floors and drafts. A raised or insulated dog bed with a soft blanket is ideal.</li>
                  <li><span className="font-semibold">Invest in dog clothing:</span> Short-haired or small breeds benefit from winter jackets or sweaters. Choose one that fits snugly but allows movement.</li>
                  <li><span className="font-semibold">Avoid long outdoor stays:</span> Limit outdoor time during extreme cold. Short, brisk walks are better than long exposure.</li>
                  <li><span className="font-semibold">Dry thoroughly:</span> After walks, always towel-dry your dog to remove moisture from their coat and paws.</li>
                </ul>
                <p className="text-gray-700 mt-4">
                  By following these simple steps, you ensure your pet stays safe and warm, preventing chills and discomfort.
                </p>
              </div>
            )}
          </section>

          {/* Section 3 */}
          <section id="section-3" className="mb-10">
            <div 
              className="flex justify-between items-center cursor-pointer"
              onClick={() => toggleSection(3)}
            >
              <h2 className="text-2xl font-bold text-gray-800">3. Taking Care of Dogs' Paws in Winter</h2>
              <span className="text-blue-600 text-xl">
                {activeSection === 3 ? '−' : '+'}
              </span>
            </div>
            {activeSection === 3 && (
              <div className="mt-4 p-4 bg-blue-50 rounded-lg">
                <p className="text-gray-700 mb-4">
                  One of the most overlooked parts of winter pet care is taking care of dogs' paws in winter. Ice, snow, and salt on roads can damage your dog's paw pads and cause painful cracks or irritation.
                </p>
                <p className="text-gray-700 font-semibold mb-2">Here's what you can do:</p>
                <ul className="list-disc pl-5 text-gray-700 space-y-2">
                  <li><span className="font-semibold">Use paw balm or wax:</span> Apply a protective layer before walks to reduce damage from cold and chemicals.</li>
                  <li><span className="font-semibold">Check paws after every walk:</span> Gently wipe or rinse your dog's paws with warm water to remove salt or debris.</li>
                  <li><span className="font-semibold">Trim paw hair:</span> Long fur between toes can trap ice and cause discomfort. Keep it neatly trimmed.</li>
                  <li><span className="font-semibold">Booties for sensitive paws:</span> Some dogs benefit from wearing winter booties. They keep the paws warm and protected from harsh surfaces.</li>
                  <li><span className="font-semibold">Regular inspection:</span> Look for redness, cuts, or dryness. If you spot any damage, apply a pet-safe moisturizer or consult your vet.</li>
                </ul>
                <p className="text-gray-700 mt-4">
                  Healthy paws mean comfortable walks — this small habit in your dog winter care guide can prevent many winter problems.
                </p>
              </div>
            )}
          </section>

          {/* Section 4 */}
          <section id="section-4" className="mb-10">
            <div 
              className="flex justify-between items-center cursor-pointer"
              onClick={() => toggleSection(4)}
            >
              <h2 className="text-2xl font-bold text-gray-800">4. Dog Winter Diet Tips</h2>
              <span className="text-blue-600 text-xl">
                {activeSection === 4 ? '−' : '+'}
              </span>
            </div>
            {activeSection === 4 && (
              <div className="mt-4 p-4 bg-blue-50 rounded-lg">
                <p className="text-gray-700 mb-4">
                  Cold weather can change your dog's activity levels and appetite, so it's important to tweak their diet.
                </p>
                <ul className="list-disc pl-5 text-gray-700 space-y-2">
                  <li><span className="font-semibold">Monitor calorie intake:</span> Indoor dogs may need slightly fewer calories if they're less active, but outdoor dogs might need more for warmth.</li>
                  <li><span className="font-semibold">Protein-rich diet:</span> Foods high in quality protein help maintain body heat and energy.</li>
                  <li><span className="font-semibold">Keep them hydrated:</span> Dogs drink less in winter, but dehydration is still possible. Make sure water bowls are clean and not frozen.</li>
                  <li><span className="font-semibold">Warm meals:</span> Slightly warming your dog's food (not hot!) can make it more appealing and comforting.</li>
                </ul>
                <p className="text-gray-700 mt-4">
                  Balanced nutrition keeps your pet's immune system strong and their coat shiny throughout the winter months.
                </p>
              </div>
            )}
          </section>

          {/* Section 5 */}
          <section id="section-5" className="mb-10">
            <div 
              className="flex justify-between items-center cursor-pointer"
              onClick={() => toggleSection(5)}
            >
              <h2 className="text-2xl font-bold text-gray-800">5. Dog Grooming in Winter</h2>
              <span className="text-blue-600 text-xl">
                {activeSection === 5 ? '−' : '+'}
              </span>
            </div>
            {activeSection === 5 && (
              <div className="mt-4 p-4 bg-blue-50 rounded-lg">
                <p className="text-gray-700 mb-4">
                  Many pet owners reduce grooming during winter — but regular maintenance is crucial. Grooming supports healthy skin and a well-functioning coat.
                </p>
                <ul className="list-disc pl-5 text-gray-700 space-y-2">
                  <li><span className="font-semibold">Brush often:</span> Brushing removes tangles, improves circulation, and distributes natural oils, which prevent dryness.</li>
                  <li><span className="font-semibold">Avoid over-bathing:</span> Too many baths can strip away natural oils, making skin dry. When needed, use moisturizing, dog-safe shampoo.</li>
                  <li><span className="font-semibold">Keep fur manageable:</span> For long-haired breeds, trimming slightly helps avoid mats and moisture retention.</li>
                  <li><span className="font-semibold">Check ears and nails:</span> Moisture buildup can cause infections, and long nails make it harder to walk on slippery surfaces.</li>
                </ul>
                <p className="text-gray-700 mt-4">
                  Following a simple dog grooming in winter routine can make a big difference in comfort and coat health.
                </p>
              </div>
            )}
          </section>

          {/* Section 6 */}
          <section id="section-6" className="mb-10">
            <div 
              className="flex justify-between items-center cursor-pointer"
              onClick={() => toggleSection(6)}
            >
              <h2 className="text-2xl font-bold text-gray-800">6. Winter Skin Care for Dogs</h2>
              <span className="text-blue-600 text-xl">
                {activeSection === 6 ? '−' : '+'}
              </span>
            </div>
            {activeSection === 6 && (
              <div className="mt-4 p-4 bg-blue-50 rounded-lg">
                <p className="text-gray-700 mb-4">
                  Dry air, low humidity, and heaters can irritate your dog's skin. Good winter skin care for dogs keeps them from itching and flaking.
                </p>
                <ul className="list-disc pl-5 text-gray-700 space-y-2">
                  <li><span className="font-semibold">Add moisture to the air:</span> A humidifier helps reduce dry skin.</li>
                  <li><span className="font-semibold">Omega-3 supplements:</span> These promote healthy skin and a glossy coat.</li>
                  <li><span className="font-semibold">Moisturizing sprays:</span> Use vet-approved hydrating sprays for dry patches.</li>
                  <li><span className="font-semibold">Gentle brushing:</span> Stimulates oil production and removes dead skin cells.</li>
                </ul>
                <p className="text-gray-700 mt-4">
                  Healthy skin is the first line of defense against cold weather issues.
                </p>
              </div>
            )}
          </section>

          {/* Section 7 */}
          <section id="section-7" className="mb-10">
            <div 
              className="flex justify-between items-center cursor-pointer"
              onClick={() => toggleSection(7)}
            >
              <h2 className="text-2xl font-bold text-gray-800">7. Common Dog Winter Health Problems</h2>
              <span className="text-blue-600 text-xl">
                {activeSection === 7 ? '−' : '+'}
              </span>
            </div>
            {activeSection === 7 && (
              <div className="mt-4 p-4 bg-blue-50 rounded-lg">
                <p className="text-gray-700 mb-4">
                  Some dogs experience seasonal issues during winter. Be aware of these dog winter health problems so you can act fast:
                </p>
                <ul className="list-disc pl-5 text-gray-700 space-y-2">
                  <li><span className="font-semibold">Hypothermia and frostbite:</span> Symptoms include shivering, weakness, or pale skin on ears and paws. Warm your dog gradually and consult a vet.</li>
                  <li><span className="font-semibold">Joint stiffness:</span> Cold temperatures can aggravate arthritis in older dogs. Provide a warm sleeping spot and consider vet-recommended joint supplements.</li>
                  <li><span className="font-semibold">Seasonal weight gain:</span> Reduced activity can lead to weight gain. Keep playtime going indoors.</li>
                  <li><span className="font-semibold">Behavioral changes:</span> Some dogs become sluggish or moody with less sunlight. Keep them active and mentally engaged.</li>
                </ul>
                <p className="text-gray-700 mt-4">
                  Preventing these issues ensures your pet stays healthy all season.
                </p>
              </div>
            )}
          </section>

          {/* Section 8 */}
          <section id="section-8" className="mb-10">
            <div 
              className="flex justify-between items-center cursor-pointer"
              onClick={() => toggleSection(8)}
            >
              <h2 className="text-2xl font-bold text-gray-800">8. Winter Essentials for Dogs</h2>
              <span className="text-blue-600 text-xl">
                {activeSection === 8 ? '−' : '+'}
              </span>
            </div>
            {activeSection === 8 && (
              <div className="mt-4 p-4 bg-blue-50 rounded-lg">
                <p className="text-gray-700 mb-4">
                  Here's a quick checklist of must-haves for the season:
                </p>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                  {[
                    "Warm, insulated dog bed",
                    "Winter jacket or sweater",
                    "Paw balm or booties",
                    "Moisturizing shampoo and conditioner",
                    "Omega-3 or skin supplements",
                    "Humidifier for indoor air",
                    "Towels for drying after walks",
                    "Interactive indoor toys"
                  ].map((item, index) => (
                    <div key={index} className="flex items-center">
                      <span className="text-blue-600 mr-2">•</span>
                      <span className="text-gray-700">{item}</span>
                    </div>
                  ))}
                </div>
                <p className="text-gray-700 mt-4">
                  Keeping these winter essentials for dogs handy ensures your pet is always comfortable.
                </p>
              </div>
            )}
          </section>

          {/* Section 9 */}
          <section id="section-9" className="mb-10">
            <div 
              className="flex justify-between items-center cursor-pointer"
              onClick={() => toggleSection(9)}
            >
              <h2 className="text-2xl font-bold text-gray-800">9. Love and Comfort Matter Most</h2>
              <span className="text-blue-600 text-xl">
                {activeSection === 9 ? '−' : '+'}
              </span>
            </div>
            {activeSection === 9 && (
              <div className="mt-4 p-4 bg-blue-50 rounded-lg">
                <p className="text-gray-700">
                  Beyond diet and skincare, winter is about bonding. Spend quality time cuddling your dog, playing indoors, or simply sitting together by the heater. Dogs thrive on affection, and that warmth — both emotional and physical — is what truly keeps them happy through the cold season.
                </p>
              </div>
            )}
          </section>

          {/* Conclusion */}
          <section className="mt-12 p-6 bg-gradient-to-r from-blue-100 to-indigo-100 rounded-xl">
            <h2 className="text-2xl font-bold text-gray-800 mb-4">Conclusion</h2>
            <p className="text-gray-700 mb-4">
              This dog winter care guide gives you everything you need to know about how to take care of dogs in winter — from warmth and nutrition to grooming and taking care of dogs' paws in winter.
            </p>
            <p className="text-gray-700">
              Stay consistent, observe your dog's behavior, and make small adjustments as the weather changes. Your effort ensures your furry friend enjoys a cozy, safe, and joyful winter beside you.
            </p>
          </section>
        </main>

        {/* Footer */}
        <footer className="bg-gray-800 text-white p-6 text-center">
          <p>© {new Date().getFullYear()} Dog Winter Care Guide. All rights reserved.</p>
        </footer>
      </div>
      </div>
      <Footer/>
      </>
    
  );
};

export default DogWinterCareGuide;