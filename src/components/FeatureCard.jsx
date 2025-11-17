

import { useRef, useEffect, useState, useMemo } from 'react';
import { Link } from 'react-router-dom';

const FeatureCard = ({
  icon: Icon,
  title,
  description,
  link,
  index = 0,
  variant = 'default'
}) => {
  const cardRef = useRef(null);
  const [isInView, setIsInView] = useState(false);
  
  // Intersection Observer
  useEffect(() => {
    const element = cardRef.current;
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

  // Memoized styles
  const styles = useMemo(() => {
    const base = {
      card: 'group p-6 md:p-8 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 relative overflow-hidden',
      iconContainer: 'w-14 h-14 md:w-16 md:h-16 rounded-xl flex items-center justify-center mb-5 md:mb-6 transition-transform duration-300 relative z-10',
      title: 'text-xl md:text-2xl font-bold mb-3 md:mb-4 leading-tight relative z-10 text-slate-900',
      description: 'mb-5 md:mb-6 leading-relaxed text-sm md:text-base relative z-10 text-slate-600',
      link: 'inline-flex items-center text-blue-600 font-semibold transition-colors duration-300 text-sm md:text-base relative z-10'
    };

    const variants = {
      card: {
        default: 'bg-white hover:bg-slate-50',
        gradient: 'bg-gradient-to-br from-blue-50 to-purple-50 hover:from-blue-100 hover:to-purple-100',
        outlined: 'bg-white border-2 border-slate-200 hover:border-blue-300'
      },
      icon: {
        default: 'bg-blue-100 text-blue-600 group-hover:scale-110',
        gradient: 'bg-gradient-to-br from-blue-600 to-blue-500 text-white group-hover:scale-110',
        outlined: 'bg-slate-100 text-blue-600 group-hover:scale-110'
      },
      title: {
        default: 'group-hover:text-blue-700',
        gradient: 'group-hover:text-blue-700',
        outlined: 'group-hover:text-blue-700'
      },
      link: {
        default: 'hover:text-blue-800 group-hover:translate-x-2',
        gradient: 'hover:text-blue-800 group-hover:translate-x-2',
        outlined: 'hover:text-blue-800 group-hover:translate-x-2'
      }
    };

    return {
      card: `${base.card} ${variants.card[variant]}`,
      iconContainer: `${base.iconContainer} ${variants.icon[variant]}`,
      title: `${base.title} ${variants.title[variant]}`,
      description: base.description,
      link: `${base.link} ${variants.link[variant]}`
    };
  }, [variant]);

  // Animation classes
  const getAnimationClass = () => {
    if (!isInView) {
      return 'opacity-0 translate-y-8';
    }
    
    return 'transition-all duration-500 ease-out opacity-100 translate-y-0';
  };

  // Arrow icon SVG
  const ArrowIcon = () => (
    <svg 
      className="ml-2 w-4 h-4" 
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14 5l7 7m0 0l-7 7m7-7H3" />
    </svg>
  );

  return (
    <article
      ref={cardRef}
      className={`${styles.card} ${getAnimationClass()}`}
      style={{
        transitionDelay: isInView ? `${index * 100}ms` : '0ms'
      }}
      aria-label={title}
    >
      {/* Subtle background pattern */}
      <div className="absolute top-0 right-0 w-32 h-32 opacity-5 pointer-events-none">
        <div className="w-full h-full bg-gradient-to-br from-blue-500 to-purple-500 rounded-full blur-2xl" />
      </div>

      {/* Icon */}
      <div className={styles.iconContainer}>
        <Icon className="w-7 h-7 md:w-8 md:h-8" />
      </div>

      {/* Title */}
      <h3 className={styles.title}>
        {title}
      </h3>

      {/* Description */}
      <p className={styles.description}>
        {description}
      </p>

      {/* Link */}
      {link && (
        <Link
          to={link}
          className={styles.link}
          aria-label={`Learn more about ${title}`}
        >
          Learn more
          <ArrowIcon />
        </Link>
      )}
    </article>
  );
};

export default FeatureCard;
