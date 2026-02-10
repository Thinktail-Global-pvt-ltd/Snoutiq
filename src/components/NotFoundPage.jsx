

import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import img from '../assets/images/404_not.avif';

const NotFoundPage = () => {
  const dogContainerRef = useRef(null);
  const [isMounted, setIsMounted] = useState(false);

  useEffect(() => {
    setIsMounted(true);

    // Keyboard shortcuts
    const handleKeyDown = (e) => {
      if (e.key === 'Escape') {
        window.location.href = '/';
      }
    };

    // Parallax effect
    const handleMouseMove = (e) => {
      if (dogContainerRef.current && window.innerWidth > 968) {
        const x = (e.clientX / window.innerWidth - 0.5) * 15;
        const y = (e.clientY / window.innerHeight - 0.5) * 15;
        dogContainerRef.current.style.transform = `translate(${x}px, ${y}px)`;
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mousemove', handleMouseMove);

    return () => {
      document.removeEventListener('keydown', handleKeyDown);
      document.removeEventListener('mousemove', handleMouseMove);
    };
  }, []);

  useEffect(() => {
    const previousTitle = document.title;
    document.title = 'Page Not Found | SnoutIQ';

    let robotsMeta = document.querySelector('meta[name="robots"]');
    let createdRobots = false;
    const previousRobots = robotsMeta?.getAttribute('content') || null;
    let googlebotMeta = document.querySelector('meta[name="googlebot"]');
    let createdGooglebot = false;
    const previousGooglebot = googlebotMeta?.getAttribute('content') || null;
    let canonicalLink = document.querySelector('link[rel="canonical"]');
    let createdCanonical = false;
    const previousCanonical = canonicalLink?.getAttribute('href') || null;
    const canonicalUrl = `${window.location.origin}/404`;

    if (!robotsMeta) {
      robotsMeta = document.createElement('meta');
      robotsMeta.setAttribute('name', 'robots');
      document.head.appendChild(robotsMeta);
      createdRobots = true;
    }
    robotsMeta.setAttribute('content', 'noindex, nofollow');

    if (!googlebotMeta) {
      googlebotMeta = document.createElement('meta');
      googlebotMeta.setAttribute('name', 'googlebot');
      document.head.appendChild(googlebotMeta);
      createdGooglebot = true;
    }
    googlebotMeta.setAttribute('content', 'noindex, nofollow');

    if (!canonicalLink) {
      canonicalLink = document.createElement('link');
      canonicalLink.setAttribute('rel', 'canonical');
      document.head.appendChild(canonicalLink);
      createdCanonical = true;
    }
    canonicalLink.setAttribute('href', canonicalUrl);

    if (window.location.pathname !== '/404') {
      window.history.replaceState(null, '', '/404');
    }

    return () => {
      document.title = previousTitle;
      if (robotsMeta) {
        if (createdRobots && robotsMeta.parentNode) {
          robotsMeta.parentNode.removeChild(robotsMeta);
        } else if (previousRobots !== null) {
          robotsMeta.setAttribute('content', previousRobots);
        } else {
          robotsMeta.removeAttribute('content');
        }
      }
      if (googlebotMeta) {
        if (createdGooglebot && googlebotMeta.parentNode) {
          googlebotMeta.parentNode.removeChild(googlebotMeta);
        } else if (previousGooglebot !== null) {
          googlebotMeta.setAttribute('content', previousGooglebot);
        } else {
          googlebotMeta.removeAttribute('content');
        }
      }
      if (canonicalLink) {
        if (createdCanonical && canonicalLink.parentNode) {
          canonicalLink.parentNode.removeChild(canonicalLink);
        } else if (previousCanonical !== null) {
          canonicalLink.setAttribute('href', previousCanonical);
        } else {
          canonicalLink.removeAttribute('href');
        }
      }
    };
  }, []);

  // Fallback image handler
  const handleImageError = (e) => {
    e.target.src = 'https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?w=800&auto=format&fit=crop&q=80';
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 via-blue-50 to-sky-100 flex items-center justify-center p-4 sm:p-6 relative overflow-hidden">
      {/* Floating paw prints background */}
      <div className="absolute inset-0 pointer-events-none opacity-5">
        {[...Array(5)].map((_, i) => (
          <div
            key={i}
            className="absolute text-4xl animate-float"
            style={{
              left: `${[10, 80, 50, 70, 30][i]}%`,
              top: `${[20, 60, 80, 15, 70][i]}%`,
              animationDelay: `${[0, 5, 10, 7, 12][i]}s`,
              animationDuration: `${[25, 30, 22, 28, 24][i]}s`,
            }}
          >
            🐾
          </div>
        ))}
      </div>

      {/* Main card */}
      <div className="w-full max-w-6xl bg-gradient-to-br from-white/95 to-white/90 backdrop-blur-xl rounded-3xl shadow-2xl shadow-blue-200/50 border border-white/50 grid grid-cols-1 lg:grid-cols-5 gap-8 lg:gap-12 p-6 sm:p-8 lg:p-12 relative overflow-hidden">
        
        {/* Gradient orbs */}
        <div className="absolute -top-48 -right-24 w-96 h-96 bg-blue-200/20 rounded-full animate-pulse-slow pointer-events-none" />
        <div className="absolute -bottom-36 -left-20 w-72 h-72 bg-sky-200/15 rounded-full animate-pulse-slow-reverse pointer-events-none" />

        {/* Visual Section */}
        <div className="lg:col-span-3 flex items-center justify-center p-4 relative order-2 lg:order-1">
          <div className="w-full max-w-lg relative">
            {/* Clouds */}
            <div className="absolute top-4 -left-24 w-20 h-6 bg-white/60 rounded-full animate-cloud-float">
              <div className="absolute w-10 h-7 bg-white/60 rounded-full -top-3.5 left-3" />
              <div className="absolute w-12 h-5 bg-white/60 rounded-full -top-2 right-2" />
            </div>
            <div className="absolute top-16 -left-36 w-24 h-7 bg-white/60 rounded-full animate-cloud-float-slow">
              <div className="absolute w-12 h-8 bg-white/60 rounded-full -top-4 left-4" />
              <div className="absolute w-14 h-6 bg-white/60 rounded-full -top-2.5 right-2.5" />
            </div>

            {/* Sparkles */}
            {[...Array(4)].map((_, i) => (
              <div
                key={i}
                className="absolute w-2 h-2 bg-gradient-to-br from-blue-500 to-sky-400 rounded-full animate-sparkle shadow-lg shadow-blue-400/60"
                style={{
                  top: `${[20, 60, 30, 70][i]}%`,
                  left: `${[15, 10, 80, 85][i]}%`,
                  animationDelay: `${[0, 0.6, 1.2, 1.8][i]}s`,
                }}
              />
            ))}

            {/* Ground with grass */}
            <div className="absolute bottom-10 left-0 right-0 h-16 overflow-hidden">
              <div className="absolute bottom-0 left-0 right-0 h-3 bg-gradient-to-r from-emerald-500/10 via-emerald-400/5 to-emerald-500/10 animate-ground-move-slow" />
              <div className="absolute bottom-3 left-0 right-0 h-6 bg-gradient-to-r from-emerald-600/8 via-emerald-500/4 to-emerald-600/8 animate-ground-move" />
            </div>

            {/* Dog Image */}
            <div 
              ref={dogContainerRef}
              className="relative w-64 h-64 sm:w-80 sm:h-80 lg:w-96 lg:h-96 mx-auto animate-float-dog"
            >
              <img
                src={img}
                alt="Happy golden retriever dog"
                className="w-full h-full object-cover rounded-full shadow-2xl shadow-blue-500/30 border-8 border-white/80 animate-pulse-border"
                onError={handleImageError}
                loading="eager"
              />
            </div>
          </div>
        </div>

        {/* Content Section */}
        <div className="lg:col-span-2 flex flex-col justify-center p-4 relative order-1 lg:order-2">
          <div className="text-center lg:text-left">
            {/* Kicker */}
            <div className="inline-block bg-gradient-to-r from-blue-600 to-sky-500 text-white px-4 py-2 rounded-full font-bold text-sm shadow-lg shadow-blue-500/25 mb-5 animate-fade-in-down">
              SnoutIQ
            </div>

            {/* Title */}
            <h1 className="text-4xl sm:text-5xl lg:text-6xl font-black text-gray-900 mb-4 leading-tight animate-fade-in-up">
              <span className="bg-gradient-to-r from-blue-600 to-sky-500 bg-clip-text text-transparent">
                404
              </span>
              <br />
              Page not found
            </h1>

            {/* Subtitle */}
            <p className="text-lg sm:text-xl text-gray-600 mb-8 leading-relaxed animate-fade-in-up animation-delay-200">
              Oops! This page has run off to chase some squirrels. Don't worry though — you're still on SnoutIQ. Let's get you back on track!
            </p>

            {/* Actions */}
            <div className="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start animate-fade-in-up animation-delay-400">
              <Link
                to="/"
                className="group inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-blue-600 to-sky-500 text-white font-bold rounded-2xl shadow-xl shadow-blue-500/25 hover:shadow-2xl hover:shadow-blue-500/35 hover:scale-105 active:scale-95 transition-all duration-300 relative overflow-hidden"
              >
                <div className="absolute inset-0 bg-gradient-to-r from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
                <svg
                  className="w-5 h-5 mr-3 transition-transform group-hover:-translate-x-1"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                  <polyline points="9 22 9 12 15 12 15 22" />
                </svg>
                Back to Home
              </Link>
            </div>

            {/* Meta info */}
            <div className="mt-6 text-sm text-gray-500 animate-fade-in-up animation-delay-600">
              💡 Tip: Press <kbd className="px-2 py-1 bg-gray-100 border border-gray-200 rounded-md text-xs font-mono shadow-sm">Esc</kbd> to return home anytime
            </div>
          </div>
        </div>
      </div>

      {/* Custom animations */}
      <style jsx global>{`
        @keyframes float {
          0%, 100% { 
            transform: translate(0, 0) rotate(0deg); 
            opacity: 0.6; 
          }
          25% { 
            transform: translate(30px, -40px) rotate(10deg); 
            opacity: 0.3; 
          }
          50% { 
            transform: translate(-20px, 20px) rotate(-5deg); 
            opacity: 0.8; 
          }
          75% { 
            transform: translate(40px, 30px) rotate(15deg); 
            opacity: 0.4; 
          }
        }

        @keyframes float-dog {
          0%, 100% { 
            transform: translateY(0px) rotate(0deg); 
          }
          50% { 
            transform: translateY(-20px) rotate(2deg); 
          }
        }

        @keyframes pulse-border {
          0%, 100% { 
            box-shadow: 
              0 20px 60px rgba(59, 130, 246, 0.3),
              0 0 0 12px rgba(255, 255, 255, 0.8),
              0 0 0 16px rgba(59, 130, 246, 0.15);
          }
          50% { 
            box-shadow: 
              0 25px 70px rgba(59, 130, 246, 0.4),
              0 0 0 12px rgba(255, 255, 255, 0.9),
              0 0 0 20px rgba(14, 165, 233, 0.25);
          }
        }

        @keyframes sparkle {
          0%, 100% { 
            transform: scale(0) rotate(0deg); 
            opacity: 0; 
          }
          50% { 
            transform: scale(1) rotate(180deg); 
            opacity: 1; 
          }
        }

        @keyframes cloud-float {
          from { transform: translateX(0); }
          to { transform: translateX(calc(100vw + 200px)); }
        }

        @keyframes cloud-float-slow {
          from { transform: translateX(0); }
          to { transform: translateX(calc(100vw + 300px)); }
        }

        @keyframes ground-move {
          from { transform: translateX(0); }
          to { transform: translateX(-128px); }
        }

        @keyframes ground-move-slow {
          from { transform: translateX(0); }
          to { transform: translateX(-64px); }
        }

        @keyframes fade-in-down {
          from { 
            opacity: 0; 
            transform: translateY(-20px); 
          }
          to { 
            opacity: 1; 
            transform: translateY(0); 
          }
        }

        @keyframes fade-in-up {
          from { 
            opacity: 0; 
            transform: translateY(20px); 
          }
          to { 
            opacity: 1; 
            transform: translateY(0); 
          }
        }

        @keyframes pulse-slow {
          0%, 100% { 
            transform: scale(1); 
            opacity: 0.5; 
          }
          50% { 
            transform: scale(1.2); 
            opacity: 0.8; 
          }
        }

        .animate-float {
          animation: float 20s ease-in-out infinite;
        }

        .animate-float-dog {
          animation: float-dog 3s ease-in-out infinite;
        }

        .animate-pulse-border {
          animation: pulse-border 2s ease-in-out infinite;
        }

        .animate-sparkle {
          animation: sparkle 2s ease-in-out infinite;
        }

        .animate-cloud-float {
          animation: cloud-float 30s linear infinite;
        }

        .animate-cloud-float-slow {
          animation: cloud-float-slow 40s linear infinite;
          animation-delay: 8s;
        }

        .animate-ground-move {
          animation: ground-move 1.2s linear infinite;
        }

        .animate-ground-move-slow {
          animation: ground-move-slow 1.8s linear infinite;
        }

        .animate-fade-in-down {
          animation: fade-in-down 0.6s ease-out both;
        }

        .animate-fade-in-up {
          animation: fade-in-up 0.6s ease-out both;
        }

        .animate-pulse-slow {
          animation: pulse-slow 8s ease-in-out infinite;
        }

        .animate-pulse-slow-reverse {
          animation: pulse-slow 6s ease-in-out infinite reverse;
        }

        .animation-delay-200 {
          animation-delay: 0.2s;
        }

        .animation-delay-400 {
          animation-delay: 0.4s;
        }

        .animation-delay-600 {
          animation-delay: 0.6s;
        }
      `}</style>
    </div>
  );
};

export default NotFoundPage;
