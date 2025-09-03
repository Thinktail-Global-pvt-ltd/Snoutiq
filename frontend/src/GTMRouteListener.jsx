// GTMRouteListener.jsx
import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import TagManager from 'react-gtm-module';

export default function GTMRouteListener() {
  const location = useLocation(); // ✅ runs only when inside Router
useEffect(() => {
  TagManager.initialize({ gtmId: 'G-TZD1L7YBDK' });
}, []);
  useEffect(() => {
    // Ensure dataLayer exists
    window.dataLayer = window.dataLayer || [];

    window.dataLayer.push({
      event: 'pageview',
      page: location.pathname + location.search,
    });

    console.log('GTM pageview event:', location.pathname + location.search);
  }, [location]);

  return null; // nothing visible
}
