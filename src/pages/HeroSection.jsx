import { Link } from "react-router-dom";
import { useRef, useEffect, useState, useMemo } from "react";
import doctor1 from "../assets/images/doctor_1.png";
import doctor2 from "../assets/images/doctor_2.png";
import doctor3 from "../assets/images/doctor_3.png";

const Hero = ({
  badge,
  title,
  subtitle,
  ctaPrimary,
  ctaSecondary,
  imagePlaceholder = true,
  gradient = true,
}) => {
  const sectionRef = useRef(null);
  const [isMounted, setIsMounted] = useState(false);
  const headingId = "hero-heading";
  const subheadingId = "hero-subheading";

  useEffect(() => {
    setIsMounted(true);
  }, []);

  const styles = useMemo(
    () => ({
      section: `relative min-h-[80vh] flex items-center overflow-hidden py-14 sm:py-16 ${
        gradient
          ? "bg-gradient-to-br from-slate-50 via-blue-50 to-slate-50"
          : "bg-white"
      }`,
      title:
        "text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-bold mb-6 leading-tight text-slate-900",
      subtitle:
        "text-base sm:text-lg md:text-xl lg:text-2xl mb-8 leading-relaxed max-w-3xl text-slate-600",
      badge:
        "inline-flex items-center space-x-2 bg-blue-100 text-blue-700 px-4 py-2 rounded-full text-sm font-medium mb-6 shadow-sm",
      primaryButton:
        "group inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-500 text-white font-semibold rounded-xl hover:shadow-2xl hover:scale-105 transition-all duration-300",
      secondaryButton:
        "group inline-flex items-center justify-center px-8 py-4 bg-white border-2 border-slate-200 text-slate-700 font-semibold rounded-xl hover:border-blue-500 hover:text-blue-600 hover:shadow-lg transition-all duration-300",
    }),
    [gradient]
  );

  const heroChecklist = useMemo(
    () => [
      {
        title: "Launch teleconsultation fast",
        copy: "Templates, automations, and onboarding for clinics.",
      },
      {
        title: "Proactive client care",
        copy: "AI-assisted triage plus secure chat keep pet parents calm.",
      },
      {
        title: "Integrated revenue",
        copy: "Scheduling, payments, and reminders live in one workflow.",
      },
      {
        title: "HIPAA-ready hosting",
        copy: "Enterprise-grade security, logging, and uptime SLAs.",
      },
    ],
    []
  );

  const heroStats = useMemo(
    () => [
      { value: "10 min", label: "Avg. response time" },
      { value: "70%", label: "Fewer missed visits" },
      { value: "24/7", label: "Live veterinary coverage" },
    ],
    []
  );

  const doctorProfiles = useMemo(
    () => [
      {
        name: "Dr. Emma Reed",
        src: doctor1,
      },
      {
        name: "Dr. Lucas Patel",
        src: doctor2,
      },
      {
        name: "Dr. Maya Bennett",
        src: doctor1,
      },
      {
        name: "Dr. Noah Kim",
        src: doctor3,
      },
    ],
    []
  );

  const Icons = useMemo(
    () => ({
      ArrowRight: () => (
        <svg
          className="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M14 5l7 7m0 0l-7 7m7-7H3"
          />
        </svg>
      ),
      Play: () => (
        <svg
          className="mr-2 w-5 h-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
          />
        </svg>
      ),
      User: () => (
        <svg
          className="w-6 h-6 text-white"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
      ),
      LargeCheck: () => (
        <svg
          className="w-8 h-8 text-white"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
      ),
    }),
    []
  );

  const renderTitle = () => {
    if (!title) return null;
    return title.split(" ").map((word, index) => {
      const shouldHighlight = /snoutiq|ai|video|telehealth/i.test(word);
      return (
        <span key={`${word}-${index}`}>
          {shouldHighlight ? (
            <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">
              {word}
            </span>
          ) : (
            word
          )}{" "}
        </span>
      );
    });
  };

  return (
    <section
      ref={sectionRef}
      className={styles.section}
      id="hero"
      aria-labelledby={headingId}
      aria-describedby={subheadingId}
    >
      <div className="absolute inset-0 overflow-hidden pointer-events-none">
        <div className="absolute top-1/4 -right-32 w-96 h-96 bg-blue-200 rounded-full blur-3xl opacity-30" />
        <div className="absolute bottom-1/4 -left-32 w-96 h-96 bg-purple-200 rounded-full blur-3xl opacity-30" />
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full relative z-10">
        <div className="grid lg:grid-cols-2 gap-10 items-center pt-12 lg:pt-16">
          <div className="text-center lg:text-left">
            {badge && (
              <div className={styles.badge}>
                <span className="w-2 h-2 bg-blue-600 rounded-full animate-pulse" />
                <span>{badge}</span>
              </div>
            )}

            <h1 id={headingId} className={styles.title}>
              {renderTitle()}
            </h1>

            <p id={subheadingId} className={styles.subtitle}>
              {subtitle}
            </p>

            <div className="grid gap-4 sm:grid-cols-2 text-left mb-8">
              {heroChecklist.map((item) => (
                <div
                  key={item.title}
                  className="flex items-start space-x-3 rounded-2xl border border-blue-100/70 bg-white/80 px-4 py-4 shadow-sm backdrop-blur"
                >
                  <span className="mt-1 inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-blue-500 text-white">
                    <svg
                      className="h-4 w-4"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M5 13l4 4L19 7"
                      />
                    </svg>
                  </span>
                  <div>
                    <p className="text-sm font-semibold text-slate-900">
                      {item.title}
                    </p>
                    <p className="text-xs text-slate-500 leading-relaxed">
                      {item.copy}
                    </p>
                  </div>
                </div>
              ))}
            </div>

            <div className="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
              <Link to={ctaPrimary.href} className={styles.primaryButton}>
                {ctaPrimary.text}
                <Icons.ArrowRight />
              </Link>

              {ctaSecondary && (
                <Link to={ctaSecondary.href} className={styles.secondaryButton}>
                  <Icons.Play />
                  {ctaSecondary.text}
                </Link>
              )}
            </div>

            <div className="mt-8 flex flex-col space-y-5 text-sm text-slate-500">
              <div className="flex items-center justify-center space-x-2 lg:justify-start">
                <div className="flex -space-x-2" aria-hidden="true">
                  {doctorProfiles.map((doctor) => (
                    <img
                      key={doctor.name}
                      src={doctor.src}
                      alt={doctor.name}
                      loading="lazy"
                      className="h-8 w-8 rounded-full object-cover border-2 border-white shadow-sm"
                    />
                  ))}
                </div>
                <span className="font-semibold text-slate-600">
                  50+ modern veterinary teams already on SnoutIQ
                </span>
              </div>
              <ul
                className="grid gap-4 text-left sm:grid-cols-3"
                aria-label="Key performance metrics"
              >
                {heroStats.map((stat) => (
                  <li
                    key={stat.label}
                    className="rounded-2xl border border-blue-100 bg-white/80 px-4 py-3 text-center shadow-sm"
                  >
                    <p className="text-2xl font-bold text-slate-900">
                      {stat.value}
                    </p>
                    <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                      {stat.label}
                    </p>
                  </li>
                ))}
              </ul>
            </div>
          </div>

          {imagePlaceholder && (
            <div className="relative">
              <div className="relative w-full aspect-square rounded-3xl overflow-hidden bg-gradient-to-br from-blue-100 to-purple-100">
                <img
                  src="https://images.unsplash.com/photo-1628009368231-7bb7cfcb0def?w=600&h=600&fit=crop&q=70" // Reduced size
                  alt="Veterinarian with pet during consultation"
                  width={600}
                  height={600}
                  className="h-full w-full object-cover"
                  loading="eager" 
                  fetchpriority="high" 
                  decoding="async"
                />

                <div className="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent" />

                <div className="animate-float-slow absolute top-8 right-8 bg-white/95 backdrop-blur-md p-6 rounded-2xl shadow-2xl max-w-xs">
                  <div className="flex items-center space-x-3 mb-3">
                    <div className="w-12 h-12 rounded-full bg-gradient-to-br from-blue-600 to-purple-600 flex items-center justify-center">
                      <Icons.User />
                    </div>
                    <div>
                      <p className="font-semibold text-slate-900">
                        Dr. Maayank Malhotra
                      </p>
                      <p className="text-sm text-slate-500">Online now</p>
                    </div>
                  </div>
                  <p className="text-sm text-slate-600">
                    Reviewing an urgent case flagged by AI triage.
                  </p>
                </div>

                <div className="animate-float-fast absolute bottom-8 left-8 bg-white/95 backdrop-blur-md p-6 rounded-2xl shadow-2xl">
                  <div className="flex items-center space-x-4">
                    <div className="w-16 h-16 rounded-xl bg-gradient-to-br from-green-500 to-green-400 flex items-center justify-center">
                      <Icons.LargeCheck />
                    </div>
                    <div>
                      <p className="font-semibold text-slate-900">
                        Expert Care
                      </p>
                      <p className="text-sm text-slate-500">
                        AI-powered + human vets
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>

      <style>{`
        @keyframes float-slow {
          0%, 100% { transform: translateY(0px); }
          50% { transform: translateY(-20px); }
        }
        @keyframes float-fast {
          0%, 100% { transform: translateY(0px); }
          50% { transform: translateY(10px); }
        }
        .animate-float-slow {
          animation: float-slow 6s ease-in-out infinite;
        }
        .animate-float-fast {
          animation: float-fast 4s ease-in-out infinite;
        }
      `}</style>
    </section>
  );
};

export default Hero;
