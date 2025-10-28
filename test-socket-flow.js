#!/usr/bin/env node

/**
 * Socket Flow Test Script
 * Tests the complete flow: chat → video call button → ring → accept → payment → connect
 */

const { io } = require('socket.io-client');

// Configuration
const SOCKET_URL = 'http://localhost:4000';
const TEST_DOCTOR_ID = 'test_doctor_123';
const TEST_PATIENT_ID = 'test_patient_456';

// Test state
let doctorSocket = null;
let patientSocket = null;
let callId = null;
let channel = null;

// Colors for console output
const colors = {
  reset: '\x1b[0m',
  bright: '\x1b[1m',
  red: '\x1b[31m',
  green: '\x1b[32m',
  yellow: '\x1b[33m',
  blue: '\x1b[34m',
  magenta: '\x1b[35m',
  cyan: '\x1b[36m'
};

function log(message, color = 'reset') {
  console.log(`${colors[color]}${message}${colors.reset}`);
}

function logStep(step, message) {
  log(`\n[STEP ${step}] ${message}`, 'cyan');
}

function logSuccess(message) {
  log(`✅ ${message}`, 'green');
}

function logError(message) {
  log(`❌ ${message}`, 'red');
}

function logWarning(message) {
  log(`⚠️ ${message}`, 'yellow');
}

// Test functions
async function testSocketConnection() {
  logStep(1, 'Testing Socket Connection');
  
  try {
    doctorSocket = io(SOCKET_URL, {
      path: '/socket.io/',
      transports: ['websocket', 'polling']
    });
    
    patientSocket = io(SOCKET_URL, {
      path: '/socket.io/',
      transports: ['websocket', 'polling']
    });
    
    await new Promise((resolve, reject) => {
      const timeout = setTimeout(() => {
        reject(new Error('Connection timeout'));
      }, 5000);
      
      let connectedCount = 0;
      
      doctorSocket.on('connect', () => {
        logSuccess('Doctor socket connected');
        connectedCount++;
        if (connectedCount === 2) {
          clearTimeout(timeout);
          resolve();
        }
      });
      
      patientSocket.on('connect', () => {
        logSuccess('Patient socket connected');
        connectedCount++;
        if (connectedCount === 2) {
          clearTimeout(timeout);
          resolve();
        }
      });
      
      doctorSocket.on('connect_error', (error) => {
        logError(`Doctor connection error: ${error.message}`);
        reject(error);
      });
      
      patientSocket.on('connect_error', (error) => {
        logError(`Patient connection error: ${error.message}`);
        reject(error);
      });
    });
    
    return true;
  } catch (error) {
    logError(`Socket connection failed: ${error.message}`);
    return false;
  }
}

async function testDoctorJoin() {
  logStep(2, 'Testing Doctor Join');
  
  return new Promise((resolve, reject) => {
    const timeout = setTimeout(() => {
      reject(new Error('Doctor join timeout'));
    }, 5000);
    
    doctorSocket.on('doctor-online', (data) => {
      if (data.doctorId === TEST_DOCTOR_ID) {
        logSuccess(`Doctor ${TEST_DOCTOR_ID} joined successfully`);
        clearTimeout(timeout);
        resolve(true);
      }
    });
    
    doctorSocket.emit('join-doctor', TEST_DOCTOR_ID);
  });
}

async function testPatientJoin() {
  logStep(3, 'Testing Patient Join');
  
  return new Promise((resolve, reject) => {
    const timeout = setTimeout(() => {
      reject(new Error('Patient join timeout'));
    }, 5000);
    
    patientSocket.on('patient-online', (data) => {
      if (data.patientId === TEST_PATIENT_ID) {
        logSuccess(`Patient ${TEST_PATIENT_ID} joined successfully`);
        clearTimeout(timeout);
        resolve(true);
      }
    });
    
    patientSocket.emit('join-patient', TEST_PATIENT_ID);
  });
}

async function testCallRequest() {
  logStep(4, 'Testing Call Request');
  
  callId = `call_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;
  channel = `channel_${callId}`;
  
  return new Promise((resolve, reject) => {
    const timeout = setTimeout(() => {
      reject(new Error('Call request timeout'));
    }, 10000);
    
    // Doctor receives call request
    doctorSocket.on('call-requested', (data) => {
      if (data.callId === callId) {
        logSuccess(`Doctor received call request: ${callId}`);
        clearTimeout(timeout);
        resolve(true);
      }
    });
    
    // Patient sends call request
    patientSocket.emit('call-requested', {
      doctorId: TEST_DOCTOR_ID,
      patientId: TEST_PATIENT_ID,
      channel: channel,
      callId: callId
    });
    
    log(`Patient requesting call with doctor ${TEST_DOCTOR_ID}`);
  });
}

async function testCallAccept() {
  logStep(5, 'Testing Call Accept');
  
  return new Promise((resolve, reject) => {
    const timeout = setTimeout(() => {
      reject(new Error('Call accept timeout'));
    }, 10000);
    
    // Patient receives call accepted
    patientSocket.on('call-accepted', (data) => {
      if (data.callId === callId) {
        logSuccess(`Patient received call accepted: ${callId}`);
        clearTimeout(timeout);
        resolve(true);
      }
    });
    
    // Doctor accepts call
    doctorSocket.emit('call-accepted', {
      callId: callId,
      doctorId: TEST_DOCTOR_ID,
      patientId: TEST_PATIENT_ID,
      channel: channel,
      requiresPayment: true
    });
    
    log(`Doctor accepting call: ${callId}`);
  });
}

async function testPaymentCompletion() {
  logStep(6, 'Testing Payment Completion');
  
  return new Promise((resolve, reject) => {
    const timeout = setTimeout(() => {
      reject(new Error('Payment completion timeout'));
    }, 10000);
    
    // Doctor receives patient paid event
    doctorSocket.on('patient-paid', (data) => {
      if (data.callId === callId) {
        logSuccess(`Doctor received payment completion: ${callId}`);
        clearTimeout(timeout);
        resolve(true);
      }
    });
    
    // Patient completes payment
    patientSocket.emit('payment-completed', {
      callId: callId,
      patientId: TEST_PATIENT_ID,
      doctorId: TEST_DOCTOR_ID,
      channel: channel,
      paymentId: `payment_${Date.now()}`
    });
    
    log(`Patient completing payment for call: ${callId}`);
  });
}

async function testCallStart() {
  logStep(7, 'Testing Call Start');
  
  return new Promise((resolve, reject) => {
    const timeout = setTimeout(() => {
      reject(new Error('Call start timeout'));
    }, 10000);
    
    // Both parties can start the call
    const callStarted = () => {
      logSuccess(`Call started successfully: ${callId}`);
      clearTimeout(timeout);
      resolve(true);
    };
    
    doctorSocket.on('call-ongoing', callStarted);
    patientSocket.on('call-ongoing', callStarted);
    
    // Either party can start the call
    doctorSocket.emit('call-started', {
      callId: callId,
      doctorId: TEST_DOCTOR_ID,
      patientId: TEST_PATIENT_ID,
      channel: channel
    });
    
    log(`Starting video call: ${callId}`);
  });
}

async function cleanup() {
  logStep(8, 'Cleaning Up');
  
  if (doctorSocket) {
    doctorSocket.emit('leave-doctor', TEST_DOCTOR_ID);
    doctorSocket.disconnect();
  }
  
  if (patientSocket) {
    patientSocket.emit('leave-patient', TEST_PATIENT_ID);
    patientSocket.disconnect();
  }
  
  logSuccess('Cleanup completed');
}

// Main test runner
async function runTests() {
  log('🚀 Starting Socket Flow Test', 'bright');
  log('=' * 50, 'blue');
  
  try {
    // Test 1: Socket Connection
    await testSocketConnection();
    
    // Test 2: Doctor Join
    await testDoctorJoin();
    
    // Test 3: Patient Join
    await testPatientJoin();
    
    // Test 4: Call Request
    await testCallRequest();
    
    // Test 5: Call Accept
    await testCallAccept();
    
    // Test 6: Payment Completion
    await testPaymentCompletion();
    
    // Test 7: Call Start
    await testCallStart();
    
    log('\n🎉 All tests passed! Socket flow is working correctly.', 'green');
    
  } catch (error) {
    logError(`Test failed: ${error.message}`);
    process.exit(1);
  } finally {
    await cleanup();
  }
}

// Run tests if this script is executed directly
if (require.main === module) {
  runTests().catch(console.error);
}

module.exports = {
  runTests,
  testSocketConnection,
  testDoctorJoin,
  testPatientJoin,
  testCallRequest,
  testCallAccept,
  testPaymentCompletion,
  testCallStart,
  cleanup
};
