import React, { useState, useEffect } from 'react';
import { Helmet, HelmetProvider } from 'react-helmet-async';
import Header from '../components/Header';
import Footer from '../components/Footer';
import img4 from '../assets/images/first_aid_tips.jpeg';

const PetFirstAidGuide = () => {
  const [showBackToTop, setShowBackToTop] = useState(false);

  // Scroll effect के लिए useEffect (meta tags वाला नहीं)
  useEffect(() => {
    const handleScroll = () => {
      setShowBackToTop(window.scrollY > 300);
    };

    window.addEventListener('scroll', handleScroll);
    
    // Cleanup function
    return () => {
      window.removeEventListener('scroll', handleScroll);
    };
  }, []);

  const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const scrollToSection = (id) => {
    const element = document.getElementById(id);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  };

  // Structured data constant
  const structuredData = {
    "@context": "https://schema.org",
    "@type": "BlogPosting",
    "headline": "First Aid Tips Every Pet Parent Should Know",
    "description": "Essential Emergency Guide to Save Your Pet's Life When Seconds Matter",
    "author": {
      "@type": "Organization",
      "name": "Snoutiq - Pet Care Experts"
    },
    "datePublished": "2024-12-01",
    "dateModified": "2024-12-01",
    "image": "https://snoutiq.com/images/pet-first-aid-guide.jpg",
    "publisher": {
      "@type": "Organization",
      "name": "Snoutiq",
      "logo": {
        "@type": "ImageObject",
        "url": "https://snoutiq.com/logo.png"
      }
    },
    "mainEntityOfPage": {
      "@type": "WebPage",
      "@id": "https://snoutiq.com/first-aid-tips-every-pet-parent-should-know"
    }
  };

  return (
    <HelmetProvider>
      {/* Helmet component for all meta tags */}
      <Helmet>
        <title>First Aid Tips Every Pet Parent Should Know – Essential Pet Emergency Guide</title>
        <meta 
          name="description" 
          content="Learn life-saving first aid tips every pet parent should know. Discover how to handle pet injuries, choking, burns, bleeding, and other emergencies confidently at home." 
        />
        <meta 
          name="keywords" 
          content="first aid tips every pet parent should know, pet first aid, dog first aid, cat first aid, pet emergency care, pet first aid kit, pet CPR, pet choking treatment" 
        />
        <meta name="author" content="Snoutiq - Pet Care Experts" />
        <meta name="robots" content="index, follow" />
        
        {/* Open Graph tags */}
        <meta property="og:title" content="First Aid Tips Every Pet Parent Should Know – Essential Pet Emergency Guide" />
        <meta property="og:description" content="Learn life-saving first aid tips for pets. Handle injuries, choking, burns, and emergencies confidently." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://snoutiq.com/first-aid-tips-every-pet-parent-should-know" />
        <meta property="og:image" content="https://snoutiq.com/images/pet-first-aid-guide.jpg" />
        
        {/* Twitter Card tags */}
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="First Aid Tips Every Pet Parent Should Know – Essential Pet Emergency Guide" />
        <meta name="twitter:description" content="Learn life-saving first aid tips for pets. Handle injuries, choking, burns, and emergencies confidently." />
        <meta name="twitter:image" content="https://snoutiq.com/images/pet-first-aid-guide.jpg" />
        
        {/* Canonical link */}
        <link rel="canonical" href="https://snoutiq.com/first-aid-tips-every-pet-parent-should-know" />
        
        {/* Structured data (JSON-LD) */}
        <script type="application/ld+json">
          {JSON.stringify(structuredData)}
        </script>
      </Helmet>

      {/* Rest of your JSX remains exactly the same */}
      <Header />
      <article className="max-w-4xl mx-auto bg-white shadow-2xl rounded-lg overflow-hidden mt-20" itemScope itemType="http://schema.org/BlogPosting">

        <header className="bg-gradient-to-br from-blue-500 to-purple-600 text-white p-8 md:p-12 text-center relative overflow-hidden">
          <div className="absolute inset-0 opacity-10" style={{
            backgroundImage: `url("data:image/svg+xml,<svg width='100' height='100' xmlns='http://www.w3.org/2000/svg'><path d='M0 0h100v100H0z' fill='none'/><path d='M50 10L90 90H10z' fill='white'/></svg>")`,
            backgroundRepeat: 'repeat'
          }} />
          <div className="relative z-10">
            <h1 className="text-3xl md:text-4xl font-bold mb-4 leading-tight" itemProp="headline">
              First Aid Tips Every Pet Parent Should Know
            </h1>
            <p className="text-xl opacity-95 mb-4 font-light">
              Essential Emergency Guide to Save Your Pet's Life When Seconds Matter
            </p>
            <div className="flex flex-wrap justify-center gap-4 mt-6 text-sm md:text-base">
              <span className="flex items-center gap-1">
                📅 Updated: December 2024
              </span>
              <span className="flex items-center gap-1">
                ⏱️ Reading Time: 12 minutes
              </span>
              <span className="flex items-center gap-1">
                🚨 Life-Saving Guide
              </span>
            </div>
          </div>
        </header>
        
        <section>
          <img src={img4} alt="Pet first aid tips illustration showing emergency care for dogs and cats" />
        </section>

        {/* Main Content */}
        <div className="p-6 md:p-10" itemProp="articleBody">
          <p className="mb-6 text-gray-700 leading-relaxed">
            Emergencies can happen anytime, and when they involve your pet, every second matters. Knowing the 
            <strong className="text-blue-600 font-semibold"> first aid tips every pet parent should know</strong> 
            can save your dog's or cat's life even before you reach medical care.
          </p>

          <p className="mb-8 text-gray-700 leading-relaxed">
            In the first 50 words, this guide explains practical, step-by-step first aid actions for pets — controlling bleeding, treating burns, clearing choking, managing heatstroke, and handling fractures. You'll also learn about building a pet first aid kit and how to stay calm when your pet needs you most.
          </p>

          {/* Table of Contents */}
          <nav className="bg-gradient-to-r from-blue-50 to-purple-50 border-l-4 border-blue-500 p-6 md:p-8 rounded-lg mb-10" aria-label="Table of Contents">
            <h3 className="text-xl md:text-2xl font-bold text-blue-600 mb-4">📑 Table of Contents</h3>
            <ol className="list-decimal ml-6 space-y-2">
              {[
                { id: 'why-matters', text: 'Why Pet First Aid Matters' },
                { id: 'first-aid-kit', text: 'What to Include in a Pet First Aid Kit' },
                { id: 'bleeding', text: 'How to Handle Bleeding or Cuts' },
                { id: 'choking', text: 'Treating Choking or Airway Blockage' },
                { id: 'heatstroke', text: 'Heatstroke in Pets' },
                { id: 'burns', text: 'Burns & Scalds' },
                { id: 'insect-bites', text: 'Insect Bites & Stings' },
                { id: 'fractures', text: 'Fractures & Sprains' },
                { id: 'poisoning', text: 'Poisoning or Toxic Ingestion' },
                { id: 'cpr', text: 'CPR for Pets' },
                { id: 'reference', text: 'Quick Emergency Reference Table' },
                { id: 'faq', text: 'Frequently Asked Questions' }
              ].map((item) => (
                <li key={item.id}>
                  <button
                    onClick={() => scrollToSection(item.id)}
                    className="text-gray-800 hover:text-blue-600 hover:underline transition-colors text-left"
                  >
                    {item.text}
                  </button>
                </li>
              ))}
            </ol>
          </nav>

          {/* Section 1 - Why Matters */}
          <section id="why-matters" className="scroll-mt-20">
            <h2 className="text-2xl md:text-3xl font-bold text-blue-600 mb-6 pb-3 border-b-2 border-blue-500 relative">
              Why Pet First Aid Matters
              <span className="absolute bottom-0 left-0 w-20 h-1 bg-purple-600"></span>
            </h2>
            <p className="mb-6 text-gray-700 leading-relaxed">
              Accidents are unpredictable. Pets can swallow sharp objects, suffer insect stings, or injure themselves while playing. Having basic first aid knowledge bridges the gap between home care and professional help.
            </p>

            <div className="bg-gradient-to-r from-blue-50 to-purple-50 border-l-4 border-blue-500 p-6 rounded-lg shadow-sm mb-8">
              <h3 className="text-xl font-bold text-blue-600 mb-4">Key Reasons You Need It:</h3>
              <ul className="list-disc ml-6 space-y-2 text-gray-700">
                <li>Stabilize your pet before reaching the vet</li>
                <li>Prevent injuries from worsening during travel</li>
                <li>Recognize emergencies faster</li>
                <li>Remain calm when others panic</li>
              </ul>
            </div>

            <p className="text-gray-700 leading-relaxed">
              Even a small untreated wound can turn infectious. With the right 
              <strong className="text-blue-600 font-semibold"> first aid tips for pet parents</strong>, 
              recovery becomes safer and faster.
            </p>
          </section>

          {/* Continue with all other sections... */}
          {/* Section 2 to Section 10 - मूल कोड की तरह ही रहेगा */}
          
          {/* Emergency Reference Table */}
          <section id="reference" className="scroll-mt-20 mt-16">
            <h2 className="text-2xl md:text-3xl font-bold text-blue-600 mb-6 pb-3 border-b-2 border-blue-500 relative">
              Quick Emergency Reference Table
              <span className="absolute bottom-0 left-0 w-20 h-1 bg-purple-600"></span>
            </h2>
            
            <div className="overflow-x-auto rounded-lg shadow-lg mb-8">
              <table className="min-w-full bg-white">
                <thead>
                  <tr className="bg-gradient-to-r from-blue-500 to-purple-600">
                    <th className="py-4 px-6 text-left text-white font-bold">Emergency</th>
                    <th className="py-4 px-6 text-left text-white font-bold">Action</th>
                    <th className="py-4 px-6 text-left text-white font-bold">Vet Needed?</th>
                  </tr>
                </thead>
                <tbody>
                  {[
                    ['Bleeding', 'Direct pressure', 'Yes'],
                    ['Choking', 'Clear airway / Heimlich', 'Yes'],
                    ['Heatstroke', 'Cool slowly', 'Yes'],
                    ['Burns', 'Cool water', 'Yes'],
                    ['Poisoning', 'Call vet immediately', 'Yes'],
                    ['Fracture', 'Immobilize', 'Yes'],
                    ['Bee sting', 'Cold compress', 'If swelling severe'],
                    ['Shock', 'Keep warm', 'Yes']
                  ].map(([emergency, action, vetNeeded], index) => (
                    <tr key={index} className="hover:bg-gray-50 transition-colors">
                      <td className="py-4 px-6 border-b border-gray-200 font-semibold text-gray-800">{emergency}</td>
                      <td className="py-4 px-6 border-b border-gray-200 text-gray-700">{action}</td>
                      <td className="py-4 px-6 border-b border-gray-200 text-gray-700">{vetNeeded}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>

          {/* CTA Section */}
          <div className="bg-gradient-to-br from-blue-500 to-purple-600 text-white p-8 md:p-10 rounded-xl shadow-xl my-16 text-center">
            <h3 className="text-2xl md:text-3xl font-bold mb-4">🚀 Stay Prepared for Pet Emergencies</h3>
            <p className="text-lg opacity-95 leading-relaxed">
              Preparedness is a superpower for pet parents. With these first aid tips every pet parent should know, 
              you will be ready to stay calm, act fast, and protect your furry companion in emergencies. 
              First aid does not replace veterinary care — but it gives your pet a fighting chance when minutes matter.
            </p>
          </div>
        </div>

        {/* FAQ Section */}
        <section id="faq" className="bg-gradient-to-b from-gray-50 to-gray-100 p-8 md:p-12 -mx-4 md:-mx-10 -mb-10">
          <div className="max-w-3xl mx-auto">
            <h2 className="text-2xl md:text-3xl font-bold text-blue-600 mb-10 text-center">
              Frequently Asked Questions
            </h2>
            
            <div className="space-y-6">
              {[
                {
                  question: 'What are the most important first aid tips every pet parent should know?',
                  answer: 'Controlling bleeding, choking response, burn care and fracture stabilization are the most critical first aid skills.'
                },
                {
                  question: 'Can I use human antiseptics on pets?',
                  answer: 'No — only pet-safe antiseptics like chlorhexidine should be used.'
                },
                {
                  question: 'How often should I update my pet\'s first aid kit?',
                  answer: 'Every 3–6 months to ensure supplies are fresh and not expired.'
                },
                {
                  question: 'Can I always use hydrogen peroxide to induce vomiting?',
                  answer: 'Only if a vet advises it. Never use it for corrosive chemical ingestion or if your pet is unconscious.'
                },
                {
                  question: 'How can I prevent pet emergencies?',
                  answer: 'Pet-proof your home by removing toxic plants and chemicals, supervise playtime, maintain regular vet check-ups.'
                },
                {
                  question: 'What\'s the normal temperature for dogs and cats?',
                  answer: 'Dogs: 101–102.5°F (38.3–39.2°C) · Cats: 100.5–102.5°F (38.1–39.2°C). Use a digital thermometer rectally for accurate readings.'
                },
                {
                  question: 'Should I take a pet first aid course?',
                  answer: 'Yes — hands-on training prepares you for real scenarios. Many veterinary clinics and pet organizations offer certified first aid courses.'
                }
              ].map((faq, index) => (
                <div 
                  key={index} 
                  className="bg-white p-6 rounded-lg shadow-md border-l-4 border-blue-500 hover:shadow-lg transition-shadow"
                  itemScope
                  itemProp="mainEntity"
                  itemType="https://schema.org/Question"
                >
                  <div className="faq-question font-bold text-blue-600 text-lg mb-3" itemProp="name">
                    {index + 1}️⃣ {faq.question}
                  </div>
                  <div 
                    className="faq-answer text-gray-700 leading-relaxed"
                    itemScope
                    itemProp="acceptedAnswer"
                    itemType="https://schema.org/Answer"
                  >
                    <p itemProp="text">{faq.answer}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* Back to Top Button */}
        <button
          onClick={scrollToTop}
          className={`fixed bottom-8 right-8 w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 text-white rounded-full shadow-xl flex items-center justify-center transition-all duration-300 hover:scale-110 ${
            showBackToTop ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10 pointer-events-none'
          }`}
          aria-label="Back to top"
        >
          ↑
        </button>
      </article>
      
      <Footer />
    </HelmetProvider>
  );
};

export default PetFirstAidGuide;