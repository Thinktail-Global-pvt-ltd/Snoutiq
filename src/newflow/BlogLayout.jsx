import React from "react";
import { Link } from "react-router-dom";
import { Button } from "./Button";

export function BlogLayout({ post }) {
  if (!post) return null;

  const HeroImage = ({ src, alt, className = "" }) => (
    <div className={`relative w-full ${className}`}>
      <img
        src={src}
        alt={alt}
        className="absolute inset-0 w-full h-full object-cover"
        loading="lazy"
      />
    </div>
  );

  const ServiceCTAButton = ({ to, children, buttonProps = {} }) => {
    // Avoid <Link><button/></Link> nesting.
    // Render Link styled like a button, or you can make Button support asChild later.
    return (
      <Link to={to} className="inline-flex">
        <Button {...buttonProps}>{children}</Button>
      </Link>
    );
  };

  if (post.type === "short") {
    return (
      <article className="max-w-[680px] mx-auto px-4 py-12">
        <div className="relative w-full aspect-video mb-8 rounded-2xl overflow-hidden">
          <img
            src={post.heroImage}
            alt={post.title}
            className="absolute inset-0 w-full h-full object-cover"
            loading="lazy"
          />
        </div>

        <h1 className="text-3xl md:text-4xl font-bold text-slate-900 mb-4">
          {post.title}
        </h1>

        <div className="flex items-center gap-4 text-sm text-slate-500 mb-8">
          <span>By {post.author}</span>
          <span>•</span>
          <span>{post.date}</span>
          <span>•</span>
          <span className="bg-brand-light text-brand px-2 py-1 rounded-full font-medium">
            {post.readTime}
          </span>
        </div>

        {post.summary && (
          <div className="bg-brand-light border-l-4 border-brand p-6 rounded-r-xl mb-8">
            <p className="text-brand-dark font-medium">{post.summary}</p>
          </div>
        )}

        <div className="prose prose-lg prose-slate max-w-none mb-12">
          {post.content}
        </div>

        {post.serviceLink && (
          <div className="bg-slate-50 border border-slate-200 p-8 rounded-2xl text-center">
            <h3 className="text-xl font-bold text-slate-900 mb-4">
              Need professional help?
            </h3>

            <ServiceCTAButton to={post.serviceLink} buttonProps={{ variant: "primary" }}>
              Book a Consultation Now
            </ServiceCTAButton>
          </div>
        )}
      </article>
    );
  }

  if (post.type === "medium") {
    return (
      <article className="max-w-[760px] mx-auto px-4 py-12">
        <div className="relative w-full aspect-video mb-8 rounded-2xl overflow-hidden">
          <img
            src={post.heroImage}
            alt={post.title}
            className="absolute inset-0 w-full h-full object-cover"
            loading="lazy"
          />
        </div>

        <h1 className="text-4xl md:text-5xl font-bold text-slate-900 mb-6">
          {post.title}
        </h1>

        <div className="flex items-center gap-4 text-sm text-slate-500 mb-10 pb-6 border-b border-slate-200">
          <div className="flex items-center gap-2">
            <div className="w-10 h-10 rounded-full bg-slate-200" />
            <span className="font-medium text-slate-900">{post.author}</span>
          </div>
          <span>•</span>
          <span>{post.date}</span>
          <span>•</span>
          <span className="bg-brand-light text-brand px-2 py-1 rounded-full font-medium">
            {post.readTime}
          </span>
        </div>

        <div className="prose prose-lg prose-slate max-w-none mb-12">
          {post.content}
        </div>

        {post.serviceLink && (
          <div className="bg-brand text-slate-900 p-10 rounded-3xl text-center mt-16">
            <h3 className="text-2xl font-bold mb-6">Ready to get started?</h3>

            <ServiceCTAButton
              to={post.serviceLink}
              buttonProps={{
                variant: "primary",
                size: "lg",
                className:
                  "bg-accent hover:bg-accent-hover text-slate-900 border-none",
              }}
            >
              Book Appointment Now
            </ServiceCTAButton>
          </div>
        )}
      </article>
    );
  }

  // Large layout
  return (
    <article className="max-w-[860px] mx-auto px-4 py-12">
      <div className="relative w-full aspect-[21/9] mb-12 rounded-3xl overflow-hidden">
        <img
          src={post.heroImage}
          alt={post.title}
          className="absolute inset-0 w-full h-full object-cover"
          loading="lazy"
        />

        <div className="absolute inset-0 bg-gradient-to-t from-slate-900/80 to-transparent flex flex-col justify-end p-8 md:p-12">
          <h1 className="text-4xl md:text-6xl font-bold text-slate-900 mb-6 leading-tight">
            {post.title}
          </h1>

          <div className="flex items-center gap-4 text-sm text-slate-200">
            <span className="font-medium text-slate-900">{post.author}</span>
            <span>•</span>
            <span>{post.date}</span>
            <span>•</span>
            <span className="bg-brand text-slate-900 px-3 py-1 rounded-full font-medium">
              {post.readTime}
            </span>
          </div>
        </div>
      </div>

      <div className="flex flex-col md:flex-row gap-12">
        <div className="md:w-1/4 hidden md:block">
          <div className="sticky top-24">
            <h4 className="font-bold text-slate-900 mb-4 uppercase tracking-wider text-sm">
              Table of Contents
            </h4>
            <ul className="space-y-3 text-sm text-slate-600">
              <li className="hover:text-brand cursor-pointer">Introduction</li>
              <li className="hover:text-brand cursor-pointer">Key Symptoms</li>
              <li className="hover:text-brand cursor-pointer">
                When to see a vet
              </li>
              <li className="hover:text-brand cursor-pointer">
                Treatment Options
              </li>
              <li className="hover:text-brand cursor-pointer">FAQ</li>
            </ul>
          </div>
        </div>

        <div className="md:w-3/4">
          <div className="prose prose-lg prose-slate max-w-none mb-16">
            {post.content}
          </div>

          {post.serviceLink && (
            <div className="bg-slate-50 border border-slate-200 p-10 rounded-3xl text-center mt-12">
              <h3 className="text-2xl font-bold text-slate-900 mb-6">
                Need expert veterinary care?
              </h3>

              <div className="flex flex-col sm:flex-row justify-center gap-4">
                <ServiceCTAButton
                  to={post.serviceLink}
                  buttonProps={{
                    variant: "primary",
                    size: "lg",
                    className: "w-full sm:w-auto",
                  }}
                >
                  Book Consultation
                </ServiceCTAButton>

                <a
                  href="https://wa.me/919999999999"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="inline-flex"
                >
                  <Button
                    variant="outline"
                    size="lg"
                    className="w-full sm:w-auto"
                  >
                    Chat on WhatsApp
                  </Button>
                </a>
              </div>
            </div>
          )}
        </div>
      </div>
    </article>
  );
}