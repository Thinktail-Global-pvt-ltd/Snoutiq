import React from 'react';
import Footer from '../components/Footer';
import Header from '../components/Header';

const DogWinterCareGuide = () => {
  return (
    <>
      <Header />
      <div className="min-h-screen bg-gray-50 py-8 px-4 mt-10">
        <div className="max-w-4xl mx-auto">
          
          {/* Blog Header */}
          <header className="text-center mb-8">
            <h1 className="text-3xl font-bold text-gray-800 mb-4">
              Dog Winter Care Guide
            </h1>
            <p className="text-gray-600 text-lg">
              How to Keep Your Dog Safe, Warm and Healthy During Winter
            </p>
            <div className="w-20 h-1 bg-blue-500 mx-auto mt-4"></div>
          </header>

          {/* Introduction */}
          <section className="bg-white rounded-lg shadow-sm p-6 mb-8">
            <p className="text-gray-700 mb-4">
              When the chilly winds start blowing, your dog feels the cold just like you do. Winter can be tough on pets — dry air, icy roads, less sunlight, and limited outdoor time.
            </p>
            <p className="text-gray-700">
              In this guide, we'll cover everything from keeping dogs warm to paw care, diet adjustments, and essential winter health tips.
            </p>
          </section>

          {/* Main Content */}
          <div className="space-y-8">
            
            {/* Section 1 */}
            <section className="bg-white rounded-lg shadow-sm p-6">
              <h2 className="text-xl font-bold text-gray-800 mb-4">1. Understanding Why Winter Care Matters</h2>
              <p className="text-gray-700">
                Many dog owners assume that their pet's fur is enough protection during cold weather — but that's not always true. Some breeds have thin coats, and even thick-furred dogs can struggle with cold air and dry indoor heat. Winter brings risks like cracked paws, dry skin, and joint stiffness. Preparing early helps you prevent these problems and keep your pet comfortable.
              </p>
            </section>

            {/* Section 2 */}
            <section className="bg-white rounded-lg shadow-sm p-6">
              <h2 className="text-xl font-bold text-gray-800 mb-4">2. How to Keep Dogs Warm in Winter</h2>
              <p className="text-gray-700 mb-4">
                Keeping your dog warm doesn't mean just putting on a sweater — it's about maintaining a cozy environment and adjusting your daily routine.
              </p>
              <ul className="list-disc pl-5 text-gray-700 space-y-2">
                <li><span className="font-semibold">Create a warm sleeping space:</span> Keep your dog's bed away from cold floors and drafts. A raised or insulated dog bed with a soft blanket is ideal.</li>
                <li><span className="font-semibold">Invest in dog clothing:</span> Short-haired or small breeds benefit from winter jackets or sweaters.</li>
                <li><span className="font-semibold">Avoid long outdoor stays:</span> Limit outdoor time during extreme cold. Short, brisk walks are better.</li>
                <li><span className="font-semibold">Dry thoroughly:</span> After walks, always towel-dry your dog to remove moisture.</li>
              </ul>
            </section>

            {/* Section 3 */}
            <section className="bg-white rounded-lg shadow-sm p-6">
              <h2 className="text-xl font-bold text-gray-800 mb-4">3. Taking Care of Dogs' Paws in Winter</h2>
              <p className="text-gray-700 mb-4">
                One of the most overlooked parts of winter pet care is paw care. Ice, snow, and salt on roads can damage your dog's paw pads and cause painful cracks.
              </p>
              <ul className="list-disc pl-5 text-gray-700 space-y-2">
                <li><span className="font-semibold">Use paw balm or wax:</span> Apply a protective layer before walks to reduce damage from cold and chemicals.</li>
                <li><span className="font-semibold">Check paws after every walk:</span> Gently wipe or rinse your dog's paws with warm water to remove salt.</li>
                <li><span className="font-semibold">Trim paw hair:</span> Long fur between toes can trap ice and cause discomfort.</li>
                <li><span className="font-semibold">Booties for sensitive paws:</span> Some dogs benefit from wearing winter booties.</li>
              </ul>
            </section>

            {/* Section 4 */}
            <section className="bg-white rounded-lg shadow-sm p-6">
              <h2 className="text-xl font-bold text-gray-800 mb-4">4. Dog Winter Diet Tips</h2>
              <p className="text-gray-700 mb-4">
                Cold weather can change your dog's activity levels and appetite, so it's important to tweak their diet.
              </p>
              <ul className="list-disc pl-5 text-gray-700 space-y-2">
                <li><span className="font-semibold">Monitor calorie intake:</span> Indoor dogs may need fewer calories if less active.</li>
                <li><span className="font-semibold">Protein-rich diet:</span> Foods high in quality protein help maintain body heat and energy.</li>
                <li><span className="font-semibold">Keep them hydrated:</span> Dogs drink less in winter, but dehydration is still possible.</li>
                <li><span className="font-semibold">Warm meals:</span> Slightly warming your dog's food can make it more appealing.</li>
              </ul>
            </section>

            {/* Section 5 */}
            <section className="bg-white rounded-lg shadow-sm p-6">
              <h2 className="text-xl font-bold text-gray-800 mb-4">5. Dog Grooming in Winter</h2>
              <p className="text-gray-700 mb-4">
                Many pet owners reduce grooming during winter — but regular maintenance is crucial for healthy skin and coat.
              </p>
              <ul className="list-disc pl-5 text-gray-700 space-y-2">
                <li><span className="font-semibold">Brush often:</span> Brushing removes tangles and distributes natural oils.</li>
                <li><span className="font-semibold">Avoid over-bathing:</span> Too many baths can strip away natural oils.</li>
                <li><span className="font-semibold">Keep fur manageable:</span> For long-haired breeds, trimming helps avoid mats.</li>
                <li><span className="font-semibold">Check ears and nails:</span> Moisture buildup can cause infections.</li>
              </ul>
            </section>

            {/* Section 6 */}
            <section className="bg-white rounded-lg shadow-sm p-6">
              <h2 className="text-xl font-bold text-gray-800 mb-4">6. Winter Skin Care for Dogs</h2>
              <p className="text-gray-700 mb-4">
                Dry air, low humidity, and heaters can irritate your dog's skin. Good winter skin care keeps them from itching and flaking.
              </p>
              <ul className="list-disc pl-5 text-gray-700 space-y-2">
                <li><span className="font-semibold">Add moisture to the air:</span> A humidifier helps reduce dry skin.</li>
                <li><span className="font-semibold">Omega-3 supplements:</span> These promote healthy skin and a glossy coat.</li>
                <li><span className="font-semibold">Moisturizing sprays:</span> Use vet-approved hydrating sprays for dry patches.</li>
                <li><span className="font-semibold">Gentle brushing:</span> Stimulates oil production and removes dead skin cells.</li>
              </ul>
            </section>

            {/* Section 7 */}
            <section className="bg-white rounded-lg shadow-sm p-6">
              <h2 className="text-xl font-bold text-gray-800 mb-4">7. Common Dog Winter Health Problems</h2>
              <p className="text-gray-700 mb-4">
                Some dogs experience seasonal issues during winter. Be aware of these problems so you can act fast:
              </p>
              <ul className="list-disc pl-5 text-gray-700 space-y-2">
                <li><span className="font-semibold">Hypothermia and frostbite:</span> Symptoms include shivering, weakness, or pale skin.</li>
                <li><span className="font-semibold">Joint stiffness:</span> Cold temperatures can aggravate arthritis in older dogs.</li>
                <li><span className="font-semibold">Seasonal weight gain:</span> Reduced activity can lead to weight gain.</li>
                <li><span className="font-semibold">Behavioral changes:</span> Some dogs become sluggish with less sunlight.</li>
              </ul>
            </section>

            {/* Section 8 */}
            <section className="bg-white rounded-lg shadow-sm p-6">
              <h2 className="text-xl font-bold text-gray-800 mb-4">8. Winter Essentials for Dogs</h2>
              <p className="text-gray-700 mb-4">
                Here's a quick checklist of must-haves for the season:
              </p>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                {[
                  "Warm, insulated dog bed",
                  "Winter jacket or sweater",
                  "Paw balm or booties",
                  "Moisturizing shampoo",
                  "Omega-3 supplements",
                  "Humidifier for indoor air",
                  "Towels for drying after walks",
                  "Interactive indoor toys"
                ].map((item, index) => (
                  <div key={index} className="flex items-center">
                    <span className="text-blue-500 mr-2">•</span>
                    <span className="text-gray-700">{item}</span>
                  </div>
                ))}
              </div>
            </section>

            {/* Section 9 */}
            <section className="bg-white rounded-lg shadow-sm p-6">
              <h2 className="text-xl font-bold text-gray-800 mb-4">9. Love and Comfort Matter Most</h2>
              <p className="text-gray-700">
                Beyond diet and skincare, winter is about bonding. Spend quality time cuddling your dog, playing indoors, or simply sitting together. Dogs thrive on affection, and that emotional warmth is what truly keeps them happy through the cold season.
              </p>
            </section>

          </div>

          {/* Conclusion */}
          <section className="bg-blue-50 rounded-lg p-6 mt-8">
            <h2 className="text-xl font-bold text-gray-800 mb-4">Conclusion</h2>
            <p className="text-gray-700 mb-4">
              This winter care guide gives you everything you need to know about how to take care of your dog in winter — from warmth and nutrition to grooming and paw care.
            </p>
            <p className="text-gray-700">
              Stay consistent, observe your dog's behavior, and make small adjustments as the weather changes. Your effort ensures your furry friend enjoys a cozy, safe, and joyful winter beside you.
            </p>
          </section>

        </div>
      </div>
      <Footer />
    </>
  );
};

export default DogWinterCareGuide;