require('dotenv').config();

const express = require('express');
const path = require('path');
const fs = require('fs');
const multer = require('multer');
const db = require('./database');
const wa = require('./whatsapp-connection');

const cors = require('cors');

const app = express();
const PORT = process.env.PORT || 3000;
const UPLOAD_TMP = path.resolve(__dirname, '../tmp_uploads');

// Garantir diretório de upload temporário
if (!fs.existsSync(UPLOAD_TMP)) {
    fs.mkdirSync(UPLOAD_TMP, { recursive: true });
}

const upload = multer({
    dest: UPLOAD_TMP,
    limits: { fileSize: 16 * 1024 * 1024 }, // 16MB max
});

app.use(express.json());
app.use(cors());

// ========== ENDPOINTS ==========

// Health check
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        whatsapp: wa.getStatus(),
        accountId: wa.getAccountId(),
        uptime: Math.floor(process.uptime()),
    });
});

// Estatísticas
app.get('/api/stats', async (req, res) => {
    try {
        const counts = await db.getCounts(wa.getAccountId());
        res.json({
            success: true,
            accountId: wa.getAccountId(),
            connection: wa.getStatus(),
            ...counts,
        });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

// Listar chats
app.get('/api/chats', async (req, res) => {
    try {
        const chats = await db.getChats(wa.getAccountId());
        res.json({ success: true, count: chats.length, chats });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

// Buscar mensagens de um chat pelo JID
app.get('/api/messages/:jid', async (req, res) => {
    try {
        let jid = req.params.jid;
        const limit = Math.min(parseInt(req.query.limit) || 200, 500);

        // Normalizar JID
        if (!jid.includes('@')) {
            jid = jid + '@s.whatsapp.net';
        }

        const chat = await db.findChatByJid(wa.getAccountId(), jid);
        if (!chat) {
            return res.status(404).json({ success: false, error: 'Chat não encontrado.' });
        }

        const messages = await db.getMessages(chat.id, limit);
        res.json({ success: true, chatId: jid, count: messages.length, messages });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

// Enviar mensagem de texto (com suporte a reply via quotedMessageId)
app.post('/api/send-message', async (req, res) => {
    try {
        const { jid, text, quotedMessageId, sentByUserId } = req.body;
        if (!jid || !text) {
            return res.status(400).json({ success: false, error: 'jid e text são obrigatórios.' });
        }

        // Se respondendo a uma mensagem, construir opções de quote
        let options = {};
        if (quotedMessageId) {
            const normalizedJid = jid.includes('@') ? jid : jid + '@s.whatsapp.net';
            options.quoted = {
                key: { remoteJid: normalizedJid, id: quotedMessageId },
                message: { conversation: '' },
            };
        }

        const result = await wa.sendTextMessage(jid, text, options);

        // Salvar mensagem enviada no banco
        const chatType = jid.endsWith('@g.us') ? 'group' : 'individual';
        const chatDbId = await db.upsertChat(wa.getAccountId(), jid, null, chatType, 0);
        if (chatDbId) {
            const timestamp = Math.floor(Date.now() / 1000);

            // Buscar texto da mensagem citada para armazenar
            let quotedText = null;
            if (quotedMessageId) {
                const pool = db.getPool();
                const [rows] = await pool.execute(
                    'SELECT message_text, message_type FROM messages WHERE message_key = ?',
                    [quotedMessageId]
                );
                if (rows.length > 0) {
                    quotedText = rows[0].message_text;
                    // Fallback para mensagens de mídia sem texto
                    if (!quotedText) {
                        const typeLabels = { image: '\ud83d\uddbc\ufe0f Imagem', video: '\ud83c\udfa5 Video', audio: '\ud83c\udfa4 Audio', document: '\ud83d\udcc4 Documento', sticker: '\ud83e\udea7 Sticker', location: '\ud83d\udccd Localizacao', contact: '\ud83d\udccc Contato' };
                        quotedText = typeLabels[rows[0].message_type] || null;
                    }
                }
            }

            await db.insertMessage(chatDbId, {
                messageId: result.key.id,
                fromJid: wa.getConnectedJid() || '',
                messageType: 'text',
                messageText: text,
                mediaUrl: null,
                mediaMimeType: null,
                isFromMe: true,
                sentByUserId: sentByUserId || null,
                status: 'sent',
                timestamp: timestamp,
                quotedMessageId: quotedMessageId || null,
                quotedText: quotedText,
            });
            await db.updateChatLastMessage(chatDbId, timestamp);
        }

        res.json({ success: true, messageId: result.key.id });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

// Enviar mensagem com mídia (imagem, documento, etc)
app.post('/api/send-media', upload.single('file'), async (req, res) => {
    try {
        const { jid, caption, sentByUserId } = req.body;
        if (!jid || !req.file) {
            return res.status(400).json({ success: false, error: 'jid e file são obrigatórios.' });
        }

        // Renomear arquivo com extensão original
        const ext = path.extname(req.file.originalname) || '.bin';
        const newPath = req.file.path + ext;
        fs.renameSync(req.file.path, newPath);

        const result = await wa.sendMediaMessage(jid, newPath, caption || '', null);

        // Salvar no banco
        const mime = req.file.mimetype || '';
        let msgType = 'document';
        if (mime.startsWith('image/')) msgType = 'image';
        else if (mime.startsWith('video/')) msgType = 'video';
        else if (mime.startsWith('audio/')) msgType = 'audio';

        const chatType = jid.endsWith('@g.us') ? 'group' : 'individual';
        const chatDbId = await db.upsertChat(wa.getAccountId(), jid, null, chatType, 0);
        if (chatDbId) {
            // Copiar arquivo para diretório de mídia permanente
            const MEDIA_DIR = path.resolve(__dirname, '../../backend/web/uploads/media');
            if (!fs.existsSync(MEDIA_DIR)) fs.mkdirSync(MEDIA_DIR, { recursive: true });
            const mediaFileName = `${result.key.id}${ext}`;
            const mediaPath = path.join(MEDIA_DIR, mediaFileName);
            fs.copyFileSync(newPath, mediaPath);
            const mediaUrl = `/uploads/media/${mediaFileName}`;

            const timestamp = Math.floor(Date.now() / 1000);
            await db.insertMessage(chatDbId, {
                messageId: result.key.id,
                fromJid: wa.getConnectedJid() || '',
                messageType: msgType,
                messageText: caption || req.file.originalname || null,
                mediaUrl: mediaUrl,
                mediaMimeType: mime,
                isFromMe: true,
                sentByUserId: sentByUserId || null,
                status: 'sent',
                timestamp: timestamp,
            });
            await db.updateChatLastMessage(chatDbId, timestamp);
        }

        // Limpar arquivo temporário
        try { fs.unlinkSync(newPath); } catch(e) {}

        res.json({ success: true, messageId: result.key.id });
    } catch (err) {
        // Limpar arquivo temporário em caso de erro
        if (req.file) try { fs.unlinkSync(req.file.path); } catch(e) {}
        res.status(500).json({ success: false, error: err.message });
    }
});

// Sincronizar nomes de grupos
app.post('/api/sync-groups', async (req, res) => {
    try {
        await wa.syncAllGroupNames();
        res.json({ success: true, message: 'Grupos sincronizados.' });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

// Reagir a uma mensagem
app.post('/api/react-message', async (req, res) => {
    try {
        const { jid, messageId, emoji } = req.body;
        if (!jid || !messageId) {
            return res.status(400).json({ success: false, error: 'jid e messageId são obrigatórios.' });
        }

        const normalizedJid = jid.includes('@') ? jid : jid + '@s.whatsapp.net';
        const key = { remoteJid: normalizedJid, id: messageId };

        // Buscar se a mensagem é fromMe para construir a key corretamente
        const pool = db.getPool();
        const [rows] = await pool.execute(
            'SELECT is_from_me FROM messages WHERE message_key = ?',
            [messageId]
        );
        if (rows.length > 0) {
            key.fromMe = !!rows[0].is_from_me;
        }

        const sock = wa.getSocket();
        await sock.sendMessage(normalizedJid, {
            react: { text: emoji || '', key: key }
        });

        res.json({ success: true });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

// Excluir uma mensagem enviada (para todos)
app.post('/api/delete-message', async (req, res) => {
    try {
        const { jid, messageId } = req.body;
        if (!jid || !messageId) {
            return res.status(400).json({ success: false, error: 'jid e messageId são obrigatórios.' });
        }

        const normalizedJid = jid.includes('@') ? jid : jid + '@s.whatsapp.net';
        const key = { remoteJid: normalizedJid, id: messageId, fromMe: true };

        const sock = wa.getSocket();
        await sock.sendMessage(normalizedJid, { delete: key });

        // Marcar como deletada no banco
        const pool = db.getPool();
        await pool.execute(
            'UPDATE messages SET message_text = NULL, message_type = \'text\', media_url = NULL, is_deleted = 1 WHERE message_key = ?',
            [messageId]
        );

        res.json({ success: true });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

// Encaminhar mensagem para outro chat
app.post('/api/forward-message', async (req, res) => {
    try {
        const { fromJid, toJid, messageId, sentByUserId } = req.body;
        if (!fromJid || !toJid || !messageId) {
            return res.status(400).json({ success: false, error: 'fromJid, toJid e messageId são obrigatórios.' });
        }

        const normalizedTo = toJid.includes('@') ? toJid : toJid + '@s.whatsapp.net';

        // Buscar dados da mensagem original no banco
        const pool = db.getPool();
        const [rows] = await pool.execute(
            'SELECT message_text, message_type, media_url, media_mime_type FROM messages WHERE message_key = ?',
            [messageId]
        );
        if (rows.length === 0) {
            return res.status(404).json({ success: false, error: 'Mensagem original não encontrada.' });
        }

        const origMsg = rows[0];
        const sock = wa.getSocket();
        let result;

        if (origMsg.message_type === 'text' || !origMsg.media_url) {
            // Encaminhar mensagem de texto
            if (!origMsg.message_text) {
                return res.status(400).json({ success: false, error: 'Mensagem sem conteúdo para encaminhar.' });
            }
            result = await sock.sendMessage(normalizedTo, { text: origMsg.message_text });
            wa.trackSentMessage(result);
        } else {
            // Encaminhar mídia - ler arquivo do disco e reenviar
            const mediaPath = path.resolve(__dirname, '../../backend/web' + origMsg.media_url);
            if (!fs.existsSync(mediaPath)) {
                if (origMsg.message_text) {
                    result = await sock.sendMessage(normalizedTo, { text: origMsg.message_text });
                    wa.trackSentMessage(result);
                } else {
                    return res.status(400).json({ success: false, error: 'Arquivo de mídia não encontrado.' });
                }
            } else {
                const mediaBuffer = fs.readFileSync(mediaPath);
                const mime = require('mime-types').lookup(mediaPath) || origMsg.media_mime_type || 'application/octet-stream';
                let content = {};

                if (origMsg.message_type === 'image') {
                    content = { image: mediaBuffer, caption: origMsg.message_text || '', mimetype: mime };
                } else if (origMsg.message_type === 'video') {
                    content = { video: mediaBuffer, caption: origMsg.message_text || '', mimetype: mime };
                } else if (origMsg.message_type === 'audio') {
                    content = { audio: mediaBuffer, mimetype: mime, ptt: mime.includes('ogg') };
                } else if (origMsg.message_type === 'document') {
                    content = { document: mediaBuffer, mimetype: mime, fileName: path.basename(mediaPath) };
                } else if (origMsg.message_type === 'sticker') {
                    content = { sticker: mediaBuffer, mimetype: mime };
                } else {
                    content = { document: mediaBuffer, mimetype: mime, fileName: path.basename(mediaPath) };
                }

                result = await sock.sendMessage(normalizedTo, content);
                wa.trackSentMessage(result);
            }
        }

        // Salvar mensagem encaminhada no banco
        if (result && result.key) {
            const chatType = normalizedTo.endsWith('@g.us') ? 'group' : 'individual';
            const chatDbId = await db.upsertChat(wa.getAccountId(), normalizedTo, null, chatType, 0);
            if (chatDbId) {
                const timestamp = Math.floor(Date.now() / 1000);
                const fwdType = (origMsg.message_type === 'text' || !origMsg.media_url) ? 'text' : origMsg.message_type;
                const fwdMediaUrl = (fwdType !== 'text' && origMsg.media_url) ? origMsg.media_url : null;
                await db.insertMessage(chatDbId, {
                    messageId: result.key.id,
                    fromJid: wa.getConnectedJid() || '',
                    messageType: fwdType,
                    messageText: origMsg.message_text || null,
                    mediaUrl: fwdMediaUrl,
                    mediaMimeType: origMsg.media_mime_type || null,
                    isFromMe: true,
                    sentByUserId: sentByUserId || null,
                    status: 'sent',
                    timestamp: timestamp,
                });
                await db.updateChatLastMessage(chatDbId, timestamp);
            }
        }

        res.json({ success: true, messageId: result?.key?.id });
    } catch (err) {
        console.error('[forward-message] ERRO:', err.message);
        res.status(500).json({ success: false, error: err.message });
    }
});

// Editar uma mensagem enviada
app.post('/api/edit-message', async (req, res) => {
    try {
        const { jid, messageId, newText } = req.body;
        if (!jid || !messageId || !newText) {
            return res.status(400).json({ success: false, error: 'jid, messageId e newText são obrigatórios.' });
        }

        const normalizedJid = jid.includes('@') ? jid : jid + '@s.whatsapp.net';
        const key = { remoteJid: normalizedJid, id: messageId, fromMe: true };

        const sock = wa.getSocket();
        await sock.sendMessage(normalizedJid, { text: newText, edit: key });

        // Atualizar no banco
        const pool = db.getPool();
        await pool.execute(
            'UPDATE messages SET message_text = ?, is_edited = 1 WHERE message_key = ?',
            [newText, messageId]
        );

        res.json({ success: true });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

// Buscar nome de contato pelo JID
app.get('/api/contact-name/:jid', async (req, res) => {
    try {
        const pool = db.getPool();
        const [rows] = await pool.execute(
            'SELECT name FROM contacts WHERE account_id = ? AND jid = ?',
            [wa.getAccountId(), req.params.jid]
        );
        res.json({
            success: true,
            name: rows.length > 0 ? rows[0].name : null,
        });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

// Status da conexão (polling pelo painel Yii2)
app.get('/api/connection-status', async (req, res) => {
    try {
        const qrImage = await wa.getQRImage();
        res.json({
            success: true,
            status: wa.getStatus(),
            accountId: wa.getAccountId(),
            jid: wa.getConnectedJid(),
            pairingCode: wa.getPairingCode(),
            hasQR: !!wa.getQR(),
            qrImage: qrImage,
        });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

// Sincronizar mensagens recentes de um chat (buscar do WhatsApp o que falta no banco)
app.post('/api/sync-chat/:jid', async (req, res) => {
    try {
        let jid = req.params.jid;
        if (!jid.includes('@')) {
            jid = jid + '@s.whatsapp.net';
        }
        const count = Math.min(parseInt(req.query.count) || 50, 200);
        const result = await wa.fetchChatMessages(jid, count);
        res.json({ success: true, ...result });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

// Sync de mensagens recentes (para recuperar msgs perdidas por falhas de E2EE)
app.post('/api/sync-recent/:jid', async (req, res) => {
    try {
        let jid = req.params.jid;
        if (!jid.includes('@')) {
            jid = jid + '@s.whatsapp.net';
        }
        const count = Math.min(parseInt(req.query.count) || 50, 200);
        const result = await wa.syncRecentMessages(jid, count);
        res.json({ success: true, ...result });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

// Carregar historico de mensagens ate uma data alvo (background, fire-and-forget)
app.post('/api/load-history/:jid', async (req, res) => {
    try {
        let jid = req.params.jid;
        if (!jid.includes('@')) {
            jid = jid + '@s.whatsapp.net';
        }
        // Default: 01/01/2026 00:00:00 UTC
        const untilTimestamp = parseInt(req.query.until) || Math.floor(new Date('2026-01-01T00:00:00Z').getTime() / 1000);
        const result = wa.enqueueHistoryLoad(jid, untilTimestamp);
        res.json({ success: true, ...result });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

// Status do carregamento de historico de um chat
app.get('/api/load-history-status/:jid', async (req, res) => {
    try {
        let jid = req.params.jid;
        if (!jid.includes('@')) {
            jid = jid + '@s.whatsapp.net';
        }
        const status = wa.getHistoryLoadStatus(jid);
        res.json({ success: true, ...status });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

// Buscar foto de perfil de um contato (e salvar no banco)
app.get('/api/profile-pic/:jid', async (req, res) => {
    try {
        let jid = req.params.jid;
        if (!jid.includes('@')) jid = jid + '@s.whatsapp.net';
        const url = await wa.fetchProfilePicUrl(jid);
        if (url) {
            const ACCOUNT_ID = parseInt(process.env.ACCOUNT_ID || '2');
            await db.upsertContact(ACCOUNT_ID, jid, null, null, url);
        }
        res.json({ success: true, url: url || null });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

// ========== START ==========

async function start() {
    console.log('');
    console.log('========================================');
    console.log('  WPP Manager - WhatsApp Service');
    console.log(`  Account ID: ${wa.getAccountId()}`);
    console.log('========================================');
    console.log('');

    // Testar conexão com o banco
    try {
        const pool = db.getPool();
        const [rows] = await pool.execute('SELECT 1');
        console.log('✓ Banco de dados conectado.');
    } catch (err) {
        console.error('✗ Erro ao conectar ao banco:', err.message);
        process.exit(1);
    }

    // Iniciar servidor HTTP
    app.listen(PORT, () => {
        console.log(`✓ API rodando em http://localhost:${PORT}`);
        console.log(`  Health: http://localhost:${PORT}/health`);
        console.log(`  Stats:  http://localhost:${PORT}/api/stats`);
        console.log('');
    });

    // Conectar ao WhatsApp
    console.log('Conectando ao WhatsApp...');
    console.log('Se for a primeira vez, um QR Code aparecerá abaixo.');
    console.log('Escaneie com: WhatsApp > Configurações > Aparelhos Conectados\n');

    await wa.startConnection();
}

start().catch((err) => {
    console.error('Erro fatal:', err);
    process.exit(1);
});
