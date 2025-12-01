'use client';

import { useRef, useEffect, useState, useMemo } from 'react';
import { Link } from 'react-router-dom';
import Hero from './HeroSection';
import FeatureCard from '../components/FeatureCard';
import Benefits from '../components/Benefits';
import Testimonials from '../components/Testimonials';
import CTA from '../components/CTA';
import Header from '../components/Header';
import Footer from '../components/Footer';

const DelhiPage = () => {
  const sectionRef = useRef(null);
  const [isInView, setIsInView] = useState(false);

  // Intersection Observer
  useEffect(() => {
    const element = sectionRef.current;
    if (!element) return;

    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setIsInView(true);
          observer.unobserve(element);
        }
      },
      { 
        threshold: 0.1,
        rootMargin: '50px'
      }
    );

    observer.observe(element);
    return () => observer.disconnect();
  }, []);

  // Memoized data
  const localFeatures = useMemo(() => [
    {
      icon: 'MapPin',
      title: 'Trusted Across Delhi',
      description: 'Supporting leading veterinary practices across South Delhi, Central Delhi, North Delhi, and East Delhi.',
    },
    {
      icon: 'Users',
      title: 'Local Language Support',
      description: 'Multi-language interface supporting Hindi, English, and regional languages for better client communication.',
    },
    {
      icon: 'Clock',
      title: '24/7 Local Support',
      description: 'Dedicated support team based in Delhi NCR, available round the clock for immediate assistance.',
    },
    {
      icon: 'Award',
      title: 'Delhi Compliance',
      description: 'Fully compliant with Delhi veterinary regulations and Indian data protection laws.',
    },
    {
      icon: 'TrendingUp',
      title: 'Proven Results',
      description: 'Delhi clinics report significant improvements in efficiency and client satisfaction.',
    },
    {
      icon: 'Phone',
      title: 'Local Training',
      description: 'In-person onboarding and training sessions available at your Delhi clinic location.',
    },
  ], []);

  const benefits = useMemo(() => [
    {
      icon: 'Heart',
      title: 'Better Patient Care',
      description: 'Streamline workflows to focus more on pets',
    },
    {
      icon: 'Clock',
      title: 'Save Time',
      description: 'Reduce admin work significantly',
    },
    {
      icon: 'Users',
      title: 'Happy Clients',
      description: 'Modern experience clients love',
    },
    {
      icon: 'Zap',
      title: 'Quick Setup',
      description: 'Go live in under 24 hours',
    },
  ], []);

//   const delhiTestimonials = useMemo(() => [
//     {
//       name: 'Dr. Priya Sharma',
//       role: 'Lead Veterinarian',
//       company: 'PetCare Clinic, South Delhi',
//       content: 'SnoutIQ has transformed our practice. The local support team is exceptional, and our clients love the convenience of video consultations.',
//       rating: 5,
//     },
//     {
//       name: 'Dr. Rajesh Kapoor',
//       role: 'Clinic Owner',
//       company: 'Delhi Animal Hospital, Central Delhi',
//       content: 'We significantly increased our appointment capacity without hiring additional staff. The results were visible within the first month.',
//       rating: 5,
//     },
//     {
//       name: 'Dr. Anjali Verma',
//       role: 'Emergency Veterinarian',
//       company: 'Delhi Vet Emergency, East Delhi',
//       content: 'The AI triage system is incredibly accurate. It helps us prioritize critical cases instantly, which is crucial for emergency care.',
//       rating: 5,
//     },
//   ], []);

  const delhiAreas = useMemo(() => [
    'South Delhi', 'Central Delhi', 'North Delhi', 'East Delhi', 'West Delhi',
    'Vasant Kunj', 'Saket', 'Greater Kailash', 'Defence Colony', 'Hauz Khas',
    'Lajpat Nagar', 'Nehru Place', 'Connaught Place', 'Karol Bagh', 'Dwarka',
  ], []);

  // SVG Icons
  const Icons = useMemo(() => ({
    MapPin: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>
    ),
    Users: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
      </svg>
    ),
    Clock: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
    ),
    Award: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
    ),
    TrendingUp: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
      </svg>
    ),
    Phone: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
      </svg>
    ),
    Heart: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
      </svg>
    ),
    Zap: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z"/>
      </svg>
    )
  }), []);

  // Animation classes
  const getAnimationClass = (index, type = 'fade') => {
    if (!isInView) {
      return type === 'fade' ? 'opacity-0' : 'opacity-0 translate-y-6';
    }
    
    const baseTransition = 'transition-all duration-500 ease-out';
    
    if (type === 'fade') {
      return `${baseTransition} opacity-100`;
    }
    
    return `${baseTransition} opacity-100 translate-y-0`;
  };

  return (
    <>
    <Header/>
      <Hero
        badge="Trusted by Delhi Veterinary Professionals"
        title="Delhi's Leading Veterinary Practice Management Software"
        subtitle="Transform your Delhi clinic with AI-powered triage, HD video consultations, and smart scheduling designed for modern veterinary practices."
        ctaPrimary={{ text: 'Schedule Delhi Demo', href: 'https://docs.google.com/forms/d/e/1FAIpQLSeQkcn7C8oSvoN4_eMRwWjN4nLfc0IPQT_ZKuwMKMAKzh4SSQ/viewform' }}
        ctaSecondary={{ text: 'Check Demo', href: 'https://docs.google.com/forms/d/e/1FAIpQLSeQkcn7C8oSvoN4_eMRwWjN4nLfc0IPQT_ZKuwMKMAKzh4SSQ/viewform' }}
      />

      <Benefits benefits={benefits} variant="default" />

      {/* Local Features */}
      <section ref={sectionRef} className="py-16 md:py-20 lg:py-24 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12 md:mb-16">
            <h2 
              className="text-3xl sm:text-4xl md:text-5xl font-bold text-slate-900 mb-4 leading-tight"
              style={{ transitionDelay: isInView ? '100ms' : '0ms' }}
            >
              Built for <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">Delhi Veterinary Practices</span>
            </h2>
            <p 
              className="text-lg sm:text-xl md:text-2xl text-slate-600 max-w-3xl mx-auto leading-relaxed"
              style={{ transitionDelay: isInView ? '200ms' : '0ms' }}
            >
              Local support, regional compliance, and features designed for Delhi clinics
            </p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
            {localFeatures.map((feature, index) => (
              <FeatureCard
                key={index}
                icon={Icons[feature.icon]}
                title={feature.title}
                description={feature.description}
                index={index}
                variant="gradient"
              />
            ))}
          </div>
        </div>
      </section>

      {/* Areas Served */}
      <section className="py-16 md:py-20 lg:py-24 bg-gradient-to-br from-slate-50 to-blue-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12 md:mb-16">
            <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold text-slate-900 mb-4 leading-tight">
              Serving Veterinary Clinics <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">Across Delhi</span>
            </h2>
            <p className="text-lg sm:text-xl md:text-2xl text-slate-600 max-w-3xl mx-auto leading-relaxed">
              We support veterinary practices in all major Delhi areas
            </p>
          </div>
          <div className="max-w-5xl mx-auto">
            <div className="bg-white p-6 md:p-8 rounded-2xl shadow-lg border border-gray-200">
              <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 md:gap-6">
                {delhiAreas.map((area, index) => (
                  <div
                    key={index}
                    className="flex items-center space-x-2 text-slate-700"
                    style={{ transitionDelay: isInView ? `${index * 50}ms` : '0ms' }}
                  >
                    <Icons.MapPin />
                    <span className="text-sm sm:text-base">{area}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Local Success Stories */}
      {/* <Testimonials testimonials={delhiTestimonials} /> */}

      {/* Why Delhi Clinics Choose SnoutIQ */}
      <section className="py-16 md:py-20 lg:py-24 bg-white">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12 md:mb-16">
            <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold text-slate-900 mb-4 leading-tight">
              Why Delhi Clinics <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">Choose SnoutIQ</span>
            </h2>
          </div>
          <div className="space-y-6 md:space-y-8">
            {[
              {
                icon: '🏆',
                title: 'Local Market Leader',
                content: 'Delhi clinics trust SnoutIQ for their practice management. We understand the unique challenges of Delhi\'s veterinary market, from high competition to diverse client demographics.'
              },
              {
                icon: '🌐',
                title: 'Regional Language Support',
                content: 'Communicate with pet owners in their preferred language. Our platform supports Hindi, English, and other regional languages, making it easier to serve Delhi\'s diverse population.'
              },
              {
                icon: '📍',
                title: 'On-Site Support',
                content: 'Our Delhi-based team provides in-person onboarding, training, and support. We\'re just a phone call away for any assistance you need.'
              },
              {
                icon: '✅',
                title: 'Full Compliance',
                content: 'Fully compliant with Delhi veterinary council regulations, Indian data protection laws, and local business requirements. Your practice stays compliant automatically.'
              }
            ].map((item, index) => (
              <div
                key={index}
                className="bg-gray-50 p-6 md:p-8 rounded-2xl border border-gray-200 hover:shadow-md transition-all duration-300"
                style={{ transitionDelay: isInView ? `${index * 100 + 200}ms` : '0ms' }}
              >
                <h3 className="text-xl sm:text-2xl font-bold text-slate-900 mb-3 md:mb-4">
                  {item.icon} {item.title}
                </h3>
                <p className="text-lg leading-relaxed text-slate-700">{item.content}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Contact Information */}
      <section className="py-16 md:py-20 lg:py-24 bg-gradient-to-br from-slate-50 to-blue-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="bg-white p-6 md:p-8 lg:p-12 rounded-3xl shadow-xl max-w-4xl mx-auto border border-gray-200">
            <div className="text-center mb-8 md:mb-10">
              <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold text-slate-900 mb-4 leading-tight">
                Get Started with a <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">Delhi Demo</span>
              </h2>
              <p className="text-lg sm:text-xl md:text-2xl text-slate-600 leading-relaxed">
                See how SnoutIQ can transform your Delhi clinic
              </p>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
  {[
    {
      icon: 'Phone',
      title: 'Call Us',
      // content: '+91-11-4567-8900',
      // href: 'tel:+911145678900'
    },
    {
      icon: 'MapPin',
      title: 'Visit Us',
      content: 'Connaught Place\nNew Delhi'
    },
    {
      icon: 'Clock',
      title: 'Support Hours',
      content: '24/7 Available\nIncluding Weekends'
    }
  ].map((item, index) => {
    const Icon = Icons[item.icon]; // ⭐ Correct way

    return (
      <div key={index} className="text-center">
        <div className="w-14 h-14 md:w-16 md:h-16 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-4">
          <Icon className="text-blue-600 w-7 h-7 md:w-8 md:h-8" />
        </div>

        <h3 className="font-bold text-slate-900 mb-2 text-lg">{item.title}</h3>
{/* 
        {item.href ? (
          <a href={item.href} className="text-blue-600 hover:text-blue-700 text-base">
            {item.content}
          </a>
        ) : (
          <p className="text-slate-600 text-base whitespace-pre-line">
            {item.content}
          </p>
        )} */}
      </div>
    );
  })}
</div>

          </div>
        </div>
      </section>

      <CTA
        title="Ready to Join Leading Delhi Clinics?"
        subtitle="Transform your Delhi veterinary practice with SnoutIQ today"
        primaryButton={{ text: 'Schedule Delhi Demo', href: 'https://docs.google.com/forms/d/e/1FAIpQLSeQkcn7C8oSvoN4_eMRwWjN4nLfc0IPQT_ZKuwMKMAKzh4SSQ/viewform' }}
        secondaryButton={{ text: 'View Pricing', href: 'https://docs.google.com/forms/d/e/1FAIpQLSeQkcn7C8oSvoN4_eMRwWjN4nLfc0IPQT_ZKuwMKMAKzh4SSQ/viewform' }}
        variant="gradient"
      />
      <Footer/>
    </>
  );
};

export default DelhiPage;