'use client';

import { useRef, useEffect, useState, useMemo } from 'react';
import Hero from './HeroSection';
import FeatureCard from '../components/FeatureCard';
import PainPoints from '../components/PainPoints';
import Workflow from '../components/Workflow';
import Testimonials from '../components/Testimonials';
import CTA from '../components/CTA';
import Footer from '../components/Footer';
import Header from '../components/Header';

const VideoConsultPage = () => {
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
  const features = useMemo(() => [
    {
      icon: 'Video',
      title: 'HD Video Quality',
      description: 'Crystal-clear 1080p video for accurate diagnosis. Automatic quality adjustment based on connection.',
    },
    {
      icon: 'Monitor',
      title: 'Screen Sharing',
      description: 'Share X-rays, test results, and educational materials directly during consultations.',
    },
    {
      icon: 'Camera',
      title: 'Multi-Camera Support',
      description: 'Switch between devices for detailed examination views. Support for external cameras and microscopes.',
    },
    {
      icon: 'Mic',
      title: 'Clear Audio',
      description: 'Noise cancellation and echo reduction for clear communication, even in busy clinics.',
    },
    {
      icon: 'Download',
      title: 'Session Recording',
      description: 'Record consultations for training, documentation, or follow-up review with proper consent.',
    },
    {
      icon: 'Lock',
      title: 'HIPAA Compliant',
      description: 'End-to-end encryption and secure data handling. Full compliance with privacy regulations.',
    },
  ], []);

  const painPoints = useMemo(() => [
    {
      problem: 'Pet owners struggle to visit clinics during work hours',
      solution: 'Video consultations available anytime, from anywhere, on any device',
    },
    {
      problem: 'Anxious pets become more stressed in clinic environments',
      solution: 'Pets stay calm at home while still receiving professional veterinary care',
    },
    {
      problem: 'Long drive times prevent timely follow-up appointments',
      solution: 'Quick virtual check-ins ensure proper recovery without the commute',
    },
    {
      problem: 'Limited consultation rooms restrict daily appointments',
      solution: 'Unlimited virtual rooms allow parallel consultations and increased revenue',
    },
  ], []);

  const workflowSteps = useMemo(() => [
    {
      number: 1,
      title: 'Owner Books',
      description: 'Pet owner selects video consultation and available time slot',
      icon: 'Users',
    },
    {
      number: 2,
      title: 'Notification Sent',
      description: 'Automated reminders with one-click join link sent to both parties',
      icon: 'Monitor',
    },
    {
      number: 3,
      title: 'HD Consultation',
      description: 'Veterinarian and owner connect via secure HD video call',
      icon: 'Video',
    },
    {
      number: 4,
      title: 'Documentation',
      description: 'Session recorded, notes saved, prescription sent automatically',
      icon: 'Download',
    },
  ], []);

//   const testimonials = useMemo(() => [
//     {
//       name: 'Dr. Amit Kumar',
//       role: 'Senior Veterinarian',
//       company: 'Mumbai Pet Clinic',
//       content: 'Video consultations have increased our capacity by 40%. We can now serve more pets without expanding our physical space.',
//       rating: 5,
//     },
//     {
//       name: 'Dr. Sneha Patel',
//       role: 'Clinic Director',
//       company: 'Bangalore Animal Hospital',
//       content: 'The video quality is exceptional. I can diagnose skin conditions and injuries almost as well as in-person visits.',
//       rating: 5,
//     },
//     {
//       name: 'Dr. Rajesh Verma',
//       role: 'Telemedicine Specialist',
//       company: 'Delhi Vet Care',
//       content: 'Pet owners love the convenience. Our satisfaction scores improved by 35% since implementing video consultations.',
//       rating: 5,
//     },
//   ], []);

  // SVG Icons
  const Icons = useMemo(() => ({
    Video: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
      </svg>
    ),
    Monitor: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
      </svg>
    ),
    Camera: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>
    ),
    Mic: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
      </svg>
    ),
    Download: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
      </svg>
    ),
    Lock: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
      </svg>
    ),
    Users: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
      </svg>
    ),
    Share2: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
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
        badge="HD Video Consultations"
        title="Connect with Pet Owners Through Seamless Video Calls"
        subtitle="Deliver high-quality veterinary care remotely with crystal-clear HD video, screen sharing, and secure communication."
        ctaPrimary={{ text: 'Start Free Trial', href: '/vet-register' }}
        ctaSecondary={{ text: 'Watch Demo', href: 'https://docs.google.com/forms/d/e/1FAIpQLSeQkcn7C8oSvoN4_eMRwWjN4nLfc0IPQT_ZKuwMKMAKzh4SSQ/viewform' }}
      />

      {/* Features Section */}
      <section ref={sectionRef} className="py-16 md:py-20 lg:py-24 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12 md:mb-16">
            <h2 
              className="text-3xl sm:text-4xl md:text-5xl font-bold leading-tight text-slate-900 mb-4"
              style={{ transitionDelay: isInView ? '100ms' : '0ms' }}
            >
              Professional-Grade <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">Video Features</span>
            </h2>
            <p 
              className="text-lg sm:text-xl md:text-2xl leading-relaxed text-slate-600 max-w-3xl mx-auto"
              style={{ transitionDelay: isInView ? '200ms' : '0ms' }}
            >
              Everything you need for effective remote consultations
            </p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
            {features.map((feature, index) => (
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

      <PainPoints
        title="Why Veterinarians Choose Video Consultations"
        subtitle="Modern solutions for modern challenges"
        painPoints={painPoints}
      />

      <Workflow
        title="Simple Video Consultation Flow"
        subtitle="From booking to documentation in four easy steps"
        steps={workflowSteps.map(step => ({
          ...step,
          icon: Icons[step.icon]
        }))}
      />

      {/* <Testimonials testimonials={testimonials} /> */}

      <CTA
        title="Ready to Start Video Consultations?"
        subtitle="Join hundreds of clinics already providing convenient remote care"
        primaryButton={{ text: 'Start Free Trial', href: '/vet-register' }}
        secondaryButton={{ text: 'See Pricing', href: 'https://docs.google.com/forms/d/e/1FAIpQLSeQkcn7C8oSvoN4_eMRwWjN4nLfc0IPQT_ZKuwMKMAKzh4SSQ/viewform' }}
        variant="gradient"
      />
      <Footer/>
    </>
  );
};

export default VideoConsultPage;