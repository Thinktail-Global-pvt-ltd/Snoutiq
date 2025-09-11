import React, { useState, useEffect } from "react";
import {
  PlusIcon,
  CheckCircleIcon,
  ClockIcon,
  XCircleIcon,
  PencilIcon,
  TrashIcon,
  BellAlertIcon,
  PlayIcon,
  PauseIcon,
  ChevronDownIcon,
  ChevronUpIcon,
} from "@heroicons/react/24/outline";

const PetDailyCare = () => {
  const [pets, setPets] = useState([
    { id: 1, name: "Buddy", species: "Dog", breed: "Golden Retriever", age: 3 },
    { id: 2, name: "Whiskers", species: "Cat", breed: "Siamese", age: 2 },
  ]);
  const [selectedPet, setSelectedPet] = useState(1);
  const [tasks, setTasks] = useState([]);
  const [showAddForm, setShowAddForm] = useState(false);
  const [editingTask, setEditingTask] = useState(null);
  const [activeTab, setActiveTab] = useState("today");
  const [currentTime, setCurrentTime] = useState(new Date());

  const [formData, setFormData] = useState({
    title: "",
    category: "",
    scheduledTime: "",
    frequency: "daily",
    description: "",
    important: false,
    reminder: false,
  });

  const taskCategories = [
    {
      id: "feeding",
      name: "Feeding",
      icon: "🍖",
      color: "bg-blue-100 text-blue-800",
    },
    {
      id: "medication",
      name: "Medication",
      icon: "💊",
      color: "bg-red-100 text-red-800",
    },
    {
      id: "exercise",
      name: "Exercise",
      icon: "🏃",
      color: "bg-green-100 text-green-800",
    },
    {
      id: "grooming",
      name: "Grooming",
      icon: "✨",
      color: "bg-purple-100 text-purple-800",
    },
    {
      id: "training",
      name: "Training",
      icon: "🎓",
      color: "bg-yellow-100 text-yellow-800",
    },
    {
      id: "other",
      name: "Other",
      icon: "📝",
      color: "bg-gray-100 text-gray-800",
    },
  ];

  useEffect(() => {
    // Load tasks from localStorage
    const savedTasks = localStorage.getItem("dailyCareTasks");
    if (savedTasks) {
      setTasks(JSON.parse(savedTasks));
    }

    // Update current time every minute
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 60000);

    return () => clearInterval(timer);
  }, []);

  useEffect(() => {
    // Save tasks to localStorage whenever they change
    localStorage.setItem("dailyCareTasks", JSON.stringify(tasks));
  }, [tasks]);

  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: type === "checkbox" ? checked : value,
    }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!formData.title || !formData.category || !formData.scheduledTime) {
      alert("Please fill in required fields");
      return;
    }

    const taskData = {
      ...formData,
      id: editingTask ? editingTask.id : Date.now(),
      petId: selectedPet,
      completed: false,
      createdAt: editingTask ? editingTask.createdAt : new Date().toISOString(),
      completedAt: null,
    };

    let updatedTasks;
    if (editingTask) {
      updatedTasks = tasks.map((task) =>
        task.id === editingTask.id ? taskData : task
      );
    } else {
      updatedTasks = [...tasks, taskData];
    }

    setTasks(updatedTasks);
    resetForm();
  };

  const resetForm = () => {
    setFormData({
      title: "",
      category: "",
      scheduledTime: "",
      frequency: "daily",
      description: "",
      important: false,
      reminder: false,
    });
    setShowAddForm(false);
    setEditingTask(null);
  };

  const handleEdit = (task) => {
    setFormData(task);
    setEditingTask(task);
    setShowAddForm(true);
  };

  const handleDelete = (taskId) => {
    if (window.confirm("Are you sure you want to delete this task?")) {
      const updatedTasks = tasks.filter((task) => task.id !== taskId);
      setTasks(updatedTasks);
    }
  };

  const toggleTaskCompletion = (taskId) => {
    const updatedTasks = tasks.map((task) => {
      if (task.id === taskId) {
        return {
          ...task,
          completed: !task.completed,
          completedAt: !task.completed ? new Date().toISOString() : null,
        };
      }
      return task;
    });
    setTasks(updatedTasks);
  };

  const getPetName = (petId) => {
    const pet = pets.find((p) => p.id.toString() === petId.toString());
    return pet ? pet.name : "Unknown Pet";
  };

  const getCategoryInfo = (categoryId) => {
    return (
      taskCategories.find((cat) => cat.id === categoryId) || taskCategories[5]
    ); // Default to "Other"
  };

  const getFilteredTasks = () => {
    let filtered = tasks.filter(
      (task) => task.petId.toString() === selectedPet.toString()
    );

    // Filter by active tab
    const now = new Date();
    const todayStart = new Date(
      now.getFullYear(),
      now.getMonth(),
      now.getDate()
    );
    const todayEnd = new Date(
      now.getFullYear(),
      now.getMonth(),
      now.getDate() + 1
    );

    switch (activeTab) {
      case "today":
        return filtered.filter((task) => {
          const taskTime = new Date(task.scheduledTime);
          return taskTime >= todayStart && taskTime < todayEnd;
        });
      case "upcoming":
        return filtered.filter(
          (task) => new Date(task.scheduledTime) > todayEnd
        );
      case "completed":
        return filtered.filter((task) => task.completed);
      default:
        return filtered;
    }
  };

  const formatTime = (timeString) => {
    const time = new Date(timeString);
    return time.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
  };

  const formatDate = (dateString) => {
    const date = new Date(dateString);
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);

    if (date.toDateString() === today.toDateString()) {
      return "Today";
    } else if (date.toDateString() === tomorrow.toDateString()) {
      return "Tomorrow";
    } else {
      return date.toLocaleDateString([], {
        weekday: "short",
        month: "short",
        day: "numeric",
      });
    }
  };

  const filteredTasks = getFilteredTasks();

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-6xl mx-auto space-y-6">
        {/* Header */}
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">
              Daily Care Tracker
            </h1>
            <p className="text-gray-600 mt-1">
              Manage your pet's daily routines and care schedule
            </p>
          </div>
          <div className="flex items-center gap-3">
            <select
              value={selectedPet}
              onChange={(e) => setSelectedPet(parseInt(e.target.value))}
              className="p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            >
              {pets.map((pet) => (
                <option key={pet.id} value={pet.id}>
                  {pet.name}
                </option>
              ))}
            </select>
            <button
              onClick={() => setShowAddForm(true)}
              className="flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-md"
            >
              <PlusIcon className="w-5 h-5 mr-2" />
              Add Task
            </button>
          </div>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div className="flex items-center">
              <div className="p-2 bg-blue-100 rounded-lg">
                <ClockIcon className="w-6 h-6 text-blue-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm text-gray-600">Today's Tasks</p>
                <p className="text-xl font-bold text-gray-900">
                  {
                    tasks.filter((task) => {
                      const today = new Date();
                      const taskTime = new Date(task.scheduledTime);
                      return (
                        taskTime.toDateString() === today.toDateString() &&
                        task.petId.toString() === selectedPet.toString()
                      );
                    }).length
                  }
                </p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div className="flex items-center">
              <div className="p-2 bg-green-100 rounded-lg">
                <CheckCircleIcon className="w-6 h-6 text-green-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm text-gray-600">Completed Today</p>
                <p className="text-xl font-bold text-gray-900">
                  {
                    tasks.filter((task) => {
                      const today = new Date();
                      const taskTime = new Date(task.scheduledTime);
                      return (
                        taskTime.toDateString() === today.toDateString() &&
                        task.petId.toString() === selectedPet.toString() &&
                        task.completed
                      );
                    }).length
                  }
                </p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div className="flex items-center">
              <div className="p-2 bg-yellow-100 rounded-lg">
                <BellAlertIcon className="w-6 h-6 text-yellow-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm text-gray-600">Upcoming</p>
                <p className="text-xl font-bold text-gray-900">
                  {
                    tasks.filter((task) => {
                      const tomorrow = new Date();
                      tomorrow.setDate(tomorrow.getDate() + 1);
                      const taskTime = new Date(task.scheduledTime);
                      return (
                        taskTime > new Date() &&
                        taskTime.toDateString() !== new Date().toDateString() &&
                        task.petId.toString() === selectedPet.toString()
                      );
                    }).length
                  }
                </p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div className="flex items-center">
              <div className="p-2 bg-red-100 rounded-lg">
                <XCircleIcon className="w-6 h-6 text-red-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm text-gray-600">Missed</p>
                <p className="text-xl font-bold text-gray-900">
                  {
                    tasks.filter((task) => {
                      const now = new Date();
                      const taskTime = new Date(task.scheduledTime);
                      return (
                        taskTime < now &&
                        !task.completed &&
                        task.petId.toString() === selectedPet.toString()
                      );
                    }).length
                  }
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Tabs */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div className="flex border-b border-gray-200">
            {["today", "upcoming", "completed"].map((tab) => {
              const tabLabels = {
                today: "Today's Tasks",
                upcoming: "Upcoming",
                completed: "Completed",
              };

              return (
                <button
                  key={tab}
                  onClick={() => setActiveTab(tab)}
                  className={`flex-1 px-4 py-3 text-sm font-medium ${
                    activeTab === tab
                      ? "text-indigo-600 border-b-2 border-indigo-600"
                      : "text-gray-500 hover:text-gray-700"
                  }`}
                >
                  {tabLabels[tab]}
                </button>
              );
            })}
          </div>
        </div>

        {/* Tasks List */}
        <div className="space-y-3">
          {filteredTasks.length === 0 ? (
            <div className="text-center py-12 bg-white rounded-xl shadow-sm border border-gray-200">
              <ClockIcon className="mx-auto h-12 w-12 text-gray-400" />
              <h3 className="mt-2 text-sm font-medium text-gray-900">
                No tasks found
              </h3>
              <p className="mt-1 text-sm text-gray-500">
                {activeTab === "today"
                  ? "Get started by adding a task for today."
                  : `No ${activeTab} tasks found.`}
              </p>
              {activeTab === "today" && (
                <div className="mt-6">
                  <button
                    onClick={() => setShowAddForm(true)}
                    className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                  >
                    <PlusIcon className="w-5 h-5 mr-2" />
                    Add Task
                  </button>
                </div>
              )}
            </div>
          ) : (
            filteredTasks.map((task) => {
              const categoryInfo = getCategoryInfo(task.category);
              const taskTime = new Date(task.scheduledTime);
              const isOverdue = !task.completed && taskTime < new Date();

              return (
                <div
                  key={task.id}
                  className={`bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-all ${
                    isOverdue ? "border-l-4 border-red-500" : ""
                  }`}
                >
                  <div className="flex items-start justify-between">
                    <div className="flex items-start space-x-3 flex-1">
                      <button
                        onClick={() => toggleTaskCompletion(task.id)}
                        className={`p-2 rounded-full mt-1 ${
                          task.completed
                            ? "bg-green-100 text-green-600"
                            : "bg-gray-100 text-gray-400 hover:bg-green-50"
                        }`}
                      >
                        <CheckCircleIcon className="w-5 h-5" />
                      </button>

                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-1">
                          <span
                            className={`px-2 py-1 rounded-full text-xs font-medium ${categoryInfo.color}`}
                          >
                            {categoryInfo.icon} {categoryInfo.name}
                          </span>
                          {task.important && (
                            <span className="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                              Important
                            </span>
                          )}
                          {isOverdue && (
                            <span className="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                              Overdue
                            </span>
                          )}
                        </div>

                        <h3
                          className={`font-medium ${
                            task.completed
                              ? "text-gray-500 line-through"
                              : "text-gray-900"
                          }`}
                        >
                          {task.title}
                        </h3>

                        {task.description && (
                          <p className="text-sm text-gray-600 mt-1">
                            {task.description}
                          </p>
                        )}

                        <div className="flex items-center mt-2 text-sm text-gray-500">
                          <ClockIcon className="w-4 h-4 mr-1" />
                          <span>
                            {formatDate(task.scheduledTime)} at{" "}
                            {formatTime(task.scheduledTime)}
                          </span>
                        </div>
                      </div>
                    </div>

                    <div className="flex space-x-2">
                      <button
                        onClick={() => handleEdit(task)}
                        className="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                      >
                        <PencilIcon className="w-5 h-5" />
                      </button>
                      <button
                        onClick={() => handleDelete(task.id)}
                        className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                      >
                        <TrashIcon className="w-5 h-5" />
                      </button>
                    </div>
                  </div>
                </div>
              );
            })
          )}
        </div>

        {/* Add/Edit Task Modal */}
        {/* {showAddForm && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
              <div className="p-6 border-b border-gray-200">
                <div className="flex justify-between items-center">
                  <h2 className="text-xl font-bold text-gray-900">
                    {editingTask ? "Edit Task" : "Add New Task"}
                  </h2>
                  <button
                    onClick={resetForm}
                    className="text-gray-400 hover:text-gray-600"
                  >
                    <XCircleIcon className="w-6 h-6" />
                  </button>
                </div>
              </div>

              <form onSubmit={handleSubmit} className="p-6 space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Task Title *
                  </label>
                  <input
                    type="text"
                    name="title"
                    value={formData.title}
                    onChange={handleInputChange}
                    placeholder="e.g., Morning Walk, Evening Meal"
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus"
                  />
                </div>
              </form>
            </div>
          </div>
        )} */}
            {showAddForm && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-xl max-w-md w-full p-6 space-y-4">
              <div className="flex justify-between items-center">
                <h2 className="text-xl font-bold">{editingTask ? 'Edit Task' : 'Add Task'}</h2>
                <button onClick={resetForm}><XCircleIcon className="w-6 h-6" /></button>
              </div>
              <form onSubmit={handleSubmit} className="space-y-4">
                {/* Title */}
                <div>
                  <label className="block text-sm">Title *</label>
                  <input type="text" name="title" value={formData.title} onChange={handleInputChange}
                    className="w-full p-2 border rounded-lg" required />
                </div>
                {/* Category */}
                <div>
                  <label className="block text-sm">Category *</label>
                  <select name="category" value={formData.category} onChange={handleInputChange}
                    className="w-full p-2 border rounded-lg" required>
                    <option value="">Select Category</option>
                    {taskCategories.map(cat => (
                      <option key={cat.id} value={cat.id}>{cat.name}</option>
                    ))}
                  </select>
                </div>
                {/* Time */}
                <div>
                  <label className="block text-sm">Scheduled Time *</label>
                  <input type="datetime-local" name="scheduledTime" value={formData.scheduledTime}
                    onChange={handleInputChange} className="w-full p-2 border rounded-lg" required />
                </div>
                {/* Frequency */}
                <div>
                  <label className="block text-sm">Frequency</label>
                  <select name="frequency" value={formData.frequency} onChange={handleInputChange}
                    className="w-full p-2 border rounded-lg">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                  </select>
                </div>
                {/* Description */}
                <div>
                  <label className="block text-sm">Description</label>
                  <textarea name="description" value={formData.description} onChange={handleInputChange}
                    className="w-full p-2 border rounded-lg" />
                </div>
                {/* Checkboxes */}
                <div className="flex items-center gap-4">
                  <label className="flex items-center gap-2">
                    <input type="checkbox" name="important" checked={formData.important} onChange={handleInputChange} />
                    Important
                  </label>
                  <label className="flex items-center gap-2">
                    <input type="checkbox" name="reminder" checked={formData.reminder} onChange={handleInputChange} />
                    Reminder
                  </label>
                </div>
                {/* Buttons */}
                <div className="flex justify-end gap-3">
                  <button type="button" onClick={resetForm} className="px-4 py-2 bg-gray-200 rounded-lg">Cancel</button>
                  <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg">
                    {editingTask ? 'Update Task' : 'Add Task'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default PetDailyCare;
