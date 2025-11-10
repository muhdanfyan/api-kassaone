# 🎨 Dynamic SHU Percentage Settings - Frontend Implementation Guide (React)

## 📋 Table of Contents
1. [Overview](#overview)
2. [TypeScript Interfaces](#typescript-interfaces)
3. [API Service](#api-service)
4. [Custom Hooks](#custom-hooks)
5. [Components](#components)
6. [Complete Page Examples](#complete-page-examples)
7. [Validation & Utils](#validation--utils)
8. [Integration with SHU Distribution](#integration-with-shu-distribution)

---

## 🎯 Overview

This guide shows how to implement the **Dynamic SHU Percentage Settings** feature in your React frontend. Users can:

✅ View all percentage settings  
✅ Create custom percentage configurations  
✅ Preview calculations before applying  
✅ Activate/deactivate settings per fiscal year  
✅ Link settings to SHU distributions  

---

## 📦 TypeScript Interfaces

### Core Interfaces

```typescript
// types/shu.types.ts

export interface ShuPercentageSetting {
  id: string;
  name: string;
  fiscal_year: string;
  is_active: boolean;
  
  // Level 1: Total SHU Distribution
  cadangan_percentage: number;
  anggota_percentage: number;
  pengurus_percentage: number;
  karyawan_percentage: number;
  dana_sosial_percentage: number;
  
  // Level 2: Member's Portion
  jasa_modal_percentage: number;
  jasa_usaha_percentage: number;
  
  description?: string;
  created_by?: string;
  creator?: {
    id: string;
    full_name: string;
  };
  created_at: string;
  updated_at: string;
}

export interface ShuBreakdownPreview {
  total_shu: number;
  cadangan: {
    percentage: number;
    amount: number;
  };
  anggota: {
    percentage: number;
    amount: number;
    breakdown: {
      jasa_modal: {
        percentage: number;
        amount: number;
      };
      jasa_usaha: {
        percentage: number;
        amount: number;
      };
    };
  };
  pengurus: {
    percentage: number;
    amount: number;
  };
  karyawan: {
    percentage: number;
    amount: number;
  };
  dana_sosial: {
    percentage: number;
    amount: number;
  };
}

export interface ShuDistribution {
  id: string;
  fiscal_year: string;
  setting_id: string;  // Link to ShuPercentageSetting
  setting?: ShuPercentageSetting;
  total_shu_amount: number;
  cadangan_amount: number;
  jasa_modal_amount: number;
  jasa_usaha_amount: number;
  distribution_date: string;
  status: 'draft' | 'approved' | 'paid_out';
  notes?: string;
}

export interface CreateSettingFormData {
  name: string;
  fiscal_year: string;
  cadangan_percentage: number;
  anggota_percentage: number;
  pengurus_percentage: number;
  karyawan_percentage: number;
  dana_sosial_percentage: number;
  jasa_modal_percentage: number;
  jasa_usaha_percentage: number;
  description?: string;
  is_active: boolean;
}
```

---

## 🔌 API Service

### SHU Settings Service

```typescript
// services/shuSettingsService.ts

import axios from 'axios';
import { ShuPercentageSetting, ShuBreakdownPreview, CreateSettingFormData } from '@/types/shu.types';

const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

export const shuSettingsService = {
  /**
   * Get all percentage settings
   */
  async getAll(params?: { fiscal_year?: string; is_active?: boolean }) {
    const response = await axios.get<{ success: boolean; data: ShuPercentageSetting[] }>(
      `${API_BASE}/shu-settings`,
      { params }
    );
    return response.data.data;
  },

  /**
   * Get single setting by ID
   */
  async getById(id: string) {
    const response = await axios.get<{ success: boolean; data: ShuPercentageSetting }>(
      `${API_BASE}/shu-settings/${id}`
    );
    return response.data.data;
  },

  /**
   * Create new setting
   */
  async create(data: CreateSettingFormData) {
    const response = await axios.post<{ success: boolean; data: ShuPercentageSetting; message: string }>(
      `${API_BASE}/shu-settings`,
      data
    );
    return response.data;
  },

  /**
   * Update existing setting
   */
  async update(id: string, data: Partial<CreateSettingFormData>) {
    const response = await axios.put<{ success: boolean; data: ShuPercentageSetting; message: string }>(
      `${API_BASE}/shu-settings/${id}`,
      data
    );
    return response.data;
  },

  /**
   * Delete setting
   */
  async delete(id: string) {
    const response = await axios.delete<{ success: boolean; message: string }>(
      `${API_BASE}/shu-settings/${id}`
    );
    return response.data;
  },

  /**
   * Activate a setting (deactivates others for same fiscal year)
   */
  async activate(id: string) {
    const response = await axios.post<{ success: boolean; data: ShuPercentageSetting; message: string }>(
      `${API_BASE}/shu-settings/${id}/activate`
    );
    return response.data;
  },

  /**
   * Preview calculation with total SHU
   */
  async preview(id: string, totalShu: number) {
    const response = await axios.post<{ success: boolean; data: ShuBreakdownPreview; message: string }>(
      `${API_BASE}/shu-settings/${id}/preview`,
      { total_shu: totalShu }
    );
    return response.data.data;
  },
};
```

---

## 🎣 Custom Hooks

### useShuSettings Hook

```typescript
// hooks/useShuSettings.ts

import { useState, useEffect } from 'react';
import { shuSettingsService } from '@/services/shuSettingsService';
import { ShuPercentageSetting, CreateSettingFormData } from '@/types/shu.types';
import { toast } from 'react-hot-toast';

export const useShuSettings = (fiscalYear?: string) => {
  const [settings, setSettings] = useState<ShuPercentageSetting[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchSettings = async () => {
    try {
      setLoading(true);
      const data = await shuSettingsService.getAll(
        fiscalYear ? { fiscal_year: fiscalYear } : undefined
      );
      setSettings(data);
      setError(null);
    } catch (err: any) {
      setError(err.message || 'Failed to fetch settings');
      toast.error('Gagal memuat data setting');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchSettings();
  }, [fiscalYear]);

  const createSetting = async (data: CreateSettingFormData) => {
    try {
      const response = await shuSettingsService.create(data);
      toast.success(response.message || 'Setting berhasil dibuat');
      await fetchSettings(); // Refresh list
      return response.data;
    } catch (err: any) {
      const message = err.response?.data?.message || 'Gagal membuat setting';
      toast.error(message);
      throw err;
    }
  };

  const updateSetting = async (id: string, data: Partial<CreateSettingFormData>) => {
    try {
      const response = await shuSettingsService.update(id, data);
      toast.success(response.message || 'Setting berhasil diupdate');
      await fetchSettings();
      return response.data;
    } catch (err: any) {
      const message = err.response?.data?.message || 'Gagal update setting';
      toast.error(message);
      throw err;
    }
  };

  const deleteSetting = async (id: string) => {
    if (!confirm('Yakin ingin menghapus setting ini?')) return;
    
    try {
      const response = await shuSettingsService.delete(id);
      toast.success(response.message || 'Setting berhasil dihapus');
      await fetchSettings();
    } catch (err: any) {
      const message = err.response?.data?.message || 'Gagal menghapus setting';
      toast.error(message);
      throw err;
    }
  };

  const activateSetting = async (id: string) => {
    try {
      const response = await shuSettingsService.activate(id);
      toast.success(response.message || 'Setting berhasil diaktifkan');
      await fetchSettings();
      return response.data;
    } catch (err: any) {
      const message = err.response?.data?.message || 'Gagal aktivasi setting';
      toast.error(message);
      throw err;
    }
  };

  return {
    settings,
    loading,
    error,
    fetchSettings,
    createSetting,
    updateSetting,
    deleteSetting,
    activateSetting,
  };
};
```

---

## ✅ Validation & Utils

```typescript
// utils/validation.ts

export const validatePercentages = (data: any) => {
  const errors: Record<string, string> = {};

  // Level 1 validation
  const level1Total = 
    data.cadangan_percentage + 
    data.anggota_percentage + 
    data.pengurus_percentage + 
    data.karyawan_percentage + 
    data.dana_sosial_percentage;

  if (Math.abs(level1Total - 100) > 0.01) {
    errors.level1 = `Total Level 1 harus 100%. Saat ini: ${level1Total.toFixed(2)}%`;
  }

  // Level 2 validation
  const level2Total = data.jasa_modal_percentage + data.jasa_usaha_percentage;
  if (Math.abs(level2Total - 100) > 0.01) {
    errors.level2 = `Total Level 2 harus 100%. Saat ini: ${level2Total.toFixed(2)}%`;
  }

  // Minimum cadangan
  if (data.cadangan_percentage < 30) {
    errors.cadangan = 'Cadangan minimal 30% sesuai UU Koperasi';
  }

  return errors;
};

// utils/format.ts

export const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(amount);
};
```

---

## 🔗 Integration with SHU Distribution

### Updated Create Distribution Form

```typescript
// Saat create distribution, pilih setting terlebih dahulu

const handleCreateDistribution = async () => {
  const payload = {
    fiscal_year: '2024',
    total_shu_amount: 150000000,
    setting_id: selectedSettingId, // ← Link ke setting!
    distribution_date: '2024-12-31',
  };

  const response = await axios.post('/api/shu-distributions', payload);
  // Distribution akan otomatis menggunakan persentase dari setting
};
```

---

## 🎉 Summary

Backend implementation complete dengan:

✅ **20 API Routes** terdaftar (13 SHU Distributions + 7 Settings)  
✅ **Dynamic Percentage System** - Fully customizable via database  
✅ **Database Migrations** - 2 new tables  
✅ **Seeder** - 4 default settings (2024, 2025, custom, CSR)  
✅ **Full CRUD** for settings management  
✅ **Preview endpoint** untuk test calculation  
✅ **Integration** dengan existing SHU Distribution  

Frontend implementation includes:

✅ **TypeScript Interfaces** untuk type safety  
✅ **API Service** dengan axios  
✅ **Custom Hooks** untuk state management  
✅ **Validation Utils** untuk form validation  
✅ **Ready-to-use Components** untuk implementasi cepat  

---

**Happy Coding! 🚀**
