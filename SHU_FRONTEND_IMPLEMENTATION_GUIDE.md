# KASSA ONE - SHU Management Frontend Implementation Guide (React)

> 📘 **Panduan implementasi lengkap untuk Frontend React dalam mengelola SHU (Sisa Hasil Usaha) Koperasi**

## 📋 Table of Contents

1. [Overview Alur SHU](#overview-alur-shu)
2. [API Endpoints](#api-endpoints)
3. [Data Models & Types](#data-models--types)
4. [Component Structure](#component-structure)
5. [Implementation Steps](#implementation-steps)
6. [Example Code](#example-code)
7. [State Management](#state-management)
8. [UI/UX Recommendations](#uiux-recommendations)

---

## 🔄 Overview Alur SHU

```
┌─────────────────────────────────────────────────────────────┐
│                    ALUR SHU MANAGEMENT                      │
└─────────────────────────────────────────────────────────────┘

1. CREATE DISTRIBUTION (Draft)
   ├─ Input: fiscal_year, total_shu_amount, distribution_date
   ├─ Auto calculate: cadangan, jasa_modal, jasa_usaha
   └─ Status: draft

2. CALCULATE ALLOCATIONS
   ├─ Hitung per member berdasarkan:
   │  ├─ Simpanan (jasa modal)
   │  └─ Transaksi (jasa usaha)
   └─ Save allocations to database

3. APPROVE DISTRIBUTION
   ├─ Review allocations
   ├─ Approve by admin/pengurus
   └─ Status: approved

4. BATCH PAYOUT
   ├─ Create transactions for each member
   ├─ Update savings balance
   └─ Status: paid_out (when all paid)

5. REPORT & MONITORING
   └─ View statistics, top members, payment progress
```

---

## 🔌 API Endpoints

### Base URL
```
http://your-api-url.com/api
```

### Authentication
All endpoints require Bearer token:
```javascript
headers: {
  'Authorization': `Bearer ${accessToken}`,
  'Content-Type': 'application/json'
}
```

### Endpoints List

#### 1. **Get All SHU Distributions**
```http
GET /shu-distributions
```

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 15)
- `status` (optional): Filter by status (`draft`, `approved`, `paid_out`)

**Response:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": "cm3abc123",
      "fiscal_year": 2025,
      "total_shu_amount": "100000000.00",
      "cadangan_amount": "30000000.00",
      "jasa_modal_amount": "28000000.00",
      "jasa_usaha_amount": "42000000.00",
      "distribution_date": "2026-01-15",
      "status": "draft",
      "approved_at": null,
      "approved_by": null,
      "notes": "SHU Tahun 2025",
      "total_members": 150,
      "paid_members": 0,
      "payment_progress": 0,
      "total_paid_out": "0.00",
      "total_unpaid": "70000000.00"
    }
  ],
  "total": 5,
  "per_page": 15,
  "last_page": 1
}
```

---

#### 2. **Get SHU Distribution by ID**
```http
GET /shu-distributions/{id}
```

**Response:**
```json
{
  "data": {
    "id": "cm3abc123",
    "fiscal_year": 2025,
    "total_shu_amount": "100000000.00",
    "cadangan_amount": "30000000.00",
    "jasa_modal_amount": "28000000.00",
    "jasa_usaha_amount": "42000000.00",
    "distribution_date": "2026-01-15",
    "status": "draft",
    "allocations": [
      {
        "id": "cm3xyz789",
        "member_id": "cm3member1",
        "member": {
          "id": "cm3member1",
          "full_name": "John Doe",
          "member_number": "001"
        },
        "jasa_modal_amount": "560000.00",
        "jasa_usaha_amount": "1050000.00",
        "amount_allocated": "1610000.00",
        "is_paid_out": false,
        "paid_out_at": null
      }
    ]
  },
  "summary": {
    "distribution_id": "cm3abc123",
    "fiscal_year": 2025,
    "status": "draft",
    "total_shu": "100000000.00",
    "members_count": 150,
    "paid_members": 0,
    "unpaid_members": 150,
    "payment_progress": 0
  }
}
```

---

#### 3. **Create SHU Distribution (Step 1)**
```http
POST /shu-distributions
```

**Request Body:**
```json
{
  "fiscal_year": 2025,
  "total_shu_amount": 100000000,
  "distribution_date": "2026-01-15",
  "notes": "SHU Tahun 2025"
}
```

**Response:**
```json
{
  "message": "SHU Distribution created successfully",
  "data": {
    "id": "cm3abc123",
    "fiscal_year": 2025,
    "total_shu_amount": "100000000.00",
    "cadangan_amount": "30000000.00",
    "jasa_modal_amount": "28000000.00",
    "jasa_usaha_amount": "42000000.00",
    "status": "draft"
  },
  "breakdown": {
    "total_shu": 100000000,
    "cadangan_amount": 30000000,
    "anggota_amount": 70000000,
    "jasa_modal_amount": 28000000,
    "jasa_usaha_amount": 42000000,
    "percentages": {
      "cadangan": 30,
      "anggota": 70,
      "jasa_modal": 40,
      "jasa_usaha": 60
    }
  }
}
```

---

#### 4. **Calculate Member Allocations (Step 2)**
```http
POST /shu-distributions/{id}/calculate
```

**Response:**
```json
{
  "message": "Allocations calculated and saved successfully",
  "data": { /* distribution with allocations */ },
  "summary": { /* summary stats */ },
  "allocations": [
    {
      "member_id": "cm3member1",
      "member_name": "John Doe",
      "member_number": "001",
      "member_savings": "10000000.00",
      "member_transactions": "50000000.00",
      "jasa_modal_proportion": 2.0,
      "jasa_usaha_proportion": 2.5,
      "jasa_modal_amount": "560000.00",
      "jasa_usaha_amount": "1050000.00",
      "amount_allocated": "1610000.00"
    }
  ]
}
```

---

#### 5. **Get Allocations for Distribution**
```http
GET /shu-distributions/{id}/allocations
```

**Query Parameters:**
- `per_page` (optional): Number of items per page
- `is_paid_out` (optional): Filter by payment status (`true`, `false`)

**Response:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": "cm3xyz789",
      "member_id": "cm3member1",
      "member": {
        "id": "cm3member1",
        "full_name": "John Doe",
        "member_number": "001"
      },
      "jasa_modal_amount": "560000.00",
      "jasa_usaha_amount": "1050000.00",
      "amount_allocated": "1610000.00",
      "is_paid_out": false,
      "payout_transaction_id": null,
      "paid_out_at": null
    }
  ],
  "total": 150,
  "per_page": 15
}
```

---

#### 6. **Approve Distribution (Step 3)**
```http
POST /shu-distributions/{id}/approve
```

**Request Body:**
```json
{
  "approved_by": "cm3admin123"
}
```

**Response:**
```json
{
  "message": "SHU Distribution approved successfully",
  "data": {
    "id": "cm3abc123",
    "status": "approved",
    "approved_at": "2025-11-10 10:30:00",
    "approved_by": "cm3admin123",
    "approver": {
      "id": "cm3admin123",
      "full_name": "Admin User"
    }
  }
}
```

---

#### 7. **Batch Payout (Step 4)**
```http
POST /shu-distributions/{id}/payout
```

**Response:**
```json
{
  "message": "Batch payout completed",
  "paid_count": 150,
  "paid_amount": "70000000.00",
  "errors": [],
  "distribution_status": "paid_out"
}
```

---

#### 8. **Get Distribution Report**
```http
GET /shu-distributions/{id}/report
```

**Response:**
```json
{
  "summary": {
    "distribution_id": "cm3abc123",
    "fiscal_year": 2025,
    "status": "paid_out",
    "total_shu": "100000000.00",
    "members_count": 150,
    "payment_progress": 100
  },
  "top_members": [
    {
      "member_name": "John Doe",
      "member_number": "001",
      "amount_allocated": "1610000.00",
      "jasa_modal": "560000.00",
      "jasa_usaha": "1050000.00",
      "is_paid_out": true
    }
  ],
  "distribution_details": {
    "total_shu": "100000000.00",
    "cadangan": "30000000.00",
    "for_members": "70000000.00",
    "breakdown": {
      "jasa_modal": "28000000.00",
      "jasa_usaha": "42000000.00"
    }
  },
  "payment_status": {
    "paid_members": 150,
    "unpaid_members": 0,
    "paid_amount": "70000000.00",
    "unpaid_amount": "0.00",
    "progress_percentage": 100
  }
}
```

---

#### 9. **Update Distribution**
```http
PUT /shu-distributions/{id}
```

**Request Body:**
```json
{
  "total_shu_amount": 105000000,
  "distribution_date": "2026-01-20",
  "notes": "Updated notes"
}
```

**Note:** Only `draft` distributions can be updated.

---

#### 10. **Delete Distribution**
```http
DELETE /shu-distributions/{id}
```

**Note:** Only `draft` distributions can be deleted.

---

## 📊 Data Models & Types

### TypeScript Interfaces

```typescript
// SHU Distribution
interface SHUDistribution {
  id: string;
  fiscal_year: number;
  total_shu_amount: string;
  cadangan_amount: string;
  jasa_modal_amount: string;
  jasa_usaha_amount: string;
  distribution_date: string; // ISO date
  status: 'draft' | 'approved' | 'paid_out';
  approved_at: string | null;
  approved_by: string | null;
  notes: string | null;
  total_members: number;
  paid_members: number;
  payment_progress: number;
  total_paid_out: string;
  total_unpaid: string;
  approver?: Member;
  allocations?: SHUAllocation[];
}

// SHU Member Allocation
interface SHUAllocation {
  id: string;
  shu_distribution_id: string;
  member_id: string;
  member?: Member;
  jasa_modal_amount: string;
  jasa_usaha_amount: string;
  amount_allocated: string;
  is_paid_out: boolean;
  payout_transaction_id: string | null;
  paid_out_at: string | null;
}

// Member (simplified)
interface Member {
  id: string;
  full_name: string;
  member_number: string;
  email: string;
}

// Distribution Summary
interface DistributionSummary {
  distribution_id: string;
  fiscal_year: number;
  status: string;
  total_shu: string;
  cadangan: string;
  jasa_modal: string;
  jasa_usaha: string;
  distribution_date: string;
  members_count: number;
  paid_members: number;
  unpaid_members: number;
  total_allocated: string;
  total_paid_out: string;
  total_unpaid: string;
  payment_progress: number;
  approved_at: string | null;
  approved_by: string | null;
}

// Report Data
interface SHUReport {
  summary: DistributionSummary;
  top_members: Array<{
    member_name: string;
    member_number: string;
    amount_allocated: string;
    jasa_modal: string;
    jasa_usaha: string;
    is_paid_out: boolean;
  }>;
  distribution_details: {
    total_shu: string;
    cadangan: string;
    for_members: string;
    breakdown: {
      jasa_modal: string;
      jasa_usaha: string;
    };
  };
  payment_status: {
    paid_members: number;
    unpaid_members: number;
    paid_amount: string;
    unpaid_amount: string;
    progress_percentage: number;
  };
}
```

---

## 🏗️ Component Structure

Recommended component hierarchy:

```
src/
├── pages/
│   └── shu/
│       ├── SHUDistributionList.tsx       // List all distributions
│       ├── SHUDistributionCreate.tsx     // Create new distribution (Step 1)
│       ├── SHUDistributionDetail.tsx     // View & manage distribution
│       └── SHUDistributionReport.tsx     // View report & statistics
├── components/
│   └── shu/
│       ├── DistributionCard.tsx          // Card display for distribution
│       ├── DistributionBreakdown.tsx     // Show cadangan, jasa modal, jasa usaha
│       ├── AllocationTable.tsx           // Table of member allocations
│       ├── AllocationFilters.tsx         // Filter allocations (paid/unpaid)
│       ├── ApprovalModal.tsx             // Modal for approval confirmation
│       ├── PayoutModal.tsx               // Modal for batch payout confirmation
│       ├── StatusBadge.tsx               // Badge for status (draft/approved/paid_out)
│       └── PaymentProgressBar.tsx        // Progress bar for payment status
├── services/
│   └── shuService.ts                     // API calls for SHU
├── hooks/
│   └── useSHU.ts                         // Custom hooks for SHU operations
└── utils/
    ├── formatCurrency.ts                 // Format Rupiah
    └── shuCalculations.ts                // Helper calculations
```

---

## 🚀 Implementation Steps

### Step 1: Setup API Service

```typescript
// src/services/shuService.ts
import axios from 'axios';

const API_BASE_URL = process.env.REACT_APP_API_URL;

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Add auth token to requests
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('accessToken');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export const shuService = {
  // Get all distributions
  getDistributions: (params?: { per_page?: number; status?: string }) =>
    api.get('/shu-distributions', { params }),

  // Get distribution by ID
  getDistribution: (id: string) =>
    api.get(`/shu-distributions/${id}`),

  // Create distribution
  createDistribution: (data: {
    fiscal_year: number;
    total_shu_amount: number;
    distribution_date: string;
    notes?: string;
  }) => api.post('/shu-distributions', data),

  // Update distribution
  updateDistribution: (id: string, data: any) =>
    api.put(`/shu-distributions/${id}`, data),

  // Delete distribution
  deleteDistribution: (id: string) =>
    api.delete(`/shu-distributions/${id}`),

  // Calculate allocations
  calculateAllocations: (id: string) =>
    api.post(`/shu-distributions/${id}/calculate`),

  // Get allocations
  getAllocations: (id: string, params?: { per_page?: number; is_paid_out?: boolean }) =>
    api.get(`/shu-distributions/${id}/allocations`, { params }),

  // Approve distribution
  approveDistribution: (id: string, approved_by: string) =>
    api.post(`/shu-distributions/${id}/approve`, { approved_by }),

  // Batch payout
  batchPayout: (id: string) =>
    api.post(`/shu-distributions/${id}/payout`),

  // Get report
  getReport: (id: string) =>
    api.get(`/shu-distributions/${id}/report`),
};
```

---

### Step 2: Create Custom Hook

```typescript
// src/hooks/useSHU.ts
import { useState } from 'react';
import { shuService } from '../services/shuService';
import { toast } from 'react-toastify'; // or your toast library

export const useSHU = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const createDistribution = async (data: any) => {
    setLoading(true);
    setError(null);
    try {
      const response = await shuService.createDistribution(data);
      toast.success('SHU Distribution created successfully!');
      return response.data;
    } catch (err: any) {
      const message = err.response?.data?.error || 'Failed to create distribution';
      setError(message);
      toast.error(message);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  const calculateAllocations = async (id: string) => {
    setLoading(true);
    setError(null);
    try {
      const response = await shuService.calculateAllocations(id);
      toast.success('Allocations calculated successfully!');
      return response.data;
    } catch (err: any) {
      const message = err.response?.data?.error || 'Failed to calculate allocations';
      setError(message);
      toast.error(message);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  const approveDistribution = async (id: string, approvedBy: string) => {
    setLoading(true);
    setError(null);
    try {
      const response = await shuService.approveDistribution(id, approvedBy);
      toast.success('Distribution approved successfully!');
      return response.data;
    } catch (err: any) {
      const message = err.response?.data?.error || 'Failed to approve distribution';
      setError(message);
      toast.error(message);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  const batchPayout = async (id: string) => {
    setLoading(true);
    setError(null);
    try {
      const response = await shuService.batchPayout(id);
      toast.success(`Payout completed! ${response.data.paid_count} members paid.`);
      return response.data;
    } catch (err: any) {
      const message = err.response?.data?.error || 'Failed to process payout';
      setError(message);
      toast.error(message);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  return {
    loading,
    error,
    createDistribution,
    calculateAllocations,
    approveDistribution,
    batchPayout,
  };
};
```

---

### Step 3: Create Distribution List Page

```tsx
// src/pages/shu/SHUDistributionList.tsx
import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { shuService } from '../../services/shuService';
import { SHUDistribution } from '../../types';
import { formatCurrency } from '../../utils/formatCurrency';
import StatusBadge from '../../components/shu/StatusBadge';
import PaymentProgressBar from '../../components/shu/PaymentProgressBar';

const SHUDistributionList: React.FC = () => {
  const navigate = useNavigate();
  const [distributions, setDistributions] = useState<SHUDistribution[]>([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState<'all' | 'draft' | 'approved' | 'paid_out'>('all');

  useEffect(() => {
    fetchDistributions();
  }, [filter]);

  const fetchDistributions = async () => {
    setLoading(true);
    try {
      const params = filter !== 'all' ? { status: filter } : {};
      const response = await shuService.getDistributions(params);
      setDistributions(response.data.data);
    } catch (error) {
      console.error('Error fetching distributions:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="container mx-auto p-6">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold">SHU Management</h1>
        <button
          onClick={() => navigate('/shu/create')}
          className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
        >
          + Create New Distribution
        </button>
      </div>

      {/* Filter Tabs */}
      <div className="flex space-x-4 mb-6">
        {['all', 'draft', 'approved', 'paid_out'].map((status) => (
          <button
            key={status}
            onClick={() => setFilter(status as any)}
            className={`px-4 py-2 rounded ${
              filter === status
                ? 'bg-blue-600 text-white'
                : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
            }`}
          >
            {status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ')}
          </button>
        ))}
      </div>

      {/* Distribution Cards */}
      {loading ? (
        <div className="text-center py-10">Loading...</div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {distributions.map((dist) => (
            <div
              key={dist.id}
              onClick={() => navigate(`/shu/${dist.id}`)}
              className="bg-white rounded-lg shadow-md p-6 cursor-pointer hover:shadow-lg transition"
            >
              <div className="flex justify-between items-start mb-4">
                <h3 className="text-xl font-bold">Tahun {dist.fiscal_year}</h3>
                <StatusBadge status={dist.status} />
              </div>

              <div className="space-y-2 mb-4">
                <div className="flex justify-between">
                  <span className="text-gray-600">Total SHU:</span>
                  <span className="font-semibold">{formatCurrency(parseFloat(dist.total_shu_amount))}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600">Members:</span>
                  <span>{dist.total_members}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600">Paid:</span>
                  <span className="text-green-600">{dist.paid_members} / {dist.total_members}</span>
                </div>
              </div>

              <PaymentProgressBar progress={dist.payment_progress} />

              <p className="text-sm text-gray-500 mt-4">
                Distribution Date: {new Date(dist.distribution_date).toLocaleDateString('id-ID')}
              </p>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default SHUDistributionList;
```

---

### Step 4: Create Distribution Form

```tsx
// src/pages/shu/SHUDistributionCreate.tsx
import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useSHU } from '../../hooks/useSHU';

const SHUDistributionCreate: React.FC = () => {
  const navigate = useNavigate();
  const { createDistribution, loading } = useSHU();

  const [formData, setFormData] = useState({
    fiscal_year: new Date().getFullYear(),
    total_shu_amount: '',
    distribution_date: '',
    notes: '',
  });

  const [breakdown, setBreakdown] = useState<any>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const result = await createDistribution({
        ...formData,
        total_shu_amount: parseFloat(formData.total_shu_amount),
      });
      setBreakdown(result.breakdown);
      // Navigate to detail page after 2 seconds
      setTimeout(() => {
        navigate(`/shu/${result.data.id}`);
      }, 2000);
    } catch (error) {
      console.error('Error creating distribution:', error);
    }
  };

  return (
    <div className="container mx-auto p-6 max-w-2xl">
      <h1 className="text-3xl font-bold mb-6">Create SHU Distribution</h1>

      <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow-md p-6 space-y-6">
        <div>
          <label className="block text-sm font-medium mb-2">Fiscal Year *</label>
          <input
            type="number"
            required
            min="2000"
            max="2100"
            value={formData.fiscal_year}
            onChange={(e) => setFormData({ ...formData, fiscal_year: parseInt(e.target.value) })}
            className="w-full border rounded px-3 py-2"
          />
        </div>

        <div>
          <label className="block text-sm font-medium mb-2">Total SHU Amount (Rp) *</label>
          <input
            type="number"
            required
            min="0"
            step="0.01"
            value={formData.total_shu_amount}
            onChange={(e) => setFormData({ ...formData, total_shu_amount: e.target.value })}
            className="w-full border rounded px-3 py-2"
            placeholder="100000000"
          />
        </div>

        <div>
          <label className="block text-sm font-medium mb-2">Distribution Date *</label>
          <input
            type="date"
            required
            value={formData.distribution_date}
            onChange={(e) => setFormData({ ...formData, distribution_date: e.target.value })}
            className="w-full border rounded px-3 py-2"
          />
        </div>

        <div>
          <label className="block text-sm font-medium mb-2">Notes</label>
          <textarea
            value={formData.notes}
            onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
            className="w-full border rounded px-3 py-2"
            rows={4}
            placeholder="Optional notes..."
          />
        </div>

        <div className="flex justify-end space-x-4">
          <button
            type="button"
            onClick={() => navigate('/shu')}
            className="px-4 py-2 border rounded hover:bg-gray-100"
          >
            Cancel
          </button>
          <button
            type="submit"
            disabled={loading}
            className="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:bg-gray-400"
          >
            {loading ? 'Creating...' : 'Create Distribution'}
          </button>
        </div>
      </form>

      {/* Show breakdown after creation */}
      {breakdown && (
        <div className="mt-6 bg-green-50 border border-green-200 rounded-lg p-6">
          <h3 className="font-bold text-green-800 mb-4">✓ Distribution Created!</h3>
          <div className="space-y-2 text-sm">
            <div className="flex justify-between">
              <span>Total SHU:</span>
              <span className="font-semibold">Rp {breakdown.total_shu.toLocaleString('id-ID')}</span>
            </div>
            <div className="flex justify-between">
              <span>Cadangan (30%):</span>
              <span>Rp {breakdown.cadangan_amount.toLocaleString('id-ID')}</span>
            </div>
            <div className="flex justify-between">
              <span>Jasa Modal (40% × 70%):</span>
              <span>Rp {breakdown.jasa_modal_amount.toLocaleString('id-ID')}</span>
            </div>
            <div className="flex justify-between">
              <span>Jasa Usaha (60% × 70%):</span>
              <span>Rp {breakdown.jasa_usaha_amount.toLocaleString('id-ID')}</span>
            </div>
          </div>
          <p className="text-sm text-gray-600 mt-4">Redirecting to detail page...</p>
        </div>
      )}
    </div>
  );
};

export default SHUDistributionCreate;
```

---

### Step 5: Distribution Detail Page

```tsx
// src/pages/shu/SHUDistributionDetail.tsx
import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { shuService } from '../../services/shuService';
import { useSHU } from '../../hooks/useSHU';
import { SHUDistribution, SHUAllocation } from '../../types';
import { formatCurrency } from '../../utils/formatCurrency';
import StatusBadge from '../../components/shu/StatusBadge';
import AllocationTable from '../../components/shu/AllocationTable';

const SHUDistributionDetail: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { calculateAllocations, approveDistribution, batchPayout, loading } = useSHU();

  const [distribution, setDistribution] = useState<SHUDistribution | null>(null);
  const [allocations, setAllocations] = useState<SHUAllocation[]>([]);
  const [summary, setSummary] = useState<any>(null);

  useEffect(() => {
    if (id) {
      fetchDistribution();
      fetchAllocations();
    }
  }, [id]);

  const fetchDistribution = async () => {
    try {
      const response = await shuService.getDistribution(id!);
      setDistribution(response.data.data);
      setSummary(response.data.summary);
    } catch (error) {
      console.error('Error fetching distribution:', error);
    }
  };

  const fetchAllocations = async () => {
    try {
      const response = await shuService.getAllocations(id!);
      setAllocations(response.data.data);
    } catch (error) {
      console.error('Error fetching allocations:', error);
    }
  };

  const handleCalculate = async () => {
    try {
      await calculateAllocations(id!);
      fetchDistribution();
      fetchAllocations();
    } catch (error) {
      console.error('Error calculating:', error);
    }
  };

  const handleApprove = async () => {
    // Get current user ID from auth context
    const currentUserId = localStorage.getItem('userId') || 'admin-id';
    
    if (window.confirm('Are you sure you want to approve this distribution?')) {
      try {
        await approveDistribution(id!, currentUserId);
        fetchDistribution();
      } catch (error) {
        console.error('Error approving:', error);
      }
    }
  };

  const handlePayout = async () => {
    if (window.confirm('Are you sure you want to process batch payout for all unpaid members?')) {
      try {
        await batchPayout(id!);
        fetchDistribution();
        fetchAllocations();
      } catch (error) {
        console.error('Error processing payout:', error);
      }
    }
  };

  if (!distribution) {
    return <div className="text-center py-10">Loading...</div>;
  }

  return (
    <div className="container mx-auto p-6">
      {/* Header */}
      <div className="flex justify-between items-center mb-6">
        <div>
          <button
            onClick={() => navigate('/shu')}
            className="text-blue-600 hover:underline mb-2"
          >
            ← Back to List
          </button>
          <h1 className="text-3xl font-bold">
            SHU Distribution - Tahun {distribution.fiscal_year}
          </h1>
        </div>
        <StatusBadge status={distribution.status} />
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div className="bg-white rounded-lg shadow p-4">
          <p className="text-gray-600 text-sm">Total SHU</p>
          <p className="text-2xl font-bold text-blue-600">
            {formatCurrency(parseFloat(distribution.total_shu_amount))}
          </p>
        </div>
        <div className="bg-white rounded-lg shadow p-4">
          <p className="text-gray-600 text-sm">Cadangan</p>
          <p className="text-2xl font-bold text-orange-600">
            {formatCurrency(parseFloat(distribution.cadangan_amount))}
          </p>
        </div>
        <div className="bg-white rounded-lg shadow p-4">
          <p className="text-gray-600 text-sm">Jasa Modal</p>
          <p className="text-2xl font-bold text-green-600">
            {formatCurrency(parseFloat(distribution.jasa_modal_amount))}
          </p>
        </div>
        <div className="bg-white rounded-lg shadow p-4">
          <p className="text-gray-600 text-sm">Jasa Usaha</p>
          <p className="text-2xl font-bold text-purple-600">
            {formatCurrency(parseFloat(distribution.jasa_usaha_amount))}
          </p>
        </div>
      </div>

      {/* Action Buttons */}
      <div className="bg-white rounded-lg shadow p-6 mb-6">
        <h2 className="text-xl font-bold mb-4">Actions</h2>
        <div className="flex space-x-4">
          {distribution.status === 'draft' && (
            <>
              <button
                onClick={handleCalculate}
                disabled={loading}
                className="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:bg-gray-400"
              >
                {loading ? 'Calculating...' : '📊 Calculate Allocations'}
              </button>
              {allocations.length > 0 && (
                <button
                  onClick={handleApprove}
                  disabled={loading}
                  className="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:bg-gray-400"
                >
                  {loading ? 'Approving...' : '✓ Approve Distribution'}
                </button>
              )}
            </>
          )}

          {distribution.status === 'approved' && (
            <button
              onClick={handlePayout}
              disabled={loading}
              className="px-6 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 disabled:bg-gray-400"
            >
              {loading ? 'Processing...' : '💰 Process Batch Payout'}
            </button>
          )}

          <button
            onClick={() => navigate(`/shu/${id}/report`)}
            className="px-6 py-2 bg-gray-600 text-white rounded hover:bg-gray-700"
          >
            📄 View Report
          </button>
        </div>
      </div>

      {/* Allocations Table */}
      {allocations.length > 0 && (
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-xl font-bold mb-4">Member Allocations</h2>
          <AllocationTable allocations={allocations} />
        </div>
      )}
    </div>
  );
};

export default SHUDistributionDetail;
```

---

## 🎨 UI/UX Recommendations

### Status Badge Component

```tsx
// src/components/shu/StatusBadge.tsx
import React from 'react';

interface StatusBadgeProps {
  status: 'draft' | 'approved' | 'paid_out';
}

const StatusBadge: React.FC<StatusBadgeProps> = ({ status }) => {
  const statusConfig = {
    draft: { color: 'bg-gray-200 text-gray-800', label: '📝 Draft' },
    approved: { color: 'bg-green-200 text-green-800', label: '✓ Approved' },
    paid_out: { color: 'bg-blue-200 text-blue-800', label: '💰 Paid Out' },
  };

  const config = statusConfig[status];

  return (
    <span className={`px-3 py-1 rounded-full text-sm font-semibold ${config.color}`}>
      {config.label}
    </span>
  );
};

export default StatusBadge;
```

### Payment Progress Bar

```tsx
// src/components/shu/PaymentProgressBar.tsx
import React from 'react';

interface PaymentProgressBarProps {
  progress: number; // 0-100
}

const PaymentProgressBar: React.FC<PaymentProgressBarProps> = ({ progress }) => {
  return (
    <div className="w-full">
      <div className="flex justify-between text-sm mb-1">
        <span className="text-gray-600">Payment Progress</span>
        <span className="font-semibold">{progress.toFixed(1)}%</span>
      </div>
      <div className="w-full bg-gray-200 rounded-full h-2.5">
        <div
          className="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
          style={{ width: `${progress}%` }}
        />
      </div>
    </div>
  );
};

export default PaymentProgressBar;
```

### Allocation Table Component

```tsx
// src/components/shu/AllocationTable.tsx
import React from 'react';
import { SHUAllocation } from '../../types';
import { formatCurrency } from '../../utils/formatCurrency';

interface AllocationTableProps {
  allocations: SHUAllocation[];
}

const AllocationTable: React.FC<AllocationTableProps> = ({ allocations }) => {
  return (
    <div className="overflow-x-auto">
      <table className="min-w-full divide-y divide-gray-200">
        <thead className="bg-gray-50">
          <tr>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              Member
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              Jasa Modal
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              Jasa Usaha
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              Total Allocated
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              Status
            </th>
          </tr>
        </thead>
        <tbody className="bg-white divide-y divide-gray-200">
          {allocations.map((allocation) => (
            <tr key={allocation.id} className="hover:bg-gray-50">
              <td className="px-6 py-4 whitespace-nowrap">
                <div>
                  <div className="font-medium text-gray-900">
                    {allocation.member?.full_name}
                  </div>
                  <div className="text-sm text-gray-500">
                    {allocation.member?.member_number}
                  </div>
                </div>
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {formatCurrency(parseFloat(allocation.jasa_modal_amount))}
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {formatCurrency(parseFloat(allocation.jasa_usaha_amount))}
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                {formatCurrency(parseFloat(allocation.amount_allocated))}
              </td>
              <td className="px-6 py-4 whitespace-nowrap">
                {allocation.is_paid_out ? (
                  <span className="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                    ✓ Paid
                  </span>
                ) : (
                  <span className="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                    ⏳ Pending
                  </span>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};

export default AllocationTable;
```

---

## 🛠️ Utility Functions

### Format Currency

```typescript
// src/utils/formatCurrency.ts
export const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
};

// Usage: formatCurrency(1000000) => "Rp 1.000.000"
```

---

## 📱 Responsive Design Tips

1. **Mobile-First Approach**: Design untuk mobile terlebih dahulu
2. **Card Layout**: Gunakan card untuk tampilan list di mobile
3. **Sticky Headers**: Buat header table sticky untuk scroll yang panjang
4. **Touch-Friendly**: Button minimal 44x44px untuk touch target
5. **Loading States**: Tampilkan skeleton atau spinner saat loading

---

## 🔐 Security Considerations

1. **Auth Token**: Selalu kirim bearer token di header
2. **CSRF Token**: Untuk POST/PUT/DELETE, sertakan CSRF token jika diperlukan
3. **Role-Based Access**: Validate user role (admin/pengurus) sebelum show action buttons
4. **Input Validation**: Validate di frontend sebelum kirim ke API
5. **Error Handling**: Jangan expose sensitive error ke user

---

## ✅ Testing Checklist

- [ ] Create distribution dengan data valid
- [ ] Calculate allocations
- [ ] Approve distribution
- [ ] Batch payout
- [ ] View report
- [ ] Filter distributions by status
- [ ] Pagination untuk allocations
- [ ] Update distribution (draft only)
- [ ] Delete distribution (draft only)
- [ ] Handle API errors gracefully
- [ ] Loading states semua action
- [ ] Responsive di mobile & desktop

---

## 🎯 Next Steps

1. Implement state management (Redux/Zustand) jika aplikasi kompleks
2. Add unit tests untuk components
3. Add e2e tests dengan Cypress/Playwright
4. Implement real-time updates dengan WebSocket
5. Add export to PDF/Excel untuk report
6. Add email notification untuk approval & payout

---

## 📚 Additional Resources

- [React Documentation](https://react.dev)
- [TypeScript Documentation](https://www.typescriptlang.org/docs/)
- [Tailwind CSS](https://tailwindcss.com)
- [Axios Documentation](https://axios-http.com)
- [React Query](https://tanstack.com/query/latest) (recommended for data fetching)

---

**Happy Coding! 🚀**

Jika ada pertanyaan atau butuh bantuan implementasi, silakan hubungi tim backend atau buka issue di repository.
