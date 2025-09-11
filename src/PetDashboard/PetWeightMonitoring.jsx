import React, { useState, useEffect } from "react";
import { Line } from "react-chartjs-2";
import {
  PlusIcon,
  TrashIcon,
  ChartBarIcon,
  PencilIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  InformationCircleIcon
} from "@heroicons/react/24/outline";
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from "chart.js";

// Chart.js register
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

const PetWeightMonitoring = () => {
  const [weights, setWeights] = useState([]);
  const [pets, setPets] = useState([
    { id: 1, name: 'Buddy', species: 'Dog', breed: 'Golden Retriever', age: 3, targetWeight: 28 },
    { id: 2, name: 'Whiskers', species: 'Cat', breed: 'Siamese', age: 2, targetWeight: 4.5 }
  ]);
  const [selectedPet, setSelectedPet] = useState(1);
  const [form, setForm] = useState({ date: "", weight: "", notes: "" });
  const [editingId, setEditingId] = useState(null);
  const [goalWeight, setGoalWeight] = useState("");
  const [timeRange, setTimeRange] = useState("all"); // all, 3m, 6m, 1y

  useEffect(() => {
    const savedWeights = localStorage.getItem("petWeights");
    const savedPets = localStorage.getItem("pets");
    
    if (savedWeights) setWeights(JSON.parse(savedWeights));
    if (savedPets) setPets(JSON.parse(savedPets));
  }, []);

  useEffect(() => {
    localStorage.setItem("petWeights", JSON.stringify(weights));
  }, [weights]);

  useEffect(() => {
    localStorage.setItem("pets", JSON.stringify(pets));
  }, [pets]);

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleAdd = (e) => {
    e.preventDefault();
    if (!form.date || !form.weight) return;

    const newEntry = {
      id: editingId || Date.now(),
      petId: selectedPet,
      date: form.date,
      weight: parseFloat(form.weight),
      notes: form.notes
    };

    if (editingId) {
      // Update existing entry
      setWeights(weights.map(w => w.id === editingId ? newEntry : w));
      setEditingId(null);
    } else {
      // Add new entry
      setWeights([...weights, newEntry].sort((a, b) => new Date(a.date) - new Date(b.date)));
    }
    
    setForm({ date: "", weight: "", notes: "" });
  };

  const handleEdit = (entry) => {
    setForm({
      date: entry.date,
      weight: entry.weight,
      notes: entry.notes || ""
    });
    setEditingId(entry.id);
  };

  const handleDelete = (id) => {
    setWeights(weights.filter((w) => w.id !== id));
  };

  const handleGoalSubmit = (e) => {
    e.preventDefault();
    if (!goalWeight) return;
    
    setPets(pets.map(pet => 
      pet.id === selectedPet ? { ...pet, targetWeight: parseFloat(goalWeight) } : pet
    ));
    setGoalWeight("");
  };

  const getFilteredWeights = () => {
    const petWeights = weights.filter(w => w.petId === selectedPet);
    
    if (timeRange === "all") return petWeights;
    
    const now = new Date();
    let cutoffDate = new Date();
    
    switch(timeRange) {
      case "3m":
        cutoffDate.setMonth(now.getMonth() - 3);
        break;
      case "6m":
        cutoffDate.setMonth(now.getMonth() - 6);
        break;
      case "1y":
        cutoffDate.setFullYear(now.getFullYear() - 1);
        break;
      default:
        return petWeights;
    }
    
    return petWeights.filter(w => new Date(w.date) >= cutoffDate);
  };

  const getPetData = () => {
    return pets.find(p => p.id === selectedPet);
  };

  const calculateStats = () => {
    const petWeights = getFilteredWeights();
    if (petWeights.length === 0) return null;
    
    const sorted = [...petWeights].sort((a, b) => new Date(a.date) - new Date(b.date));
    const latest = sorted[sorted.length - 1];
    const previous = sorted.length > 1 ? sorted[sorted.length - 2] : null;
    
    let change = 0;
    let changePercent = 0;
    let trend = "stable";
    
    if (previous) {
      change = latest.weight - previous.weight;
      changePercent = (change / previous.weight) * 100;
      
      if (change > 0.5) trend = "up";
      else if (change < -0.5) trend = "down";
    }
    
    const pet = getPetData();
    const progress = pet && pet.targetWeight ? 
      ((latest.weight / pet.targetWeight) * 100).toFixed(1) : null;
    
    return {
      current: latest.weight,
      change,
      changePercent: Math.abs(changePercent).toFixed(1),
      trend,
      progress,
      unit: "kg"
    };
  };

  const filteredWeights = getFilteredWeights();
  const stats = calculateStats();
  const pet = getPetData();

  // Chart data
  const chartData = {
    labels: filteredWeights.map((w) =>
      new Date(w.date).toLocaleDateString([], { month: "short", day: "numeric" })
    ),
    datasets: [
      {
        label: "Weight (kg)",
        data: filteredWeights.map((w) => w.weight),
        borderColor: "rgb(79, 70, 229)",
        backgroundColor: "rgba(79, 70, 229, 0.1)",
        tension: 0.4,
        fill: true,
        pointBackgroundColor: "rgb(79, 70, 229)",
        pointBorderColor: "#fff",
        pointBorderWidth: 2,
        pointRadius: 5,
        pointHoverRadius: 7
      },
      ...(pet && pet.targetWeight ? [{
        label: "Target Weight",
        data: Array(filteredWeights.length).fill(pet.targetWeight),
        borderColor: "rgb(16, 185, 129)",
        borderWidth: 1,
        borderDash: [5, 5],
        pointRadius: 0,
        fill: false
      }] : [])
    ]
  };

  const chartOptions = {
    responsive: true,
    plugins: {
      legend: { 
        position: "top",
        labels: {
          usePointStyle: true,
          padding: 20
        }
      },
      title: { 
        display: true, 
        text: "Weight Progress Over Time",
        font: { size: 16, weight: 'bold' }
      },
      tooltip: {
        backgroundColor: 'rgba(255, 255, 255, 0.95)',
        titleColor: '#1f2937',
        bodyColor: '#4b5563',
        borderColor: '#e5e7eb',
        borderWidth: 1,
        padding: 12,
        boxPadding: 6,
        usePointStyle: true,
        callbacks: {
          label: function(context) {
            return `${context.dataset.label}: ${context.parsed.y} kg`;
          }
        }
      }
    },
    scales: {
      y: {
        beginAtZero: false,
        grid: {
          drawBorder: false
        },
        title: {
          display: true,
          text: 'Weight (kg)'
        }
      },
      x: {
        grid: {
          display: false
        }
      }
    },
    maintainAspectRatio: false
  };

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-6xl mx-auto space-y-6">
        {/* Header */}
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <div className="flex items-center gap-3">
              <ChartBarIcon className="w-8 h-8 text-indigo-600" />
              <h1 className="text-2xl font-bold text-gray-900">
                Pet Weight Monitoring
              </h1>
            </div>
            <p className="text-gray-600 mt-1">
              Track your pet's weight trends and progress toward health goals
            </p>
          </div>
          
          <div className="flex items-center gap-3">
            <select
              value={selectedPet}
              onChange={(e) => setSelectedPet(parseInt(e.target.value))}
              className="p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            >
              {pets.map(pet => (
                <option key={pet.id} value={pet.id}>{pet.name}</option>
              ))}
            </select>
          </div>
        </div>

        {/* Stats Cards */}
        {stats && (
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">Current Weight</p>
                  <p className="text-3xl font-bold text-gray-900 mt-1">
                    {stats.current} <span className="text-lg text-gray-600">kg</span>
                  </p>
                </div>
                <div className="p-3 bg-indigo-100 rounded-xl">
                  <ChartBarIcon className="w-6 h-6 text-indigo-600" />
                </div>
              </div>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">Weight Change</p>
                  <div className="flex items-center mt-1">
                    {stats.trend === "up" ? (
                      <ArrowTrendingUpIcon className="w-5 h-5 text-red-500 mr-1" />
                    ) : stats.trend === "down" ? (
                      <ArrowTrendingDownIcon className="w-5 h-5 text-green-500 mr-1" />
                    ) : null}
                    <p className={`text-2xl font-bold ${stats.trend === "up" ? "text-red-600" : stats.trend === "down" ? "text-green-600" : "text-gray-900"}`}>
                      {stats.change > 0 ? "+" : ""}{stats.change.toFixed(1)} kg
                    </p>
                  </div>
                  <p className="text-sm text-gray-500 mt-1">
                    {stats.change !== 0 ? `${stats.changePercent}% from last measurement` : "No significant change"}
                  </p>
                </div>
              </div>
            </div>

            {stats.progress && (
              <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm text-gray-600">Progress to Target</p>
                    <p className="text-3xl font-bold text-gray-900 mt-1">
                      {stats.progress}%
                    </p>
                    <p className="text-sm text-gray-500 mt-1">
                      Target: {pet.targetWeight} kg
                    </p>
                  </div>
                  <div className="p-3 bg-green-100 rounded-xl">
                    <InformationCircleIcon className="w-6 h-6 text-green-600" />
                  </div>
                </div>
              </div>
            )}
          </div>
        )}

        {/* Add Weight Form */}
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">
            {editingId ? "Edit Weight Record" : "Add New Weight Record"}
          </h2>
          <form onSubmit={handleAdd} className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Date
              </label>
              <input
                type="date"
                name="date"
                value={form.date}
                onChange={handleChange}
                className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Weight (kg)
              </label>
              <input
                type="number"
                step="0.1"
                min="0.1"
                name="weight"
                value={form.weight}
                onChange={handleChange}
                placeholder="e.g. 12.5"
                className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Notes (Optional)
              </label>
              <input
                type="text"
                name="notes"
                value={form.notes}
                onChange={handleChange}
                placeholder="e.g. After meal"
                className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              />
            </div>
            <div className="flex items-end">
              <button
                type="submit"
                className="w-full bg-indigo-600 text-white py-3 rounded-lg hover:bg-indigo-700 transition-colors flex items-center justify-center shadow-md"
              >
                <PlusIcon className="w-5 h-5 mr-2" />
                {editingId ? "Update" : "Add"} Record
              </button>
            </div>
          </form>
        </div>

        {/* Chart and Time Filter */}
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
          <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <h2 className="text-lg font-semibold text-gray-900">Weight Progress Chart</h2>
            <div className="flex items-center gap-2">
              <span className="text-sm text-gray-700">Time Range:</span>
              <select
                value={timeRange}
                onChange={(e) => setTimeRange(e.target.value)}
                className="p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              >
                <option value="all">All Time</option>
                <option value="3m">Last 3 Months</option>
                <option value="6m">Last 6 Months</option>
                <option value="1y">Last Year</option>
              </select>
            </div>
          </div>
          
          {filteredWeights.length === 0 ? (
            <div className="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
              <p className="text-gray-500">
                No weight records yet. Add your pet's first record above.
              </p>
            </div>
          ) : (
            <div className="h-96">
              <Line data={chartData} options={chartOptions} />
            </div>
          )}
        </div>

        {/* Weight Goal Setting */}
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">Weight Goal</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <p className="text-sm text-gray-600 mb-3">
                Set a target weight for {pet?.name}. This helps track progress toward health goals.
              </p>
              <form onSubmit={handleGoalSubmit} className="flex gap-2">
                <input
                  type="number"
                  step="0.1"
                  min="0.1"
                  value={goalWeight}
                  onChange={(e) => setGoalWeight(e.target.value)}
                  placeholder="Target weight in kg"
                  className="flex-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                />
                <button
                  type="submit"
                  className="px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                >
                  Set Goal
                </button>
              </form>
            </div>
            <div className="bg-gray-50 p-4 rounded-lg">
              <p className="text-sm font-medium text-gray-700">Current Target</p>
              <p className="text-xl font-bold text-indigo-600 mt-1">
                {pet?.targetWeight ? `${pet.targetWeight} kg` : "Not set"}
              </p>
            </div>
          </div>
        </div>

        {/* Weight Records List */}
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
          <div className="flex justify-between items-center mb-6">
            <h2 className="text-lg font-semibold text-gray-900">Weight History</h2>
            {/* <span className="text-sm text-gray-500"> */}
                          <span className="text-sm text-gray-500">
              {filteredWeights.length} record(s)
            </span>
          </div>

          {filteredWeights.length === 0 ? (
            <p className="text-gray-500 text-sm">No weight records yet.</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="min-w-full border border-gray-200 rounded-lg overflow-hidden">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-4 py-2 text-left text-sm font-medium text-gray-700 border-b">Date</th>
                    <th className="px-4 py-2 text-left text-sm font-medium text-gray-700 border-b">Weight (kg)</th>
                    <th className="px-4 py-2 text-left text-sm font-medium text-gray-700 border-b">Notes</th>
                    <th className="px-4 py-2 text-left text-sm font-medium text-gray-700 border-b">Actions</th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {filteredWeights.map((w) => (
                    <tr key={w.id}>
                      <td className="px-4 py-2 text-sm text-gray-700">
                        {new Date(w.date).toLocaleDateString()}
                      </td>
                      <td className="px-4 py-2 text-sm font-semibold text-gray-900">
                        {w.weight} kg
                      </td>
                      <td className="px-4 py-2 text-sm text-gray-600">
                        {w.notes || "-"}
                      </td>
                      <td className="px-4 py-2 flex gap-2">
                        <button
                          onClick={() => handleEdit(w)}
                          className="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg"
                          title="Edit"
                        >
                          <PencilIcon className="w-5 h-5" />
                        </button>
                        <button
                          onClick={() => handleDelete(w.id)}
                          className="p-2 text-red-600 hover:bg-red-50 rounded-lg"
                          title="Delete"
                        >
                          <TrashIcon className="w-5 h-5" />
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default PetWeightMonitoring;
