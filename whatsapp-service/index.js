const express = require('express');
const cors = require('cors');
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode');

const app = express();
const PORT = process.env.PORT || 3001;

app.use(cors());
app.use(express.json());

let client = null;
let qrCodeData = null;
let connectionStatus = 'disconnected';
let sessionInfo = null;

function initializeClient() {
    if (client) {
        client.destroy();
    }

    client = new Client({
        authStrategy: new LocalAuth({
            dataPath: './whatsapp-session'
        }),
        puppeteer: {
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--no-first-run',
                '--no-zygote',
                '--disable-gpu'
            ]
        }
    });

    client.on('qr', async (qr) => {
        console.log('QR Code received');
        connectionStatus = 'qr_ready';
        qrCodeData = await qrcode.toDataURL(qr);
    });

    client.on('ready', async () => {
        console.log('WhatsApp client is ready!');
        connectionStatus = 'connected';
        qrCodeData = null;
        
        try {
            const info = client.info;
            sessionInfo = {
                pushname: info.pushname,
                phone: info.wid.user,
                platform: info.platform
            };
        } catch (e) {
            console.error('Error getting session info:', e);
        }
    });

    client.on('authenticated', () => {
        console.log('WhatsApp authenticated');
        connectionStatus = 'authenticated';
    });

    client.on('auth_failure', (msg) => {
        console.error('WhatsApp authentication failed:', msg);
        connectionStatus = 'auth_failed';
        qrCodeData = null;
        sessionInfo = null;
    });

    client.on('disconnected', (reason) => {
        console.log('WhatsApp disconnected:', reason);
        connectionStatus = 'disconnected';
        qrCodeData = null;
        sessionInfo = null;
    });

    client.initialize();
    connectionStatus = 'initializing';
}

// API Routes
app.get('/status', (req, res) => {
    res.json({
        success: true,
        status: connectionStatus,
        hasQR: qrCodeData !== null,
        hasSession: sessionInfo !== null
    });
});

app.get('/qr', (req, res) => {
    if (connectionStatus === 'connected') {
        return res.json({
            success: true,
            status: 'connected',
            message: 'WhatsApp already connected',
            qr: null
        });
    }

    if (!qrCodeData) {
        return res.json({
            success: false,
            status: connectionStatus,
            message: connectionStatus === 'initializing' 
                ? 'Waiting for QR code...' 
                : 'No QR code available. Try initializing first.',
            qr: null
        });
    }

    res.json({
        success: true,
        status: connectionStatus,
        qr: qrCodeData
    });
});

app.get('/session-info', (req, res) => {
    if (connectionStatus !== 'connected' || !sessionInfo) {
        return res.json({
            success: false,
            message: 'Not connected',
            session: null
        });
    }

    res.json({
        success: true,
        session: sessionInfo
    });
});

app.post('/initialize', (req, res) => {
    try {
        initializeClient();
        res.json({
            success: true,
            message: 'WhatsApp client initializing...',
            status: connectionStatus
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

app.post('/disconnect', async (req, res) => {
    try {
        if (client) {
            await client.logout();
            await client.destroy();
            client = null;
        }
        connectionStatus = 'disconnected';
        qrCodeData = null;
        sessionInfo = null;

        res.json({
            success: true,
            message: 'WhatsApp disconnected'
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

app.post('/send', async (req, res) => {
    const { phone, message } = req.body;

    if (!phone || !message) {
        return res.status(400).json({
            success: false,
            message: 'Phone and message are required'
        });
    }

    if (connectionStatus !== 'connected') {
        return res.status(400).json({
            success: false,
            message: 'WhatsApp not connected'
        });
    }

    try {
        // Format phone number
        let formattedPhone = phone.replace(/[^0-9]/g, '');
        if (formattedPhone.startsWith('0')) {
            formattedPhone = '62' + formattedPhone.substring(1);
        } else if (!formattedPhone.startsWith('62')) {
            formattedPhone = '62' + formattedPhone;
        }
        formattedPhone = formattedPhone + '@c.us';

        const result = await client.sendMessage(formattedPhone, message);
        
        res.json({
            success: true,
            message: 'Message sent successfully',
            messageId: result.id._serialized
        });
    } catch (error) {
        console.error('Send message error:', error);
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

// Health check
app.get('/health', (req, res) => {
    res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

app.listen(PORT, () => {
    console.log(`WhatsApp service running on port ${PORT}`);
    console.log('Call POST /initialize to start the WhatsApp client');
});
