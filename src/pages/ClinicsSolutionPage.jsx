'use client';

import { useRef, useEffect, useState, useMemo } from 'react';
import { Link } from 'react-router-dom';
import Hero from './HeroSection';
import FeatureCard from '../components/FeatureCard';
import Workflow from '../components/Workflow';
import Testimonials from '../components/Testimonials';
import CTA from '../components/CTA';
import Footer from '../components/Footer';
import Header from '../components/Header';

const ClinicsSolutionPage = () => {
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
  const benefits = useMemo(() => [
    {
      icon: 'TrendingUp',
      title: 'Increase Revenue',
      description: '40% revenue growth through more appointments, reduced no-shows, and efficient operations.',
    },
    {
      icon: 'Users',
      title: 'Better Patient Care',
      description: 'Deliver exceptional care with AI-powered diagnostics and seamless video consultations.',
    },
    {
      icon: 'Clock',
      title: 'Save 10+ Hours Weekly',
      description: 'Automated scheduling, reminders, and documentation free up staff for patient care.',
    },
    {
      icon: 'DollarSign',
      title: 'Reduce Operating Costs',
      description: 'Cut administrative overhead by 30% with intelligent automation and streamlined workflows.',
    },
    {
      icon: 'BarChart3',
      title: 'Data-Driven Insights',
      description: 'Make informed decisions with real-time analytics on performance, revenue, and outcomes.',
    },
    {
      icon: 'Settings',
      title: 'Easy Integration',
      description: 'Seamless integration with existing EMR systems, labs, and pharmacy partners.',
    },
  ], []);

  const implementationSteps = useMemo(() => [
    {
      number: 1,
      title: 'Onboarding',
      description: 'Dedicated team helps you set up, import data, and train staff',
      icon: 'Users',
    },
    {
      number: 2,
      title: 'Customization',
      description: 'Configure workflows, branding, and integrations to match your practice',
      icon: 'Settings',
    },
    {
      number: 3,
      title: 'Go Live',
      description: 'Launch with full support. We monitor and optimize during first month',
      icon: 'HeartPulse',
    },
    {
      number: 4,
      title: 'Scale & Grow',
      description: 'Continuous improvements and new features as your practice grows',
      icon: 'TrendingUp',
    },
  ], []);

//   const testimonials = useMemo(() => [
//     {
//       name: 'Dr. Vikram Malhotra',
//       role: 'Clinic Owner',
//       company: 'Delhi Pet Hospital',
//       content: 'Within 3 months, our revenue increased by 45% and client satisfaction reached all-time highs. SnoutIQ transformed our entire operation.',
//       rating: 5,
//     },
//     {
//       name: 'Dr. Meera Reddy',
//       role: 'Practice Manager',
//       company: 'Hyderabad Vet Clinic',
//       content: 'We handle 60% more appointments with the same staff. The automation features are incredible and save us hours every day.',
//       rating: 5,
//     },
//     {
//       name: 'Dr. Arjun Kapoor',
//       role: 'Chief Veterinarian',
//       company: 'Mumbai Animal Care',
//       content: 'Best investment we made for our practice. The ROI was positive in just 2 months. Our team and clients love it.',
//       rating: 5,
//     },
//   ], []);

  // SVG Icons
  const Icons = useMemo(() => ({
    TrendingUp: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
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
    DollarSign: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
      </svg>
    ),
    BarChart3: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
      </svg>
    ),
    Settings: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>
    ),
    HeartPulse: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 12h.01M8 12h.01M12 12h.01M16 12h.01M20 12h.01"/>
      </svg>
    ),
    Building2: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
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
        badge="Built for Veterinary Clinics"
        title="Transform Your Clinic into a Modern Digital Practice"
        subtitle="Complete practice management solution that increases revenue, improves efficiency, and delivers exceptional patient care."
        ctaPrimary={{ text: 'Schedule Demo', href: '/pricing' }}
        ctaSecondary={{ text: 'View Pricing', href: '/pricing' }}
      />

      {/* Benefits Section */}
      <section ref={sectionRef} className="py-16 md:py-20 lg:py-24 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12 md:mb-16">
            <h2 
              className="text-3xl sm:text-4xl md:text-5xl font-bold leading-tight text-slate-900 mb-4"
              style={{ transitionDelay: isInView ? '100ms' : '0ms' }}
            >
              Benefits for <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">Your Clinic</span>
            </h2>
            <p 
              className="text-lg sm:text-xl md:text-2xl leading-relaxed text-slate-600 max-w-3xl mx-auto"
              style={{ transitionDelay: isInView ? '200ms' : '0ms' }}
            >
              Everything you need to modernize and grow your practice
            </p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
            {benefits.map((benefit, index) => (
              <FeatureCard
                key={index}
                icon={Icons[benefit.icon]}
                title={benefit.title}
                description={benefit.description}
                index={index}
                variant="gradient"
              />
            ))}
          </div>
        </div>
      </section>

      {/* Clinic Sizes Section */}
      <section className="py-16 md:py-20 lg:py-24 bg-gradient-to-br from-slate-50 to-blue-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12 md:mb-16">
            <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold leading-tight text-slate-900 mb-4">
              Perfect for <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">All Clinic Sizes</span>
            </h2>
            <p className="text-lg sm:text-xl md:text-2xl leading-relaxed text-slate-600 max-w-3xl mx-auto mb-12">
              From solo practitioners to multi-location enterprises
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
            {/* Solo Practice */}
            <div 
              className="bg-white p-6 md:p-8 rounded-2xl shadow-lg border border-gray-200 hover:shadow-xl transition-all duration-300"
              style={{ transitionDelay: isInView ? '300ms' : '0ms' }}
            >
              <div className="text-center mb-6">
                <div className="w-16 h-16 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                  <Icons.Building2 className="text-blue-600 w-8 h-8" />
                </div>
                <h3 className="text-2xl md:text-3xl font-bold text-slate-900 mb-2">Solo Practice</h3>
                <p className="text-slate-600">1-2 veterinarians</p>
              </div>
              <ul className="space-y-3">
                {['Easy setup & onboarding', 'Affordable pricing', 'All core features', '24/7 support'].map((feature, index) => (
                  <li key={index} className="flex items-start">
                    <span className="text-blue-600 mr-2 text-lg">✓</span>
                    <span className="text-slate-700">{feature}</span>
                  </li>
                ))}
              </ul>
            </div>

            {/* Multi-Doctor Clinic */}
            <div 
              className="bg-gradient-to-br from-blue-50 to-purple-50 p-6 md:p-8 rounded-2xl shadow-xl border-2 border-blue-200 relative hover:scale-105 transition-all duration-300"
              style={{ transitionDelay: isInView ? '400ms' : '0ms' }}
            >
              <div className="absolute -top-4 left-1/2 transform -translate-x-1/2 bg-blue-600 text-white px-4 py-1 rounded-full text-sm font-semibold">
                Most Popular
              </div>
              <div className="text-center mb-6">
                <div className="w-16 h-16 bg-gradient-to-br from-blue-600 to-blue-500 rounded-xl flex items-center justify-center mx-auto mb-4">
                  <Icons.Users className="text-white w-8 h-8" />
                </div>
                <h3 className="text-2xl md:text-3xl font-bold text-slate-900 mb-2">Multi-Doctor Clinic</h3>
                <p className="text-slate-600">3-10 veterinarians</p>
              </div>
              <ul className="space-y-3">
                {['Everything in Solo', 'Advanced analytics', 'Team collaboration', 'Priority support'].map((feature, index) => (
                  <li key={index} className="flex items-start">
                    <span className="text-blue-600 mr-2 text-lg">✓</span>
                    <span className="text-slate-700">{feature}</span>
                  </li>
                ))}
              </ul>
            </div>

            {/* Enterprise */}
            <div 
              className="bg-white p-6 md:p-8 rounded-2xl shadow-lg border border-gray-200 hover:shadow-xl transition-all duration-300"
              style={{ transitionDelay: isInView ? '500ms' : '0ms' }}
            >
              <div className="text-center mb-6">
                <div className="w-16 h-16 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                  <Icons.Building2 className="text-blue-600 w-8 h-8" />
                </div>
                <h3 className="text-2xl md:text-3xl font-bold text-slate-900 mb-2">Enterprise</h3>
                <p className="text-slate-600">Multiple locations</p>
              </div>
              <ul className="space-y-3">
                {['Everything in Multi-Doctor', 'Custom integrations', 'Dedicated account manager', 'SLA guarantee'].map((feature, index) => (
                  <li key={index} className="flex items-start">
                    <span className="text-blue-600 mr-2 text-lg">✓</span>
                    <span className="text-slate-700">{feature}</span>
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </div>
      </section>

      <Workflow
        title="Quick Implementation Process"
        subtitle="From signup to fully operational in 2 weeks"
        steps={implementationSteps.map(step => ({
          ...step,
          icon: Icons[step.icon]
        }))}
      />

      {/* <Testimonials testimonials={testimonials} /> */}

      <CTA
        title="Ready to Modernize Your Clinic?"
        subtitle="Start growing your practice with SnoutIQ today"
        primaryButton={{ text: 'Schedule Demo', href: '/pricing' }}
        secondaryButton={{ text: 'View Pricing', href: '/pricing' }}
        variant="gradient"
      />
      <Footer/>
    </>
  );
};

export default ClinicsSolutionPage;