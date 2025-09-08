import React, { useState } from 'react';

const VetPayment = () => {
  const [walletBalance, setWalletBalance] = useState(12500);
  const [transactions, setTransactions] = useState([
    { id: 1, type: 'credit', amount: 2000, description: 'Video Consultation', date: '2024-01-15', status: 'completed' },
    { id: 2, type: 'debit', amount: 500, description: 'Withdrawal', date: '2024-01-14', status: 'completed' },
    { id: 3, type: 'credit', amount: 1500, description: 'Clinic Visit', date: '2024-01-13', status: 'completed' },
    { id: 4, type: 'credit', amount: 3000, description: 'Emergency Service', date: '2024-01-12', status: 'pending' },
    { id: 5, type: 'debit', amount: 1000, description: 'Equipment Purchase', date: '2024-01-10', status: 'completed' }
  ]);

  const [servicePrices, setServicePrices] = useState({
    videoConsultation: 500,
    clinicVisit: 800,
    emergencyService: 2000,
    homeVisit: 1500,
    groomingService: 700
  });

  const [withdrawAmount, setWithdrawAmount] = useState('');
  const [showWithdrawModal, setShowWithdrawModal] = useState(false);

  const handlePriceChange = (service, newPrice) => {
    setServicePrices(prev => ({
      ...prev,
      [service]: parseInt(newPrice) || 0
    }));
  };

  const handleWithdraw = () => {
    if (withdrawAmount && withdrawAmount > 0 && withdrawAmount <= walletBalance) {
      const newTransaction = {
        id: transactions.length + 1,
        type: 'debit',
        amount: parseInt(withdrawAmount),
        description: 'Withdrawal',
        date: new Date().toISOString().split('T')[0],
        status: 'processing'
      };
      
      setTransactions(prev => [newTransaction, ...prev]);
      setWalletBalance(prev => prev - parseInt(withdrawAmount));
      setWithdrawAmount('');
      setShowWithdrawModal(false);
    }
  };

  const TransactionItem = ({ transaction }) => (
    <div className="flex items-center justify-between p-4 border-b border-gray-100">
      <div className="flex items-center">
        <div className={`w-10 h-10 rounded-full flex items-center justify-center ${
          transaction.type === 'credit' ? 'bg-green-100' : 'bg-red-100'
        }`}>
          <svg className={`w-5 h-5 ${
            transaction.type === 'credit' ? 'text-green-600' : 'text-red-600'
          }`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
            {transaction.type === 'credit' ? (
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            ) : (
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            )}
          </svg>
        </div>
        <div className="ml-4">
          <p className="font-medium text-gray-900">{transaction.description}</p>
          <p className="text-sm text-gray-500">{transaction.date}</p>
        </div>
      </div>
      <div className="text-right">
        <p className={`font-semibold ${
          transaction.type === 'credit' ? 'text-green-600' : 'text-red-600'
        }`}>
          {transaction.type === 'credit' ? '+' : '-'}₹{transaction.amount}
        </p>
        <span className={`text-xs px-2 py-1 rounded-full ${
          transaction.status === 'completed' ? 'bg-green-100 text-green-800' :
          transaction.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
          'bg-blue-100 text-blue-800'
        }`}>
          {transaction.status}
        </span>
      </div>
    </div>
  );

  const PriceInput = ({ label, value, onChange }) => (
    <div className="flex items-center justify-between p-4 border-b border-gray-100">
      <span className="text-gray-700">{label}</span>
      <div className="flex items-center">
        <span className="text-gray-500 mr-2">₹</span>
        <input
          type="number"
          value={value}
          onChange={(e) => onChange(e.target.value)}
          className="w-24 px-3 py-1 border border-gray-300 rounded-md text-right focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
    </div>
  );

  return (
    <div className="max-w-6xl mx-auto p-6">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-2">Payment & Wallet</h1>
        <p className="text-gray-600">Manage your earnings, set service prices, and withdraw funds</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {/* Left Column - Wallet & Transactions */}
        <div className="space-y-6">
          {/* Wallet Balance Card */}
          <div className="bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl p-6 text-white">
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-lg font-semibold">Wallet Balance</h2>
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            
            <div className="mb-6">
              <p className="text-3xl font-bold">₹{walletBalance.toLocaleString()}</p>
              <p className="text-blue-200 text-sm">Available for withdrawal</p>
            </div>

            <button
              onClick={() => setShowWithdrawModal(true)}
              className="w-full bg-white text-blue-600 py-3 rounded-lg font-semibold hover:bg-blue-50 transition-colors"
            >
              Withdraw Funds
            </button>
          </div>

          {/* Transactions History */}
          <div className="bg-white rounded-xl shadow-md">
            <div className="p-6 border-b border-gray-100">
              <h2 className="text-lg font-semibold text-gray-900">Recent Transactions</h2>
            </div>
            <div className="divide-y divide-gray-100">
              {transactions.map(transaction => (
                <TransactionItem key={transaction.id} transaction={transaction} />
              ))}
            </div>
            <div className="p-4 text-center">
              <button className="text-blue-600 hover:text-blue-800 text-sm font-medium">
                View All Transactions
              </button>
            </div>
          </div>
        </div>

        {/* Right Column - Service Pricing */}
        <div className="space-y-6">
          {/* Service Pricing Card */}
          <div className="bg-white rounded-xl shadow-md">
            <div className="p-6 border-b border-gray-100">
              <h2 className="text-lg font-semibold text-gray-900">Service Pricing</h2>
              <p className="text-sm text-gray-600">Set your prices for different services</p>
            </div>
            
            <div className="divide-y divide-gray-100">
              <PriceInput
                label="Video Consultation"
                value={servicePrices.videoConsultation}
                onChange={(value) => handlePriceChange('videoConsultation', value)}
              />
              <PriceInput
                label="Clinic Visit"
                value={servicePrices.clinicVisit}
                onChange={(value) => handlePriceChange('clinicVisit', value)}
              />
              <PriceInput
                label="Emergency Service"
                value={servicePrices.emergencyService}
                onChange={(value) => handlePriceChange('emergencyService', value)}
              />
              <PriceInput
                label="Home Visit"
                value={servicePrices.homeVisit}
                onChange={(value) => handlePriceChange('homeVisit', value)}
              />
              <PriceInput
                label="Grooming Service"
                value={servicePrices.groomingService}
                onChange={(value) => handlePriceChange('groomingService', value)}
              />
            </div>

            <div className="p-6">
              <button className="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                Save Prices
              </button>
            </div>
          </div>

          {/* Earnings Summary */}
          <div className="bg-white rounded-xl shadow-md p-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">Earnings Summary</h2>
            
            <div className="grid grid-cols-2 gap-4 mb-4">
              <div className="bg-gray-50 p-4 rounded-lg">
                <p className="text-sm text-gray-600">This Month</p>
                <p className="text-xl font-bold text-gray-900">₹8,500</p>
              </div>
              <div className="bg-gray-50 p-4 rounded-lg">
                <p className="text-sm text-gray-600">Last Month</p>
                <p className="text-xl font-bold text-gray-900">₹12,300</p>
              </div>
            </div>

            <div className="bg-green-50 p-4 rounded-lg">
              <p className="text-sm text-green-600">Total Earnings</p>
              <p className="text-2xl font-bold text-green-900">₹1,24,800</p>
            </div>
          </div>
        </div>
      </div>

      {/* Withdraw Modal */}
      {showWithdrawModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-2xl p-6 w-full max-w-md">
            <h2 className="text-xl font-semibold text-gray-900 mb-4">Withdraw Funds</h2>
            
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Amount to Withdraw
              </label>
              <div className="relative">
                <span className="absolute left-3 top-2 text-gray-500">₹</span>
                <input
                  type="number"
                  value={withdrawAmount}
                  onChange={(e) => setWithdrawAmount(e.target.value)}
                  className="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter amount"
                  max={walletBalance}
                />
              </div>
              <p className="text-sm text-gray-500 mt-2">
                Available: ₹{walletBalance.toLocaleString()}
              </p>
            </div>

            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Bank Account
              </label>
              <select className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option>ICICI Bank **** 1234</option>
                <option>HDFC Bank **** 5678</option>
                <option>Add new bank account</option>
              </select>
            </div>

            <div className="flex gap-3">
              <button
                onClick={() => setShowWithdrawModal(false)}
                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                onClick={handleWithdraw}
                disabled={!withdrawAmount || withdrawAmount > walletBalance}
                className="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Confirm Withdraw
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default VetPayment;