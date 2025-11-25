'use client';

import { useRef, useEffect, useState, useMemo } from 'react';
import { Link } from 'react-router-dom';
import Hero from './HeroSection';
import FeatureCard from '../components/FeatureCard';
import Benefits from '../components/Benefits';
import Testimonials from '../components/Testimonials';
import CTA from '../components/CTA';
import Footer from '../components/Footer';
import Header from '../components/Header';

const GurugramPage = () => {
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
      title: 'Trusted Across Gurugram',
      description: 'Supporting premium veterinary practices across DLF, Golf Course Road, Sector 14, and Cyber City.',
    },
    {
      icon: 'Users',
      title: 'Premium Client Experience',
      description: 'Tailored for Gurugram\'s discerning pet owners who expect world-class veterinary care and service.',
    },
    {
      icon: 'Clock',
      title: 'Priority Gurugram Support',
      description: 'Dedicated account managers for Gurugram clinics with priority response times.',
    },
    {
      icon: 'Award',
      title: 'Enterprise-Grade Security',
      description: 'Bank-level security and compliance meeting international standards expected by Gurugram practices.',
    },
    {
      icon: 'TrendingUp',
      title: 'Proven Results',
      description: 'Gurugram clinics report significant improvements in efficiency and client satisfaction.',
    },
    {
      icon: 'Phone',
      title: 'White-Glove Onboarding',
      description: 'Personalized setup and training at your Gurugram clinic with dedicated implementation specialist.',
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

//   const gurugramTestimonials = useMemo(() => [
//     {
//       name: 'Dr. Rahul Mehta',
//       role: 'Clinic Owner',
//       company: 'Gurugram Animal Hospital, DLF Phase 3',
//       content: 'Our Gurugram clientele expects the best, and SnoutIQ delivers. The platform is sophisticated, reliable, and our clients love the modern experience.',
//       rating: 5,
//     },
//     {
//       name: 'Dr. Kavita Desai',
//       role: 'Lead Veterinarian',
//       company: 'Premium Pet Care, Golf Course Road',
//       content: 'SnoutIQ helped us position as Gurugram\'s most tech-forward clinic. We saw significant growth while maintaining our premium service standards.',
//       rating: 5,
//     },
//     {
//       name: 'Dr. Arjun Kapoor',
//       role: 'Practice Manager',
//       company: 'Cyber City Vet Clinic, Gurugram',
//       content: 'The level of support is exceptional. Our dedicated account manager understands our premium practice needs and ensures everything runs perfectly.',
//       rating: 5,
//     },
//   ], []);

  const gurugramAreas = useMemo(() => [
    'DLF Phase 1-5', 'Golf Course Road', 'Cyber City', 'MG Road', 'Sohna Road',
    'Sector 14 Market', 'Sector 29', 'DLF City', 'Nirvana Country', 'Ardee City',
    'South City', 'Palam Vihar', 'DLF Garden City', 'Sector 56', 'New Gurugram',
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
        badge="Trusted by Premium Gurugram Professionals"
        title="Gurugram's Premium Veterinary Practice Management Software"
        subtitle="Elevate your Gurugram clinic with enterprise-grade technology. AI-powered triage, luxury video consultations, and premium client experience."
        ctaPrimary={{ text: 'Schedule Premium Demo', href: '/vet-register' }}
        // ctaSecondary={{ text: 'Call: +91-124-567-8900', href: 'tel:+911245678900' }}
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
              Built for <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">Premium Gurugram Practices</span>
            </h2>
            <p 
              className="text-lg sm:text-xl md:text-2xl text-slate-600 max-w-3xl mx-auto leading-relaxed"
              style={{ transitionDelay: isInView ? '200ms' : '0ms' }}
            >
              Enterprise features and white-glove support for Gurugram's leading veterinary clinics
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
              Serving Premium Clinics <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">Across Gurugram</span>
            </h2>
            <p className="text-lg sm:text-xl md:text-2xl text-slate-600 max-w-3xl mx-auto leading-relaxed">
              Trusted by leading veterinary practices in Gurugram's premier locations
            </p>
          </div>
          <div className="max-w-5xl mx-auto">
            <div className="bg-white p-6 md:p-8 rounded-2xl shadow-lg border border-gray-200">
              <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 md:gap-6">
                {gurugramAreas.map((area, index) => (
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
      {/* <Testimonials testimonials={gurugramTestimonials} /> */}

      {/* Why Gurugram Clinics Choose SnoutIQ */}
      <section className="py-16 md:py-20 lg:py-24 bg-white">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12 md:mb-16">
            <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold text-slate-900 mb-4 leading-tight">
              Why Premium Gurugram Clinics <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">Choose SnoutIQ</span>
            </h2>
          </div>
          <div className="space-y-6 md:space-y-8">
            {[
              {
                icon: '🏆',
                title: 'Premium Positioning',
                content: 'SnoutIQ helps Gurugram clinics maintain their premium brand positioning. Our sophisticated platform impresses discerning clients and sets you apart from competition.'
              },
              {
                icon: '💎',
                title: 'Enterprise Features',
                content: 'Access to advanced features like custom integrations, white-label options, and API access. Everything a premium Gurugram practice needs to stay ahead.'
              },
              {
                icon: '🤝',
                title: 'Dedicated Support',
                content: 'Every Gurugram clinic gets a dedicated account manager who understands your premium practice needs. Priority support with fast response times.'
              },
              {
                icon: '🔒',
                title: 'Bank-Level Security',
                content: 'Enterprise-grade security infrastructure with data centers in India. Full compliance with international standards and regulations your clients expect.'
              },
              {
                icon: '📊',
                title: 'Advanced Analytics',
                content: 'Deep insights into practice performance, client behavior, and revenue optimization. Make data-driven decisions to grow your premium practice.'
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

      {/* Premium Package Features */}
      <section className="py-16 md:py-20 lg:py-24 bg-gradient-to-br from-blue-50 to-purple-50">
        <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12 md:mb-16">
            <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold text-slate-900 mb-4 leading-tight">
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">Gurugram Premium</span> Package
            </h2>
            <p className="text-lg sm:text-xl md:text-2xl text-slate-600 leading-relaxed">
              Exclusive features for Gurugram's leading veterinary practices
            </p>
          </div>
          <div className="bg-white p-6 md:p-8 lg:p-10 rounded-3xl shadow-xl border border-gray-200">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8 mb-8">
              {[
                {
                  title: 'Dedicated Account Manager',
                  description: 'Personal support specialist for your practice'
                },
                {
                  title: 'White-Label Options',
                  description: 'Customize with your clinic branding'
                },
                {
                  title: 'Custom Integrations',
                  description: 'Connect with your existing systems'
                },
                {
                  title: 'Priority Feature Access',
                  description: 'Early access to new features'
                },
                {
                  title: '99.9% SLA Guarantee',
                  description: 'Maximum uptime commitment'
                },
                {
                  title: 'On-Site Training',
                  description: 'Personalized training at your clinic'
                }
              ].map((feature, index) => (
                <div key={index} className="flex items-start space-x-3">
                  <div className="w-6 h-6 rounded-full bg-blue-600 flex items-center justify-center text-white flex-shrink-0 mt-1 text-sm">✓</div>
                  <div>
                    <h4 className="font-bold text-slate-900 mb-1 text-base">{feature.title}</h4>
                    <p className="text-slate-600 text-sm">{feature.description}</p>
                  </div>
                </div>
              ))}
            </div>
            <div className="text-center pt-6 border-t border-gray-200">
              <p className="text-slate-600 mb-4 text-base">Contact us for premium pricing</p>
              <Link
                to="https://docs.google.com/forms/d/e/1FAIpQLSeQkcn7C8oSvoN4_eMRwWjN4nLfc0IPQT_ZKuwMKMAKzh4SSQ/viewform"
                className="inline-block px-6 sm:px-8 py-3 sm:py-4 bg-gradient-to-r from-blue-600 to-blue-500 text-white font-semibold rounded-xl hover:shadow-xl hover:scale-105 transition-all text-base"
              >
                Schedule Premium Consultation
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* Contact Information */}
      <section className="py-16 md:py-20 lg:py-24 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="bg-gradient-to-br from-slate-50 to-blue-50 p-6 md:p-8 lg:p-12 rounded-3xl shadow-xl max-w-4xl mx-auto border border-gray-200">
            <div className="text-center mb-8 md:mb-10">
              <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold text-slate-900 mb-4 leading-tight">
                Experience the <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">Premium Difference</span>
              </h2>
              <p className="text-lg sm:text-xl md:text-2xl text-slate-600 leading-relaxed">
                Schedule a personalized demo at your Gurugram clinic
              </p>
            </div>
           <div className="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
  {[
    {
      icon: 'Phone',
      title: 'Priority Line',
      // content: '+91-124-567-8900',
      // href: 'tel:+911245678900'
    },
    {
      icon: 'MapPin',
      title: 'Office',
      content: 'DLF Cyber City\nGurugram'
    },
    {
      icon: 'Clock',
      title: 'Dedicated Support',
      content: '24/7 Premium\nPriority Response'
    }
  ].map((item, index) => {
    const Icon = Icons[item.icon];

    return (
      <div key={index} className="text-center">
        <div className="w-14 h-14 md:w-16 md:h-16 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-4">
          <Icon className="text-blue-600 w-7 h-7 md:w-8 md:h-8" />
        </div>

        <h3 className="font-bold text-slate-900 mb-2 text-lg">{item.title}</h3>

        {/* {item.href ? (
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
        title="Ready to Join Leading Gurugram Clinics?"
        subtitle="Transform your practice with SnoutIQ Premium today"
        primaryButton={{ text: 'Schedule Premium Demo', href: '/vet-register' }}
        secondaryButton={{ text: 'View Demo', href: '/vet-register' }}
        variant="gradient"
      />
      <Footer/>
    </>
  );
};

export default GurugramPage;