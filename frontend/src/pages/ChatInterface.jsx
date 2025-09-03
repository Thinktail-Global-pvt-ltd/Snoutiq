import React, { useEffect, useRef, useState } from 'react';
import ChatInput from '../components/ChatInput';
import RightSidebar from '../components/RightSidebar';
import axios from '../axios';
import toast from 'react-hot-toast';
import { useNavigate, useParams } from 'react-router-dom';
import Siderbar from '../components/Sidebar';
import TypingIndicator from '../components/TypingIndicator';
import ReactMarkdown from "react-markdown";
import MobileLayout from '../components/MobileLayout';
import { FaThumbsDown, FaThumbsUp } from 'react-icons/fa';
import VetListV2 from '../components/VetListV2';
import axiosClient from '../axios';

const ChatInterface = () => {
  const navigate = useNavigate();
  const { chatToken } = useParams();
  const divRef = useRef(null);

  const [messages, setMessages] = useState([]);

  const getChats = async () => {
    try {
      const token = localStorage.getItem('token');
      if (!token) {
        toast.error('Please log in to continue');
        return;
      }

      const response = await axios.get('user/ai/chats/' + chatToken, {
        headers: { Authorization: `Bearer ${token}` },
      });
      setMessages(response.data.messages.map((d) => {
        return {
          text: d.message,
          sender: d.type == "User" ? 'user' : 'ai', id: d.id, rated: d.rated
        }
      }))
    } catch (error) {
      console.log(error)
      const errorMessage =
        error.response && error.response.data && error.response.data.message
          ? error.response.data.message
          : 'Login failed. Please try again.';
      toast.error(errorMessage);
    }
  }
  useEffect(() => { getChats(); }, [chatToken])

  const [sending, setSending] = useState(false);

  useEffect(() => {
    // Scroll to bottom whenever messages change
    if (divRef.current) {
      divRef.current.scrollTop = divRef.current.scrollHeight;
    }
  }, [messages, sending]);

  const handleSendMessage = async (inputMessage) => {
    if (inputMessage.trim() === '') return;
    setSending(true);
    // Add user message
    setMessages(prev => [...prev, { text: inputMessage, sender: 'user' }]);
    try {
      const token = localStorage.getItem("token")
      const res = await axios.post('/user/ai/chats/' + chatToken, {
        message: inputMessage
      }, {
        headers: { Authorization: `Bearer ${token}` },
      });
      setMessages(prev => [...prev, { text: res.data.response, sender: 'ai', id: res.data.id, reated: null }]);

    } catch (error) {
      console.log(error);
      const errorMessage =
        error.response && error.response.data && error.response.data.message
          ? error.response.data.message
          : 'Login failed. Please try again.';
      toast.error(errorMessage);
    } finally {
      setSending(false);
    }
    // Simulate AI response
    // setTimeout(() => {
    //     setMessages(prev => [...prev, {
    //         text: `Thank you for your message: "${inputMessage}". I'm here to help with your pet care questions!`,
    //         sender: 'ai'
    //     }]);
    // }, 1000);
  };

  const [ratingLoading, setRatingLoading] = useState(0);

  const rateit = async (chat_id, rated) => {
    try {
      setRatingLoading(chat_id)
      const token = localStorage.getItem("token")
      const res = await axios.post('/user/ai/rated/' + chat_id, {
        rated: rated
      }, {
        headers: { Authorization: `Bearer ${token}` },
      });
      await getChats();
      toast.success(res.data.message)
    } catch (error) {
      const errorMessage =
        error.response && error.response.data && error.response.data.message
          ? error.response.data.message
          : 'Error send feedback';
      toast.error(errorMessage);
    } finally {
      setRatingLoading(0)
    }
  }
  
 
  // Chat content component
  const ChatContent = () => {
    const [location, setLocation] = useState(null);
    const [error, setError] = useState(null);
    const [data, setData] = useState({ vets: [], groomers: [] });
    useEffect(() => {
      // alert("Hi")
      if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(
          (position) => {
            setLocation({
              lat: position.coords.latitude,
              lng: position.coords.longitude,
            });
          },
          (err) => {
            setError(err.message);
          }
        );
      } else {
        console.log("Geolocation is not supported by your browser.");
      }
    }, []);

    useEffect(() => {
      const fetch = async () => {
        try {
          const res = await axiosClient.get("/fetchNearbyPlaces?lat=" + location.lat + "&lng=" + location.lng);
          console.log("location")

          console.log("HII", res.data);
          setData(res.data)
        } catch (error) {
          console.log("Error", error)
        }
      }
      if (location !== null) {
        fetch();
      }
      console.log("bkl")
    }, [location])
    return (
      <div className="flex flex-col  px-4 lg:px-8 py-8 justify-center items-center h-screen">

        {/* Chat Container - Centered */}
        <div className="w-full justify-center items-center flex flex-col 
                h-[90%] fixed bottom-0 left-0 p-6 sm:p-0 
                md:h-[90%] md:static">

          {/* Chat Display Area */}
          <div className="flex-1 overflow-y-auto p-4 bg-white rounded-xl mb-6 w-full" ref={divRef}>
            {messages.map((msg, index) => (
              <div
                key={index}
                className={`mb-4 p-4 rounded-lg max-w-[80%] ${msg.sender === 'user'
                  ? 'bg-gray-200 text-gray-800 ml-auto'
                  : 'bg-white text-gray-800 mr-auto'
                  }`}
              >
                {msg.sender === 'ai' && (
                  <div className="flex items-center mb-2">
                    {/* Assistant: */}
                  </div>
                )}
                <div className="whitespace-pre-line">
                  {msg.sender === 'ai' && msg.rated == null && (
                    <div className='mb-4 flex space-around gap-3'>
                      {ratingLoading == msg.id ? (<>
                        Sending Feedback...
                      </>) : (<>
                        <FaThumbsUp onClick={() => rateit(msg.id, 'up')} />
                        <FaThumbsDown onClick={() => { rateit(msg.id, 'down') }} />
                      </>)}
                    </div>
                  )}
                  <ReactMarkdown>
                    {msg.text}
                  </ReactMarkdown>

                </div>
              </div>
            ))}
            {sending && (
              <div className='text-left mr-auto'>
                <TypingIndicator />
              </div>)}

            <VetListV2 data={data} />


          </div>

          {/* Chat Input */}
          <div className="w-full">
            <ChatInput onSendMessage={handleSendMessage} />
            <div className='sm:hidden block text-sm p-1'>
              This is AI-generated advice. For serious health concerns, please consult with a licensed veterinarian.

            </div>
          </div>
        </div>
      </div>
    )
  };

  return (
    <>
      {/* Mobile Layout */}
      <div className="lg:hidden">
        <MobileLayout>
          <ChatContent />
        </MobileLayout>
      </div>

      {/* Desktop Layout */}
      <div className="hidden lg:block">
        <div className="min-h-screen w-[55%] fixed left-[20%] px-24 mt-4 mb-24">
          {/* Main Content Area - 75-80% width */}
          <div>
            <Siderbar />
          </div>
          <ChatContent />
          <div className='mb-24'>
            <RightSidebar />
          </div>
        </div>
      </div>
    </>
  );
};

export default ChatInterface; 