<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi Akun Member</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-top: none;
        }
        .credentials {
            background-color: #fff;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #4CAF50;
        }
        .credentials-label {
            font-weight: bold;
            color: #666;
            margin-top: 10px;
        }
        .credentials-value {
            font-size: 16px;
            color: #333;
            margin: 5px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Selamat Datang di Koperasi!</h1>
    </div>
    
    <div class="content">
        <p>Yth. <strong>{{ $member->full_name }}</strong>,</p>
        
        <p>Akun Anda telah berhasil dibuat oleh admin. Berikut adalah informasi kredensial Anda:</p>
        
        <div class="credentials">
            <div class="credentials-label">Username:</div>
            <div class="credentials-value">{{ $member->username }}</div>
            
            <div class="credentials-label">Password Sementara:</div>
            <div class="credentials-value"><strong>{{ $temporaryPassword }}</strong></div>
            
            <div class="credentials-label">Email:</div>
            <div class="credentials-value">{{ $member->email }}</div>
        </div>
        
        <div class="warning">
            <strong>⚠️ Penting:</strong> 
            <ul>
                <li>Ini adalah password sementara yang harus diganti saat login pertama kali</li>
                <li>Simpan informasi ini dengan aman</li>
                <li>Jangan bagikan password Anda kepada siapapun</li>
            </ul>
        </div>
        
        <p><strong>Langkah Selanjutnya:</strong></p>
        <ol>
            <li>Klik tombol di bawah untuk melengkapi registrasi</li>
            <li>Upload dokumen yang diperlukan (KTP, Foto Selfie, Bukti Pembayaran)</li>
            <li>Ganti password sementara dengan password baru</li>
            <li>Tunggu verifikasi dari admin</li>
        </ol>
        
        <div style="text-align: center;">
            <a href="{{ $completeRegistrationUrl }}" class="button">Lengkapi Registrasi</a>
        </div>
        
        <p>Jika Anda memerlukan bantuan, silakan hubungi admin koperasi.</p>
        
        <p>Terima kasih,<br>
        <strong>Tim Koperasi</strong></p>
    </div>
    
    <div class="footer">
        <p>Email ini dikirim secara otomatis. Mohon tidak membalas email ini.</p>
        <p>&copy; {{ date('Y') }} Koperasi. All rights reserved.</p>
    </div>
</body>
</html>
