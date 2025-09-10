import React, { useState, useEffect, useContext } from "react";
import axios from "../axios";
import { AuthContext } from "../auth/AuthContext";

const DetailedWeatherWidget = () => {
  const [weather, setWeather] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const { user } = useContext(AuthContext);

  const fetchWeather = async () => {
    try {
      setLoading(true);
      const response = await axios.get(
        `https://snoutiq.com/backend/api/weather/by-coords?lat=${user.latitude}&lon=${user.longitude}`
      );

      if (response.data.status === "success") {
        setWeather(response.data);
        setError(null);
      } else {
        setError("Unable to fetch weather data");
      }
    } catch (err) {
      console.error("Weather fetch error:", err);
      setError("Failed to load weather");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchWeather();

    // Refresh every 30 minutes
    const interval = setInterval(fetchWeather, 30 * 60 * 1000);

    return () => clearInterval(interval);
  }, []);

  const getWeatherIcon = (weatherCondition) => {
    const condition = weatherCondition.toLowerCase();
    if (condition.includes("sun") || condition.includes("clear")) {
      return "☀️";
    } else if (condition.includes("cloud")) {
      return "☁️";
    } else if (condition.includes("rain")) {
      return "🌧️";
    } else if (condition.includes("mist") || condition.includes("fog")) {
      return "🌫️";
    } else if (condition.includes("snow")) {
      return "❄️";
    } else {
      return "🌤️";
    }
  };

  if (loading) {
    return (
      <div className="flex items-center text-sm text-gray-600 bg-blue-50 px-3 py-1 rounded-lg">
        <div className="animate-spin h-4 w-4 border-2 border-blue-500 border-t-transparent rounded-full mr-2"></div>
        Loading weather...
      </div>
    );
  }

  if (error) {
    return (
      <div className="text-sm text-red-600 bg-red-50 px-3 py-1 rounded-lg">
        ⚠️ Weather unavailable
      </div>
    );
  }

  if (!weather) return null;

  return (
    <div className="flex items-center space-x-4 text-sm">
      <div className="bg-blue-50 px-3 py-1 rounded-lg flex items-center">
        <span className="text-lg mr-1">
          {getWeatherIcon(weather.current.weather)}
        </span>
        <span className="font-medium text-blue-700">
          {weather.current.temperatureC}°C
        </span>
        <div className="hidden md:block ml-2 text-blue-600">
          {weather.current.weather} • Feels like {weather.current.feelsLikeC}°C
        </div>
      </div>
    </div>
  );
};

export default DetailedWeatherWidget;
