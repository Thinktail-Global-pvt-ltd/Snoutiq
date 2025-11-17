import { memo, useRef, useEffect, useState, useMemo } from 'react';

const Stats = ({ stats, variant = 'default' }) => {
  const ref = useRef(null);
  const [isInView, setIsInView] = useState(false);
  
  // Optimized intersection observer
  useEffect(() => {
    const element = ref.current;
    if (!element) return;

    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setIsInView(true);
          observer.disconnect();
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

  // Memoized style configurations
  const styles = useMemo(() => ({
    container: {
      default: 'bg-white',
      gradient: 'bg-gradient-to-br from-blue-50 to-purple-50',
      dark: 'bg-gradient-to-br from-gray-900 to-gray-800 text-white',
    },
    textColor: {
      default: 'text-gray-900',
      gradient: 'text-gray-900',
      dark: 'text-white',
    },
    subTextColor: {
      default: 'text-gray-600',
      gradient: 'text-gray-600',
      dark: 'text-gray-300',
    }
  }), []);

  // Animation classes based on state
  const getAnimationClass = (index, type) => {
    if (!isInView) {
      return type === 'number' 
        ? 'opacity-0 scale-50' 
        : 'opacity-0 translate-y-5';
    }
    
    const baseTransition = 'transition-all duration-500 ease-out';
    
    if (type === 'number') {
      return `${baseTransition} opacity-100 scale-100`;
    }
    
    return `${baseTransition} opacity-100 translate-y-0`;
  };

  return (
    <section 
      ref={ref}
      className={`py-16 px-4 sm:px-6 lg:px-8 ${styles.container[variant]}`}
    >
      <div className="max-w-7xl mx-auto">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-8">
          {stats.map((stat, index) => (
            <div
              key={`stat-${index}`}
              className="text-center"
            >
              {/* Number with optimized animations */}
              <div 
                className={`
                  text-4xl md:text-5xl font-bold mb-2
                  ${getAnimationClass(index, 'number')}
                  ${variant === 'dark' 
                    ? 'text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-500' 
                    : 'text-blue-600'
                  }
                `}
                style={{
                  transitionDelay: isInView ? `${index * 100}ms` : '0ms'
                }}
              >
                {stat.value}
                {stat.suffix && (
                  <span className="text-2xl md:text-3xl align-super">
                    {stat.suffix}
                  </span>
                )}
              </div>
              
              {/* Label with optimized animations */}
              <p 
                className={`
                  text-base md:text-lg font-medium
                  ${styles.subTextColor[variant]}
                  ${getAnimationClass(index, 'label')}
                `}
                style={{
                  transitionDelay: isInView ? `${index * 100 + 200}ms` : '0ms'
                }}
              >
                {stat.label}
              </p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

export default memo(Stats);
