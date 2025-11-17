'use client';

import { useRef, useEffect, useState, useMemo } from 'react';
import { Link } from 'react-router-dom';
import Hero from './HeroSection';
import FeatureCard from '../components/FeatureCard';
import CTA from '../components/CTA';
import Footer from '../components/Footer';
import Header from '../components/Header';

const FeaturesPage = () => {
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

  // Memoized features data
  const features = useMemo(() => [
    {
      icon: 'Video',
      title: 'HD Video Consultations',
      description: 'Crystal-clear video quality for accurate remote diagnosis. Multi-device support, screen sharing, and recording capabilities.',
      link: '/video-consult',
    },
    // {
    //   icon: 'Brain',
    //   title: 'AI-Powered Triage',
    //   description: 'Intelligent symptom analysis that prioritizes urgent cases and recommends the most appropriate specialists automatically.',
    //   link: '/ai-triage',
    // },
    {
      icon: 'Calendar',
      title: 'Smart Scheduling',
      description: 'Automated booking with intelligent time slot optimization. Smart reminders help reduce no-shows significantly.',
    },
    {
      icon: 'MessageSquare',
      title: 'Secure Messaging',
      description: 'HIPAA-compliant messaging for follow-ups, prescriptions, and care instructions. Real-time chat support.',
    },
    {
      icon: 'BarChart3',
      title: 'Analytics Dashboard',
      description: 'Real-time insights into clinic performance, revenue trends, patient outcomes, and operational efficiency.',
    },
    {
      icon: 'Shield',
      title: 'Medical Records',
      description: 'Cloud-based EMR with secure storage, instant access, and seamless sharing. Full HIPAA compliance.',
    },
    {
      icon: 'Clock',
      title: '24/7 Availability',
      description: 'Round-the-clock access for emergency cases. On-call vet network for after-hours consultations.',
    },
    {
      icon: 'Users',
      title: 'Multi-Clinic Management',
      description: 'Manage multiple locations from one dashboard. Centralized patient records and staff coordination.',
    },
    {
      icon: 'Zap',
      title: 'Quick Prescriptions',
      description: 'Digital prescription generation with e-signature. Direct pharmacy integration for faster fulfillment.',
    },
    {
      icon: 'FileText',
      title: 'Automated Documentation',
      description: 'AI-assisted note-taking and documentation. Automatic report generation and billing integration.',
    },
  ], []);

  // SVG Icons
  const Icons = useMemo(() => ({
    Video: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
      </svg>
    ),
    Brain: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
      </svg>
    ),
    Calendar: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
    ),
    MessageSquare: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
      </svg>
    ),
    BarChart3: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
      </svg>
    ),
    Shield: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
      </svg>
    ),
    Clock: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
    ),
    Users: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
      </svg>
    ),
    Zap: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z"/>
      </svg>
    ),
    FileText: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
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
        badge="Complete Feature Suite"
        title="Every Tool Your Veterinary Practice Needs"
        subtitle="From AI-powered triage to HD video consultations, SnoutIQ provides a comprehensive platform for modern veterinary care."
        ctaPrimary={{ text: 'Start Free Trial', href: '/register?utm_source=header&utm_medium=cta&utm_campaign=vet_landing' }}
        ctaSecondary={{ text: 'Schedule Demo', href: '/register?utm_source=header&utm_medium=cta&utm_campaign=vet_landing' }}
        imagePlaceholder={true}
      />

      {/* Features Section */}
      <section ref={sectionRef} className="py-16 md:py-20 lg:py-24 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12 md:mb-16">
            <h2 
              className="text-3xl sm:text-4xl md:text-5xl font-bold leading-tight text-slate-900 mb-4"
              style={{ transitionDelay: isInView ? '100ms' : '0ms' }}
            >
              Powerful Features for <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">Modern Clinics</span>
            </h2>
            <p 
              className="text-lg sm:text-xl md:text-2xl leading-relaxed text-slate-600 max-w-3xl mx-auto"
              style={{ transitionDelay: isInView ? '200ms' : '0ms' }}
            >
              Everything you need to deliver exceptional veterinary care
            </p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
            {features.map((feature, index) => (
              <FeatureCard
                key={index}
                icon={Icons[feature.icon]}
                title={feature.title}
                description={feature.description}
                link={feature.link}
                index={index}
                variant="outlined"
              />
            ))}
          </div>
        </div>
      </section>

      <CTA
        title="Ready to Experience All Features?"
        subtitle="Start your 14-day free trial and see how SnoutIQ transforms your practice"
        primaryButton={{ text: 'Start Free Trial', href: '/register?utm_source=header&utm_medium=cta&utm_campaign=vet_landing' }}
        secondaryButton={{ text: 'Contact Sales', href: 'https://docs.google.com/forms/d/e/1FAIpQLSdLBk7Yv8ODnzUV_0KrCotH1Kc91d1VpeUHWyovxXO_GYC4yw/viewform' }}
        variant="gradient"
      />
      <Footer/>
    </>
  );
};

export default FeaturesPage;