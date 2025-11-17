

import { useRef, useEffect, useState, useMemo } from 'react';

const Workflow = ({ title, subtitle, steps, eyebrow = 'Made-for-vets workflow', id = 'workflow' }) => {
  const sectionRef = useRef(null);
  const [isInView, setIsInView] = useState(false);
  
  // Intersection Observer for section
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
    section: 'py-12 md:py-20 lg:py-28 bg-gradient-to-br from-slate-50 to-blue-50',
    title: 'text-3xl sm:text-4xl md:text-5xl font-bold mb-4 leading-tight text-slate-900',
    subtitle: 'text-base sm:text-lg md:text-xl max-w-3xl mx-auto leading-relaxed text-slate-600',
    stepCard: 'bg-white p-6 md:p-8 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 relative z-10 hover:-translate-y-1',
    numberBadge: 'w-12 h-12 md:w-14 md:h-14 bg-gradient-to-br from-blue-600 to-blue-500 rounded-full flex items-center justify-center text-white font-bold text-lg md:text-xl mb-5 md:mb-6 mx-auto relative z-20 shadow-lg',
    iconContainer: 'w-14 h-14 md:w-16 md:h-16 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-4',
    stepTitle: 'text-lg md:text-xl font-bold text-slate-900 mb-2 md:mb-3 text-center',
    stepDesc: 'text-slate-600 text-center leading-relaxed text-sm md:text-base'
  }), []);

  // Animation classes
  const getAnimationClass = (index, type) => {
    if (!isInView) {
      return type === 'section' ? 'opacity-0 translate-y-8' : 'opacity-0 translate-y-6';
    }
    
    const baseTransition = 'transition-all duration-500 ease-out';
    
    if (type === 'section') {
      return `${baseTransition} opacity-100 translate-y-0`;
    }
    
    return `${baseTransition} opacity-100 translate-y-0`;
  };

  return (
    <section 
      ref={sectionRef}
      className={styles.section}
      id={id}
      aria-labelledby={`${id}-heading`}
      aria-describedby={`${id}-description`}
    >
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header Section */}
        <div 
          className={`text-center mb-12 md:mb-16 ${getAnimationClass(0, 'section')}`}
          style={{
            transitionDelay: isInView ? '100ms' : '0ms'
          }}
        >
          <p className="text-sm font-semibold uppercase tracking-[0.3em] text-blue-600 mb-3">
            {eyebrow}
          </p>
          <h2 id={`${id}-heading`} className={styles.title}>
            {title}
          </h2>
          <p id={`${id}-description`} className={styles.subtitle}>
            {subtitle}
          </p>
        </div>

        {/* Steps Section */}
        <div className="relative">
          {/* Desktop Connection Line */}
          <div 
            className="hidden lg:block absolute top-24 left-0 right-0 h-1 bg-gradient-to-r from-blue-600 via-blue-500 to-purple-500"
            style={{ width: 'calc(100% - 8rem)', left: '4rem' }} 
          />

          <ol className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8">
            {steps.map((step, index) => {
              const Icon = step.icon;
              return (
                <li
                  key={`step-${index}`}
                  className="relative"
                  aria-label={step.title}
                >
                  {/* Step Card */}
                  <div 
                    className={styles.stepCard}
                    style={{
                      transitionDelay: isInView ? `${index * 150 + 300}ms` : '0ms'
                    }}
                  >
                    {/* Number Badge */}
                    <div className={styles.numberBadge}>
                      {step.number}
                    </div>

                    {/* Icon */}
                    <div className={styles.iconContainer}>
                      <Icon className="text-blue-600" size={28} />
                    </div>

                    {/* Content */}
                    <h3 className={styles.stepTitle}>
                      {step.title}
                    </h3>
                    <p className={styles.stepDesc}>
                      {step.description}
                    </p>
                  </div>

                  {/* Mobile Arrow */}
                  {index < steps.length - 1 && (
                    <div className="lg:hidden flex justify-center my-4">
                      <div className="w-1 h-12 bg-gradient-to-b from-blue-600 to-purple-500" />
                    </div>
                  )}
                </li>
              );
            })}
          </ol>
        </div>
      </div>
    </section>
  );
};

export default Workflow;
