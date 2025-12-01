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

const AITriagePage = () => {
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
      icon: 'Brain',
      title: 'Smart Symptom Analysis',
      description: 'Advanced AI evaluates symptoms, medical history, and vital signs to determine urgency level.',
    },
    {
      icon: 'Target',
      title: 'Specialist Routing',
      description: 'Automatically routes cases to the most appropriate specialist based on symptoms and expertise.',
    },
    {
      icon: 'Zap',
      title: 'Instant Prioritization',
      description: 'Real-time urgency scoring ensures critical cases get immediate attention within seconds.',
    },
    {
      icon: 'AlertCircle',
      title: 'Emergency Detection',
      description: 'Recognizes life-threatening conditions instantly and triggers emergency protocols.',
    },
    {
      icon: 'TrendingUp',
      title: 'Continuous Learning',
      description: 'AI improves over time by learning from outcomes and veterinarian feedback.',
    },
    {
      icon: 'Shield',
      title: 'Vet-Approved',
      description: 'Final decisions always remain with veterinarians. AI provides recommendations, not mandates.',
    },
  ], []);

  const painPoints = useMemo(() => [
    {
      problem: 'Urgent cases get delayed when mixed with routine appointments',
      solution: 'AI instantly identifies and prioritizes emergency cases for immediate attention',
    },
    {
      problem: 'Front desk staff struggle to assess medical urgency accurately',
      solution: 'Intelligent triage provides consistent, accurate urgency assessments',
    },
    {
      problem: 'Specialists receive cases outside their expertise area',
      solution: 'Smart routing matches each case with the most qualified veterinarian',
    },
    {
      problem: 'Manual triage consumes valuable staff time and resources',
      solution: 'Automated system handles initial assessment in seconds, not minutes',
    },
  ], []);

  const workflowSteps = useMemo(() => [
    {
      number: 1,
      title: 'Symptom Input',
      description: 'Owner enters pet symptoms, behavior changes, and photos through app',
      icon: 'Activity',
    },
    {
      number: 2,
      title: 'AI Analysis',
      description: 'Machine learning evaluates data against 100K+ medical cases',
      icon: 'Brain',
    },
    {
      number: 3,
      title: 'Urgency Score',
      description: 'System assigns priority level: Emergency, Urgent, Routine, or Follow-up',
      icon: 'Target',
    },
    {
      number: 4,
      title: 'Smart Routing',
      description: 'Case automatically routed to appropriate specialist with recommendations',
      icon: 'CheckCircle',
    },
  ], []);

//   const testimonials = useMemo(() => [
//     {
//       name: 'Dr. Kavita Desai',
//       role: 'Emergency Veterinarian',
//       company: 'Delhi Emergency Vet',
//       content: 'The AI triage has been a game-changer for our emergency department. We can identify critical cases within seconds.',
//       rating: 5,
//     },
//     {
//       name: 'Dr. Suresh Nair',
//       role: 'Head Veterinarian',
//       company: 'Chennai Pet Hospital',
//       content: 'Accuracy is impressive. It catches warning signs that even experienced staff might miss during busy periods.',
//       rating: 5,
//     },
//     {
//       name: 'Dr. Neha Singh',
//       role: 'Practice Manager',
//       company: 'Pune Veterinary Clinic',
//       content: 'Our efficiency improved by 60%. The AI handles initial assessment so our vets can focus on actual treatment.',
//       rating: 5,
//     },
//   ], []);

  // SVG Icons
  const Icons = useMemo(() => ({
    Brain: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
      </svg>
    ),
    Target: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
      </svg>
    ),
    Zap: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z"/>
      </svg>
    ),
    AlertCircle: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
    ),
    TrendingUp: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
      </svg>
    ),
    Shield: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
      </svg>
    ),
    Activity: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
      </svg>
    ),
    CheckCircle: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
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
        badge="AI-Powered Triage"
        title="Intelligent Triage That Prioritizes Every Case Correctly"
        subtitle="Advanced AI analyzes symptoms and medical history to prioritize urgent cases and route to specialists automatically."
        ctaPrimary={{ text: 'Start Free Trial', href: 'https://docs.google.com/forms/d/e/1FAIpQLSeQkcn7C8oSvoN4_eMRwWjN4nLfc0IPQT_ZKuwMKMAKzh4SSQ/viewform' }}
        ctaSecondary={{ text: 'See How It Works', href: 'https://docs.google.com/forms/d/e/1FAIpQLSeQkcn7C8oSvoN4_eMRwWjN4nLfc0IPQT_ZKuwMKMAKzh4SSQ/viewform' }}
      />

      {/* Features Section */}
      <section ref={sectionRef} className="py-16 md:py-20 lg:py-24 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12 md:mb-16">
            <h2 
              className="text-3xl sm:text-4xl md:text-5xl font-bold leading-tight text-slate-900 mb-4"
              style={{ transitionDelay: isInView ? '100ms' : '0ms' }}
            >
              AI-Powered <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">Triage Features</span>
            </h2>
            <p 
              className="text-lg sm:text-xl md:text-2xl leading-relaxed text-slate-600 max-w-3xl mx-auto"
              style={{ transitionDelay: isInView ? '200ms' : '0ms' }}
            >
              Advanced intelligence for better patient outcomes
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
        title="Why AI Triage Matters"
        subtitle="Traditional triage methods struggle with accuracy and speed"
        painPoints={painPoints}
      />

      <Workflow
        title="How AI Triage Works"
        subtitle="From symptom input to specialist routing in seconds"
        steps={workflowSteps.map(step => ({
          ...step,
          icon: Icons[step.icon]
        }))}
      />

      {/* <Testimonials testimonials={testimonials} /> */}

      <CTA
        title="Experience AI-Powered Triage Today"
        subtitle="See how intelligent triage can transform your practice efficiency"
        primaryButton={{ text: 'Start Free Trial', href: 'https://docs.google.com/forms/d/e/1FAIpQLSeQkcn7C8oSvoN4_eMRwWjN4nLfc0IPQT_ZKuwMKMAKzh4SSQ/viewform' }}
        secondaryButton={{ text: 'Request Demo', href: 'https://docs.google.com/forms/d/e/1FAIpQLSeQkcn7C8oSvoN4_eMRwWjN4nLfc0IPQT_ZKuwMKMAKzh4SSQ/viewform' }}
        variant="gradient"
      />
      <Footer/>
    </>
  );
};

export default AITriagePage;