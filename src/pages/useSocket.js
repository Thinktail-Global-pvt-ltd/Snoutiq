import { useEffect, useState } from 'react';
import { socket } from '../socket';

export const useSocket = () => {
  const [isConnected, setIsConnected] = useState(socket.connected);
  const [socketError, setSocketError] = useState(null);

  useEffect(() => {
    const onConnect = () => {
      setIsConnected(true);
      setSocketError(null);
    };

    const onDisconnect = () => {
      setIsConnected(false);
    };

    const onError = (error) => {
      setSocketError(error.message);
    };

    socket.on('connect', onConnect);
    socket.on('disconnect', onDisconnect);
    socket.on('connect_error', onError);

    return () => {
      socket.off('connect', onConnect);
      socket.off('disconnect', onDisconnect);
      socket.off('connect_error', onError);
    };
  }, []);

  return { socket, isConnected, socketError };
};
