import { useEffect, useState, useContext, lazy } from "react";
import axios from "../axios";
import toast from "react-hot-toast";
const PlusCircleIcon = lazy(() =>
  import("@heroicons/react/24/outline/PlusCircleIcon")
);
const TrashIcon = lazy(() => import("@heroicons/react/24/outline/TrashIcon"));

import { AuthContext } from "../auth/AuthContext";
import { useNavigate, useParams } from "react-router-dom";

// Pet Images
import PowBrosPic from "../assets/pets/Paw Bros, 2 Years, New Delhi.webp";
import BabyLokiPine from "../assets/pets/Baby Loki, 20 days, Pune.webp";
import TigerPic from "../assets/pets/I am Tiger, 1 year, Haryana.webp";
import Kitty from "../assets/pets/Kitty, 2 months, Delhi.webp";

import LuckyPic from "../assets/pets/Lucy, 7 years, Pune.webp";
import NotTwins from "../assets/pets/Not Twins, 2 years, New Delhi,.webp";
import OliverPic from "../assets/pets/Oliver, 40 days, Faridabad.webp";
import ShadowPic from "../assets/pets/Shadow, 1 Year, Mahabaleshwar.webp";

const Sidebar = () => {
  const [history, setHistory] = useState([]);
  const [mainPet, setMainPet] = useState(null);
  const [loading, setLoading] = useState(false);
  const [deletingId, setDeletingId] = useState(null);

  const { user, chatRoomToken, updateChatRoomToken } = useContext(AuthContext);
  const navigate = useNavigate();

  // ✅ Get current chat room token from URL params
  const { chat_room_token: currentChatRoomToken } = useParams();

  // Fetch chat history
  const fetchHistory = async () => {
    if (!user) return;
    setLoading(true);
    try {
      const token = localStorage.getItem("token");
      const res = await axios.get(
        `https://snoutiq.com/backend/api/chat/listRooms?user_id=${user.id}`,
        { headers: { Authorization: `Bearer ${token}` } }
      );
      const sorted = res.data.rooms.sort(
        (a, b) =>
          new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
      );
      setHistory(sorted);
    } catch (err) {
      console.error(err);
      toast.error("Failed to fetch chat history");
    } finally {
      setLoading(false);
    }
  };

  // Start new chat
  const handleNewChat = async () => {
    if (!user) return;
    try {
      const token = localStorage.getItem("token");
      const res = await axios.get(
        `https://snoutiq.com/backend/api/chat-rooms/new?user_id=${user.id}`,
        { headers: { Authorization: `Bearer ${token}` } }
      );
      const { chat_room_token } = res.data;

      if (updateChatRoomToken) updateChatRoomToken(chat_room_token);
      toast.success("New chat started!");

      await fetchHistory();
      navigate(`/chat/${chat_room_token}`);
      return chat_room_token;
    } catch (err) {
      console.error(err);
      toast.error("Failed to start new chat");
    }
  };

  const handleDeleteChat = async (chatId, chatRoomToken, e) => {
    e.preventDefault();
    e.stopPropagation();
    if (!window.confirm("Are you sure you want to delete this chat?")) return;

    setDeletingId(chatId);
    try {
      const token = localStorage.getItem("token");
      await axios.delete(
        `https://snoutiq.com/backend/api/chat-rooms/${chatRoomToken}`,
        {
          headers: { Authorization: `Bearer ${token}` },
          data: { user_id: user.id },
        }
      );

      toast.success("Chat deleted");

      // Remove deleted chat from state
      setHistory((prev) => prev.filter((c) => c.id !== chatId));

      // ✅ FIX: Only create new chat if the deleted chat is the current active chat
      if (chatRoomToken === currentChatRoomToken) {
        handleNewChat();
      }
    } catch (err) {
      console.error(err);
      toast.error("Failed to delete chat");
    } finally {
      setDeletingId(null);
    }
  };

  const handleHistoryClick = async (chatRoomToken) => {
    if (!chatRoomToken) return;

    if (updateChatRoomToken) {
      updateChatRoomToken(chatRoomToken);
    }

    // Wait a tick to ensure Dashboard sees updated context
    setTimeout(() => {
      navigate(`/chat/${chatRoomToken}`);
      window.dispatchEvent(
        new CustomEvent("chatRoomChanged", {
          detail: chatRoomToken,
        })
      );
    }, 0);
  };

  // Pet of the day
  useEffect(() => {
    const pets = [
      { name: "Paw Bros", img: PowBrosPic, age: "2 years", loc: "New Delhi" },
      { name: "Baby Loki", img: BabyLokiPine, age: "20 days", loc: "Pune" },
      { name: "I am Tiger", img: TigerPic, age: "1 year", loc: "Haryana" },
      { name: "Kitty", img: Kitty, age: "2 months", loc: "New Delhi" },
      { name: "Lucy", img: LuckyPic, age: "7 years", loc: "Pune" },
      { name: "Not Twins", img: NotTwins, age: "2 years", loc: "New Delhi" },
      { name: "Oliver", img: OliverPic, age: "40 days", loc: "Faridabad" },
      { name: "Shadow", img: ShadowPic, age: "1 year", loc: "Mahabaleshwar" },
    ];
    setMainPet(pets[Math.floor(Math.random() * pets.length)]);
  }, []);

  useEffect(() => {
    fetchHistory();
  }, []);

  const formatDate = (dateStr) => {
    const d = new Date(dateStr);
    const diff = Math.floor((new Date() - d) / (1000 * 60 * 60 * 24));
    return diff === 0
      ? "Today"
      : diff === 1
      ? "Yesterday"
      : diff < 7
      ? `${diff} days ago`
      : d.toLocaleDateString();
  };

  return (
    <div className="fixed left-0 top-[70px] h-[calc(100vh-70px)] w-[260px] bg-[#EFF6FF] border-r border-gray-200 flex flex-col">
      {/* Header */}
      <div className="flex items-center justify-between px-4 py-3 border-b">
        <h3 className="text-sm font-semibold text-gray-700">Chat History</h3>
        <button
          onClick={handleNewChat}
          className="flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-700 p-1 rounded hover:bg-blue-50 transition-colors"
        >
          <PlusCircleIcon className="w-4 h-4" /> New Chat
        </button>
      </div>

      {/* History */}
      <div className="flex-1 overflow-y-auto px-2 py-3 space-y-1">
        {loading ? (
          <div className="flex justify-center items-center h-20">
            <div className="animate-spin h-5 w-5 border-2 border-blue-500 border-t-transparent rounded-full"></div>
          </div>
        ) : history.length === 0 ? (
          <div className="text-center p-4">
            <p className="text-gray-500 text-sm mb-2">No chat history yet</p>
            <button
              onClick={handleNewChat}
              className="text-xs text-blue-600 hover:text-blue-700 font-medium"
            >
              Start your first chat
            </button>
          </div>
        ) : (
          history.map((item) => (
            <div
              key={item.id}
              onClick={() => handleHistoryClick(item.chat_room_token)}
              className="group flex items-center justify-between p-2 rounded-md hover:bg-gray-100 border border-transparent hover:border-gray-200 transition-all duration-200"
            >
              <div className="flex-1 min-w-0">
                {/* <span className="font-medium text-gray-700 text-sm truncate block">
                  {item.name || "New Chat"}
                </span> */}
                <span className="font-medium text-gray-700 text-sm truncate block">
                  {item.summary && !item.summary.startsWith("New chat -")
                    ? item.summary
                    : "New Chat"}
                </span>
              </div>
              <button
                onClick={(e) =>
                  handleDeleteChat(item.id, item.chat_room_token, e)
                }
                disabled={deletingId === item.id}
                className="opacity-0 group-hover:opacity-60 hover:opacity-100 p-1 rounded hover:bg-gray-200 transition-all duration-200 ml-2"
              >
                {deletingId === item.id ? (
                  <div className="w-4 h-4 border-2 border-gray-400 border-t-transparent rounded-full animate-spin"></div>
                ) : (
                  <TrashIcon className="w-4 h-4 text-gray-500" />
                )}
              </button>
            </div>
          ))
        )}
      </div>

      {/* Pet of the day - Improved Section */}
      {mainPet && (
        <div className="border-t p-3 bg-gray-50">
          <div className="flex items-center justify-between mb-2">
            <h3 className="text-xs font-semibold text-gray-600">
              Pet of the Day
            </h3>
            <span className="bg-red-500 text-white text-[9px] px-2 py-0.5 rounded-full">
              LIVE
            </span>
          </div>
          <div className="bg-orange-100 rounded-lg p-3 flex flex-col items-center">
            <div className="relative w-full aspect-square max-w-[180px] mb-2">
              <img
                src={mainPet.img}
                alt={mainPet.name}
                className="w-full h-full rounded-lg object-cover shadow-sm"
                width="180"
                height="180"
                loading="lazy"
              />
            </div>
            <div className="text-center">
              <h4 className="text-sm font-medium text-gray-800">
                {mainPet.name}
              </h4>
              <p className="text-xs text-gray-600">
                {mainPet.age}, {mainPet.loc}
              </p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default Sidebar;
