import React, {
  useState,
  useEffect,
  useContext,
  useRef,
  useCallback,
  useMemo,
} from "react";
import { Link, useNavigate, useLocation } from "react-router-dom";
import { motion, AnimatePresence } from "framer-motion";
import {
  Bars3Icon,
  XMarkIcon,
  ChevronDownIcon,
  UserIcon,
  HomeIcon,
  HeartIcon,
  ArrowRightOnRectangleIcon,
} from "@heroicons/react/24/outline";
import logo from "../assets/images/logo.webp";
import { AuthContext } from "../auth/AuthContext";

const navItems = [
  {
    label: "Platform",
    // href: "/#features",
    description: "Telehealth, EMR, and payments in one secure stack",
    dropdown: [
      {
        label: "Full Feature Overview",
        description: "Explore everything SnoutIQ automates for care teams",
        href: "/features",
      },
      {
        label: "Video Consultation",
        description: "Crystal clear HD visits with notes and screen share",
        href: "/video-consult",
      },
      // {
      //   label: "AI Triage",
      //   description: "Guide pet parents and flag emergencies automatically",
      //   href: "/ai-triage",
      // },
    ],
  },
  {
    label: "Solutions",
    href: "/clinics-solution",
    description: "Built for single-site clinics and multi-location groups",
  },
  {
    label: "Locations",
    // href: "/#trusted-by",
    description: "See the cities where SnoutIQ operates today",
    dropdown: [
      {
        label: "Delhi NCR",
        description: "Flagship coverage with 24/7 vet support",
        href: "/delhi",
      },
      {
        label: "Gurugram",
        description: "Priority consultations for urban pet parents",
        href: "/gurugram",
      },
      // {
      //   label: "Bengaluru",
      //   description: "Launching shortly - stay notified",
      //   href: "/#trusted-by",
      // },
    ],
  },
  // {
  //   label: "Pricing",
  //   href: "/pricing",
  //   description: "Transparent plans with no surprise fees",
  // },
  {
    label: "Resources",
    // href: "/blog",
    description: "Guides and success stories for modern vet teams",
    dropdown: [
      {
        label: "Blog Home",
        description: "Product updates, guides, and customer stories",
        href: "/blog",
      },
      // {
      //   label: "Reduce No-Shows",
      //   description: "Framework for reliable appointment adherence",
      //   href: "/blog/how-to-reduce-no-shows",
      // },
      // {
      //   label: "Tick Fever Guide",
      //   description: "Shareable health education for pet parents",
      //   href: "/blog/tick-fever",
      // },
    ],
  },
];

const Header = React.memo(function Header() {
  const [isOpen, setIsOpen] = useState(false);
  const [openDropdown, setOpenDropdown] = useState(null);
  const [openMobileDropdown, setOpenMobileDropdown] = useState(null);
  const [isUserDropdownOpen, setIsUserDropdownOpen] = useState(false);
  const [isScrolled, setIsScrolled] = useState(false);

  const navigate = useNavigate();
  const location = useLocation();
  const { user } = useContext(AuthContext);
  const userDropdownRef = useRef(null);

  useEffect(() => {
    let ticking = false;
    const handleScroll = () => {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(() => {
        setIsScrolled(window.scrollY > 20);
        ticking = false;
      });
    };

    window.addEventListener("scroll", handleScroll, { passive: true });
    handleScroll();
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (
        userDropdownRef.current &&
        !userDropdownRef.current.contains(event.target)
      ) {
        setIsUserDropdownOpen(false);
      }
    };

    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const handleLogout = useCallback(() => {
    localStorage.clear();
    navigate("/");
    setIsUserDropdownOpen(false);
  }, [navigate]);

  const handleLogin = useCallback(() => {
    navigate("/login");
  }, [navigate]);

  const handleRegister = useCallback(() => {
    navigate(
      "/register?utm_source=header&utm_medium=cta&utm_campaign=vet_landing"
    );
  }, [navigate]);

  const handleDashboard = useCallback(() => {
    navigate("/dashboard");
  }, [navigate]);

  const handlePetInfo = useCallback(() => {
    navigate("/pet-info");
  }, [navigate]);

  const toggleMobileDropdown = (label) => {
    setOpenMobileDropdown((prev) => (prev === label ? null : label));
  };

  const dropdownMotion = useMemo(
    () => ({
      initial: { opacity: 0, y: 10, scale: 0.96 },
      animate: { opacity: 1, y: 0, scale: 1 },
      exit: { opacity: 0, y: 10, scale: 0.96 },
    }),
    []
  );

  const getHeaderOffset = useCallback(() => {
    if (typeof document === "undefined") return 0;
    const headerEl = document.querySelector("header");
    if (headerEl) {
      return headerEl.getBoundingClientRect().height + 16;
    }
    return 88;
  }, []);

  const smoothScrollToHash = useCallback((hash) => {
    if (!hash) return;
    const targetId = hash.replace("#", "");

    let attempts = 0;
    const maxAttempts = 12;

    const scroll = () => {
      const element = document.getElementById(targetId);
      if (element) {
        const elementPosition =
          element.getBoundingClientRect().top + window.scrollY;
        const offset = getHeaderOffset();
        const targetPosition = Math.max(elementPosition - offset, 0);
        window.scrollTo({
          top: targetPosition,
          behavior: "smooth",
        });
        return true;
      }
      return false;
    };

    if (scroll()) return;

    const interval = setInterval(() => {
      attempts++;
      if (scroll() || attempts >= maxAttempts) {
        clearInterval(interval);
      }
    }, 200);
  }, [getHeaderOffset]);

  const closeMenus = useCallback(() => {
    setIsOpen(false);
    setOpenDropdown(null);
    setOpenMobileDropdown(null);
  }, []);

  const handleNavClick = useCallback(
    (e, href) => {
      if (!href) return;

      const isExternal = href.startsWith("http");
      const hasHash = href.includes("#");

      if (isExternal) {
        e?.preventDefault();
        closeMenus();
        window.open(href, "_blank", "noopener");
        return;
      }

      if (hasHash) {
        e?.preventDefault();
        closeMenus();
        const [path, hash] = href.split("#");
        const cleanPath = path || "/";

        if (location.pathname !== cleanPath) {
          navigate(cleanPath, { replace: false });
          setTimeout(() => smoothScrollToHash(`#${hash}`), 500);
        } else {
          setTimeout(() => smoothScrollToHash(`#${hash}`), 150);
        }
        return;
      }

      // Regular internal links rely on Link's default navigation
      closeMenus();
    },
    [closeMenus, location.pathname, navigate, smoothScrollToHash]
  );

  return (
    <header
      className={`fixed top-0 left-0 right-0 z-50 transition-all duration-300 ${
        isScrolled
          ? "bg-white/95 backdrop-blur-lg shadow-lg border-b border-gray-100"
          : "bg-white/90 backdrop-blur-md border-b border-gray-100/50"
      }`}
    >
      <a
        href="#main-content"
        className="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-1/2 focus:-translate-x-1/2 focus:px-4 focus:py-2 focus:rounded-full focus:bg-blue-600 focus:text-white focus:z-50"
      >
        Skip to main content
      </a>
      <nav aria-label="Primary navigation" className="relative">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16 lg:h-20">
            <Link
              to={user ? "/dashboard" : "/"}
              className="flex items-center space-x-3 group"
            >
              <img
                src={logo}
                alt="SnoutIQ logo"
                loading="lazy"
                className="h-6 w-auto transition-transform duration-200 group-hover:scale-105"
              />
            </Link>

            <div className="hidden lg:flex items-center space-x-8">
              {navItems.map((item) => (
                <div
                  key={item.label}
                  className="relative"
                  onMouseEnter={() => item.dropdown && setOpenDropdown(item.label)}
                  onMouseLeave={() => setOpenDropdown(null)}
                >
                  <Link
                    to={item.href}
                    className="flex items-center text-gray-700 hover:text-blue-600 font-medium transition-all duration-200 py-2 px-3 rounded-lg hover:bg-blue-50/50"
                    onClick={(e) => handleNavClick(e, item.href)}
                  >
                    <span className="text-sm tracking-tight">{item.label}</span>
                    {item.dropdown && (
                      <ChevronDownIcon
                        className={`ml-1 w-4 h-4 transition-transform duration-200 ${
                          openDropdown === item.label ? "rotate-180" : ""
                        }`}
                      />
                    )}
                  </Link>

                  <AnimatePresence>
                    {item.dropdown && openDropdown === item.label && (
                      <motion.div
                        {...dropdownMotion}
                        className="absolute top-full left-0 mt-1 w-72 bg-white rounded-2xl shadow-2xl border border-gray-100/80 overflow-hidden ring-1 ring-black/5"
                        role="menu"
                        aria-label={`${item.label} menu`}
                      >
                        <div className="px-4 py-3 text-xs font-semibold text-blue-600 uppercase tracking-wide bg-blue-50/80">
                          {item.description || item.label}
                        </div>
                        <div className="divide-y divide-gray-50">
                          {item.dropdown.map((dropItem) => (
                            <Link
                              key={dropItem.label}
                              to={dropItem.href}
                              className="flex flex-col px-4 py-3 text-left hover:bg-blue-50 transition-all duration-200"
                              onClick={(e) => handleNavClick(e, dropItem.href)}
                            >
                              <span className="text-sm font-semibold text-slate-900">
                                {dropItem.label}
                              </span>
                              {dropItem.description && (
                                <span className="text-xs text-slate-500">
                                  {dropItem.description}
                                </span>
                              )}
                            </Link>
                          ))}
                        </div>
                      </motion.div>
                    )}
                  </AnimatePresence>
                </div>
              ))}
            </div>

            <div className="hidden lg:flex items-center space-x-4">
              {!user ? (
                <>
                  <button
                    onClick={handleLogin}
                    className="px-6 py-2.5 text-sm font-semibold text-gray-700 hover:text-blue-600 transition-all duration-200 rounded-lg hover:bg-gray-50"
                  >
                    Sign in
                  </button>
                  <button
                    onClick={handleRegister}
                    className="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-500 text-white text-sm font-semibold rounded-xl hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200 shadow-md"
                  >
                    Get Started
                  </button>
                </>
              ) : (
                <div className="flex items-center space-x-4">
                  <div className="relative" ref={userDropdownRef}>
                    <button
                      onClick={() => setIsUserDropdownOpen((prev) => !prev)}
                      className="flex items-center space-x-3 bg-white rounded-xl px-4 py-2.5 shadow-sm border border-gray-200 hover:shadow-md transition-all duration-200"
                      aria-haspopup="true"
                      aria-expanded={isUserDropdownOpen}
                    >
                      <div className="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg flex items-center justify-center shadow-md">
                        <UserIcon className="w-4 h-4 text-white" />
                      </div>
                      <div className="flex flex-col items-start">
                        <span className="text-sm font-semibold text-gray-800">
                          {user?.name || "User"}
                        </span>
                        <span className="text-xs text-gray-500">Pet Owner</span>
                      </div>
                      <ChevronDownIcon
                        className={`w-4 h-4 text-gray-400 transition-transform duration-200 ${
                          isUserDropdownOpen ? "rotate-180" : ""
                        }`}
                      />
                    </button>

                    <AnimatePresence>
                      {isUserDropdownOpen && (
                        <motion.div
                          initial={{ opacity: 0, y: 10, scale: 0.95 }}
                          animate={{ opacity: 1, y: 0, scale: 1 }}
                          exit={{ opacity: 0, y: 10, scale: 0.95 }}
                          className="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50"
                          role="menu"
                        >
                          <button
                            onClick={handleDashboard}
                            className="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-all duration-200"
                          >
                            <HomeIcon className="w-4 h-4 mr-3" />
                            Dashboard
                          </button>
                          <button
                            onClick={handlePetInfo}
                            className="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-all duration-200"
                          >
                            <HeartIcon className="w-4 h-4 mr-3" />
                            My Pets
                          </button>
                          <div className="border-t border-gray-100 my-1" />
                          <button
                            onClick={handleLogout}
                            className="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-all duration-200"
                          >
                            <ArrowRightOnRectangleIcon className="w-4 h-4 mr-3" />
                            Logout
                          </button>
                        </motion.div>
                      )}
                    </AnimatePresence>
                  </div>
                </div>
              )}
            </div>

            <button
              onClick={() => setIsOpen((prev) => !prev)}
              className="lg:hidden p-2 rounded-lg text-gray-600 hover:text-blue-600 hover:bg-gray-100 transition-all duration-200"
              aria-label="Toggle navigation"
            >
              {isOpen ? (
                <XMarkIcon className="w-6 h-6" />
              ) : (
                <Bars3Icon className="w-6 h-6" />
              )}
            </button>
          </div>
        </div>

        <AnimatePresence>
          {isOpen && (
            <motion.div
              initial={{ opacity: 0, height: 0 }}
              animate={{ opacity: 1, height: "auto" }}
              exit={{ opacity: 0, height: 0 }}
              className="lg:hidden bg-white border-t border-gray-200 shadow-lg"
            >
              <div className="max-w-7xl mx-auto px-4 sm:px-6 py-6 space-y-4">
                {navItems.map((item) => (
                  <div
                    key={item.label}
                    className="border-b border-gray-100 pb-2 last:border-b-0"
                  >
                    {!item.dropdown ? (
                      <Link
                        to={item.href}
                        className="block py-3 text-gray-700 hover:text-blue-600 font-medium transition-colors text-lg"
                        onClick={(e) => handleNavClick(e, item.href)}
                      >
                        {item.label}
                      </Link>
                    ) : (
                      <>
                        <button
                          type="button"
                          onClick={() => toggleMobileDropdown(item.label)}
                          className="flex items-center justify-between w-full py-3 text-gray-700 hover:text-blue-600 font-medium transition-colors text-lg"
                          aria-expanded={openMobileDropdown === item.label}
                        >
                          <span>{item.label}</span>
                          <ChevronDownIcon
                            className={`w-5 h-5 transform transition-transform ${
                              openMobileDropdown === item.label
                                ? "rotate-180"
                                : ""
                            }`}
                          />
                        </button>
                        {openMobileDropdown === item.label && (
                          <div className="pl-4 mt-2 space-y-2 border-l-2 border-gray-100">
                            {item.dropdown.map((dropItem) => (
                              <Link
                                key={dropItem.label}
                                to={dropItem.href}
                                className="block py-2 text-gray-600 hover:text-blue-600 transition-colors text-sm"
                                onClick={(e) => handleNavClick(e, dropItem.href)}
                              >
                                <span className="font-semibold block">
                                  {dropItem.label}
                                </span>
                                {dropItem.description && (
                                  <span className="text-xs text-slate-500">
                                    {dropItem.description}
                                  </span>
                                )}
                              </Link>
                            ))}
                          </div>
                        )}
                      </>
                    )}
                  </div>
                ))}

                <div className="pt-4 space-y-3 border-t border-gray-200">
                  {!user ? (
                    <>
                      <button
                        onClick={() => {
                          handleLogin();
                          setIsOpen(false);
                        }}
                        className="block w-full py-3 text-center text-gray-700 font-medium border border-gray-300 rounded-xl hover:border-blue-500 hover:text-blue-600 transition-all duration-200"
                      >
                        Sign in
                      </button>
                      <button
                        onClick={() => {
                          handleRegister();
                          setIsOpen(false);
                        }}
                        className="block w-full py-3 text-center bg-gradient-to-r from-blue-600 to-blue-500 text-white font-medium rounded-xl hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200 shadow-md"
                      >
                        Get Started
                      </button>
                    </>
                  ) : (
                    <>
                      <button
                        onClick={() => {
                          handleDashboard();
                          setIsOpen(false);
                        }}
                        className="flex items-center w-full py-3 px-4 text-gray-700 font-medium border border-gray-300 rounded-xl hover:border-blue-500 hover:text-blue-600 transition-all duration-200"
                      >
                        <HomeIcon className="w-5 h-5 mr-3" />
                        Dashboard
                      </button>
                      <button
                        onClick={() => {
                          handlePetInfo();
                          setIsOpen(false);
                        }}
                        className="flex items-center w-full py-3 px-4 text-gray-700 font-medium border border-gray-300 rounded-xl hover:border-blue-500 hover:text-blue-600 transition-all duration-200"
                      >
                        <HeartIcon className="w-5 h-5 mr-3" />
                        My Pets
                      </button>
                      <button
                        onClick={() => {
                          handleLogout();
                          setIsOpen(false);
                        }}
                        className="flex items-center w-full py-3 px-4 text-red-600 font-medium border border-red-200 rounded-xl hover:border-red-500 hover:bg-red-50 transition-all duration-200"
                      >
                        <ArrowRightOnRectangleIcon className="w-5 h-5 mr-3" />
                        Logout
                      </button>
                    </>
                  )}
                </div>
              </div>
            </motion.div>
          )}
        </AnimatePresence>
      </nav>
    </header>
  );
});

export default Header;
