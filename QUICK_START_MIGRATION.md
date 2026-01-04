# Quick Start - Migration `member_id_number` Removal

## 🚀 Untuk Developer Backend

### 1. Run Migration
```bash
cd d:\Projects\KASSAONEEE\api-kassaone
php artisan migrate
```

### 2. (Opsional) Reset Database dengan Data Baru
```bash
php artisan migrate:fresh --seed
```

### 3. Test API
```bash
# Test Register
curl -X POST http://localhost:8000/api/register \
  -F "full_name=Test User" \
  -F "email=test@example.com" \
  -F "password=password123" \
  -F "password_confirmation=password123" \
  -F "member_type=Biasa"

# Username akan auto-generate: MEM-0001

# Test Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"MEM-0001","password":"password123"}'
```

---

## 💻 Untuk Developer Frontend

### 1. Baca Guide Lengkap
Buka file: `FRONTEND_MIGRATION_GUIDE.md`

### 2. Update TypeScript Interface
```typescript
interface Member {
  id: string;
  username: string;  // ← Ini sekarang juga Member ID (format: MEM-0001)
  // member_id_number: string;  ← HAPUS INI
  full_name: string;
  email: string;
}
```

### 3. Update UI Components
```tsx
// Ganti semua tampilan member_id_number dengan username
<div>
  <p>Nomor Anggota: {member.username}</p>
  {/* <p>Member ID: {member.member_id_number}</p> ← HAPUS */}
</div>
```

### 4. Update API Calls
```typescript
// Hapus member_id_number dari request
const data = {
  full_name: "...",
  // member_id_number: "...",  ← HAPUS
  username: "...",  // Opsional, auto-generate di backend
};
```

---

## 📋 Checklist

### Backend:
- [ ] Migration dijalankan (`php artisan migrate`)
- [ ] Test register member baru
- [ ] Test login dengan username baru
- [ ] Verify JWT token (tidak ada `member_id_number`)
- [ ] Test member list API
- [ ] Test organization API

### Frontend:
- [ ] Update TypeScript interfaces
- [ ] Replace all `member_id_number` dengan `username`
- [ ] Update UI components
- [ ] Update forms (hapus input member_id_number)
- [ ] Update search/filter functions
- [ ] Test login form
- [ ] Test member list display

---

## 🔗 Documentation

- **Backend Details**: `BACKEND_MIGRATION_SUMMARY.md`
- **Frontend Guide**: `FRONTEND_MIGRATION_GUIDE.md`

---

## ⚡ Key Changes Summary

| Aspect | Before | After |
|--------|--------|-------|
| **Username Format** | `KASSA001` (3 digit) | `MEM-0001` (4 digit) |
| **Member ID Column** | Separate `member_id_number` | Uses `username` |
| **Login** | `KASSA001` | `MEM-0001` |
| **API Response** | Contains both fields | Only `username` |
| **Display Label** | "Username" & "Member ID" | "Nomor Anggota" (use username) |

---

## ❓ FAQ

**Q: Apakah user lama perlu update username?**  
A: Tidak wajib. User lama tetap bisa login dengan username lama. Hanya member baru yang akan dapat format `MEM-####`.

**Q: Bagaimana cara update username lama ke format baru?**  
A: Lihat section "Update Existing Members" di `BACKEND_MIGRATION_SUMMARY.md`

**Q: Apakah ini breaking change?**  
A: Ya, untuk frontend. API response structure berubah. Lihat `FRONTEND_MIGRATION_GUIDE.md`

**Q: Bagaimana rollback?**  
A: `php artisan migrate:rollback` dan restore kode lama dari git.

---

*Last Updated: 2026-01-04*
