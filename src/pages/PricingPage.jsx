import { useState, useRef, useEffect, useMemo, lazy, Suspense } from 'react';
import { Link } from 'react-router-dom';

const Header = lazy(() => import('../components/Header'));
const CTA = lazy(() => import('../components/CTA'));
const Footer = lazy(() => import('../components/Footer'));

const PricingPage = () => {
  const [billingCycle, setBillingCycle] = useState('annual');
  const sectionRef = useRef(null);
  const [isInView, setIsInView] = useState(false);

  // Intersection Observer for animations
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

  // Memoized plans data
  const plans = useMemo(() => [
    {
      name: 'Starter',
      description: 'Perfect for solo practitioners',
      monthlyPrice: 99,
      annualPrice: 990,
      features: [
        'Up to 2 veterinarians',
        '500 consultations/month',
        'HD Video consultations',
        'AI Triage',
        'Basic analytics',
        'Email support',
        'Mobile apps',
        'Cloud storage (50GB)',
      ],
      notIncluded: [
        'Advanced analytics',
        'Priority support',
        'Custom integrations',
        'Dedicated account manager',
      ],
      highlighted: false,
    },
    {
      name: 'Professional',
      description: 'Most popular for growing clinics',
      monthlyPrice: 299,
      annualPrice: 2990,
      features: [
        'Up to 10 veterinarians',
        'Unlimited consultations',
        'Everything in Starter',
        'Advanced analytics',
        'Team collaboration',
        'Priority support',
        'API access',
        'Cloud storage (500GB)',
        'Custom branding',
        'Multiple locations',
      ],
      notIncluded: [
        'Custom integrations',
        'Dedicated account manager',
        'SLA guarantee',
      ],
      highlighted: true,
    },
    {
      name: 'Enterprise',
      description: 'For multi-location practices',
      monthlyPrice: null,
      annualPrice: null,
      features: [
        'Unlimited veterinarians',
        'Unlimited consultations',
        'Everything in Professional',
        'Custom integrations',
        'Dedicated account manager',
        'SLA guarantee (99.9%)',
        'Advanced security',
        'Unlimited cloud storage',
        'White-label options',
        'Training & onboarding',
        'Custom development',
      ],
      notIncluded: [],
      highlighted: false,
    },
  ], []);

  // Memoized FAQs
  const faqs = useMemo(() => [
    {
      question: 'Is there a free trial?',
      answer: 'Yes! We offer a 14-day free trial with full access to all features. No credit card required.',
    },
    {
      question: 'Can I switch plans later?',
      answer: 'Absolutely. You can upgrade or downgrade your plan at any time. Changes take effect at the start of your next billing cycle.',
    },
    {
      question: 'What payment methods do you accept?',
      answer: 'We accept all major credit cards, debit cards, and bank transfers for annual plans.',
    },
    {
      question: 'Is there a setup fee?',
      answer: 'No setup fees for Starter and Professional plans. Enterprise plans include complimentary white-glove onboarding.',
    },
    {
      question: 'What if I exceed my consultation limit?',
      answer: 'Starter plan consultations beyond 500/month are charged at ₹50 per consultation. We\'ll notify you before any overage charges.',
    },
    {
      question: 'Do you offer discounts for annual billing?',
      answer: 'Yes! Annual billing saves you 2 months (17% discount) compared to monthly billing.',
    },
  ], []);

  // SVG Icons
  const Icons = useMemo(() => ({
    Check: () => (
      <svg className="w-5 h-5 flex-shrink-0 mr-3 mt-0.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
      </svg>
    ),
    X: () => (
      <svg className="w-5 h-5 flex-shrink-0 mr-3 mt-0.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
      </svg>
    )
  }), []);

  // Animation classes
  const getAnimationClass = (index, type = 'fade') => {
    if (!isInView) {
      return type === 'fade' ? 'opacity-0' : 'opacity-0 translate-y-6';
    }
    
    const baseTransition = 'transition-all duration-500 ease-out';
    const delays = ['100ms', '200ms', '300ms', '400ms', '500ms', '600ms'];
    
    if (type === 'fade') {
      return `${baseTransition} opacity-100`;
    }
    
    return `${baseTransition} opacity-100 translate-y-0`;
  };

  return (
    <>
    <Suspense fallback={<div className="h-16 w-full bg-white/80" />}>
      <Header/>
    </Suspense>
      {/* Hero Section */}
      <section 
        ref={sectionRef}
        className="py-16 md:py-20 lg:py-24 bg-gradient-to-br from-slate-50 via-blue-50 to-slate-50 pt-28"
      >
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12 md:mb-16">
            <h1 
              className="text-4xl sm:text-5xl md:text-6xl font-bold leading-tight text-slate-900 mb-6"
              style={{
                transitionDelay: isInView ? '100ms' : '0ms'
              }}
            >
              Simple, <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">Transparent</span> Pricing
            </h1>
            <p 
              className="text-lg sm:text-xl md:text-2xl leading-relaxed text-slate-600 max-w-3xl mx-auto mb-8"
              style={{
                transitionDelay: isInView ? '200ms' : '0ms'
              }}
            >
              Choose the perfect plan for your practice. All plans include 14-day free trial.
            </p>

            {/* Billing Toggle */}
            <div 
              className="inline-flex items-center bg-white p-2 rounded-xl shadow-lg border border-gray-200"
              style={{
                transitionDelay: isInView ? '300ms' : '0ms'
              }}
            >
              <button
                onClick={() => setBillingCycle('monthly')}
                className={`px-6 sm:px-8 py-3 rounded-lg font-semibold transition-all ${
                  billingCycle === 'monthly'
                    ? 'bg-gradient-to-r from-blue-600 to-blue-500 text-white shadow-md'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-gray-50'
                }`}
              >
                Monthly
              </button>
              <button
                onClick={() => setBillingCycle('annual')}
                className={`px-6 sm:px-8 py-3 rounded-lg font-semibold transition-all relative ${
                  billingCycle === 'annual'
                    ? 'bg-gradient-to-r from-blue-600 to-blue-500 text-white shadow-md'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-gray-50'
                }`}
              >
                Annual
                <span className="absolute -top-2 -right-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                  Save 17%
                </span>
              </button>
            </div>
          </div>

          {/* Pricing Cards */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8 mb-16">
            {plans.map((plan, index) => (
              <div
                key={plan.name}
                className={`relative bg-white rounded-2xl shadow-lg overflow-hidden flex flex-col border border-gray-200 hover:shadow-xl transition-all duration-300 ${
                  plan.highlighted
                    ? 'ring-2 ring-blue-600 scale-105 md:scale-110 z-10'
                    : 'hover:scale-105'
                }`}
                style={{
                  transitionDelay: isInView ? `${index * 100 + 400}ms` : '0ms'
                }}
              >
                {plan.highlighted && (
                  <div className="absolute top-0 left-0 right-0 bg-gradient-to-r from-blue-600 to-blue-500 text-white text-center py-2 text-sm font-semibold">
                    Most Popular
                  </div>
                )}

                <div className={`p-6 md:p-8 flex flex-col flex-grow ${plan.highlighted ? 'pt-14' : ''}`}>
                  <h3 className="text-2xl md:text-3xl font-bold text-slate-900 mb-2">
                    {plan.name}
                  </h3>
                  <p className="text-slate-600 mb-6">{plan.description}</p>

                  <div className="mb-8">
                    {plan.monthlyPrice ? (
                      <>
                        <div className="flex items-baseline">
                          <span className="text-5xl font-bold text-slate-900">
                            ₹
                            {billingCycle === 'monthly'
                              ? plan.monthlyPrice.toLocaleString()
                              : Math.round(plan.annualPrice / 12).toLocaleString()}
                          </span>
                          <span className="text-slate-600 ml-2">/month</span>
                        </div>
                        {billingCycle === 'annual' && (
                          <p className="text-sm text-slate-500 mt-2">
                            ₹{plan.annualPrice.toLocaleString()} billed annually
                          </p>
                        )}
                      </>
                    ) : (
                      <div className="text-3xl font-bold text-slate-900">
                        Custom Pricing
                      </div>
                    )}
                  </div>

                  <Link
                    to={plan.monthlyPrice ? 'https://docs.google.com/forms/d/e/1FAIpQLSeQkcn7C8oSvoN4_eMRwWjN4nLfc0IPQT_ZKuwMKMAKzh4SSQ/viewform' : 'https://docs.google.com/forms/d/e/1FAIpQLSeQkcn7C8oSvoN4_eMRwWjN4nLfc0IPQT_ZKuwMKMAKzh4SSQ/viewform'}
                    className={`w-full px-6 py-4 rounded-xl font-semibold text-center transition-all mb-8 ${
                      plan.highlighted
                        ? 'bg-gradient-to-r from-blue-600 to-blue-500 text-white hover:shadow-lg hover:scale-105'
                        : 'bg-gray-100 text-slate-900 hover:bg-gray-200 hover:scale-105'
                    }`}
                  >
                    {plan.monthlyPrice ? 'Start Free Trial' : 'Contact Sales'}
                  </Link>

                  <ul className="space-y-4 flex-grow">
                    {plan.features.map((feature, i) => (
                      <li key={i} className="flex items-start">
                        <Icons.Check />
                        <span className="text-slate-700 text-sm md:text-base">{feature}</span>
                      </li>
                    ))}
                    {plan.notIncluded.map((feature, i) => (
                      <li key={i} className="flex items-start opacity-50">
                        <Icons.X />
                        <span className="text-slate-500 text-sm md:text-base">{feature}</span>
                      </li>
                    ))}
                  </ul>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* FAQ Section */}
      <section className="py-16 md:py-20 lg:py-24 bg-white">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12 md:mb-16">
            <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold leading-tight text-slate-900 mb-4">
              Frequently Asked <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">Questions</span>
            </h2>
          </div>

          <div className="space-y-6 md:space-y-8">
            {faqs.map((faq, index) => (
              <div
                key={index}
                className="bg-gray-50 p-6 md:p-8 rounded-2xl border border-gray-200 hover:shadow-md transition-all duration-300"
                style={{
                  transitionDelay: isInView ? `${index * 100 + 200}ms` : '0ms'
                }}
              >
                <h3 className="text-xl md:text-2xl font-bold text-slate-900 mb-3">
                  {faq.question}
                </h3>
                <p className="text-lg leading-relaxed text-slate-600">{faq.answer}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <Suspense fallback={<div className="h-48 bg-white" />}>
        <CTA
          title="Ready to Get Started?"
          subtitle="Start your 14-day free trial today. No credit card required."
          primaryButton={{ text: 'Start Free Trial', href: 'https://docs.google.com/forms/d/e/1FAIpQLSeQkcn7C8oSvoN4_eMRwWjN4nLfc0IPQT_ZKuwMKMAKzh4SSQ/viewform' }}
          secondaryButton={{ text: 'Schedule Demo', href: 'https://docs.google.com/forms/d/e/1FAIpQLSeQkcn7C8oSvoN4_eMRwWjN4nLfc0IPQT_ZKuwMKMAKzh4SSQ/viewform' }}
          variant="gradient"
        />
      </Suspense>
      <Suspense fallback={<div className="h-24 bg-blue-50" />}>
        <Footer/>
      </Suspense>
    </>
  );
};

export default PricingPage;
