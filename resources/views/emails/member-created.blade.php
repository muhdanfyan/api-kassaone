@component('mail::message')
# Akun Koperasi Anda Telah Dibuat

Halo **{{ $memberName }}**,

Akun keanggotaan koperasi Anda telah dibuat oleh admin.

**Username:** {{ $username }}  
**Password Sementara:** {{ $password }}

Silakan login ke sistem dan lengkapi data berikut:

1. Upload scan KTP
2. Upload foto selfie dengan KTP
3. Data ahli waris
4. Pilihan simpanan (Simpanan Pokok & Wajib)
5. Upload bukti pembayaran

@component('mail::button', ['url' => $loginUrl])
Login Sekarang
@endcomponent

Setelah data lengkap dan pembayaran diverifikasi, akun Anda akan aktif.

**Penting:** Ganti password Anda setelah login pertama untuk keamanan akun.

Terima kasih,  
{{ config('app.name') }}
@endcomponent
