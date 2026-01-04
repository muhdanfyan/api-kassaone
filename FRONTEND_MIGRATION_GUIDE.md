# Frontend Implementation Guide - Refactoring Member ID

## 📋 Ringkasan Perubahan

Kolom `member_id_number` telah **dihapus** dari database. Sekarang **`username`** berfungsi ganda sebagai:
- ✅ **Username untuk login** 
- ✅ **Nomor anggota/Member ID** (format: `MEM-0001`, `MEM-0002`, dll)

---

## 🔄 Perubahan di Backend API

### 1. **Format Username Baru**
```
❌ SEBELUMNYA:
- Username: KASSA001, KASSA002, KASSA003 (3 digit)
- Member ID: MEM-0001, MEM-0002 (terpisah)

✅ SEKARANG:
- Username: MEM-0001, MEM-0002, MEM-0003 (4 digit)
- Member ID: (tidak ada, pakai username)
```

### 2. **Response API yang Berubah**

#### A. Register/Login Response
```json
// ❌ LAMA
{
  "data": {
    "member_id_number": "MEM-0001",
    "username": "KASSA001",
    "full_name": "John Doe",
    "email": "john@example.com"
  }
}

// ✅ BARU
{
  "data": {
    "username": "MEM-0001",  // Ini sekaligus Member ID
    "full_name": "John Doe",
    "email": "john@example.com"
  }
}
```

#### B. Member List Response
```json
// ❌ LAMA
{
  "data": [
    {
      "id": "cm4abcd1234",
      "member_id_number": "MEM-0001",
      "username": "KASSA001",
      "full_name": "John Doe"
    }
  ]
}

// ✅ BARU
{
  "data": [
    {
      "id": "cm4abcd1234",
      "username": "MEM-0001",
      "full_name": "John Doe"
    }
  ]
}
```

#### C. Organization/Member Details Response
```json
// ❌ LAMA
{
  "pengurus": [
    {
      "id": "cm4abc123",
      "member_id_number": "MEM-0001",
      "name": "John Doe",
      "position": "Ketua"
    }
  ]
}

// ✅ BARU
{
  "pengurus": [
    {
      "id": "cm4abc123",
      "username": "MEM-0001",  // Gunakan ini untuk tampilan Member ID
      "name": "John Doe",
      "position": "Ketua"
    }
  ]
}
```

#### D. JWT Token Claims
```json
// ❌ LAMA
{
  "member_id_number": "MEM-0001",
  "username": "KASSA001",
  "email": "john@example.com",
  "role_id": "role_123"
}

// ✅ BARU
{
  "username": "MEM-0001",  // Tidak ada member_id_number lagi
  "email": "john@example.com",
  "role_id": "role_123"
}
```

---

## 🛠️ Langkah-langkah Update Frontend

### 1. **Update State Management / Store**

Jika Anda menggunakan Redux, Zustand, Context API, atau state management lainnya:

```typescript
// ❌ LAMA
interface Member {
  id: string;
  member_id_number: string;  // HAPUS INI
  username: string;
  full_name: string;
  email: string;
}

// ✅ BARU
interface Member {
  id: string;
  username: string;  // Ini sekarang juga Member ID
  full_name: string;
  email: string;
}
```

### 2. **Update UI Components**

#### A. Member Card/List Component
```tsx
// ❌ LAMA
<div>
  <p>Member ID: {member.member_id_number}</p>
  <p>Username: {member.username}</p>
</div>

// ✅ BARU (Opsi 1 - Tampilkan sebagai Member ID)
<div>
  <p>Member ID: {member.username}</p>
</div>

// ✅ BARU (Opsi 2 - Tampilkan keduanya jika perlu)
<div>
  <p>Member ID: {member.username}</p>
  <p>Login Username: {member.username}</p>
</div>
```

#### B. Login Form
```tsx
// ✅ TIDAK BERUBAH - Username tetap digunakan untuk login
<input
  name="username"
  placeholder="Username (contoh: MEM-0001)"
  // User sekarang login dengan format MEM-0001
/>
<input
  name="password"
  placeholder="Password"
/>
```

#### C. Registration Form
```tsx
// ❌ LAMA
<input name="member_id_number" placeholder="Member ID (opsional)" />
<input name="username" placeholder="Username (opsional)" />

// ✅ BARU - username auto-generate di backend
<input 
  name="username" 
  placeholder="Username (opsional, auto-generate: MEM-0001)" 
/>
// Tidak perlu input member_id_number lagi
```

#### D. Member Profile Display
```tsx
// ❌ LAMA
<div className="profile-info">
  <p><strong>Nomor Anggota:</strong> {member.member_id_number}</p>
  <p><strong>Username:</strong> {member.username}</p>
  <p><strong>Nama:</strong> {member.full_name}</p>
</div>

// ✅ BARU
<div className="profile-info">
  <p><strong>Nomor Anggota:</strong> {member.username}</p>
  <p><strong>Nama:</strong> {member.full_name}</p>
</div>
```

### 3. **Update API Calls**

```typescript
// ❌ LAMA
const registerMember = async (data) => {
  const formData = new FormData();
  formData.append('full_name', data.full_name);
  formData.append('member_id_number', data.member_id_number); // HAPUS
  formData.append('username', data.username);
  // ...
};

// ✅ BARU
const registerMember = async (data) => {
  const formData = new FormData();
  formData.append('full_name', data.full_name);
  formData.append('username', data.username); // Opsional, auto-generate di backend
  // ...
};
```

### 4. **Update Search/Filter Functions**

```typescript
// ❌ LAMA
const searchMembers = (query: string, members: Member[]) => {
  return members.filter(member => 
    member.full_name.includes(query) ||
    member.member_id_number.includes(query) ||  // HAPUS
    member.username.includes(query)
  );
};

// ✅ BARU
const searchMembers = (query: string, members: Member[]) => {
  return members.filter(member => 
    member.full_name.includes(query) ||
    member.username.includes(query)  // Username sekarang adalah Member ID juga
  );
};
```

### 5. **Update Export/Report Functions**

```typescript
// ❌ LAMA
const exportToCSV = (members: Member[]) => {
  const csv = members.map(m => 
    `${m.member_id_number},${m.username},${m.full_name},${m.email}`
  ).join('\n');
  // ...
};

// ✅ BARU
const exportToCSV = (members: Member[]) => {
  const csv = members.map(m => 
    `${m.username},${m.full_name},${m.email}`  // username = Member ID
  ).join('\n');
  // ...
};
```

---

## 📊 Table Header Updates

Jika ada table yang menampilkan data member:

```tsx
// ❌ LAMA
<thead>
  <tr>
    <th>Nomor Anggota</th>
    <th>Username</th>
    <th>Nama Lengkap</th>
    <th>Email</th>
  </tr>
</thead>

// ✅ BARU (Opsi 1 - Gabungkan)
<thead>
  <tr>
    <th>Nomor Anggota / Username</th>
    <th>Nama Lengkap</th>
    <th>Email</th>
  </tr>
</thead>

// ✅ BARU (Opsi 2 - Hanya Nomor Anggota)
<thead>
  <tr>
    <th>Nomor Anggota</th>
    <th>Nama Lengkap</th>
    <th>Email</th>
  </tr>
</thead>
```

---

## ⚠️ Important Notes

### 1. **Username Format**
- Format baru: `MEM-0001`, `MEM-0002`, `MEM-0003`, dst (4 digit)
- Auto-generate oleh backend jika tidak diisi
- Unique dan tidak boleh duplikat

### 2. **Login Credentials**
- User login menggunakan `username` (format: `MEM-0001`)
- Password tetap sama

### 3. **Backward Compatibility**
Jika ada data lama yang masih menggunakan `member_id_number`:
- Pastikan jalankan migration: `php artisan migrate`
- Data lama akan tetap memiliki kolom username yang berbeda
- Update manual jika diperlukan

### 4. **Display Labels**
Anda bisa menggunakan label yang lebih user-friendly:
- `username` → Tampilkan sebagai **"Nomor Anggota"**
- Atau **"ID Member"**
- Atau **"Kode Member"**

---

## 🔍 Testing Checklist

- [ ] Login dengan username format baru (`MEM-0001`)
- [ ] Register member baru (auto-generate username)
- [ ] Display member list (tidak ada `member_id_number`)
- [ ] Search member by username
- [ ] Export member data
- [ ] Organization structure display
- [ ] Member profile page
- [ ] JWT token claims (tidak ada `member_id_number`)

---

## 🚀 Deployment Steps

1. **Backend:**
   ```bash
   php artisan migrate
   php artisan db:seed --class=DemoUserSeeder
   php artisan db:seed --class=MemberSeeder
   ```

2. **Frontend:**
   - Update all components yang menggunakan `member_id_number`
   - Replace dengan `username`
   - Test thoroughly
   - Deploy

---

## 💡 Example Migration Path

Jika Anda punya code base yang besar:

```typescript
// Helper function untuk transition period
const getMemberId = (member: any): string => {
  // Fallback untuk backward compatibility
  return member.username || member.member_id_number || 'N/A';
};

// Gunakan di component
<p>Nomor Anggota: {getMemberId(member)}</p>

// Setelah semua data ter-migrate, hapus fallback
<p>Nomor Anggota: {member.username}</p>
```

---

## 📞 Support

Jika ada pertanyaan atau issue:
1. Check API response structure
2. Verify migration sudah running
3. Check JWT token claims
4. Verify seeder data

---

## 🎯 Key Takeaways

✅ **`member_id_number` DIHAPUS**  
✅ **`username` SEKARANG ADALAH MEMBER ID**  
✅ **Format: `MEM-0001` (4 digit)**  
✅ **Login pakai `username` (MEM-0001)**  
✅ **Display `username` sebagai "Nomor Anggota"**

---

*Guide ini memastikan frontend Anda kompatibel dengan perubahan backend yang telah dilakukan.*
