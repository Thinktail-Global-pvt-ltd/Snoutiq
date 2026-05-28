import React from "react";
import androidLogo from "../assets/android.PNG";
import iosLogo from "../assets/ios.PNG";
import logo from '../assets/images/logo.png'

const ANDROID_URL =
  "https://play.google.com/store/apps/details?id=com.petai.snoutiq";
const IOS_URL = "https://apps.apple.com/us/app/id6761260254";
const IOS_DEEPLINK = "itms-apps://apps.apple.com/us/app/id6761260254";

const PawLogo = () => (
  <div className="relative flex h-16 w-16 items-center justify-center rounded-3xl bg-gradient-to-br from-blue-500 to-cyan-400 shadow-lg shadow-blue-500/25 sm:h-20 sm:w-20">
    <div className="absolute -right-1 -top-1 h-5 w-5 rounded-full bg-white/25 sm:h-6 sm:w-6" />
    <svg width="42" height="42" viewBox="0 0 64 64" fill="none" aria-hidden="true">
      <path
        d="M23.4 28.2c3.1 0 5.6-3.4 5.6-7.6S26.5 13 23.4 13s-5.6 3.4-5.6 7.6 2.5 7.6 5.6 7.6ZM40.6 28.2c3.1 0 5.6-3.4 5.6-7.6S43.7 13 40.6 13 35 16.4 35 20.6s2.5 7.6 5.6 7.6ZM14.9 37.2c3 0 5.4-2.9 5.4-6.4s-2.4-6.4-5.4-6.4-5.4 2.9-5.4 6.4 2.4 6.4 5.4 6.4ZM49.1 37.2c3 0 5.4-2.9 5.4-6.4s-2.4-6.4-5.4-6.4-5.4 2.9-5.4 6.4 2.4 6.4 5.4 6.4ZM32 33.2c-7.7 0-15.4 8.5-15.4 14.2 0 4.4 3.9 6.6 8.6 5.2 2.3-.7 4.5-1.5 6.8-1.5s4.5.8 6.8 1.5c4.7 1.4 8.6-.8 8.6-5.2 0-5.7-7.7-14.2-15.4-14.2Z"
        fill="white"
      />
    </svg>
  </div>
);

const StoreButton = ({ href, variant, iconSrc, iconAlt }) => {
  const isDark = variant === "dark";

  return (
    <a
      href={href}
      target="_blank"
      rel="noreferrer"
      aria-label={iconAlt}
      className={`group block w-full max-w-[340px] overflow-hidden rounded-2xl transition duration-200 active:scale-[0.99] sm:max-w-[360px] ${
        isDark
          ? "bg-slate-950 shadow-xl shadow-slate-950/20 hover:-translate-y-0.5 hover:bg-slate-900"
          : "border border-slate-200 bg-white shadow-lg shadow-slate-200/70 hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50/60"
      }`}
    >
      <img
        src={iconSrc}
        alt={iconAlt}
        className={`block h-auto w-full object-contain ${
          isDark ? "bg-slate-950" : "bg-white"
        }`}
      />
    </a>
  );
};

export default function DownloadSnoutIQApp() {
  const iosStoreUrl =
    typeof navigator !== "undefined" &&
    /iPad|iPhone|iPod/.test(navigator.userAgent)
      ? IOS_DEEPLINK
      : IOS_URL;

  return (
    <main className="min-h-screen overflow-hidden bg-[#F4F8FF] px-4 pb-8 pt-4 text-slate-950 sm:px-6 sm:pt-6 lg:px-8">
      <div className="pointer-events-none fixed inset-0 overflow-hidden">
        <div className="absolute -left-24 top-10 h-72 w-72 rounded-full bg-blue-300/30 blur-3xl" />
        <div className="absolute -right-24 bottom-10 h-80 w-80 rounded-full bg-cyan-300/30 blur-3xl" />
      </div>

      <section className="relative mx-auto flex min-h-[calc(100vh-64px)] w-full max-w-5xl items-start justify-center pt-2 sm:items-center sm:pt-0">
        <div className="w-full">
          <div className="mb-4 flex justify-center sm:mb-6">
            <div className="inline-flex max-w-full items-center gap-3 rounded-full border border-white/80 bg-white/90 px-4 py-2 shadow-lg shadow-blue-950/10 backdrop-blur">
              <div className="min-w-0 leading-tight">
                <img
                  src={logo}
                  alt="logo"
                  className="h-auto w-20 max-w-full sm:w-34"
                />

              </div>
            </div>
          </div>

          <div className="grid w-full items-center gap-6 rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-2xl shadow-blue-950/10 backdrop-blur sm:gap-8 sm:p-6 md:p-8 lg:p-10">
            <div className="order-2 md:order-1">
              <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-4 py-2 text-xs font-bold text-blue-700 sm:mb-7 sm:text-sm">
                <span className="h-2 w-2 rounded-full bg-emerald-500" />
                <span className="text-[#309BD8]">Pet health, now easier</span>
              </div>

              <h1 className="max-w-xl text-3xl font-black leading-[1.05] tracking-tight text-slate-950 sm:text-5xl lg:text-6xl">
                Download <span className="text-[#309BD8]">SnoutIQ</span> App
              </h1>

              <p className="mt-4 max-w-xl text-sm font-medium leading-6 text-slate-600 sm:mt-5 sm:text-lg sm:leading-7">
                Manage your pet's health, reminders, vaccination records, vet visits and care journey from one simple app.
              </p>

              <div className="mt-7 flex flex-col gap-4 sm:mt-8 md:max-w-[700px] md:flex-row">
                <StoreButton
                  href={ANDROID_URL}
                  variant="dark"
                  iconSrc={androidLogo}
                  iconAlt="Android"
                />

                <StoreButton
                  href={iosStoreUrl}
                  variant="light"
                  iconSrc={iosLogo}
                  iconAlt="iOS"
                />
              </div>

              <div className="mt-6 flex flex-wrap gap-2 text-xs font-semibold text-slate-500 sm:mt-7 sm:gap-3 sm:text-sm">
                <span className="rounded-full bg-slate-100 px-3 py-2 sm:px-4">
                  Vaccination reminders
                </span>
                <span className="rounded-full bg-slate-100 px-3 py-2 sm:px-4">
                  Vet booking
                </span>
                <span className="rounded-full bg-slate-100 px-3 py-2 sm:px-4">
                  Health records
                </span>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
  );
}
