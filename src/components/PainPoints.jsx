import { useRef, useEffect, useState, useMemo } from 'react';

const PainPoints = ({ title, subtitle, painPoints, eyebrow = 'Before & After SnoutIQ', id = 'pain-points' }) => {
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
        rootMargin: '100px'
      }
    );

    observer.observe(element);
    return () => observer.disconnect();
  }, []);

  // Memoized styles
  const styles = useMemo(() => ({
    section: 'py-12 md:py-20 lg:py-24 bg-white',
    title: 'text-3xl sm:text-4xl md:text-5xl font-bold mb-4 leading-tight text-slate-900',
    subtitle: 'text-base sm:text-lg md:text-xl max-w-3xl mx-auto leading-relaxed text-slate-600',
    columnTitle: 'text-xl font-semibold text-slate-900 mb-6 flex items-center',
    problemCard: 'flex items-start space-x-4 p-4 bg-red-50 rounded-xl border border-red-100 shadow-sm',
    solutionCard: 'flex items-start space-x-4 p-4 bg-green-50 rounded-xl border border-green-100 shadow-sm',
    problemIcon: 'text-red-500 flex-shrink-0 mt-1',
    solutionIcon: 'text-green-500 flex-shrink-0 mt-1',
    text: 'text-slate-700'
  }), []);

  // Animation classes
  const getAnimationClass = (index, type) => {
    if (!isInView) {
      if (type === 'header') return 'opacity-0 translate-y-8';
      if (type === 'column') return 'opacity-0 translate-x-8';
      return 'opacity-0 translate-x-4';
    }
    
    const baseTransition = 'transition-all duration-500 ease-out';
    
    if (type === 'header') {
      return `${baseTransition} opacity-100 translate-y-0`;
    }
    
    if (type === 'column') {
      const direction = index === 0 ? '-translate-x-0' : 'translate-x-0';
      return `${baseTransition} opacity-100 ${direction}`;
    }
    
    return `${baseTransition} opacity-100 translate-x-0`;
  };

  // SVG Icons to replace Lucide
  const Icons = useMemo(() => ({
    XCircle: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    ),
    CheckCircle: () => (
      <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    )
  }), []);

  return (
    <section 
      ref={sectionRef}
      className={styles.section}
      id={id}
      aria-labelledby={`${id}-heading`}
      aria-describedby={`${id}-subheading`}
    >
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header Section */}
        <div 
          className={`text-center mb-12 md:mb-16 ${getAnimationClass(0, 'header')}`}
          style={{
            transitionDelay: isInView ? '100ms' : '0ms'
          }}
        >
          <p className="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600 mb-3">
            {eyebrow}
          </p>
          <h2 id={`${id}-heading`} className={styles.title}>
            {title}
          </h2>
          <p id={`${id}-subheading`} className={styles.subtitle}>
            {subtitle}
          </p>
        </div>

        {/* Pain Points Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8 max-w-6xl mx-auto">
          {/* Problems Column */}
          <div 
            className={`space-y-6 ${getAnimationClass(0, 'column')}`}
            style={{
              transitionDelay: isInView ? '200ms' : '0ms'
            }}
          >
            <h3 id={`${id}-problems`} className={styles.columnTitle}>
              <Icons.XCircle />
              <span className="ml-3">Without SnoutIQ</span>
            </h3>
            {painPoints.map((point, index) => (
              <article
                key={`problem-${index}`}
                className={styles.problemCard}
                style={{
                  transitionDelay: isInView ? `${index * 100 + 300}ms` : '0ms'
                }}
              >
                <div className={styles.problemIcon}>
                  <Icons.XCircle />
                </div>
                <p className={styles.text}>{point.problem}</p>
              </article>
            ))}
          </div>

          {/* Solutions Column */}
          <div 
            className={`space-y-6 ${getAnimationClass(1, 'column')}`}
            style={{
              transitionDelay: isInView ? '200ms' : '0ms'
            }}
          >
            <h3 id={`${id}-solutions`} className={styles.columnTitle}>
              <Icons.CheckCircle />
              <span className="ml-3">With SnoutIQ</span>
            </h3>
            {painPoints.map((point, index) => (
              <article
                key={`solution-${index}`}
                className={styles.solutionCard}
                style={{
                  transitionDelay: isInView ? `${index * 100 + 300}ms` : '0ms'
                }}
              >
                <div className={styles.solutionIcon}>
                  <Icons.CheckCircle />
                </div>
                <p className={styles.text}>{point.solution}</p>
              </article>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
};

export default PainPoints;
