
import { memo, useRef, useEffect, useState, useMemo } from 'react';

const ClientLogos = ({
  eyebrow = 'Trusted by forward-thinking clinics',
  title = 'Partnering with veterinary leaders nationwide',
  subtitle = 'Independent hospitals, ER groups, and corporate consolidators rely on SnoutIQ to modernise client experience.',
  id = 'trusted-by',
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
          observer.disconnect();
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

  const clients = useMemo(
    () => [
      'Bayview Animal Hospital',
      'VetWell Collective',
      'Animal Care Center',
      'Paws & Claws Clinic',
      'City Vet Services',
      'Happy Pets Clinic',
      'Premier Animal Hospital',
      'Companion Care Vet',
    ],
    []
  );

  const duplicatedClients = useMemo(() => [...clients, ...clients], [clients]);
  const headingId = `${id}-heading`;
  const descriptionId = `${id}-description`;

  return (
    <section
      ref={sectionRef}
      id={id}
      aria-labelledby={headingId}
      aria-describedby={descriptionId}
      className="py-12 md:py-16 bg-slate-50 overflow-hidden"
    >
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div
          className={`text-center mb-12 transition-all duration-500 ease-out ${
            isInView ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'
          }`}
        >
          <p className="text-sm font-semibold uppercase tracking-widest text-blue-600 mb-2">
            {eyebrow}
          </p>
          <h2 id={headingId} className="text-2xl md:text-3xl font-bold text-slate-900">
            {title}
          </h2>
          <p
            id={descriptionId}
            className="text-base md:text-lg text-slate-600 max-w-3xl mx-auto mt-3"
          >
            {subtitle}
          </p>
        </div>

        <div className="relative">
          <div className="absolute left-0 top-0 bottom-0 w-24 bg-gradient-to-r from-slate-50 to-transparent pointer-events-none" aria-hidden="true" />
          <div className="absolute right-0 top-0 bottom-0 w-24 bg-gradient-to-l from-slate-50 to-transparent pointer-events-none" aria-hidden="true" />

          <div className="overflow-hidden">
            <ul className="flex gap-8 animate-logo-marquee hover:[animation-play-state:paused]">
              {duplicatedClients.map((client, index) => (
                <li
                  key={`${client}-${index}`}
                  className="flex items-center justify-center px-8 py-5 rounded-2xl bg-white shadow-sm border border-slate-100 min-w-[220px]"
                >
                  <span className="text-base font-semibold text-slate-600 whitespace-nowrap">
                    {client}
                  </span>
                </li>
              ))}
            </ul>
          </div>
        </div>
      </div>

      <style>{`
        @keyframes logo-marquee {
          0% { transform: translateX(0); }
          100% { transform: translateX(-50%); }
        }
        .animate-logo-marquee {
          animation: logo-marquee 30s linear infinite;
        }
        @media (max-width: 768px) {
          .animate-logo-marquee {
            animation-duration: 18s;
          }
        }
      `}</style>
    </section>
  );
};

export default memo(ClientLogos);
