

import { Link } from 'react-router-dom';
import { useRef, useEffect, useState, useMemo } from 'react';

const CTA = ({
  title,
  subtitle,
  primaryButton,
  secondaryButton,
  variant = 'gradient',
  eyebrow = 'Let’s build better care together',
  bullets = ['14-day free trial', 'No credit card required', 'Cancel anytime'],
  id = 'cta',
}) => {
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

  // Memoized styles with proper padding
  const styles = useMemo(() => {
    const base = {
      // Section padding - fixed
      section: 'py-12 md:py-16 lg:py-20',
      
      // Container spacing
      container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
      
      // CTA box padding - increased for better visibility
      ctaBox: 'rounded-[50px] p-8 md:p-12 lg:p-16 relative overflow-hidden',
      
      // Title margins
      title: 'text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold mb-4 md:mb-6 leading-tight',
      
      // Subtitle margins
      subtitle: 'text-lg sm:text-xl md:text-2xl mb-8 md:mb-10 leading-relaxed',
      
      // Button base styles
      buttonBase: 'inline-flex items-center justify-center px-6 py-3 md:px-8 md:py-4 font-semibold rounded-xl transition-all duration-300 text-base md:text-lg',
      
      // Button specific styles
      primaryButton: 'hover:shadow-xl hover:scale-105',
      secondaryButton: 'border-2 hover:shadow-lg'
    };

    const variants = {
      gradient: {
        ctaBox: 'bg-gradient-to-r from-blue-600 via-blue-500 to-purple-600',
        title: 'text-white',
        subtitle: 'text-white/90',
        primaryButton: 'bg-white text-blue-600 hover:bg-gray-50',
        secondaryButton: 'bg-white/20 backdrop-blur-sm text-white border-white/30 hover:bg-white/30 hover:border-white/50'
      },
      solid: {
        ctaBox: 'bg-blue-600',
        title: 'text-white',
        subtitle: 'text-white/90',
        primaryButton: 'bg-white text-blue-600 hover:bg-gray-50',
        secondaryButton: 'bg-white/20 backdrop-blur-sm text-white border-white/30 hover:bg-white/30 hover:border-white/50'
      },
      outlined: {
        ctaBox: 'bg-white border-2 border-blue-600',
        title: 'text-slate-900',
        subtitle: 'text-slate-600',
        primaryButton: 'bg-gradient-to-r from-blue-600 to-blue-500 text-white hover:from-blue-700 hover:to-blue-600',
        secondaryButton: 'border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white'
      }
    };

    return {
      ...base,
      ...variants[variant]
    };
  }, [variant]);

  // Animation classes
  const getAnimationClass = () => {
    if (!isInView) {
      return 'opacity-0 translate-y-6';
    }
    return 'transition-all duration-500 ease-out opacity-100 translate-y-0';
  };

  // Arrow icon SVG
  const ArrowIcon = () => (
    <svg 
      className="ml-2 w-4 h-4 md:w-5 md:h-5 group-hover:translate-x-1 transition-transform" 
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14 5l7 7m0 0l-7 7m7-7H3" />
    </svg>
  );

  return (
    <section
      id={id}
      aria-labelledby={`${id}-heading`}
      aria-describedby={`${id}-description`}
      className={styles.section}
    >
      <div className={styles.container}>
        <div 
          ref={sectionRef}
          className={`${styles.ctaBox} ${getAnimationClass()}`}
          style={{
            transitionDelay: isInView ? '100ms' : '0ms'
          }}
        >
          {/* Background Decoration */}
          <div className="absolute inset-0 overflow-hidden pointer-events-none">
            <div className="absolute top-0 right-0 w-64 h-64 md:w-80 md:h-80 lg:w-96 lg:h-96 bg-white/10 rounded-full blur-3xl" />
            <div className="absolute bottom-0 left-0 w-64 h-64 md:w-80 md:h-80 lg:w-96 lg:h-96 bg-white/10 rounded-full blur-3xl" />
          </div>

          <div className="relative z-10 text-center max-w-4xl mx-auto px-6 py-10 md:py-12 lg:py-16">
            <p className="text-sm font-semibold uppercase tracking-[0.3em] text-white/80 mb-4">
              {eyebrow}
            </p>
            <h2 
              id={`${id}-heading`}
              className={styles.title}
              style={{
                transitionDelay: isInView ? '200ms' : '0ms'
              }}
            >
              {title}
            </h2>

            <p 
              id={`${id}-description`}
              className={styles.subtitle}
              style={{
                transitionDelay: isInView ? '300ms' : '0ms'
              }}
            >
              {subtitle}
            </p>

            <div
              className="flex flex-wrap items-center justify-center gap-3 mb-8"
              style={{
                transitionDelay: isInView ? '350ms' : '0ms'
              }}
            >
              {bullets.map((point) => (
                <span
                  key={point}
                  className="inline-flex items-center rounded-full border border-white/30 bg-white/10 px-4 py-1 text-sm font-medium text-white/90 backdrop-blur"
                >
                  <span className="mr-2 block h-2 w-2 rounded-full bg-white/80" />
                  {point}
                </span>
              ))}
            </div>

            {/* Buttons */}
            <div 
              className="flex flex-col sm:flex-row gap-4 justify-center items-center"
              style={{
                transitionDelay: isInView ? '400ms' : '0ms'
              }}
            >
              {/* Primary Button */}
              <Link
                to={primaryButton.href}
                className={`${styles.buttonBase} ${styles.primaryButton} group`}
              >
                {primaryButton.text}
                <ArrowIcon />
              </Link>

              {/* Secondary Button */}
              {secondaryButton && (
                <Link
                  to={secondaryButton.href}
                  className={`${styles.buttonBase} ${styles.secondaryButton}`}
                >
                  {secondaryButton.text}
                </Link>
              )}
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default CTA;
