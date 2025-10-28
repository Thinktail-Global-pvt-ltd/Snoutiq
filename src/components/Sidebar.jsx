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

  const { user, updateChatRoomToken } = useContext(AuthContext);
  const navigate = useNavigate();

  const { chat_room_token: currentChatRoomToken } = useParams();

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
      setHistory((prev) => prev.filter((c) => c.id !== chatId));

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

    setTimeout(() => {
      navigate(`/chat/${chatRoomToken}`);
      window.dispatchEvent(
        new CustomEvent("chatRoomChanged", {
          detail: chatRoomToken,
        })
      );
    }, 0);
  };

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
    const init = async () => {
      await fetchHistory();
    };
    init();
  }, []);

  return (
    <div className="fixed left-0 top-[70px] h-[calc(100vh-70px)] w-[260px] bg-white rounded-tr-xl shadow-md border-r border-gray-200 flex flex-col p-4">
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-sm font-semibold text-gray-700">Chat History</h3>
        <button
          onClick={handleNewChat}
          className="text-blue-600 hover:text-blue-700 transition-colors"
        >
          <PlusCircleIcon className="w-5 h-5" />
        </button>
      </div>

      {/* History */}
      <div className="flex-1 overflow-y-auto space-y-2 mb-6">
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
          history.map((item) =>
            item ? (
              <div
                key={item.id}
                onClick={() => handleHistoryClick(item.chat_room_token)}
                className={`group flex items-center justify-between p-3 rounded-lg cursor-pointer transition-all text-sm ${
                  currentChatRoomToken === item.chat_room_token
                    ? 'bg-blue-50 text-blue-700 font-medium'
                    : 'hover:bg-gray-100 text-gray-700'
                }`}
              >
                <div className="flex-1 min-w-0">
                  <span className="truncate block">
                    {item.summary || "New Chat"}
                  </span>
                </div>
                <button
                  onClick={(e) =>
                    handleDeleteChat(item.id, item.chat_room_token, e)
                  }
                  disabled={deletingId === item.id}
                  className="opacity-0 group-hover:opacity-100 transition-opacity ml-2"
                >
                  {deletingId === item.id ? (
                    <div className="w-3.5 h-3.5 border-2 border-gray-400 border-t-transparent rounded-full animate-spin"></div>
                  ) : (
                    <TrashIcon className="w-3.5 h-3.5 text-red-500" />
                  )}
                </button>
              </div>
            ) : null
          )
        )}
      </div>

      {/* Pet of the Day */}
      {mainPet && (
        <div className="mt-auto pt-6 border-t">
          <p className="text-xs font-semibold uppercase text-gray-500 mb-3">
            Pet of the Day
          </p>
          <div className="bg-orange-50 rounded-lg p-3">
            <div className="w-full h-36 mb-3 rounded-lg overflow-hidden">
              <img
                src={mainPet.img}
                alt={mainPet.name}
                className="w-full h-full object-cover"
                loading="lazy"
              />
            </div>
            <p className="text-sm font-semibold text-gray-800">{mainPet.name}</p>
            <p className="text-xs text-gray-600">{mainPet.age}, {mainPet.loc}</p>
          </div>
        </div>
      )}
    </div>
  );
};

export default Sidebar;
