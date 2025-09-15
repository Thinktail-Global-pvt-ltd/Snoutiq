import React, { useContext, useState } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { Fragment } from 'react';
import { Bars3Icon, XMarkIcon, ChatBubbleLeftRightIcon } from '@heroicons/react/24/outline';
import Sidebar from './Sidebar';
import RightSidebar from './RightSidebar';
import OfferIcon from '../assets/images/offericon.webp';
import VetIcon from '../assets/images/veticon.webp';
import groomericon from '../assets/images/groomericon.webp';
import newchaticon from '../assets/images/newchaticon.webp';
import { useNavigate } from 'react-router-dom';
import { AuthContext } from '../auth/AuthContext';

const MobileLayout = ({ children }) => {
  const [isLeftDrawerOpen, setIsLeftDrawerOpen] = useState(false);
  const [isRightDrawerOpen, setIsRightDrawerOpen] = useState(false);
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);

  const navigate = useNavigate();
  const { user, login } = useContext(AuthContext);

  // Close drawer functions
  const closeLeftDrawer = () => setIsLeftDrawerOpen(false);
  const closeRightDrawer = () => setIsRightDrawerOpen(false);

  const toggleDropdown = () => {
    setIsDropdownOpen(!isDropdownOpen);
  };
  const handleLogout = () => {
    localStorage.clear();
    navigate('/');
    window.location.reload()
  };
  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
      {/* Floating Action Buttons */}
      <div className="fixed right-4 bottom-24 z-40 flex flex-col gap-4">
        <button
          className="w-14 h-14 rounded-full bg-indigo-600 shadow-lg flex items-center justify-center hover:bg-indigo-700 transition-all duration-300 transform hover:scale-105"
          onClick={() => setIsRightDrawerOpen(true)}
        >
          <img src={OfferIcon} alt="Offers" className="w-7 h-7 filter invert" />
        </button>

        <button
          className="w-14 h-14 rounded-full bg-emerald-500 shadow-lg flex items-center justify-center hover:bg-emerald-600 transition-all duration-300 transform hover:scale-105"
          onClick={() => setIsRightDrawerOpen(true)}
        >
          <img src={VetIcon} alt="Vet" className="w-7 h-7 filter invert" />
        </button>

        <button
          className="w-14 h-14 rounded-full bg-amber-500 shadow-lg flex items-center justify-center hover:bg-amber-600 transition-all duration-300 transform hover:scale-105"
          onClick={() => setIsRightDrawerOpen(true)}
        >
          <img src={groomericon} alt="Groomer" className="w-7 h-7 filter invert" />
        </button>
      </div>

      {/* Mobile Header with Toggle Buttons */}
      <div className="lg:hidden fixed top-0 left-0 right-0 z-50 bg-white shadow-md px-4 py-3 flex items-center justify-between">
        <button
          onClick={() => setIsLeftDrawerOpen(true)}
          className="p-2 rounded-lg bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
        >
          <Bars3Icon className="w-6 h-6 text-gray-700" />
        </button>

        <h1 className="text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600">
          Snoutiq AI
        </h1>

        <div className="relative">
          <div
            className="bg-white rounded-lg px-4 py-3 flex items-center space-x-3 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
            onClick={toggleDropdown}
          >
            <div className="w-8 h-8 bg-gray-200 rounded-lg flex items-center justify-center">
              <svg className="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <div className="flex flex-col">
              {/* <div className="text-sm font-semibold text-gray-800">{user.name}</div> */}
              <div className="text-xs text-gray-500">Pet Owner</div>
            </div>
            <svg className="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
            </svg>
          </div>
          {isDropdownOpen && (
            <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50">
              <a
                href='/user-dashboard/*'
                className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
              >
                Dashboard
              </a>
              <button
                onClick={handleLogout}
                className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
              >
                Logout
              </button>
            </div>
          )}
        </div>
      </div>

      {/* Left Drawer - Chat History */}
      <Transition.Root show={isLeftDrawerOpen} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={setIsLeftDrawerOpen}>
          <Transition.Child
            as={Fragment}
            enter="ease-in-out duration-300"
            enterFrom="opacity-0"
            enterTo="opacity-100"
            leave="ease-in-out duration-300"
            leaveFrom="opacity-100"
            leaveTo="opacity-0"
          >
            <div className="fixed inset-0 bg-gray-800 bg-opacity-75 transition-opacity" />
          </Transition.Child>

          <div className="fixed inset-0 overflow-hidden">
            <div className="absolute inset-0 overflow-hidden">
              <div className="pointer-events-none fixed inset-y-0 left-0 flex max-w-full pr-10">
                <Transition.Child
                  as={Fragment}
                  enter="transform transition ease-in-out duration-300"
                  enterFrom="-translate-x-full"
                  enterTo="translate-x-0"
                  leave="transform transition ease-in-out duration-300"
                  leaveFrom="translate-x-0"
                  leaveTo="-translate-x-full"
                >
                  <Dialog.Panel className="pointer-events-auto w-screen max-w-md">
                    <div className="flex h-full flex-col bg-white shadow-xl">
                      <div className="flex items-center justify-between px-6 py-5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
                        <Dialog.Title className="text-lg font-semibold">
                          Chat History
                        </Dialog.Title>
                        <button
                          type="button"
                          className="rounded-md text-white hover:text-gray-200 transition-colors"
                          onClick={() => setIsLeftDrawerOpen(false)}
                        >
                          <XMarkIcon className="w-6 h-6" />
                        </button>
                      </div>
                      <div className="flex-1 overflow-y-auto">
                        <div className="px-4 py-4">
                          <Sidebar isMobile={true} onItemClick={closeLeftDrawer} />
                        </div>
                      </div>
                    </div>
                  </Dialog.Panel>
                </Transition.Child>
              </div>
            </div>
          </div>
        </Dialog>
      </Transition.Root>

      {/* Right Drawer - Additional Features */}
      <Transition.Root show={isRightDrawerOpen} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={setIsRightDrawerOpen}>
          <Transition.Child
            as={Fragment}
            enter="ease-in-out duration-300"
            enterFrom="opacity-0"
            enterTo="opacity-100"
            leave="ease-in-out duration-300"
            leaveFrom="opacity-100"
            leaveTo="opacity-0"
          >
            <div className="fixed inset-0 bg-gray-800 bg-opacity-75 transition-opacity" />
          </Transition.Child>

          <div className="fixed inset-0 overflow-hidden">
            <div className="absolute inset-0 overflow-hidden">
              <div className="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                <Transition.Child
                  as={Fragment}
                  enter="transform transition ease-in-out duration-300"
                  enterFrom="translate-x-full"
                  enterTo="translate-x-0"
                  leave="transform transition ease-in-out duration-300"
                  leaveFrom="translate-x-0"
                  leaveTo="translate-x-full"
                >
                  <Dialog.Panel className="pointer-events-auto w-screen max-w-full">
                    <div className="flex h-full flex-col bg-white shadow-xl">
                      <div className="flex items-center justify-between px-6 py-5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
                        <Dialog.Title className="text-lg font-semibold">
                          Features & Offers
                        </Dialog.Title>
                        <button
                          type="button"
                          className="rounded-md text-white hover:text-gray-200 transition-colors"
                          onClick={() => setIsRightDrawerOpen(false)}
                        >
                          <XMarkIcon className="w-6 h-6" />
                        </button>
                      </div>
                      <div className="flex-1 overflow-y-auto">
                        <div className="w-full">
                          <RightSidebar isMobile={true} onItemClick={closeRightDrawer} />
                        </div>
                      </div>
                    </div>
                  </Dialog.Panel>
                </Transition.Child>
              </div>
            </div>
          </div>
        </Dialog>
      </Transition.Root>

      {/* Main Content */}
      <div className="lg:hidden pt-16 pb-6 px-4">
        <div className="bg-white rounded-xl shadow-sm p-4 min-h-[calc(100vh-7rem)]">
          {children}
        </div>
      </div>
    </div>
  );
};

export default MobileLayout;