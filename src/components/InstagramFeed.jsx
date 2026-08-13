import React, { useState, useEffect, useRef } from 'react';
import { ChevronLeft, ChevronRight, Layers, Play, Instagram, AlertCircle } from 'lucide-react';

/**
 * ============================================================================
 * INSTAGRAM FEED COMPONENT (Instagram Graph API)
 * ============================================================================
 * 
 * SETUP INSTRUCTIONS:
 * 1. Add your Instagram Access Token to your .env file:
 *    VITE_INSTAGRAM_ACCESS_TOKEN=yaha_apna_token_daalein
 * 
 * 2. SECURITY REMINDER:
 *    Ensure '.env' is added to your '.gitignore' file so your token is 
 *    never accidentally pushed to public GitHub repositories!
 * ============================================================================
 */

export default function InstagramFeed({ title = 'Follow Us on Instagram' }) {
  const [posts, setPosts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const scrollContainerRef = useRef(null);

  useEffect(() => {
    const fetchInstagramPosts = async () => {
      const token = import.meta.env.VITE_INSTAGRAM_ACCESS_TOKEN;

      if (!token) {
        setLoading(false);
        setError('Posts load nahi ho paye, thodi der baad try karein');
        return;
      }

      try {
        setLoading(true);
        setError(null);

        const fields = 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp';
        const url = `https://graph.instagram.com/me/media?fields=${fields}&access_token=${encodeURIComponent(token)}`;

        const response = await fetch(url);

        if (!response.ok) {
          throw new Error(`Instagram API Response Status: ${response.status}`);
        }

        const data = await response.json();

        if (data && Array.isArray(data.data)) {
          setPosts(data.data);
        } else {
          setPosts([]);
        }
      } catch (err) {
        // Safe error log without breaking UI
        console.warn('Instagram Feed API Fetch Error:', err.message || err);
        setError('Posts load nahi ho paye, thodi der baad try karein');
      } finally {
        setLoading(false);
      }
    };

    fetchInstagramPosts();
  }, []);

  // Programmatic scroll handlers for desktop navigation arrows
  const scrollLeft = () => {
    if (scrollContainerRef.current) {
      scrollContainerRef.current.scrollBy({ left: -300, behavior: 'smooth' });
    }
  };

  const scrollRight = () => {
    if (scrollContainerRef.current) {
      scrollContainerRef.current.scrollBy({ left: 300, behavior: 'smooth' });
    }
  };

  // Helper to format timestamps to readable dates
  const formatDate = (isoString) => {
    if (!isoString) return '';
    try {
      const date = new Date(isoString);
      return date.toLocaleDateString('en-IN', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
      });
    } catch {
      return '';
    }
  };

  // Helper to truncate text to max characters
  const truncateText = (text, maxLength = 60) => {
    if (!text) return 'Instagram Post';
    return text.length > maxLength ? `${text.slice(0, maxLength)}...` : text;
  };

  return (
    <div className="w-full max-w-7xl mx-auto py-6 px-4 font-sans">
      {/* Header section with Title and Desktop Navigation Controls */}
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center gap-2">
          <Instagram className="w-6 h-6 text-pink-600" />
          <h2 className="text-xl md:text-2xl font-bold text-gray-900 tracking-tight">
            {title}
          </h2>
        </div>

        {/* Scroll Buttons (Desktop view) */}
        {!loading && !error && posts.length > 0 && (
          <div className="hidden md:flex items-center gap-2">
            <button
              onClick={scrollLeft}
              aria-label="Scroll Left"
              className="p-2 rounded-full border border-gray-200 bg-white hover:bg-gray-100 text-gray-700 shadow-sm transition-all active:scale-95"
            >
              <ChevronLeft className="w-5 h-5" />
            </button>
            <button
              onClick={scrollRight}
              aria-label="Scroll Right"
              className="p-2 rounded-full border border-gray-200 bg-white hover:bg-gray-100 text-gray-700 shadow-sm transition-all active:scale-95"
            >
              <ChevronRight className="w-5 h-5" />
            </button>
          </div>
        )}
      </div>

      {/* State 1: Loading Skeleton */}
      {loading && (
        <div className="flex overflow-x-auto gap-4 py-2 scrollbar-none snap-x snap-mandatory">
          {[1, 2, 3, 4].map((n) => (
            <div
              key={n}
              className="w-[280px] flex-shrink-0 bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden animate-pulse flex flex-col"
            >
              <div className="w-full aspect-square bg-gray-200" />
              <div className="p-4 space-y-2 flex-1 flex flex-col justify-between">
                <div className="space-y-1">
                  <div className="h-4 bg-gray-200 rounded w-full" />
                  <div className="h-4 bg-gray-200 rounded w-3/4" />
                </div>
                <div className="h-3 bg-gray-200 rounded w-1/3 mt-3" />
              </div>
            </div>
          ))}
        </div>
      )}

      {/* State 2: Friendly Error Fallback Message */}
      {!loading && error && (
        <div className="bg-red-50 border border-red-200 rounded-xl p-6 text-center max-w-md mx-auto my-4 shadow-sm">
          <AlertCircle className="w-8 h-8 text-red-500 mx-auto mb-2" />
          <p className="text-red-800 font-medium text-sm md:text-base">
            {error}
          </p>
        </div>
      )}

      {/* State 3: Empty Posts State */}
      {!loading && !error && posts.length === 0 && (
        <div className="bg-gray-50 border border-gray-200 rounded-xl p-6 text-center max-w-md mx-auto my-4 text-gray-500 text-sm">
          Koi posts available nahi hain.
        </div>
      )}

      {/* State 4: Horizontal Scroll Row for Posts */}
      {!loading && !error && posts.length > 0 && (
        <div
          ref={scrollContainerRef}
          className="flex overflow-x-auto gap-4 py-2 scroll-smooth snap-x snap-mandatory focus:outline-none"
          style={{
            WebkitOverflowScrolling: 'touch',
            scrollSnapType: 'x mandatory'
          }}
        >
          {posts.map((post) => {
            const isVideo = post.media_type === 'VIDEO';
            const isCarousel = post.media_type === 'CAROUSEL_ALBUM';
            const displayImage = isVideo ? (post.thumbnail_url || post.media_url) : post.media_url;

            return (
              <a
                key={post.id}
                href={post.permalink}
                target="_blank"
                rel="noopener noreferrer"
                className="w-[280px] flex-shrink-0 snap-start bg-white rounded-xl shadow-md hover:shadow-lg border border-gray-100 overflow-hidden group transition-all duration-300 flex flex-col cursor-pointer"
              >
                {/* Media Image Container with Badges */}
                <div className="relative w-full aspect-square overflow-hidden bg-gray-100">
                  <img
                    src={displayImage}
                    alt={post.caption || 'Instagram Post'}
                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    loading="lazy"
                    referrerPolicy="no-referrer"
                    onError={(e) => {
                      // Fallback image handling
                      e.target.src = 'https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?q=80&w=400&auto=format&fit=crop';
                    }}
                  />

                  {/* Top-Right Badge for Media Type */}
                  {(isCarousel || isVideo) && (
                    <div className="absolute top-2.5 right-2.5 bg-black/60 backdrop-blur-md text-white p-1.5 rounded-full shadow-md">
                      {isCarousel && <Layers className="w-4 h-4" />}
                      {isVideo && <Play className="w-4 h-4 fill-white" />}
                    </div>
                  )}
                </div>

                {/* Content Details: Truncated Caption & Readable Date */}
                <div className="p-4 flex-1 flex flex-col justify-between bg-white">
                  <p className="text-xs md:text-sm text-gray-700 font-normal leading-snug line-clamp-2">
                    {truncateText(post.caption, 60)}
                  </p>
                  <span className="text-[11px] text-gray-400 font-medium mt-3 block">
                    {formatDate(post.timestamp)}
                  </span>
                </div>
              </a>
            );
          })}
        </div>
      )}
    </div>
  );
}
