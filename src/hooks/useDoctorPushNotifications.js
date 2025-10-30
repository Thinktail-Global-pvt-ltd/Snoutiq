import { useEffect, useRef } from 'react';
import axiosClient from '../axios';

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');

  const rawData = atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }

  return outputArray;
}

async function fetchPublicKey() {
  const response = await axiosClient.get('/config/webpush');
  return response.data?.publicKey;
}

export default function useDoctorPushNotifications(doctorId) {
  const hasRegisteredRef = useRef(false);

  useEffect(() => {
    if (!doctorId || hasRegisteredRef.current) {
      return;
    }

    if (
      !('serviceWorker' in navigator) ||
      !('PushManager' in window) ||
      typeof Notification === 'undefined'
    ) {
      console.warn('Push notifications not supported in this browser.');
      return;
    }

    hasRegisteredRef.current = true;

    let isActive = true;

    const register = async () => {
      try {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
          console.warn('Notification permission denied.');
          return;
        }

        const registration = await navigator.serviceWorker.register('/service-worker.js');
        let subscription = await registration.pushManager.getSubscription();

        const publicKey = import.meta.env.VITE_WEB_PUSH_PUBLIC_KEY || (await fetchPublicKey());
        if (!publicKey) {
          console.warn('Missing VAPID public key.');
          return;
        }

        if (!subscription) {
          subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(publicKey),
          });
        }

        if (!isActive || !subscription) return;

        await axiosClient.post('/doctor/push-subscriptions', {
          doctor_id: doctorId,
          subscription: subscription.toJSON(),
          user_agent: navigator.userAgent,
          platform: navigator.platform,
        });
      } catch (error) {
        console.error('Failed to register push notifications', error);
      }
    };

    register();

    const messageHandler = (event) => {
      if (event.data?.type === 'pushsubscriptionchange' && event.data.subscription) {
        axiosClient.post('/doctor/push-subscriptions', {
          doctor_id: doctorId,
          subscription: event.data.subscription,
          user_agent: navigator.userAgent,
          platform: navigator.platform,
        }).catch((error) => {
          console.error('Failed to update push subscription after change', error);
        });
      }
    };

    navigator.serviceWorker.addEventListener('message', messageHandler);

    return () => {
      isActive = false;
      navigator.serviceWorker.removeEventListener('message', messageHandler);
    };
  }, [doctorId]);
}
