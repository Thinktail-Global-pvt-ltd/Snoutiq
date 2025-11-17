

import { useRef, useEffect, useState, useMemo } from 'react';

const Testimonials = ({
  testimonials,
  eyebrow = 'Customer stories',
  title = 'Loved by veterinary leaders',
  subtitle = 'See what veterinary professionals are saying about SnoutIQ',
  id = 'testimonials',
}) => {
  const sectionRef = useRef(null);
  const [isInView, setIsInView] = useState(false);

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
        rootMargin: '50px',
      }
    );

    observer.observe(element);
    return () => observer.disconnect();
  }, []);

  const [titleLead, titleAccent] = useMemo(() => {
    if (!title) return ['', ''];
    const words = title.split(' ');
    if (words.length === 1) {
      return [title, ''];
    }
    return [words.slice(0, -1).join(' '), words[words.length - 1]];
  }, [title]);

  const styles = useMemo(
    () => ({
      section: 'py-12 md:py-20 lg:py-28 bg-gradient-to-br from-slate-50 to-blue-50',
      title: 'text-3xl sm:text-4xl md:text-5xl font-bold mb-4 leading-tight text-slate-900',
      subtitle: 'text-base sm:text-lg md:text-xl max-w-2xl mx-auto leading-relaxed text-slate-600',
      card: 'bg-white p-6 md:p-8 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 relative hover:-translate-y-1',
      gradientText: 'text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600',
      quoteIcon: 'absolute top-6 right-6 text-blue-100',
      content: 'mb-6 relative z-10 leading-relaxed text-slate-600',
      avatar: 'w-12 h-12 rounded-full bg-gradient-to-br from-blue-600 to-purple-600 flex items-center justify-center text-white font-bold text-lg',
      authorName: 'font-semibold text-slate-900',
      authorRole: 'text-sm leading-tight text-slate-500',
    }),
    []
  );

  const getAnimationClass = (index, type) => {
    if (!isInView) {
      return type === 'header' ? 'opacity-0 translate-y-8' : 'opacity-0 translate-y-6';
    }

    const baseTransition = 'transition-all duration-500 ease-out';

    if (type === 'header') {
      return `${baseTransition} opacity-100 translate-y-0`;
    }

    return `${baseTransition} opacity-100 translate-y-0`;
  };

  const renderStars = (rating) =>
    Array.from({ length: 5 }).map((_, i) => (
      <svg
        key={i}
        className={`h-4 w-4 ${i < rating ? 'text-yellow-400' : 'text-slate-200'}`}
        viewBox="0 0 20 20"
        fill="currentColor"
        aria-hidden="true"
      >
        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.539 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
      </svg>
    ));

  return (
    <section
      ref={sectionRef}
      className={styles.section}
      id={id}
      aria-labelledby={`${id}-heading`}
      aria-describedby={`${id}-description`}
    >
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div
          className={`text-center mb-12 md:mb-16 ${getAnimationClass(0, 'header')}`}
          style={{
            transitionDelay: isInView ? '100ms' : '0ms',
          }}
        >
          <p className="text-sm font-semibold uppercase tracking-[0.3em] text-blue-600 mb-3">
            {eyebrow}
          </p>
          <h2 id={`${id}-heading`} className={styles.title}>
            {titleLead}{' '}
            {titleAccent && <span className={styles.gradientText}>{titleAccent}</span>}
          </h2>
          <p id={`${id}-description`} className={styles.subtitle}>
            {subtitle}
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
          {testimonials.map((testimonial, index) => (
            <article
              key={`testimonial-${index}`}
              className={styles.card}
              style={{
                transitionDelay: isInView ? `${index * 100 + 300}ms` : '0ms',
              }}
              itemScope
              itemType="https://schema.org/Review"
            >
              <div className={styles.quoteIcon} aria-hidden="true">
                <svg fill="currentColor" viewBox="0 0 24 24" className="w-12 h-12">
                  <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5c-1.073 0-2.099-.49-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5c-1.073 0-2.099-.49-2.748-1.179z" />
                </svg>
              </div>

              <div className="flex space-x-1 mb-4" aria-label={`Rated ${testimonial.rating} out of 5`}>
                {renderStars(testimonial.rating)}
              </div>

              <p className={styles.content} itemProp="reviewBody">
                “{testimonial.content}”
              </p>

              <div className="flex items-center space-x-3">
                <div className={styles.avatar} aria-hidden="true">
                  {testimonial.name.charAt(0)}
                </div>
                <div className="min-w-0">
                  <h4 className={styles.authorName} itemProp="author" itemScope itemType="https://schema.org/Person">
                    <span itemProp="name">{testimonial.name}</span>
                  </h4>
                  <p className={styles.authorRole}>
                    <span itemProp="jobTitle">{testimonial.role}</span>,{' '}
                    <span itemProp="name">{testimonial.company}</span>
                  </p>
                </div>
              </div>
              <meta itemProp="reviewRating" content={String(testimonial.rating)} />
            </article>
          ))}
        </div>
      </div>
    </section>
  );
};

export default Testimonials;
