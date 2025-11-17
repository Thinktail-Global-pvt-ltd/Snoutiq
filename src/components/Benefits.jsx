import React, { memo } from 'react';
import { motion } from 'framer-motion';

const VARIANT_CONTAINER_CLASS = {
  default: 'bg-white',
  gradient: 'bg-gradient-to-br from-primary/5 to-accent/5',
  dark: 'bg-gradient-dark text-white',
};

function Benefits({
  benefits,
  variant = 'default',
  eyebrow = 'Built for modern care teams',
  title = 'Why veterinary groups choose SnoutIQ',
  description = 'A single operating system for intake, consults, and follow-up means tighter workflows and calmer pet parents.',
  id = 'benefits',
}) {
  const headingId = `${id}-heading`;
  const descriptionId = `${id}-description`;

  return (
    <section
      id={id}
      aria-labelledby={headingId}
      aria-describedby={descriptionId}
      className={`py-12 md:py-16 lg:py-20 ${VARIANT_CONTAINER_CLASS[variant]}`}
    >
      <div className="container-custom">
        <div className="text-center max-w-3xl mx-auto mb-10 md:mb-14">
          <p className="text-sm font-semibold uppercase tracking-widest text-blue-600 mb-3">
            {eyebrow}
          </p>
          <h2 id={headingId} className="text-3xl md:text-4xl lg:text-5xl font-bold text-slate-900 mb-4">
            {title}
          </h2>
          <p
            id={descriptionId}
            className="text-base md:text-lg text-slate-600 leading-relaxed"
          >
            {description}
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 md:gap-6 lg:gap-8">
          {benefits.map((benefit, index) => {
            const Icon = benefit.icon;
            const iconNode = (() => {
              if (!Icon) return null;
              if (typeof Icon === 'function') {
                return <Icon size={22} className="md:w-6 md:h-6" aria-hidden="true" />;
              }
              if (React.isValidElement(Icon)) {
                return Icon;
              }
              if (
                typeof Icon === 'object' &&
                Icon !== null &&
                ('render' in Icon || '$$typeof' in Icon)
              ) {
                return React.createElement(Icon, {
                  size: 22,
                  className: 'md:w-6 md:h-6',
                  'aria-hidden': true,
                });
              }
              return null;
            })();

            return (
              <motion.article
                key={benefit.title}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true, amount: 0.2 }}
                transition={{ duration: 0.45, delay: index * 0.06 }}
                className={`flex flex-col items-start text-left p-5 md:p-6 rounded-2xl border ${
                  variant === 'dark'
                    ? 'bg-white/10 border-white/10 text-white'
                    : 'bg-white border-blue-50 shadow-md'
                } hover:shadow-xl hover:-translate-y-1 transition-all duration-300`}
                aria-label={benefit.title}
              >
                <div
                  className={`w-12 h-12 md:w-14 md:h-14 rounded-2xl ${
                    variant === 'dark'
                      ? 'bg-white/20 text-white'
                      : 'bg-gradient-to-br from-primary to-primary-light  shadow-lg'
                  } flex items-center justify-center mb-3 md:mb-4`}
                >
                  {iconNode ? (
                    iconNode
                  ) : (
                    <svg
                      className="w-5 h-5 md:w-6 md:h-6"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                      aria-hidden="true"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M5 13l4 4L19 7"
                      />
                    </svg>
                  )}
                </div>

                <h3
                  className={`text-lg font-bold mb-2 ${
                    variant === 'dark' ? 'text-white' : 'text-slate-900'
                  }`}
                >
                  {benefit.title}
                </h3>

                <p
                  className={`text-sm leading-relaxed ${
                    variant === 'dark' ? 'text-slate-300' : 'text-slate-600'
                  }`}
                >
                  {benefit.description}
                </p>
              </motion.article>
            );
          })}
        </div>
      </div>
    </section>
  );
}

export default memo(Benefits);
