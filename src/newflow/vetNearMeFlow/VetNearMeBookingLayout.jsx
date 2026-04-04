import React, { useEffect, useMemo, useState } from "react";
import { Helmet } from "react-helmet-async";
import { Outlet, useLocation } from "react-router-dom";
import {
  BOOKING_FLOW_ROUTES,
  BOOKING_PRICING,
  COVERAGE_AREAS,
  FAQ_ITEMS,
  FEATURED_VETS,
  FEATURES,
  HOW_IT_WORKS_STEPS,
  NETWORK_BADGES,
  REVIEWS,
  STANDARD_CHECKS,
  TRUST_PILLS,
  VALUE_ROWS,
} from "./bookingFlowData";
import { VetNearMeBookingProvider } from "./VetNearMeBookingContext";
import logoImage from "../../assets/images/logo.png";
import "./VetNearMeBooking.css";

const STEP_NUMBER_BY_PATH = {
  [BOOKING_FLOW_ROUTES.lead]: 1,
  [BOOKING_FLOW_ROUTES.petDetails]: 2,
  [BOOKING_FLOW_ROUTES.payment]: 3,
  [BOOKING_FLOW_ROUTES.success]: 4,
};

function StepIndicator({ currentStep }) {
  if (currentStep === 4) return null;

  return (
    <div className="steps-indicator">
      {[1, 2, 3].map((stepNumber, index) => (
        <React.Fragment key={stepNumber}>
          <div
            className={[
              "si-item",
              stepNumber === currentStep ? "active" : "",
              stepNumber < currentStep ? "done" : "",
            ]
              .filter(Boolean)
              .join(" ")}
          >
            <div className="si-dot">{stepNumber}</div>
            <span>{stepNumber === 1 ? "You" : stepNumber === 2 ? "Your pet" : "Pay"}</span>
          </div>
          {index < 2 ? <div className="si-line" /> : null}
        </React.Fragment>
      ))}
    </div>
  );
}

function VetNearMeBookingPage() {
  const location = useLocation();
  const [openFaqIndex, setOpenFaqIndex] = useState(null);
  const [selectedArea, setSelectedArea] = useState("Gurgaon");
  const [showStickyCta, setShowStickyCta] = useState(false);

  const currentStep = useMemo(
    () => STEP_NUMBER_BY_PATH[location.pathname] || 1,
    [location.pathname]
  );
  const isSuccessStep = currentStep === 4;
  const isStandaloneStep =
    currentStep === 2 || currentStep === 3 || isSuccessStep;

  useEffect(() => {
    if (isSuccessStep) return;

    const formCard = document.getElementById("main-form");
    if (!formCard) return;

    formCard.scrollIntoView({
      behavior: "smooth",
      block: currentStep === 1 ? "center" : "start",
    });
  }, [currentStep, isSuccessStep]);

  useEffect(() => {
    if (currentStep !== 1) {
      setShowStickyCta(false);
      return undefined;
    }

    const leadCta = document.getElementById("lead-form-cta");
    if (!leadCta) {
      setShowStickyCta(false);
      return undefined;
    }

    const observer = new IntersectionObserver(
      ([entry]) => {
        setShowStickyCta(!entry.isIntersecting);
      },
      { threshold: 0.15 }
    );

    observer.observe(leadCta);

    return () => {
      observer.disconnect();
    };
  }, [currentStep]);

  const scrollToForm = () => {
    const formCard = document.getElementById("main-form");
    if (!formCard) return;

    formCard.scrollIntoView({
      behavior: "smooth",
      block: "center",
    });
  };

  return (
    <>
      <Helmet>
        <title>
          {currentStep === 4
            ? "Booking Confirmed | Vet at Home — Snoutiq"
            : "Vet Near Me in Delhi NCR | Vet at Home — Snoutiq"}
        </title>
        <meta
          name="description"
          content='Searched for a vet near me in Delhi NCR? Snoutiq sends verified, experienced veterinarians to your home in Gurgaon, Delhi, Noida and Faridabad. Same-day visits. ₹999.'
        />
        <link rel="canonical" href={`https://snoutiq.com${location.pathname}`} />
      </Helmet>

      {isStandaloneStep ? (
        <div
          className={`vet-near-me-page standalone-page${
            isSuccessStep ? " success-page" : ""
          }`}
        >
          <div className="standalone-flow">
            <div
              className={`form-card standalone-form-card${
                isSuccessStep ? " success-form-card" : ""
              }`}
              id="main-form"
            >
              <StepIndicator currentStep={currentStep} />
              <Outlet />
            </div>
          </div>
        </div>
      ) : (
      <div className="vet-near-me-page">
        <div className="bridge-bar">
          Searched for <b>"vet near me"</b>? A verified vet comes to your home
          anywhere in Delhi NCR — Gurgaon · Delhi · Noida · Faridabad
        </div>

        <nav>
          <div className="logo">
            <img src={logoImage} alt="Snoutiq" className="logo-image" />
          </div>
          <button type="button" className="nav-call" onClick={scrollToForm}>
            <svg
              width="13"
              height="13"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth="2.5"
            >
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.8a16 16 0 0 0 6.29 6.29l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />
            </svg>
            Book now
          </button>
        </nav>

        <section className="hero">
          <div className="intent-note">
            <div
              style={{
                fontSize: 18,
                lineHeight: 1,
                flexShrink: 0,
                marginTop: 1,
              }}
            >
              💡
            </div>
            <p>
              <b>Looked up "vet near me"?</b> Instead of travelling to a crowded
              clinic with a stressed pet, a qualified vet comes to your home —
              anywhere in Delhi NCR.
            </p>
          </div>

          <h1 className="hl">
            The vet nearest to you
            <br />
            <em>is at your door.</em>
          </h1>
          <p className="sub">
            Verified, experienced veterinarians for home visits across Gurgaon,
            Delhi, Noida and Faridabad. Your pet stays calm. You don't leave
            home.
          </p>

          <div className="price-block">
            <div className="price-now">₹{BOOKING_PRICING.currentPrice}</div>
            <div className="price-was">₹{BOOKING_PRICING.originalPrice}</div>
            <div className="price-save">20% off</div>
          </div>
          <p className="price-note">
            Per home visit · Includes up to ₹200 of essential medicines ·
            Additional medicines only with your approval
          </p>

          <div className="form-card" id="main-form">
            <StepIndicator currentStep={currentStep} />
            <Outlet />
          </div>
        </section>

        <div className="pills">
          {TRUST_PILLS.map((pill) => (
            <div className="pill" key={pill}>
              <div className="dot" />
              {pill}
            </div>
          ))}
        </div>

        <section className="features">
          <div className="eyebrow">What you get</div>
          <h2 className="sec-h">
            A proper vet visit at your home —
            <br />
            <em>not just a quick drop-in</em>
          </h2>

          <div className="feat-grid">
            {FEATURES.map((feature) => (
              <div className="feat" key={feature.title}>
                <div className="feat-icon">{feature.icon}</div>
                <div>
                  <h4>{feature.title}</h4>
                  <p>{feature.body}</p>
                </div>
              </div>
            ))}
          </div>
        </section>

        <section className="network">
          <div className="eyebrow">Backed by real clinics</div>
          <h3>Backed by 20+ partner clinics across Delhi NCR</h3>
          <p>
            Snoutiq home-visit vets are attached to trusted neighbourhood clinics
            across Gurgaon, Delhi, Noida and Faridabad. When a case needs lab
            tests, X-rays or hospitalisation, they route you to the right
            facility — no dead ends.
          </p>
          <div className="network-badges">
            {NETWORK_BADGES.map((badge) => (
              <span className="network-pill" key={badge}>
                {badge}
              </span>
            ))}
          </div>
        </section>

        <section className="standard">
          <div className="eyebrow">Our vets</div>
          <h2 className="sec-h">
            Vets we'd trust
            <br />
            <em>in our own homes</em>
          </h2>
          <p className="standard-sub">
            Every Snoutiq vet is a real, experienced veterinarian — not a
            compounder, para-vet or support staff sent alone. We look for
            pet-loving vets who examine properly, explain clearly, and don't
            rush.
          </p>

          <div className="checks">
            {STANDARD_CHECKS.map((check) => (
              <div className="chk" key={check.title}>
                <div className="chk-mark">✓</div>
                <p>
                  <strong>{check.title}</strong>
                  {check.body}
                </p>
              </div>
            ))}
          </div>
        </section>

        <section className="vets">
          <div className="eyebrow">Featured vets</div>
          <h2 className="sec-h" style={{ marginBottom: 6 }}>
            The vets your pet will meet
          </h2>
          <p className="vets-sub">
            A few examples from our network. Profiles vary by area — the
            standards stay the same everywhere.
          </p>

          <div className="vet-scroll">
            {FEATURED_VETS.map((vet) => (
              <article className="vet-card" key={vet.name}>
                <div className="vet-photo">
                  <div className="vet-photo-initials">{vet.initials}</div>
                </div>
                <div className="vet-body">
                  <div className="vet-name">{vet.name}</div>
                  <div className="vet-cred">{vet.credentials}</div>
                  <div className="vet-meta">
                    {vet.tags.map((tag) => (
                      <span className="vet-tag" key={tag}>
                        {tag}
                      </span>
                    ))}
                  </div>
                  <p className="vet-stat">
                    <b>{vet.statLine1}</b> pets treated · <b>{vet.statLine2}</b>{" "}
                    repeat calls
                  </p>
                </div>
              </article>
            ))}
          </div>
        </section>

        <div className="adv-wrap">
          <div className="adv">
            <div className="adv-top">
              <div className="adv-av">SG</div>
              <div>
                <div className="adv-name">Dr. Shashank Goyal</div>
                <div className="adv-role">
                  Senior Veterinarian · Blue Coat Vet, Gurgaon
                </div>
              </div>
            </div>
            <div className="adv-quote">
              "Our home-visit protocol is built to match good clinic standards
              — from how history is taken to how the visit is documented. Pet
              parents get clarity, not confusion."
            </div>
          </div>
        </div>

        <section className="value">
          <div className="eyebrow">Why it matters</div>
          <h2 className="sec-h" style={{ marginBottom: 8 }}>
            Not all home vet visits
            <br />
            are the <em>same</em>
          </h2>
          <p className="value-note">
            When you search "vet near me" and book a home visit, you can't always
            tell who's actually showing up. Here's what Snoutiq does differently.
          </p>

          <div style={{ overflowX: "auto" }}>
            <table className="value-table">
              <thead>
                <tr>
                  <th>What you should ask</th>
                  <th style={{ background: "var(--teal)", color: "#fff" }}>
                    Snoutiq home visit
                  </th>
                  <th>Typical unverified visit</th>
                </tr>
              </thead>
              <tbody>
                {VALUE_ROWS.map((row) => (
                  <tr key={row.label}>
                    <td className="value-label">{row.label}</td>
                    <td className="value-good">{row.good}</td>
                    <td className="value-bad">{row.bad}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>

        <section className="how">
          <div className="eyebrow">Simple process</div>
          <h2 className="sec-h" style={{ marginBottom: 6 }}>
            From your query to vet at door —
            <br />
            <em>within 60 minutes</em>
          </h2>
          <p
            style={{
              fontSize: 14,
              color: "var(--ink2)",
              marginBottom: 20,
              lineHeight: 1.6,
            }}
          >
            You won't be left waiting and wondering. A dedicated Pet Parent
            Assistant manages your booking end to end and keeps you informed at
            every step.
          </p>
          <div className="steps">
            {HOW_IT_WORKS_STEPS.map((step, index) => (
              <div className="step" key={step.title}>
                <div className="step-n">{index + 1}</div>
                <div>
                  <h4>{step.title}</h4>
                  <p>{step.body}</p>
                </div>
              </div>
            ))}
          </div>
        </section>

        <section className="areas">
          <div className="eyebrow">Coverage</div>
          <h2 className="sec-h" style={{ marginBottom: 4, fontSize: 26 }}>
            Vets near you across Delhi NCR
          </h2>
          <p style={{ fontSize: 14, color: "var(--ink2)", marginBottom: 0 }}>
            Tap your area to check availability
          </p>
          <div className="area-chips">
            {COVERAGE_AREAS.map((area) => (
              <button
                key={area}
                type="button"
                className={`achip ${selectedArea === area ? "on" : ""}`}
                onClick={() => setSelectedArea(area)}
              >
                {area}
              </button>
            ))}
          </div>
        </section>

        <section className="reviews">
          <div className="eyebrow">Pet parents in Delhi NCR</div>
          <h2 className="sec-h" style={{ marginBottom: 20 }}>
            What they said after the visit
          </h2>

          {REVIEWS.map((review) => (
            <div className="rev" key={review.name}>
              <div className="stars">★★★★★</div>
              <div className="rev-q">{review.quote}</div>
              <div className="rev-body">{review.body}</div>
              <div className="rev-meta">
                <span className="rev-name">{review.name}</span>
                <span className="pet-tag">{review.petTag}</span>
              </div>
            </div>
          ))}
        </section>

        <section className="faq">
          <div className="eyebrow">Questions</div>
          <h2 className="sec-h" style={{ marginBottom: 20 }}>
            FAQ
          </h2>

          {FAQ_ITEMS.map((item, index) => {
            const isOpen = openFaqIndex === index;

            return (
              <div className="faq-item" key={item.q}>
                <button
                  type="button"
                  className={`faq-q ${isOpen ? "open" : ""}`}
                  onClick={() => setOpenFaqIndex(isOpen ? null : index)}
                >
                  {item.q}
                </button>
                <div className={`faq-a ${isOpen ? "open" : ""}`}>{item.a}</div>
              </div>
            );
          })}
        </section>

        <footer>
          <img src={logoImage} alt="Snoutiq" className="f-logo-image" />
          <p>
            Vet near you, at home — across Delhi NCR. A ThinkTail Global Pvt.
            Ltd. product.
          </p>
        </footer>

        <div className={`sticky${showStickyCta ? " visible" : ""}`}>
          <div className="sticky-left">
            <p>Vet near you · Home visit · Delhi NCR</p>
            <small>₹999 · 100% refund if vet not confirmed</small>
          </div>
          <button type="button" className="sticky-btn" onClick={scrollToForm}>
            Book now
          </button>
        </div>
      </div>
      )}
    </>
  );
}

export default function VetNearMeBookingLayout() {
  return (
    <VetNearMeBookingProvider>
      <VetNearMeBookingPage />
    </VetNearMeBookingProvider>
  );
}
