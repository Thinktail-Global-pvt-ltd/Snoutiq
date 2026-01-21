import { useEffect, useRef, useState } from 'react';
import { createEcho, REVERB_CONFIG } from '../lib/echo';
import { apiPost, apiBaseUrl } from '../lib/api';
import Pusher from 'pusher-js';

const cardStyle = {
  border: '1px solid #e2e8f0',
  borderRadius: 12,
  padding: '16px',
  marginBottom: '12px',
  background: '#fff',
  boxShadow: '0 10px 30px rgba(0,0,0,0.05)',
};

const buttonStyle = (primary = false) => ({
  padding: '10px 14px',
  borderRadius: 10,
  border: 'none',
  cursor: 'pointer',
  fontWeight: 700,
  background: primary ? '#2563eb' : '#6b7280',
  color: '#fff',
  width: '100%',
});

const inputStyle = {
  padding: '10px 12px',
  borderRadius: 10,
  border: '1px solid #d4d4d8',
  width: '100%',
};

export default function PatientCallTest() {
  const [patientId, setPatientId] = useState(1);
  const [callId, setCallId] = useState('');
  const [callStatus, setCallStatus] = useState('idle');
  const [connected, setConnected] = useState(false);
  const [subscribed, setSubscribed] = useState(false);
  const [logs, setLogs] = useState([]);

  const echoRef = useRef(null);
  const channelRef = useRef(null);

  const log = (msg, payload = null) => {
    const entry = `${new Date().toISOString()} — ${msg}${payload ? ` | ${payload}` : ''}`;
    setLogs((prev) => [entry, ...prev].slice(0, 200));
  };

  const renderConfig = () => {
    return (
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit,minmax(180px,1fr))', gap: 8, marginTop: 8 }}>
        <span style={{ padding: '6px 10px', borderRadius: 8, background: '#e0f2fe' }}>
          host: {REVERB_CONFIG.host}
        </span>
        <span style={{ padding: '6px 10px', borderRadius: 8, background: '#e0f2fe' }}>
          port: {REVERB_CONFIG.port}
        </span>
        <span style={{ padding: '6px 10px', borderRadius: 8, background: '#e0f2fe' }}>
          scheme: {REVERB_CONFIG.scheme}
        </span>
        <span style={{ padding: '6px 10px', borderRadius: 8, background: '#e0f2fe' }}>
          path: {REVERB_CONFIG.path}
        </span>
        <span style={{ padding: '6px 10px', borderRadius: 8, background: '#e0f2fe' }}>
          key: {(REVERB_CONFIG.key || '').slice(0, 12)}...
        </span>
      </div>
    );
  };

  const cleanup = () => {
    if (channelRef.current) {
      channelRef.current.stopListening('.CallStatusUpdated');
      channelRef.current = null;
    }
    if (echoRef.current) {
      echoRef.current.disconnect();
      echoRef.current = null;
    }
    setConnected(false);
    setSubscribed(false);
  };

  const connectAndListen = () => {
    cleanup();
    const echo = createEcho();
    echoRef.current = echo;

    const pusherConn = echo.connector?.pusher?.connection;
    if (pusherConn?.bind) {
      pusherConn.bind('state_change', (states) =>
        log('pusher state', JSON.stringify(states))
      );
    }
    const conn = echo.connector?.pusher?.connection;
    if (conn?.bind) {
      conn.bind('connected', () => setConnected(true));
      conn.bind('disconnected', () => setConnected(false));
      conn.bind('error', (err) => log('echo error', JSON.stringify(err)));
    }

    const chan = echo.channel(`patient.${patientId}`);
    channelRef.current = chan;
    chan
      .listen('.CallStatusUpdated', (e) => {
        log('CallStatusUpdated', JSON.stringify(e));
        if (e?.call_id) setCallId(e.call_id);
        if (e?.status) setCallStatus(e.status);
      })
      .subscribed(() => {
        setSubscribed(true);
        log(`subscribed patient.${patientId}`);
      })
      .error((err) => {
        setSubscribed(false);
        log('channel error', JSON.stringify(err));
      });
  };

  const requestCall = async () => {
    try {
      const data = await apiPost('/api/calls/request', {
        patient_id: Number(patientId),
      });
      log('request ok', JSON.stringify(data));
      if (data?.call_id) {
        setCallId(data.call_id);
        setCallStatus(data.status || 'ringing');
      }
    } catch (error) {
      log('request failed', error.message);
    }
  };

  const cancelCall = async () => {
    if (!callId) {
      log('cancel', 'no call_id');
      return;
    }
    try {
      const data = await apiPost(`/api/calls/${callId}/cancel`);
      log('cancel ok', JSON.stringify(data));
    } catch (error) {
      log('cancel failed', error.message);
    }
  };

  useEffect(() => {
    return () => cleanup();
  }, []);

  return (
    <div style={{ padding: 20, maxWidth: 800, margin: '0 auto' }}>
      <h1 style={{ fontSize: 26, marginBottom: 8 }}>Patient Call Test</h1>
      <p style={{ color: '#4b5563', marginBottom: 16 }}>
        Backend: {apiBaseUrl()} — Subscribe to <code>patient.{'{id}'}</code> for <code>.CallStatusUpdated</code>.
      </p>

      <div style={cardStyle}>
        <div style={{ display: 'grid', gap: 12, gridTemplateColumns: '1fr 1fr' }}>
          <div>
            <label style={{ fontWeight: 700, marginBottom: 4, display: 'block' }}>Patient ID</label>
            <input
              style={inputStyle}
              type="number"
              value={patientId}
              onChange={(e) => setPatientId(e.target.value)}
            />
          </div>
          <div>
            <label style={{ fontWeight: 700, marginBottom: 4, display: 'block' }}>Call ID</label>
            <input style={inputStyle} value={callId} onChange={(e) => setCallId(e.target.value)} />
          </div>
        </div>
        <div style={{ display: 'grid', gap: 10, gridTemplateColumns: '1fr 1fr', marginTop: 12 }}>
          <button style={buttonStyle(true)} onClick={connectAndListen}>
            Connect &amp; Listen
          </button>
          <button style={buttonStyle(false)} onClick={requestCall}>
            Request Call
          </button>
          <button style={buttonStyle(false)} onClick={cancelCall} disabled={!callId}>
            Cancel Call
          </button>
        </div>
        <div style={{ marginTop: 12, display: 'flex', gap: 8, flexWrap: 'wrap' }}>
          <span style={{ padding: '6px 10px', borderRadius: 8, background: connected ? '#dcfce7' : '#fee2e2' }}>
            Echo: {connected ? 'connected' : 'disconnected'}
          </span>
          <span style={{ padding: '6px 10px', borderRadius: 8, background: subscribed ? '#dcfce7' : '#fee2e2' }}>
            Channel: {subscribed ? `patient.${patientId}` : 'not subscribed'}
          </span>
          <span style={{ padding: '6px 10px', borderRadius: 8, background: '#e0f2fe' }}>
            Status: {callStatus}
          </span>
        </div>
        <div style={{ marginTop: 8 }}>
          <strong>WS Config</strong>
          {renderConfig()}
        </div>
      </div>

      <div style={cardStyle}>
        <h3 style={{ marginTop: 0 }}>Event Log</h3>
        <div
          style={{
            border: '1px solid #e5e7eb',
            borderRadius: 10,
            padding: 10,
            maxHeight: 240,
            overflow: 'auto',
            background: '#0f172a',
            color: '#e5e7eb',
            fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace',
            fontSize: 13,
          }}
        >
          {logs.length === 0 ? <div>waiting...</div> : logs.map((l, idx) => <div key={idx}>{l}</div>)}
        </div>
      </div>
    </div>
  );
}
