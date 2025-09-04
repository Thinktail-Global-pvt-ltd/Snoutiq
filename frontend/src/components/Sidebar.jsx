import { useEffect, useState, useContext } from "react";
import axios from "../axios";
import { Link, useNavigate } from "react-router-dom";
import toast from "react-hot-toast";
import { PlusCircleIcon, TrashIcon } from "@heroicons/react/24/outline";
import { AuthContext } from "../auth/AuthContext";
import { useParams } from "react-router-dom";

// 🐾 Pet Images
import PowBrosPic from "./../assets/pets/Paw Bros, 2 Years, New Delhi.jpg";
import BabyLokiPine from "./../assets/pets/Baby Loki, 20 days, Pune.jpg";
import TigerPic from "./../assets/pets/I am Tiger, 1 year, Haryana.jpg";
import Kitty from "./../assets/pets/Kitty, 2 months, Delhi.jpg";
import LuckyPic from "./../assets/pets/Lucy, 7 years, Pune.jpg";
import NotTwins from "./../assets/pets/Not Twins, 2 years, New Delhi,.jpg";
import OliverPic from "./../assets/pets/Oliver, 40 days, Faridabad.jpg";
import ShadowPic from "./../assets/pets/Shadow, 1 Year, Mahabaleshwar.jpg";

const Sidebar = ({ isMobile = false, onItemClick }) => {
  const [mainPet, setActivePet] = useState(null);
  const [deletingId, setDeletingId] = useState(null);


  const { user } = useContext(AuthContext);
  const { chat_room_token } = useParams();
  const navigate = useNavigate();

  const [history, setHistory] = useState([]);
  const [isLoading, setIsLoading] = useState(false);

  // ✅ Fetch chat history
  const fetchHistory = useCallback(async () => {
    if (!user?.id) return;
    setIsLoading(true);
    try {
      const token = localStorage.getItem("token");
      if (!token) throw new Error("No token found");

      const res = await axios.get(`/chat/history/${user.id}/`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      if (res.data?.history) {
        setHistory(res.data.history);
      }
    } catch (err) {
      console.error("Error fetching chat history:", err);
    } finally {
      setIsLoading(false);
    }
  }, [user?.id]);

  useEffect(() => {
    fetchHistory();
  }, [fetchHistory]);

  // ✅ Create new chat
  const handleCreateNewChat = async () => {
    try {
      const token = localStorage.getItem("token");
      if (!token) throw new Error("No token found");

      const res = await axios.post(
        "/chat/create/",
        { user_id: user.id },
        { headers: { Authorization: `Bearer ${token}` } }
      );

      if (res.data?.chat_room_token) {
        const newChat = {
          chat_room_token: res.data.chat_room_token,
          last_message: "",
        };
        setHistory((prev) => [newChat, ...prev]); // ✅ update immediately
        navigate(`/chat/${res.data.chat_room_token}`);
      }
    } catch (err) {
      console.error("Error creating new chat:", err);
      toast.error("Failed to create new chat");
    }
  };

  // ✅ Delete chat
  const handleDeleteChat = async (tokenToDelete) => {
    try {
      const token = localStorage.getItem("token");
      if (!token) throw new Error("No token found");

      await axios.delete(`/chat/delete/${tokenToDelete}/`, {
        headers: { Authorization: `Bearer ${token}` },
      });

      setHistory((prev) =>
        prev.filter((chat) => chat.chat_room_token !== tokenToDelete)
      );

      if (chat_room_token === tokenToDelete) {
        navigate("/dashboard");
      }
    } catch (err) {
      console.error("Error deleting chat:", err);
      toast.error("Failed to delete chat");
    }
  };

  // const handleNewChat = async () => {
  //   if (!user || !user.id) return;

  //   try {
  //     const res = await axios.get(
  //       `https://snoutiq.com/backend/api/chat-rooms/new?user_id=${user.id}`,
  //       { headers: { Authorization: `Bearer ${localStorage.getItem("token")}` } }
  //     );

  //     const { chat_room_token } = res.data;
  //     toast.success("New chat started!");
  //     navigate(`/chat/${chat_room_token}`);
  //     // Optional: fetchHistory(); // to update sidebar with new chat
  //   } catch (err) {
  //     console.error(err);
  //     toast.error("Failed to start new chat");
  //   }
  // };

  // const fetchHistory = async () => {
  //   setIsLoading(true);
  //   try {
  //     const token = localStorage.getItem("token");
  //     if (!token) return;

  //     const response = await axios.get(
  //       `http://192.168.1.21:8000/api/chat/listRooms?user_id=1`,
  //       { headers: { Authorization: `Bearer ${token}` } }
  //     );

  //     console.log(response.data, "res");

  //     // Sort by creation date, newest first
  //     const sortedHistory = response.data.rooms.sort(
  //       (a, b) => new Date(b.created_at) - new Date(a.created_at)
  //     );

  //     setHistory(sortedHistory);
  //   } catch (error) {
  //     console.error("Failed to fetch chat history:", error);
  //     toast.error("Failed to fetch chat history");
  //   } finally {
  //     setIsLoading(false);
  //   }
  // };

  // ✅ Delete chat function

  // const fetchHistory = async () => {
  //   setIsLoading(true);
  //   try {
  //     const token = localStorage.getItem("token");
  //     if (!token) return;

  //     const response = await axios.get(
  //       `https://snoutiq.com/backend/api/chat/listRooms?user_id=${user.id}`,
  //       { headers: { Authorization: `Bearer ${token}` } }
  //     );

  //     console.log(response.data, "res");

  //     // Sort by creation date: newest first
  //     const sortedHistory = response.data.rooms.sort(
  //       (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
  //     );

  //     setHistory(sortedHistory);
  //   } catch (error) {
  //     console.error("Failed to fetch chat history:", error);
  //     toast.error("Failed to fetch chat history");
  //   } finally {
  //     setIsLoading(false);
  //   }
  // };

  // const handleDeleteChat = async (chatId, chatRoomToken, e) => {
  //   e.preventDefault();
  //   e.stopPropagation();

  //   if (!window.confirm("Are you sure you want to delete this chat?")) return;

  //   setDeletingId(chatId);

  //   try {
  //     const token = localStorage.getItem("token");

  //     await axios.delete(
  //       `https://snoutiq.com/backend/api/chat-rooms/${chatRoomToken}`,
  //       {
  //         headers: { Authorization: `Bearer ${token}` },
  //         data: { user_id: user.id }
  //       }
  //     );

  //     toast.success("Chat deleted successfully");

  //     // Remove from local state
  //     setHistory(prev => prev.filter(chat => chat.id !== chatId));
  //   } catch (error) {
  //     console.error("Failed to delete chat:", error);
  //     toast.error("Failed to delete chat");
  //   } finally {
  //     setDeletingId(null);
  //   }
  // };


  // Format date for display
  
  const formatDate = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 0) {
      return "Today";
    } else if (diffDays === 1) {
      return "Yesterday";
    } else if (diffDays < 7) {
      return `${diffDays} days ago`;
    } else {
      return date.toLocaleDateString();
    }
  };

  useEffect(() => {
    fetchHistory();
  }, []);

  useEffect(() => {
    const petsFamus = [
      { name: "Paw Bros", img: PowBrosPic, age: "2 years", loc: "New Delhi" },
      { name: "Baby Loki", img: BabyLokiPine, age: "20 days", loc: "Pune" },
      { name: "I am Tiger", img: TigerPic, age: "1 year", loc: "Haryana" },
      { name: "Kitty", img: Kitty, age: "2 months", loc: "New Delhi" },
      { name: "Lucy", img: LuckyPic, age: "7 years", loc: "Pune" },
      { name: "Not Twins", img: NotTwins, age: "2 years", loc: "New Delhi" },
      { name: "Oliver", img: OliverPic, age: "40 days", loc: "Faridabad" },
      { name: "Shadow", img: ShadowPic, age: "1 year", loc: "Mahabaleshwar" },
    ];
    setActivePet(petsFamus[Math.floor(Math.random() * petsFamus.length)]);
  }, []);

  return (
    <div className="fixed left-0 top-[70px] h-[calc(100vh-70px)] w-[260px] bg-white border-r border-gray-200 flex flex-col">
      {/* Header */}
      <div className="flex items-center justify-between px-4 py-3 border-b">
        <h3 className="text-sm font-semibold text-gray-700">Chat History</h3>
        <button
          onClick={handleCreateNewChat}
          className="flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-700 p-1 rounded hover:bg-blue-50 transition-colors"
          title="Start new chat"
        >
          <PlusCircleIcon className="w-4 h-4" />
          New Chat
        </button>
      </div>

      {/* History List */}
      <div className="flex-1 overflow-y-auto px-2 py-3 space-y-1">
        {isLoading ? (
          <div className="flex justify-center items-center h-20">
            <div className="animate-spin h-5 w-5 border-2 border-blue-500 border-t-transparent rounded-full"></div>
          </div>
        ) : history.length === 0 ? (
          <div className="text-center p-4">
            <p className="text-gray-500 text-sm mb-2">No chat history yet</p>
            <button
              onClick={handleCreateNewChat}
              className="text-xs text-blue-600 hover:text-blue-700 font-medium"
            >
              Start your first chat
            </button>
          </div>
        ) : (
          // history.map((item) => (
          //   <Link
          //     key={item.id}
          //     to={`/chat/${item.chat_room_token}`}
          //     className="group flex items-center justify-between p-2 rounded-md hover:bg-gray-100 border border-transparent hover:border-gray-200 transition-all duration-200"
          //   >
          //     <div className="flex-1 min-w-0">
          //       <span className="font-medium text-gray-700 text-sm truncate block">
          //         {item.name || "New Chat"}
          //       </span>
          //       <span className="text-xs text-gray-400">
          //         {formatDate(item.created_at)}
          //       </span>
          //     </div>

          //     <button
          //       onClick={(e) => handleDeleteChat(item.id, item.chat_room_token, e)}
          //       disabled={deletingId === item.id}
          //       className="opacity-0 group-hover:opacity-60 hover:opacity-100 p-1 rounded hover:bg-gray-200 transition-all duration-200 ml-2"
          //       title="Delete chat"
          //     >
          //       {deletingId === item.id ? (
          //         <div className="w-4 h-4 border-2 border-gray-400 border-t-transparent rounded-full animate-spin"></div>
          //       ) : (
          //         <TrashIcon className="w-4 h-4 text-gray-500" />
          //       )}
          //     </button>
          //   </Link>
          // ))
            history.map((chat) => (
            <div
              key={chat.chat_room_token}
              className={`flex items-center justify-between p-2 rounded-lg cursor-pointer ${
                chat_room_token === chat.chat_room_token
                  ? "bg-blue-200"
                  : "bg-white"
              }`}
              onClick={() => navigate(`/chat/${chat.chat_room_token}`)}
            >
              <span className="truncate text-sm">
                {chat.last_message || "New Chat"}
              </span>
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  handleDeleteChat(chat.chat_room_token);
                }}
                className="p-1 hover:bg-gray-100 rounded"
              >
                <TrashIcon className="w-4 h-4 text-gray-500" />
              </button>
            </div>
          ))

        )}
      </div>

      {/* Pet of the Day */}
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
          <div className="bg-orange-100 rounded-lg p-2 flex flex-col items-center">
            <img
              src={mainPet.img}
              alt={mainPet.name}
              className="w-20 h-20 rounded-lg object-cover mb-1"
            />
            <span className="text-xs font-medium text-gray-700 text-center">
              {mainPet.name}, {mainPet.age}, {mainPet.loc}
            </span>
          </div>
        </div>
      )}
    </div>
  );
};

export default Sidebar;