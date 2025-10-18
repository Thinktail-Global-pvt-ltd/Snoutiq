import { useCallback, useEffect, useRef, useState } from "react";
import { useAuth } from "../auth/AuthContext";
import axios from "axios";
import { useNavigate } from "react-router-dom";
import toast from "react-hot-toast";

// Design System Constants
const DESIGN = {
  COLORS: {
    primary: '#667eea',
    secondary: '#764ba2',
    white: '#FFFFFF',
    black: '#000000',
    gray50: '#F9FAFB',
    gray100: '#F3F4F6',
    gray200: '#E5E7EB',
    gray300: '#D1D5DB',
    gray400: '#9CA3AF',
    gray500: '#6B7280',
    gray600: '#4B5563',
    gray700: '#374151',
    gray800: '#1F2937',
    gray900: '#111827',
    success: '#10B981',
    warning: '#F59E0B',
    error: '#EF4444',
    info: '#3B82F6',
    background: '#F0F4FF',
    surface: '#FFFFFF',
    overlay: 'rgba(0, 0, 0, 0.5)',
  },
  GRADIENTS: {
    primary: ['#667eea', '#764ba2'],
    background: ['#F8F9FA', '#E5E7EB'],
  },
};

const CACHE_KEYS = {
  USER_PROFILE: "user_profile_cache",
  PETS_DATA: "pets_data_cache",
  STATS_DATA: "stats_data_cache",
  DOG_BREEDS: "dog_breeds_cache",
};

const CACHE_DURATION = 10 * 60 * 1000;

const ProfileScreen = () => {
  const [userProfile, setUserProfile] = useState(null);
  const [loading, setLoading] = useState(true);
  const [petsLoading, setPetsLoading] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const { logout, user, token } = useAuth();
  const navigate = useNavigate();
  

  const [stats, setStats] = useState([
    {
      label: "Registered Pets",
      value: "—",
      icon: "🐾",
      description: "Your furry family members",
    },
    {
      label: "Completed Visits",
      value: "—",
      icon: "🩺",
      description: "Vet appointments completed",
    },
    {
      label: "Active Plans",
      value: "—",
      icon: "📋",
      description: "Health or grooming plans",
    },
    {
      label: "Total Rewards",
      value: "—",
      icon: "🏆",
      description: "Points earned so far",
    },
  ]);

  const [selectedPet, setSelectedPet] = useState(null);
  const [editModalVisible, setEditModalVisible] = useState(false);
  const [petFormData, setPetFormData] = useState({
    name: "",
    breed: "",
    age: "",
    gender: "",
    weight: "",
    petType: "",
  });

  const [breedModalVisible, setBreedModalVisible] = useState(false);
  const [breedSearch, setBreedSearch] = useState("");
  const [dogBreeds, setDogBreeds] = useState([]);
  const [catBreeds] = useState([
    { label: "American Shorthair", value: "american_shorthair" },
    { label: "Domestic Shorthair", value: "domestic_shorthair" },
    { label: "Siamese", value: "siamese" },
    { label: "Persian", value: "persian" },
    { label: "Maine Coon", value: "maine_coon" },
    { label: "Bengal", value: "bengal" },
    { label: "Ragdoll", value: "ragdoll" },
    { label: "Sphynx", value: "sphynx" },
    { label: "Mixed Breed", value: "mixed_breed" },
    { label: "Other", value: "other" },
  ]);
  const [loadingBreeds, setLoadingBreeds] = useState(false);

  // Cache implementation
  const cacheData = async (key, data) => {
    try {
      localStorage.setItem(key, JSON.stringify({ data, timestamp: Date.now() }));
    } catch (error) {
      console.error("Cache save error:", error);
    }
  };

  const getCachedData = async (key, forceRefresh = false) => {
    try {
      if (forceRefresh) return null;
      const cached = localStorage.getItem(key);
      if (cached) {
        const { data, timestamp } = JSON.parse(cached);
        if (Date.now() - timestamp < CACHE_DURATION) return data;
      }
    } catch (error) {
      console.error("Cache read error:", error);
    }
    return null;
  };

  const clearCache = async (key) => {
    try {
      localStorage.removeItem(key);
    } catch (error) {
      console.error("Cache clear error:", error);
    }
  };

  const clearAllProfileCache = async () => {
    try {
      Object.values(CACHE_KEYS).forEach(key => clearCache(key));
    } catch (error) {
      console.error("Error clearing cache:", error);
    }
  };

  const fetchDogBreeds = async () => {
    setLoadingBreeds(true);
    try {
      const cachedBreeds = await getCachedData(CACHE_KEYS.DOG_BREEDS);
      if (cachedBreeds) {
        setDogBreeds(cachedBreeds);
        return;
      }

      const response = await axios.get("https://snoutiq.com/backend/api/dog-breeds/all", {
        timeout: 10000,
      });

      if (response.data.status === "success" && response.data.breeds) {
        const breeds = [];
        Object.keys(response.data.breeds).forEach((breedKey) => {
          const subBreeds = response.data.breeds[breedKey];
          if (subBreeds.length === 0) {
            breeds.push({
              label: breedKey.charAt(0).toUpperCase() + breedKey.slice(1),
              value: breedKey,
            });
          } else {
            subBreeds.forEach((subBreed) => {
              breeds.push({
                label: `${subBreed.charAt(0).toUpperCase() + subBreed.slice(1)} ${breedKey.charAt(0).toUpperCase() + breedKey.slice(1)}`,
                value: `${breedKey}/${subBreed}`,
              });
            });
          }
        });
        breeds.sort((a, b) => a.label.localeCompare(b.label));
        breeds.push(
          { label: "Mixed Breed", value: "mixed_breed" },
          { label: "Other", value: "other" }
        );
        setDogBreeds(breeds);
        await cacheData(CACHE_KEYS.DOG_BREEDS, breeds);
        toast.success("Dog breeds loaded successfully!");
      }
    } catch (error) {
      console.error("Error fetching breeds:", error);
      toast.error("Failed to load dog breeds. Using default options.");
    } finally {
      setLoadingBreeds(false);
    }
  };

  const fetchPetsFromAPI = async (userId, forceRefresh = false) => {
    try {
      setPetsLoading(true);
      if (!forceRefresh) {
        const cachedPets = await getCachedData(CACHE_KEYS.PETS_DATA);
        if (cachedPets) return cachedPets;
      }

      const response = await axios.get(
        `https://snoutiq.com/backend/api/users/${userId}/pets`,
        {
          timeout: 10000,
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token}`,
          },
        }
      );

      if (response.data.status === "success" && Array.isArray(response.data.data)) {
        const transformedPets = response.data.data.map((pet) => ({
          id: pet.id,
          name: pet.name || "Unknown Pet",
          age: pet.pet_age || 0,
          gender: pet.pet_gender || "",
          breed: pet.breed || "Pet",
          avatar: pet.pet_doc1,
          petType: pet.breed?.toLowerCase().includes("cat") ? "cat" : "dog",
          weight: pet.weight || "",
        }));

        await cacheData(CACHE_KEYS.PETS_DATA, transformedPets);
        return transformedPets;
      }
      return [];
    } catch (error) {
      console.error("Error fetching pets:", error);
      toast.error("Failed to load pets data");
      if (!forceRefresh) {
        const cachedPets = await getCachedData(CACHE_KEYS.PETS_DATA);
        return cachedPets || [];
      }
      return [];
    } finally {
      setPetsLoading(false);
    }
  };

  const fetchUserData = async (forceRefresh = false) => {
    try {
      setLoading(true);
      if (forceRefresh) await clearAllProfileCache();

      if (!forceRefresh) {
        const cachedProfile = await getCachedData(CACHE_KEYS.USER_PROFILE);
        if (cachedProfile) {
          setUserProfile(cachedProfile);
          const cachedStats = await getCachedData(CACHE_KEYS.STATS_DATA);
          if (cachedStats) setStats(cachedStats);
          setLoading(false);
          return;
        }
      }

      let petsArray = [];
      let joinDate = new Date().toLocaleDateString("en-US", { month: "long", year: "numeric" });
      let daysActive = "0";

      if (user) {
        const createdAt = new Date(user.created_at || Date.now());
        joinDate = createdAt.toLocaleDateString("en-US", { month: "long", year: "numeric" });
        daysActive = Math.round((Date.now() - createdAt.getTime()) / (1000 * 60 * 60 * 24)).toString();

        if (user.id) {
          const apiPets = await fetchPetsFromAPI(user.id, forceRefresh);
          if (apiPets && apiPets.length > 0) petsArray = apiPets;
        }

        const profileData = {
          name: user.name || "User",
          email: user.email || "user@example.com",
          phone: user.phone ? `+91 ${user.phone}` : "+91 0000000000",
          location: user.latitude && user.longitude ? `${user.latitude}, ${user.longitude}` : "Haryana, Gurgaon",
          joinDate,
          avatar: "",
          pets: petsArray,
        };

        setUserProfile(profileData);
        const updatedStats = [
          { ...stats[0], value: petsArray.length.toString() },
          { ...stats[1]},
          { ...stats[2] },
        ];
        setStats(updatedStats);

        await cacheData(CACHE_KEYS.USER_PROFILE, profileData);
        await cacheData(CACHE_KEYS.STATS_DATA, updatedStats);
        
        if (forceRefresh) {
          toast.success("Profile data refreshed!");
        }
      }
    } catch (error) {
      console.error("Error fetching user data:", error);
      toast.error("Failed to load profile data");
    } finally {
      setLoading(false);
    }
  };

  const handleDeletePet = async (petId) => {
    const petName = userProfile?.pets?.find(pet => pet.id === petId)?.name || "this pet";
    
    toast((t) => (
      <div className="flex flex-col space-y-3">
        <p className="font-semibold text-gray-900">Delete Pet?</p>
        <p className="text-gray-600">Are you sure you want to delete {petName}? This action cannot be undone.</p>
        <div className="flex space-x-2 justify-end">
          <button
            onClick={() => toast.dismiss(t.id)}
            className="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium transition-colors"
          >
            Cancel
          </button>
          <button
            onClick={async () => {
              toast.dismiss(t.id);
              await performDeletePet(petId, petName);
            }}
            className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium transition-colors"
          >
            Delete
          </button>
        </div>
      </div>
    ), {
      duration: 10000,
      position: 'top-center',
    });
  };

  const performDeletePet = async (petId, petName) => {
    const deleteToast = toast.loading(`Deleting ${petName}...`);
    
    setPetsLoading(true);
    try {
      await axios.delete(`https://snoutiq.com/backend/api/pets/${petId}`, {
        headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json" },
      });
      await fetchUserData(true);
      toast.dismiss(deleteToast);
      toast.success(`🐾 ${petName} has been removed from your family`, {
        duration: 4000,
        icon: '🗑️'
      });
    } catch (error) {
      console.error("Error deleting pet:", error);
      toast.dismiss(deleteToast);
      toast.error(`Failed to delete ${petName}. Please try again.`, {
        duration: 4000
      });
    } finally {
      setPetsLoading(false);
    }
  };

  const handleEditPet = (pet) => {
    setSelectedPet(pet);
    setPetFormData({
      name: pet.name,
      breed: pet.breed,
      age: pet.age?.toString(),
      gender: pet.gender,
      weight: pet.weight?.toString(),
      petType: pet.petType || (pet.breed?.toLowerCase().includes("cat") ? "cat" : "dog"),
    });
    setEditModalVisible(true);
    toast.success(`Editing ${pet.name}`, { icon: '✏️' });
  };

  const handleUpdatePet = async () => {
    if (!selectedPet) return;
    
    const updateToast = toast.loading(`Updating ${selectedPet.name}...`);
    setPetsLoading(true);
    
    try {
      await axios.put(
        `https://snoutiq.com/backend/api/pets/${selectedPet.id}`,
        {
          name: petFormData.name,
          breed: petFormData.breed,
          pet_age: petFormData.age ? parseFloat(petFormData.age) : null,
          pet_gender: petFormData.gender,
          weight: petFormData.weight ? parseFloat(petFormData.weight) : null,
        },
        {
          headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json" },
        }
      );
      await fetchUserData(true);
      setEditModalVisible(false);
      toast.dismiss(updateToast);
      toast.success(`🎉 ${petFormData.name} updated successfully!`, {
        duration: 3000,
        icon: '✅'
      });
    } catch (error) {
      console.error("Error updating pet:", error);
      toast.dismiss(updateToast);
      toast.error("Failed to update pet. Please try again.", {
        duration: 4000
      });
    } finally {
      setPetsLoading(false);
    }
  };

  const handleAddPet = () => {
    navigate("/user-dashboard/add-pet", { state: { user: user.id, token } });
    toast("Let's add a new furry friend! 🐾", { icon: '🎉' });
  };

  useEffect(() => {
    const checkForNewPet = async () => {
      const expectingNewPet = localStorage.getItem('expecting_new_pet');
      if (expectingNewPet === 'true') {
        await fetchUserData(true);
        localStorage.removeItem('expecting_new_pet');
        toast.success("New pet added to your family! 🎉");
      } else {
        await fetchUserData(false);
      }
      await fetchDogBreeds();
    };
    checkForNewPet();
  }, [user, token]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    const refreshToast = toast.loading("Refreshing profile...");
    try {
      await fetchUserData(true);
      toast.dismiss(refreshToast);
      toast.success("Profile refreshed!", { duration: 2000 });
    } catch (error) {
      toast.dismiss(refreshToast);
      toast.error("Failed to refresh profile");
    } finally {
      setRefreshing(false);
    }
  }, []);

  // Shimmer Loader Component
  const ShimmerLoader = () => (
    <div className="p-6 space-y-6">
      {/* Profile Shimmer */}
      <div className="animate-pulse">
        <div className="flex items-center space-x-4 mb-6">
          <div className="w-20 h-20 bg-gray-200 rounded-full"></div>
          <div className="flex-1 space-y-3">
            <div className="h-6 bg-gray-200 rounded w-3/4"></div>
            <div className="h-4 bg-gray-200 rounded w-1/2"></div>
          </div>
        </div>
        
        {/* Stats Shimmer */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          {[1, 2, 3, 4].map((item) => (
            <div key={item} className="bg-white rounded-xl p-4 shadow-lg">
              <div className="flex flex-col items-center space-y-2">
                <div className="w-12 h-12 bg-gray-200 rounded-full"></div>
                <div className="h-4 bg-gray-200 rounded w-3/4"></div>
                <div className="h-3 bg-gray-200 rounded w-1/2"></div>
              </div>
            </div>
          ))}
        </div>
        
        {/* Pets Shimmer */}
        <div className="space-y-3">
          {[1, 2].map((item) => (
            <div key={item} className="bg-white rounded-xl p-4 shadow-lg animate-pulse">
              <div className="flex items-center space-x-4">
                <div className="w-16 h-16 bg-gray-200 rounded-full"></div>
                <div className="flex-1 space-y-2">
                  <div className="h-5 bg-gray-200 rounded w-1/3"></div>
                  <div className="h-4 bg-gray-200 rounded w-2/3"></div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );

  if (loading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-purple-50">
        <div className="bg-gradient-to-r from-purple-600 to-blue-600 text-white p-6">
          <h1 className="text-2xl font-bold">My Profile</h1>
        </div>
        <ShimmerLoader />
      </div>
    );
  }

  const getBreedOptions = () => {
    const breeds = petFormData.petType === "dog" ? dogBreeds : catBreeds;
    if (breedSearch) {
      return breeds.filter((breed) => breed.label.toLowerCase().includes(breedSearch.toLowerCase()));
    }
    return breeds;
  };

  const renderBreedItem = (item) => (
    <button
      key={item.value}
      className="flex justify-between items-center w-full p-4 border-b border-gray-100 hover:bg-gray-50 transition-colors"
      onClick={() => {
        setPetFormData((prev) => ({ ...prev, breed: item.value }));
        setBreedModalVisible(false);
        setBreedSearch("");
        toast.success(`Selected: ${item.label}`, { duration: 2000 });
      }}
    >
      <span className="text-gray-900 font-medium">{item.label}</span>
      {petFormData.breed === item.value && (
        <span className="text-green-500 text-lg">✓</span>
      )}
    </button>
  );

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-purple-50">
      {/* Header */}
      <div className="bg-gradient-to-r from-purple-600 to-blue-600 text-white p-2">
        <div className="max-w-6xl mx-auto">
          <div className="flex items-center justify-between">
            <h1 className="text-2xl font-bold">My Profile</h1>
            <button 
              onClick={onRefresh}
              disabled={refreshing}
              className="p-2 bg-white bg-opacity-20 rounded-lg hover:bg-opacity-30 transition-colors disabled:opacity-50"
              title="Refresh"
            >
              <span className={`text-white ${refreshing ? 'animate-spin' : ''}`}>
                🔄
              </span>
            </button>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-6xl mx-auto p-4 space-y-6">
        {/* Profile Card */}
        <div className="bg-white rounded-2xl shadow-lg p-6">
          <div className="flex flex-col md:flex-row items-start space-y-4 md:space-y-0 md:space-x-6">
            {/* Avatar */}
            <div className="relative">
              {userProfile?.avatar ? (
                <img 
                  src={userProfile.avatar} 
                  alt="Profile" 
                  className="w-20 h-20 rounded-full border-4 border-white shadow-lg"
                />
              ) : (
                <div className="w-20 h-20 rounded-full bg-gradient-to-r from-purple-500 to-blue-500 flex items-center justify-center border-4 border-white shadow-lg">
                  <span className="text-white text-xl font-bold">
                    {userProfile?.name?.charAt(0).toUpperCase() || "U"}
                  </span>
                </div>
              )}
            </div>

            {/* Profile Info */}
            <div className="flex-1 space-y-3">
              <div>
                <h2 className="text-2xl font-bold text-gray-900">{userProfile?.name || "User"}</h2>
                <p className="text-gray-600">{userProfile?.email}</p>
              </div>
              
              <div className="space-y-2">
                <div className="flex items-center space-x-2">
                  <span className="text-gray-500">📍</span>
                  <span className="text-gray-600 text-sm">{userProfile?.location}</span>
                </div>
                <div className="flex items-center space-x-2">
                  <span className="text-gray-500">📅</span>
                  <span className="text-gray-600 text-sm">Joined {userProfile?.joinDate}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Stats Grid */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {stats.map((stat, index) => (
            <div
              key={index}
              className="bg-white rounded-xl p-4 shadow-lg text-center hover:shadow-xl transition-shadow cursor-pointer"
              onClick={() => toast(stat.description, { icon: stat.icon })}
            >
              <div className="text-3xl mb-2">{stat.icon}</div>
              <div className="text-2xl font-bold text-gray-900">{stat.value}</div>
              <div className="text-sm text-gray-600 font-medium">{stat.label}</div>
              <div className="text-xs text-gray-500 mt-1">{stat.description}</div>
            </div>
          ))}
        </div>

        {/* My Pets Section */}
        <div className="bg-white rounded-2xl shadow-lg p-6">
          <div className="flex justify-between items-center mb-6">
            <div className="flex items-center space-x-3">
              <span className="text-2xl">🐾</span>
              <h3 className="text-xl font-bold text-gray-900">My Pets</h3>
              {userProfile?.pets && userProfile.pets.length > 0 && (
                <span className="bg-purple-100 text-purple-800 text-sm font-medium px-2 py-1 rounded-full">
                  {userProfile.pets.length} pets
                </span>
              )}
            </div>
            <button
              onClick={handleAddPet}
              className="bg-purple-600 hover:bg-purple-700 text-white w-12 h-12 rounded-full flex items-center justify-center shadow-lg transition-colors"
              title="Add New Pet"
            >
              <span className="text-xl">+</span>
            </button>
          </div>

          {petsLoading ? (
            <div className="flex justify-center py-8">
              <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
            </div>
          ) : userProfile?.pets && userProfile.pets.length > 0 ? (
            <div className="space-y-4">
              {userProfile.pets.map((pet) => (
                <div key={pet.id} className="flex justify-between items-center bg-gray-50 rounded-xl p-4 hover:shadow-md transition-shadow">
                  <div className="flex items-center space-x-4 flex-1">
                    {pet.avatar ? (
                      <img 
                        src={pet.avatar} 
                        alt={pet.name}
                        className="w-16 h-16 rounded-full object-cover"
                      />
                    ) : (
                      <div className="w-16 h-16 rounded-full bg-gradient-to-r from-purple-500 to-blue-500 flex items-center justify-center">
                        <span className="text-white font-bold text-lg">
                          {pet.name?.charAt(0).toUpperCase() || "P"}
                        </span>
                      </div>
                    )}
                    <div className="flex-1">
                      <h4 className="font-semibold text-gray-900 text-lg">{pet.name}</h4>
                      <p className="text-gray-600 text-sm">
                        {pet.breed} • {pet.age} yrs • {pet.gender}
                        {pet.weight && ` • ${pet.weight} kg`}
                      </p>
                    </div>
                  </div>

                  <div className="flex space-x-2">
                    <button
                      onClick={() => handleEditPet(pet)}
                      className="p-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                      title="Edit Pet"
                    >
                      <span className="text-purple-600">✏️</span>
                    </button>
                    <button
                      onClick={() => handleDeletePet(pet.id)}
                      className="p-2 bg-red-50 hover:bg-red-100 rounded-lg transition-colors"
                      title="Delete Pet"
                    >
                      <span className="text-red-600">🗑️</span>
                    </button>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <button
              onClick={handleAddPet}
              className="w-full py-12 border-2 border-dashed border-gray-300 rounded-xl hover:border-purple-400 hover:bg-purple-50 transition-all flex flex-col items-center justify-center space-y-3"
            >
              <span className="text-6xl text-gray-400">🐾</span>
              <div className="text-center">
                <h4 className="text-lg font-semibold text-gray-900">Add Your First Pet</h4>
                <p className="text-gray-600">Tap to get started</p>
              </div>
            </button>
          )}
        </div>

        {/* Refresh Indicator */}
        {refreshing && (
          <div className="fixed bottom-4 right-4 bg-purple-600 text-white px-4 py-2 rounded-full shadow-lg flex items-center gap-2">
            <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
            <span className="text-sm font-medium">Refreshing...</span>
          </div>
        )}
      </div>

      {/* Edit Pet Modal */}
      {editModalVisible && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-end justify-center z-50">
          <div className="bg-white rounded-t-3xl w-full max-w-2xl max-h-[85vh] overflow-hidden">
            {/* Modal Header */}
            <div className="flex justify-between items-center p-6 border-b border-gray-200">
              <h3 className="text-xl font-bold text-gray-900">Edit Pet</h3>
              <button
                onClick={() => setEditModalVisible(false)}
                className="text-gray-500 hover:text-gray-700 transition-colors"
              >
                <span className="text-2xl">×</span>
              </button>
            </div>

            {/* Modal Body */}
            <div className="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
              {/* Pet Name */}
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">
                  Pet Name
                </label>
                <input
                  type="text"
                  value={petFormData.name}
                  onChange={(e) => setPetFormData(prev => ({ ...prev, name: e.target.value }))}
                  placeholder="Enter pet's name"
                  className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                />
              </div>

              {/* Pet Type */}
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">
                  Pet Type
                </label>
                <div className="flex space-x-2">
                  {["dog", "cat"].map((type) => (
                    <button
                      key={type}
                      onClick={() => setPetFormData(prev => ({ ...prev, petType: type, breed: "" }))}
                      className={`flex-1 py-3 px-4 rounded-lg border-2 transition-colors ${
                        petFormData.petType === type
                          ? "bg-purple-600 text-white border-purple-600"
                          : "bg-white text-gray-700 border-gray-300 hover:border-purple-400"
                      }`}
                    >
                      {type.charAt(0).toUpperCase() + type.slice(1)}
                    </button>
                  ))}
                </div>
              </div>

              {/* Breed */}
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">
                  Breed
                </label>
                <button
                  onClick={() => petFormData.petType && setBreedModalVisible(true)}
                  disabled={!petFormData.petType || loadingBreeds}
                  className="w-full p-3 border border-gray-300 rounded-lg flex justify-between items-center hover:border-purple-400 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span className={petFormData.breed ? "text-gray-900" : "text-gray-500"}>
                    {petFormData.breed
                      ? (petFormData.petType === "dog" ? dogBreeds : catBreeds).find(b => b.value === petFormData.breed)?.label || petFormData.breed
                      : loadingBreeds ? "Loading..." : "Select breed"}
                  </span>
                  <span className="text-gray-400">▼</span>
                </button>
              </div>

              {/* Age and Weight */}
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-semibold text-gray-700 mb-2">
                    Age (years)
                  </label>
                  <input
                    type="number"
                    value={petFormData.age}
                    onChange={(e) => setPetFormData(prev => ({ ...prev, age: e.target.value }))}
                    placeholder="0"
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                  />
                </div>
                <div>
                  <label className="block text-sm font-semibold text-gray-700 mb-2">
                    Weight (kg)
                  </label>
                  <input
                    type="number"
                    value={petFormData.weight}
                    onChange={(e) => setPetFormData(prev => ({ ...prev, weight: e.target.value }))}
                    placeholder="0.0"
                    step="0.1"
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                  />
                </div>
              </div>

              {/* Gender */}
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">
                  Gender
                </label>
                <div className="flex space-x-2">
                  {["Male", "Female"].map((gender) => (
                    <button
                      key={gender}
                      onClick={() => setPetFormData(prev => ({ ...prev, gender }))}
                      className={`flex-1 py-3 px-4 rounded-lg border-2 transition-colors ${
                        petFormData.gender === gender
                          ? "bg-purple-600 text-white border-purple-600"
                          : "bg-white text-gray-700 border-gray-300 hover:border-purple-400"
                      }`}
                    >
                      {gender}
                    </button>
                  ))}
                </div>
              </div>
            </div>

            {/* Modal Footer */}
            <div className="flex space-x-3 p-6 border-t border-gray-200">
              <button
                onClick={() => setEditModalVisible(false)}
                className="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg transition-colors"
              >
                Cancel
              </button>
              <button
                onClick={handleUpdatePet}
                disabled={petsLoading}
                className="flex-1 py-3 bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white font-semibold rounded-lg transition-colors disabled:opacity-50"
              >
                {petsLoading ? (
                  <div className="flex items-center justify-center space-x-2">
                    <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                    <span>Updating...</span>
                  </div>
                ) : (
                  "Update"
                )}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Breed Selection Modal */}
      {breedModalVisible && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-end justify-center z-50">
          <div className="bg-white rounded-t-3xl w-full max-w-2xl h-[70vh] overflow-hidden">
            {/* Modal Header */}
            <div className="flex justify-between items-center p-6 border-b border-gray-200">
              <h3 className="text-xl font-bold text-gray-900">Select Breed</h3>
              <button
                onClick={() => setBreedModalVisible(false)}
                className="text-gray-500 hover:text-gray-700 transition-colors"
              >
                <span className="text-2xl">×</span>
              </button>
            </div>

            {/* Search */}
            <div className="p-4 border-b border-gray-200">
              <div className="relative">
                <span className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                  🔍
                </span>
                <input
                  type="text"
                  value={breedSearch}
                  onChange={(e) => setBreedSearch(e.target.value)}
                  placeholder="Search breeds..."
                  className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                />
              </div>
            </div>

            {/* Breed List */}
            <div className="h-full overflow-y-auto">
              {getBreedOptions().length > 0 ? (
                getBreedOptions().map(renderBreedItem)
              ) : (
                <div className="text-center py-8 text-gray-500">
                  No breeds found matching your search
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ProfileScreen;