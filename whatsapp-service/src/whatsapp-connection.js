const { default: makeWASocket, useMultiFileAuthState, DisconnectReason, Browsers, makeCacheableSignalKeyStore, fetchLatestWaWebVersion, downloadMediaMessage } = require('@whiskeysockets/baileys');
const pino = require('pino');
const QRCode = require('qrcode');
const NodeCache = require('node-cache');
const fs = require('fs');
const path = require('path');
const db = require('./database');

const logger = pino({ level: process.env.LOG_LEVEL || 'info' });
const ACCOUNT_ID = parseInt(process.env.ACCOUNT_ID || '2');
const AUTH_DIR = process.env.AUTH_DIR || './auth_info';
const MEDIA_DIR = process.env.MEDIA_DIR || path.resolve(__dirname, '../../backend/web/uploads/media');
const PHONE_NUMBER = process.env.PHONE_NUMBER || '';

// Cache para retry de mensagens que falharam na decriptação (Bad MAC)
// O Baileys usa isso para saber quantas vezes já tentou re-solicitar uma mensagem
const msgRetryCounterCache = new NodeCache({ stdTTL: 600, checkperiod: 60 }); // 10 min TTL

let sock = null;
let connectionStatus = 'disconnected'; // disconnected, connecting, connected, qr_pending, pairing_code
let reconnectAttempt = 0;
const MAX_RECONNECT_DELAY = 120000; // 2 minutos max
const MAX_RECONNECT_ATTEMPTS = 5;
let pairingCodeRequested = false;
let currentQR = null;       // QR code string (raw data)
let currentPairingCode = null; // Pairing code de 8 dígitos
let connectedJid = null;     // JID quando conectado
let syncComplete = false;    // Flag: sync inicial concluido, eventos reais podem ser processados
let connectionStableAt = 0;  // Timestamp apos o qual a conexao é considerada estavel
// Cooldown: chats finalizados por fromMe nao podem ser reabertos por chats.update imediatamente
// Evita o ciclo: responder grupo -> finaliza -> chats.update unread=1 -> reabre
const recentlyFinalizedChats = new Map(); // chatJid -> timestamp
// Eventos de unread recebidos durante sync (antes de syncComplete) para processar depois
let pendingUnreadEvents = []; // [{id: chatJid, unreadCount: N}]
let ownLid = null;           // LID (Linked Identity) da conta, para detectar msgs do WhatsApp Web
let currentSaveCreds = null; // Referência à função saveCreds para graceful shutdown

// === History loading state ===
const historyLoadState = new Map(); // jid -> { status: 'loading'|'done'|'error', iteration, oldestTs }
const historyLoadQueue = [];        // { jid, untilTimestamp }
let historyLoadProcessing = false;

// Limpar credenciais parciais para evitar login corrompido
function cleanAuthDir() {
    try {
        const dir = path.resolve(AUTH_DIR);
        if (fs.existsSync(dir)) {
            const files = fs.readdirSync(dir);
            for (const file of files) {
                fs.unlinkSync(path.join(dir, file));
            }
            logger.info('Auth dir limpo - credenciais parciais removidas.');
        }
    } catch (err) {
        logger.error(`Erro ao limpar auth dir: ${err.message}`);
    }
}

// Limpar sessões corrompidas no startup (arquivos vazios ou com JSON inválido)
// Sessões corrompidas são a causa principal de PreKeyError
function cleanCorruptedSessions() {
    try {
        const dir = path.resolve(AUTH_DIR);
        if (!fs.existsSync(dir)) return;
        let cleaned = 0;
        const files = fs.readdirSync(dir);
        for (const file of files) {
            if (!file.startsWith('session-') && !file.startsWith('sender-key-')) continue;
            const filePath = path.join(dir, file);
            const stat = fs.statSync(filePath);
            // Arquivo vazio ou muito pequeno = corrompido
            if (stat.size < 10) {
                fs.unlinkSync(filePath);
                cleaned++;
                continue;
            }
            // Verificar se o JSON é válido
            try {
                JSON.parse(fs.readFileSync(filePath, 'utf8'));
            } catch (e) {
                fs.unlinkSync(filePath);
                cleaned++;
            }
        }
        if (cleaned > 0) {
            logger.info(`[auth] ${cleaned} sessão(ões) corrompida(s) removida(s) no startup.`);
        }
    } catch (err) {
        logger.error(`[auth] Erro ao limpar sessões: ${err.message}`);
    }
}

// Tipos válidos no ENUM do banco
const VALID_TYPES = ['text', 'image', 'video', 'audio', 'document', 'sticker', 'location', 'contact'];

// Extensões de arquivo por mime type
const MIME_EXTENSIONS = {
    'image/jpeg': '.jpg', 'image/png': '.png', 'image/gif': '.gif', 'image/webp': '.webp',
    'video/mp4': '.mp4', 'video/3gpp': '.3gp',
    'audio/ogg; codecs=opus': '.ogg', 'audio/mpeg': '.mp3', 'audio/mp4': '.m4a', 'audio/ogg': '.ogg',
    'application/pdf': '.pdf', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': '.xlsx',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document': '.docx',
};

// Cache de fotos de perfil já buscadas nesta sessão (evita chamadas repetidas)
const profilePicCache = new NodeCache({ stdTTL: 3600, checkperiod: 120 }); // 1h TTL

// Buscar URL da foto de perfil de um contato
async function fetchProfilePicUrl(jid) {
    if (!sock || !jid) return undefined;
    // Não buscar para grupos, newsletters, broadcasts, LIDs
    if (!jid.endsWith('@s.whatsapp.net')) return undefined;

    const cached = profilePicCache.get(jid);
    if (cached !== undefined) return cached || undefined; // null = sem foto (cached)

    try {
        const url = await sock.profilePictureUrl(jid, 'image');
        profilePicCache.set(jid, url || null);
        return url || undefined;
    } catch (err) {
        // 404 = sem foto, 401 = privacidade - ambos normais
        profilePicCache.set(jid, null);
        return undefined;
    }
}

// Tipos de mensagem que contêm mídia baixável
const MEDIA_TYPES = ['image', 'video', 'audio', 'document', 'sticker'];

// Baixar e salvar mídia de uma mensagem
async function downloadAndSaveMedia(msg, messageType) {
    try {
        if (!MEDIA_TYPES.includes(messageType)) return null;
        if (!msg.message) return null;

        // Garantir que o diretório existe
        if (!fs.existsSync(MEDIA_DIR)) {
            fs.mkdirSync(MEDIA_DIR, { recursive: true });
        }

        const buffer = await downloadMediaMessage(msg, 'buffer', {});
        if (!buffer || buffer.length === 0) return null;

        // Determinar extensão e nome do arquivo
        const msgContent = msg.message.imageMessage || msg.message.videoMessage ||
            msg.message.audioMessage || msg.message.documentMessage || msg.message.stickerMessage;
        const mime = msgContent?.mimetype || '';
        const ext = MIME_EXTENSIONS[mime] || (mime.startsWith('image/') ? '.jpg' : mime.startsWith('audio/') ? '.ogg' : '.bin');
        const fileName = `${msg.key.id}${ext}`;
        const filePath = path.join(MEDIA_DIR, fileName);

        fs.writeFileSync(filePath, buffer);
        logger.info(`Mídia salva: ${fileName} (${(buffer.length / 1024).toFixed(1)}KB)`);

        // Retornar URL relativa (acessível via web)
        return `/uploads/media/${fileName}`;
    } catch (err) {
        logger.debug(`Erro ao baixar mídia: ${err.message}`);
        return null;
    }
}

// Mensagens de sistema/protocolo que devem ser ignoradas (não salvar no banco)
const SKIP_MESSAGE_TYPES = [
    'protocolMessage',      // Leitura, revogação, etc
    'senderKeyDistributionMessage', // Distribuição de chaves de criptografia
    'messageContextInfo',   // Contexto interno
];

// Extrair tipo e texto da mensagem Baileys
function parseMessage(msg) {
    const m = msg.message;
    const emptyResult = { type: null, text: null, mediaMime: null, skip: true, quotedStanzaId: null, quotedText: null };
    if (!m) return emptyResult;

    // Verificar se é mensagem de sistema que deve ser ignorada
    const keys = Object.keys(m);
    if (keys.length === 1 && SKIP_MESSAGE_TYPES.includes(keys[0])) {
        return emptyResult;
    }
    // protocolMessage pode vir junto com outro tipo — ignorar se sozinho
    if (m.protocolMessage && keys.length <= 2) {
        return emptyResult;
    }

    let result;
    if (m.conversation) result = { type: 'text', text: m.conversation, mediaMime: null };
    else if (m.extendedTextMessage) result = { type: 'text', text: m.extendedTextMessage.text, mediaMime: null };
    else if (m.editedMessage) {
        // Edições são tratadas via evento 'messages.update' — skip aqui
        return emptyResult;
    }
    else if (m.imageMessage) result = { type: 'image', text: m.imageMessage.caption || null, mediaMime: m.imageMessage.mimetype };
    else if (m.videoMessage) result = { type: 'video', text: m.videoMessage.caption || null, mediaMime: m.videoMessage.mimetype };
    else if (m.audioMessage) result = { type: 'audio', text: null, mediaMime: m.audioMessage.mimetype };
    else if (m.documentMessage) result = { type: 'document', text: m.documentMessage.fileName || null, mediaMime: m.documentMessage.mimetype };
    else if (m.stickerMessage) result = { type: 'sticker', text: null, mediaMime: m.stickerMessage.mimetype };
    else if (m.contactMessage) result = { type: 'contact', text: m.contactMessage.displayName || null, mediaMime: null };
    else if (m.locationMessage) result = { type: 'location', text: `${m.locationMessage.degreesLatitude},${m.locationMessage.degreesLongitude}`, mediaMime: null };
    else if (m.reactionMessage) {
        // Reações são tratadas via evento 'messages.reaction' — skip aqui
        return emptyResult;
    }
    else {
        const firstKey = keys.filter(k => !SKIP_MESSAGE_TYPES.includes(k))[0];
        if (!firstKey) return emptyResult;
        result = { type: 'text', text: `[${firstKey}]`, mediaMime: null };
    }

    // Extrair contextInfo para mensagens citadas (quotes/replies)
    let quotedStanzaId = null;
    let quotedText = null;
    const contentWithContext = m.extendedTextMessage || m.imageMessage || m.videoMessage
        || m.audioMessage || m.documentMessage;
    if (contentWithContext?.contextInfo) {
        const ctx = contentWithContext.contextInfo;
        quotedStanzaId = ctx.stanzaId || null;
        if (ctx.quotedMessage) {
            const qm = ctx.quotedMessage;
            // Tentar extrair texto/caption primeiro
            quotedText = qm.conversation
                || qm.extendedTextMessage?.text
                || qm.imageMessage?.caption
                || qm.videoMessage?.caption
                || qm.documentMessage?.fileName
                || null;
            // Fallback para mensagens de mídia sem texto/caption
            if (!quotedText) {
                if (qm.imageMessage) quotedText = '\ud83d\uddbc\ufe0f Imagem';
                else if (qm.videoMessage) quotedText = '\ud83c\udfa5 Video';
                else if (qm.audioMessage) quotedText = '\ud83c\udfa4 Audio';
                else if (qm.documentMessage) quotedText = '\ud83d\udcc4 Documento';
                else if (qm.stickerMessage) quotedText = '\ud83e\udea7 Sticker';
                else if (qm.locationMessage) quotedText = '\ud83d\udccd Localizacao';
                else if (qm.contactMessage) quotedText = '\ud83d\udccc Contato';
            }
        }
    }

    // Garantir que o tipo é válido para o ENUM do banco
    if (result && result.type && !VALID_TYPES.includes(result.type)) {
        result.type = 'text';
    }
    return { ...result, skip: false, quotedStanzaId, quotedText };
}

// Verificar se mensagem foi enviada por nós (inclui WhatsApp Web via LID)
function isOwnMessage(msg) {
    if (msg.key.fromMe) return true;
    // Mensagens do WhatsApp Web chegam com fromMe=false mas participant=nossoLID@lid
    if (ownLid && msg.key.participant) {
        const participantBase = msg.key.participant.split(':')[0].split('@')[0];
        const lidBase = ownLid.split(':')[0].split('@')[0];
        if (participantBase === lidBase) return true;
    }
    return false;
}

// Determinar tipo de chat pelo JID
function getChatType(jid) {
    if (!jid) return 'individual';
    if (jid.endsWith('@g.us')) return 'group';
    if (jid.endsWith('@broadcast')) return 'broadcast';
    return 'individual';
}

// Extrair número de telefone do JID
function jidToPhone(jid) {
    if (!jid) return null;
    const match = jid.match(/^(\d+)@/);
    return match ? match[1] : null;
}

// Processar e salvar uma mensagem no banco
async function processMessage(msg, chatDbId, fromMe) {
    const { type, text, mediaMime, skip, quotedStanzaId, quotedText } = parseMessage(msg);

    // Ignorar mensagens de sistema/protocolo
    if (skip || !type) return null;

    // Se fromMe não foi passado explicitamente, usar isOwnMessage
    if (fromMe === undefined) fromMe = isOwnMessage(msg);

    const timestamp = msg.messageTimestamp
        ? (typeof msg.messageTimestamp === 'object' ? msg.messageTimestamp.low : Number(msg.messageTimestamp))
        : Math.floor(Date.now() / 1000);

    // Baixar mídia se for mensagem com arquivo
    let mediaUrl = null;
    if (MEDIA_TYPES.includes(type) && msg.message) {
        mediaUrl = await downloadAndSaveMedia(msg, type);
    }

    await db.insertMessage(chatDbId, {
        messageId: msg.key.id,
        fromJid: msg.key.participant || msg.key.remoteJid,
        messageType: type,
        messageText: text,
        mediaUrl: mediaUrl,
        mediaMimeType: mediaMime,
        isFromMe: fromMe ? 1 : 0,
        status: fromMe ? 'sent' : 'delivered',
        timestamp: timestamp,
        quotedMessageId: quotedStanzaId,
        quotedText: quotedText,
    });

    return timestamp;
}

// Iniciar conexão com WhatsApp
async function startConnection() {
    connectionStatus = 'connecting';
    pairingCodeRequested = false;
    logger.info(`Iniciando conexao WhatsApp para account_id=${ACCOUNT_ID}...`);

    // Limpar sessões corrompidas antes de conectar
    cleanCorruptedSessions();

    // Buscar versão mais recente do WhatsApp Web para evitar erro 405
    let waVersion;
    try {
        const { version } = await fetchLatestWaWebVersion({});
        waVersion = version;
        logger.info(`Versao WhatsApp Web: ${version.join('.')}`);
    } catch (err) {
        waVersion = [2, 3000, 1034187832];
        logger.warn(`Falha ao buscar versao, usando fallback: ${waVersion.join('.')}`);
    }

    const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR);
    currentSaveCreds = saveCreds; // Guardar ref para graceful shutdown

    // Se tem número de telefone e não tem credenciais, usar pairing code
    const isNewLogin = !state.creds.registered;

    sock = makeWASocket({
        version: waVersion,
        auth: {
            creds: state.creds,
            keys: makeCacheableSignalKeyStore(state.keys, logger),
        },
        browser: Browsers.macOS('Desktop'),
        syncFullHistory: true,
        logger: logger.child({ module: 'baileys' }),
        generateHighQualityLinkPreview: false,
        markOnlineOnConnect: false,
        // Retry de mensagens que falharam na decriptação (Bad MAC)
        // Quando o Baileys não consegue decifrar uma msg, ele re-solicita ao WhatsApp
        msgRetryCounterCache,
        maxMsgRetryCount: 5, // tentar até 5 vezes
        // Callback para fornecer o conteúdo de uma mensagem para retry
        // O WhatsApp pede o conteúdo original para re-encriptar
        getMessage: async (key) => {
            try {
                const pool = db.getPool();
                const [rows] = await pool.execute(
                    'SELECT message_text, message_type FROM messages WHERE message_key = ? LIMIT 1',
                    [key.id]
                );
                if (rows.length > 0 && rows[0].message_text) {
                    return { conversation: rows[0].message_text };
                }
            } catch (err) {
                logger.debug(`getMessage erro: ${err.message}`);
            }
            return undefined;
        },
    });

    // Solicitar pairing code se for novo login com número configurado
    if (isNewLogin && PHONE_NUMBER) {
        // Esperar conexão websocket estabilizar antes de solicitar pairing code
        setTimeout(async () => {
            if (pairingCodeRequested || connectionStatus === 'connected') return;
            try {
                pairingCodeRequested = true;
                connectionStatus = 'pairing_code';
                const code = await sock.requestPairingCode(PHONE_NUMBER);
                currentPairingCode = code;
                logger.info(`Pairing code gerado: ${code} - acesse a tela de conexao no painel web.`);
            } catch (err) {
                console.log('\nErro ao solicitar pairing code: ' + err.message);
                console.log('Tentando via QR code...\n');
                pairingCodeRequested = false;
            }
        }, 10000);
    }

    // Salvar credenciais quando atualizadas
    sock.ev.on('creds.update', saveCreds);

    // Salvamento periódico do auth state a cada 5 min (proteção contra crash)
    // Se o processo morrer abruptamente, no máximo 5 min de estado são perdidos
    // Também monitora pre-keys disponíveis
    setInterval(async () => {
        try {
            await saveCreds();
            // Monitorar pre-keys: verificar quantas restam em disco
            const authDir = path.resolve(AUTH_DIR);
            const preKeyFiles = fs.readdirSync(authDir).filter(f => f.startsWith('pre-key-'));
            if (preKeyFiles.length < 10) {
                logger.warn(`[auth] ALERTA: Apenas ${preKeyFiles.length} pre-keys restantes! Risco de falha de decriptação.`);
            }
        } catch (err) {
            logger.error(`[auth] Erro no salvamento periódico: ${err.message}`);
        }
    }, 300000); // 5 minutos

    // Status da conexão
    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr && !pairingCodeRequested) {
            connectionStatus = 'qr_pending';
            currentQR = qr;
            logger.info('Novo QR code disponivel - acesse a tela de conexao no painel web.');
        }

        if (connection === 'open') {
            connectionStatus = 'connected';
            reconnectAttempt = 0;
            currentQR = null;
            currentPairingCode = null;
            syncComplete = false;
            connectionStableAt = Date.now() + 2 * 60 * 1000; // 2 min para estabilizar (sync inicial ~30s)
            connectedJid = sock.user?.id || '';
            ownLid = sock.user?.lid || null;
            logger.info(`Conectado ao WhatsApp! JID: ${connectedJid} LID: ${ownLid}`);
            await db.updateAccountStatus(ACCOUNT_ID, true, connectedJid);

            // Sincronizar nomes de grupos após conexão (com delay para estabilizar)
            setTimeout(() => syncAllGroupNames(), 5000);

            // Aguardar sync inicial antes de processar eventos de leitura
            setTimeout(async () => {
                syncComplete = true;
                logger.info('Sync inicial concluido - deteccao de leitura ativada.');

                // Processar eventos de unread que chegaram durante o sync (chats.update)
                if (pendingUnreadEvents.length > 0) {
                    logger.info(`[pos-sync] Processando ${pendingUnreadEvents.length} evento(s) de unread do sync...`);
                    try {
                        const pool = db.getPool();
                        for (const evt of pendingUnreadEvents) {
                            if (evt.id.endsWith('@newsletter') || evt.id.endsWith('@lid')) continue;
                            const [chatRows] = await pool.execute(
                                'SELECT id, chat_name, chat_type FROM chats WHERE account_id = ? AND chat_id = ?',
                                [ACCOUNT_ID, evt.id]
                            );
                            if (chatRows.length > 0) {
                                const chat = chatRows[0];
                                const [existing] = await pool.execute(
                                    'SELECT id FROM conversas WHERE chat_id = ? AND status IN (?, ?)',
                                    [chat.id, 'aguardando', 'em_atendimento']
                                );
                                if (existing.length === 0) {
                                    await db.reabrirConversa(chat.id, ACCOUNT_ID);
                                    logger.info(`[pos-sync] Conversa reaberta para ${chat.chat_name || evt.id} (unread=${evt.unreadCount})`);
                                }
                            }
                        }
                    } catch (err) {
                        logger.error(`[pos-sync] Erro eventos: ${err.message}`);
                    }
                    pendingUnreadEvents = [];
                }

                // Backup: verificar chats marcados como "não lido" no WhatsApp sem conversa ativa
                // Apenas unread_count = -1 (marcado manualmente), NÃO unread_count >= 1
                // pois o unread_count pode estar defasado no DB após restart
                // Chats com msgs não lidas reais são cobertos pelos pendingUnreadEvents acima
                try {
                    const pool = db.getPool();
                    const [markedChats] = await pool.execute(
                        `SELECT c.id, c.chat_id, c.chat_name, c.chat_type, c.unread_count
                         FROM chats c
                         LEFT JOIN conversas cv ON cv.chat_id = c.id AND cv.status IN ('aguardando', 'em_atendimento')
                         WHERE c.account_id = ? AND c.unread_count = -1 AND cv.id IS NULL
                           AND c.chat_id NOT LIKE '%@newsletter' AND c.chat_id NOT LIKE '%@lid'
                           AND c.chat_id != 'status@broadcast'`,
                        [ACCOUNT_ID]
                    );
                    if (markedChats.length > 0) {
                        logger.info(`[pos-sync] ${markedChats.length} chat(s) não lidos no DB sem conversa ativa...`);
                        for (const chat of markedChats) {
                            await db.reabrirConversa(chat.id, ACCOUNT_ID);
                            logger.info(`[pos-sync] Conversa reaberta para ${chat.chat_name || chat.chat_id} (unread=${chat.unread_count})`);
                        }
                    }
                } catch (err) {
                    logger.error(`[pos-sync] Erro backup DB: ${err.message}`);
                }

                // Finalizar conversas aguardando onde a ultima msg é fromMe (já respondemos)
                // MAS respeitar chats marcados como não lidos (unread_count >= 1 ou -1)
                try {
                    const pool = db.getPool();
                    const [staleConversas] = await pool.execute(
                        `SELECT cv.id, cv.cliente_nome, ch.id as chat_db_id, ch.chat_id as chat_jid
                         FROM conversas cv
                         INNER JOIN chats ch ON ch.id = cv.chat_id
                         INNER JOIN messages m ON m.chat_id = ch.id
                           AND m.id = (SELECT MAX(m2.id) FROM messages m2 WHERE m2.chat_id = ch.id)
                         WHERE cv.status = 'aguardando'
                           AND ch.account_id = ?
                           AND m.is_from_me = 1
                           AND ch.unread_count = 0`,
                        [ACCOUNT_ID]
                    );
                    if (staleConversas.length > 0) {
                        logger.info(`[pos-sync] ${staleConversas.length} conversa(s) aguardando com ultima msg fromMe...`);
                        for (const cv of staleConversas) {
                            await db.finalizarConversaLida(cv.chat_db_id);
                            recentlyFinalizedChats.set(cv.chat_jid, Date.now());
                            logger.info(`[pos-sync] Conversa #${cv.id} (${cv.cliente_nome}) finalizada - ultima msg é fromMe`);
                        }
                    }
                } catch (err) {
                    logger.error(`[pos-sync] Erro finalizar fromMe: ${err.message}`);
                }
            }, 30000);

            // Reconciliação periódica: a cada 2 min
            setInterval(async () => {
                if (!syncComplete || !sock) return;
                try {
                    const pool = db.getPool();

                    // 1) Finalizar conversas cuja ultima msg é fromMe (respondemos mas evento não chegou)
                    // Respeitar chats marcados como não lidos (unread_count >= 1 ou -1)
                    const [fromMeStale] = await pool.execute(
                        `SELECT cv.id, cv.cliente_nome, ch.id as chat_db_id, ch.chat_id as chat_jid
                         FROM conversas cv
                         INNER JOIN chats ch ON ch.id = cv.chat_id
                         INNER JOIN messages m ON m.chat_id = ch.id
                           AND m.id = (SELECT MAX(m2.id) FROM messages m2 WHERE m2.chat_id = ch.id)
                         WHERE cv.status = 'aguardando'
                           AND ch.account_id = ?
                           AND m.is_from_me = 1
                           AND ch.unread_count = 0`,
                        [ACCOUNT_ID]
                    );
                    for (const cv of fromMeStale) {
                        await db.finalizarConversaLida(cv.chat_db_id);
                        recentlyFinalizedChats.set(cv.chat_jid, Date.now());
                        logger.info(`[reconciliacao] Conversa #${cv.id} (${cv.cliente_nome}) finalizada - ultima msg é fromMe`);
                    }

                    // 2) Reabrir chats marcados como "não lido" no WhatsApp (unread_count = -1)
                    // Nota: NÃO usar unread_count >= 1 aqui pois pode estar defasado no DB
                    // O unread_count >= 1 só é verificado no check pós-sync (único, após conectar)
                    const [unreadNoConv] = await pool.execute(
                        `SELECT c.id, c.chat_id, c.chat_name, c.unread_count
                         FROM chats c
                         LEFT JOIN conversas cv ON cv.chat_id = c.id AND cv.status IN ('aguardando', 'em_atendimento')
                         WHERE c.account_id = ? AND c.unread_count = -1 AND cv.id IS NULL
                           AND c.chat_id NOT LIKE '%@newsletter' AND c.chat_id NOT LIKE '%@lid'
                           AND c.chat_id != 'status@broadcast'`,
                        [ACCOUNT_ID]
                    );
                    for (const chat of unreadNoConv) {
                        await db.reabrirConversa(chat.id, ACCOUNT_ID);
                        logger.info(`[reconciliacao] Conversa reaberta para ${chat.chat_name || chat.chat_id} (unread=${chat.unread_count})`);
                    }

                    // 3) Forçar refresh de estado: para cada chat aguardando, subscrever presença
                    const [filaChats] = await pool.execute(
                        `SELECT DISTINCT ch.chat_id as jid
                         FROM conversas cv
                         INNER JOIN chats ch ON ch.id = cv.chat_id
                         WHERE cv.status = 'aguardando' AND ch.account_id = ?
                           AND ch.chat_id NOT LIKE '%@g.us'`,
                        [ACCOUNT_ID]
                    );
                    for (const chat of filaChats) {
                        try { await sock.presenceSubscribe(chat.jid); } catch (e) { /* ignore */ }
                    }
                } catch (err) {
                    logger.error(`[reconciliacao] Erro: ${err.message}`);
                }
            }, 120000); // 2 minutos
        }

        if (connection === 'close') {
            connectionStatus = 'disconnected';
            await db.updateAccountStatus(ACCOUNT_ID, false, null);

            const statusCode = lastDisconnect?.error?.output?.statusCode;

            // 405 = rate limit do WhatsApp - NÃO reconectar (piora o bloqueio)
            if (statusCode === 405) {
                reconnectAttempt++;
                if (reconnectAttempt >= MAX_RECONNECT_ATTEMPTS) {
                    // Limpar depois de um delay para não conflitar com creds.update
                    setTimeout(() => cleanAuthDir(), 2000);
                    console.log('\n============================================');
                    console.log('  BLOQUEADO PELO WHATSAPP (Rate Limit)');
                    console.log('  ');
                    console.log('  O WhatsApp bloqueou temporariamente');
                    console.log('  as tentativas de conexao.');
                    console.log('  ');
                    console.log('  Aguarde 15-30 minutos e reinicie o servico:');
                    console.log('  node src/server.js');
                    console.log('============================================\n');
                    return; // Para de reconectar
                }
                // Nas primeiras tentativas, usar delay longo
                const delay = Math.min(30000 * Math.pow(2, reconnectAttempt - 1), MAX_RECONNECT_DELAY);
                const delaySec = Math.round(delay / 1000);
                console.log(`\n⟳ Rate limit (405). Tentativa ${reconnectAttempt}/${MAX_RECONNECT_ATTEMPTS}. Aguardando ${delaySec}s...`);
                // Limpar auth dir LOGO ANTES de reconectar (após creds.update ter escrito)
                setTimeout(() => {
                    cleanAuthDir();
                    startConnection();
                }, delay);
                return;
            }

            const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

            if (shouldReconnect) {
                reconnectAttempt++;
                const delay = Math.min(10000 * Math.pow(2, reconnectAttempt - 1), MAX_RECONNECT_DELAY);
                const delaySec = Math.round(delay / 1000);
                console.log(`\n⟳ Desconectado (code=${statusCode}). Tentativa ${reconnectAttempt}. Reconectando em ${delaySec}s...`);
                setTimeout(startConnection, delay);
            } else if (statusCode === DisconnectReason.loggedOut) {
                cleanAuthDir();
                logger.warn('Deslogado do WhatsApp (loggedOut). Escaneie o QR code novamente.');
                console.log('\n✗ Deslogado. Reinicie o serviço para parear novamente.\n');
            } else {
                // Status desconhecido - tentar reconectar sem limpar credenciais
                reconnectAttempt++;
                const delay = Math.min(15000 * Math.pow(2, reconnectAttempt - 1), MAX_RECONNECT_DELAY);
                const delaySec = Math.round(delay / 1000);
                logger.warn(`Desconectado com status desconhecido (code=${statusCode}). Reconectando em ${delaySec}s...`);
                setTimeout(startConnection, delay);
            }
        }
    });

    // Sync de contatos
    sock.ev.on('contacts.upsert', async (contacts) => {
        logger.info(`Recebidos ${contacts.length} contatos do sync.`);
        let count = 0;
        for (const contact of contacts) {
            try {
                const phone = jidToPhone(contact.id);
                const picUrl = await fetchProfilePicUrl(contact.id);
                await db.upsertContact(ACCOUNT_ID, contact.id, contact.name || contact.notify || null, phone, picUrl);
                count++;
            } catch (err) {
                logger.error(`Erro ao salvar contato ${contact.id}: ${err.message}`);
            }
        }
        logger.info(`${count} contatos salvos no banco.`);
    });

    // Sync de chats
    sock.ev.on('chats.upsert', async (chats) => {
        logger.info(`Recebidos ${chats.length} chats do sync.`);
        for (const chat of chats) {
            try {
                if (chat.id.endsWith('@lid')) continue;
                const chatType = getChatType(chat.id);
                const chatName = chat.name || chat.subject || null;
                await db.upsertChat(ACCOUNT_ID, chat.id, chatName, chatType, chat.unreadCount || 0);
            } catch (err) {
                logger.error(`Erro ao salvar chat ${chat.id}: ${err.message}`);
            }
        }
    });

    // Sync de histórico de mensagens (bulk - sync inicial)
    sock.ev.on('messaging-history.set', async ({ messages, chats, contacts, isLatest }) => {
        logger.info(`History sync: ${messages?.length || 0} msgs, ${chats?.length || 0} chats, ${contacts?.length || 0} contatos (isLatest=${isLatest})`);

        // Salvar contatos do sync
        if (contacts && contacts.length > 0) {
            for (const contact of contacts) {
                try {
                    const phone = jidToPhone(contact.id);
                    await db.upsertContact(ACCOUNT_ID, contact.id, contact.name || contact.notify || null, phone);
                } catch (err) { /* ignore duplicates */ }
            }
        }

        // Salvar chats do sync
        if (chats && chats.length > 0) {
            for (const chat of chats) {
                try {
                    if (chat.id.endsWith('@lid')) continue;
                    const chatType = getChatType(chat.id);
                    await db.upsertChat(ACCOUNT_ID, chat.id, chat.name || chat.subject || null, chatType, chat.unreadCount || 0);
                } catch (err) { /* ignore */ }
            }
        }

        // Salvar mensagens do sync
        if (messages && messages.length > 0) {
            let saved = 0;
            for (const msg of messages) {
                try {
                    const chatJid = msg.key.remoteJid;
                    if (!chatJid || chatJid.endsWith('@lid')) continue;

                    const chatType = getChatType(chatJid);
                    const chatDbId = await db.upsertChat(ACCOUNT_ID, chatJid, null, chatType, null);
                    if (!chatDbId) continue;

                    const ts = await processMessage(msg, chatDbId);
                    if (ts) {
                        await db.updateChatLastMessage(chatDbId, ts);
                        saved++;
                        // NÃO criar conversas no history sync - apenas salvar mensagens/chats.
                        // Conversas na fila são criadas apenas pelo messages.upsert (tipo notify)
                        // para evitar inundar a fila com histórico antigo após re-pareamento.
                    }
                } catch (err) {
                    logger.debug(`Erro msg sync: ${err.message}`);
                }
            }
            logger.info(`${saved}/${messages.length} mensagens do history sync salvas.`);
        }
    });

    // Mensagens em tempo real (novas mensagens)
    sock.ev.on('messages.upsert', async ({ messages, type }) => {
        if (type !== 'notify') return; // Só processa mensagens novas

        for (const msg of messages) {
            try {
                const chatJid = msg.key.remoteJid;
                if (!chatJid || chatJid === 'status@broadcast') continue;

                // Ignorar JIDs LID (Linked Identity) - geram chats duplicados
                if (chatJid.endsWith('@lid')) continue;

                const chatType = getChatType(chatJid);
                const pushName = msg.pushName || null;
                const fromMe = isOwnMessage(msg);

                // Para grupos, NÃO usar pushName como nome do chat (pushName é do remetente!)
                // Para chats individuais, pushName É o nome do contato
                let chatName = null;
                if (chatType === 'individual' && !fromMe) {
                    chatName = pushName;
                } else if (chatType === 'group') {
                    chatName = await fetchGroupName(chatJid);
                }

                const chatDbId = await db.upsertChat(ACCOUNT_ID, chatJid, chatName, chatType, null);
                if (!chatDbId) continue;

                // Salvar/atualizar contato (o remetente da mensagem)
                const contactJid = fromMe ? chatJid : (msg.key.participant || chatJid);
                const phone = jidToPhone(contactJid);
                if (!fromMe) {
                    const picUrl = await fetchProfilePicUrl(contactJid);
                    await db.upsertContact(ACCOUNT_ID, contactJid, pushName, phone, picUrl);
                }

                const ts = await processMessage(msg, chatDbId, fromMe);
                if (!ts) continue; // Mensagem de sistema ignorada

                await db.updateChatLastMessage(chatDbId, ts);

                // Auto-criar conversa na fila quando chega msg (nao fromMe)
                if (!fromMe) {
                    const clienteNumero = chatType === 'individual'
                        ? (jidToPhone(chatJid) || chatJid)
                        : chatJid;
                    const clienteNome = chatType === 'individual'
                        ? (chatName || pushName)
                        : (chatName || chatJid);
                    await db.upsertConversa(chatDbId, ACCOUNT_ID, clienteNumero, clienteNome);
                }

                // Quando NÓS respondemos (via WhatsApp Web/celular), finalizar conversa aguardando.
                if (fromMe && syncComplete) {
                    await db.finalizarConversaLida(chatDbId);
                    // Cooldown: impedir que chats.update reabra imediatamente (especialmente em grupos)
                    recentlyFinalizedChats.set(chatJid, Date.now());
                }

                const { type: msgType, text } = parseMessage(msg);
                const direction = fromMe ? '→' : '←';
                logger.info(`${direction} [${chatJid}] (${msgType}) ${(text || '').substring(0, 50)}`);
            } catch (err) {
                logger.error(`Erro ao processar mensagem: ${err.message}`);
            }
        }
    });

    // Atualização de status de mensagem (lido, entregue, etc) e edições
    sock.ev.on('messages.update', async (updates) => {
        for (const update of updates) {
            try {
                // Handle status updates
                if (update.update?.status) {
                    const statusMap = { 2: 'sent', 3: 'delivered', 4: 'read' };
                    const status = statusMap[update.update.status];
                    if (status && update.key?.id) {
                        const dbPool = db.getPool();
                        await dbPool.execute(
                            'UPDATE messages SET status = ? WHERE message_key = ?',
                            [status, update.key.id]
                        );
                    }
                }

                // Handle message edits
                if (update.update?.message?.editedMessage) {
                    const editedMsg = update.update.message.editedMessage.message;
                    const newText = editedMsg?.conversation
                        || editedMsg?.extendedTextMessage?.text
                        || null;
                    if (newText && update.key?.id) {
                        const dbPool = db.getPool();
                        await dbPool.execute(
                            'UPDATE messages SET message_text = ?, is_edited = 1 WHERE message_key = ?',
                            [newText, update.key.id]
                        );
                        logger.info(`Mensagem editada: ${update.key.id}`);
                    }
                }
            } catch (err) { /* ignore */ }
        }
    });

    // Handle reactions (reações vinculadas à mensagem original)
    sock.ev.on('messages.reaction', async (reactions) => {
        for (const { reaction, key } of reactions) {
            try {
                if (!key?.id) continue;
                const emoji = reaction.text || ''; // string vazia = remover reação
                const senderJid = reaction.key?.participant || reaction.key?.remoteJid || '';
                const timestamp = Math.floor(Date.now() / 1000);

                await db.updateMessageReaction(key.id, emoji, senderJid, timestamp);
                logger.info(`Reação ${emoji || '(removida)'} na msg ${key.id} por ${senderJid}`);

                // Se NÓS reagimos a uma msg (fromMe), finalizar conversa (equivale a "responder")
                const isOwnReaction = reaction.key?.fromMe || isOwnMessage({ key: reaction.key });
                if (isOwnReaction && emoji && syncComplete) {
                    const chatJid = key.remoteJid;
                    if (chatJid) {
                        const chatRow = await db.findChatByJid(ACCOUNT_ID, chatJid);
                        if (chatRow) {
                            await db.finalizarConversaLida(chatRow.id);
                            recentlyFinalizedChats.set(chatJid, Date.now());
                            logger.info(`[reaction] Conversa finalizada - reação ${emoji} fromMe no chat ${chatJid}`);
                        }
                    }
                }
            } catch (err) {
                logger.debug(`Erro ao processar reação: ${err.message}`);
            }
        }
    });

    // Debug: capturar todos os eventos de chat para diagnostico
    sock.ev.on('chats.delete', (ids) => {
        logger.info(`[chats.delete] ${JSON.stringify(ids)}`);
    });

    // Atualização de chats (unread count, etc)
    sock.ev.on('chats.update', async (updates) => {
        for (const update of updates) {
            try {
                const dbPool = db.getPool();
                const fields = Object.keys(update).filter(k => k !== 'id').join(', ');
                logger.info(`[chats.update] ${update.id} campos: ${fields} unreadCount=${update.unreadCount} syncComplete=${syncComplete}`);

                if (update.unreadCount !== undefined) {
                    await dbPool.execute(
                        'UPDATE chats SET unread_count = ?, updated_at = NOW() WHERE account_id = ? AND chat_id = ?',
                        [update.unreadCount, ACCOUNT_ID, update.id]
                    );
                }

                // unreadCount: >0 = N msgs não lidas, -1 = marcado manualmente como não lido, 0 = lido
                const isUnread = update.unreadCount > 0 || update.unreadCount === -1;

                // Ignorar JIDs que nunca devem criar conversas
                const skipConversaJid = update.id.endsWith('@newsletter') || update.id.endsWith('@lid') || update.id === 'status@broadcast';

                // Coletar eventos de unread durante sync para processar depois
                if (!syncComplete && isUnread && !skipConversaJid) {
                    pendingUnreadEvents.push({ id: update.id, unreadCount: update.unreadCount });
                }

                if (syncComplete && update.unreadCount !== undefined && !skipConversaJid) {
                    const [chatRows] = await dbPool.execute(
                        'SELECT id FROM chats WHERE account_id = ? AND chat_id = ?',
                        [ACCOUNT_ID, update.id]
                    );
                    if (chatRows.length > 0) {
                        const chatDbId = chatRows[0].id;
                        if (isUnread) {
                            // Cooldown: se a conversa foi finalizada por fromMe há menos de 60s, ignorar
                            // Isso evita o ciclo em grupos: responder -> finaliza -> chats.update unread=1 -> reabre
                            const finTime = recentlyFinalizedChats.get(update.id);
                            if (finTime && (Date.now() - finTime) < 60000) {
                                logger.info(`[chats.update] Ignorando reopen de ${update.id} - finalizado por resposta há ${Math.round((Date.now() - finTime) / 1000)}s`);
                            } else {
                                logger.info(`[chats.update] Chat não lido: ${update.id} (unread=${update.unreadCount}), reabrindo...`);
                                await db.reabrirConversa(chatDbId, ACCOUNT_ID);
                            }
                        } else if (update.unreadCount === 0 && Date.now() > connectionStableAt) {
                            // Chat lido (abriu no WhatsApp Web) → finalizar conversa
                            // Só após conexão estável para evitar falsos positivos do sync
                            logger.info(`[chats.update] Chat lido: ${update.id}, finalizando conversa...`);
                            await db.finalizarConversaLida(chatDbId);
                        }
                    }
                }
            } catch (err) {
                logger.error(`[chats.update] Erro: ${err.message}`);
            }
        }
    });

    return sock;
}

function getSocket() { return sock; }
function getStatus() { return connectionStatus; }
function getAccountId() { return ACCOUNT_ID; }
function getQR() { return currentQR; }
function getPairingCode() { return currentPairingCode; }
function getConnectedJid() { return connectedJid; }

// Cache de nomes de grupo para evitar chamadas repetidas
const groupNameCache = {};

// Buscar nome do grupo via metadata do WhatsApp
async function fetchGroupName(groupJid) {
    // Verificar cache primeiro
    if (groupNameCache[groupJid]) return groupNameCache[groupJid];

    try {
        if (!sock || connectionStatus !== 'connected') return null;
        const metadata = await sock.groupMetadata(groupJid);
        if (metadata && metadata.subject) {
            groupNameCache[groupJid] = metadata.subject;
            logger.info(`Grupo ${groupJid}: "${metadata.subject}" (${metadata.participants?.length || 0} participantes)`);
            return metadata.subject;
        }
    } catch (err) {
        logger.debug(`Erro ao buscar metadata do grupo ${groupJid}: ${err.message}`);
    }
    return null;
}

// Sincronizar nomes de todos os grupos existentes no banco
async function syncAllGroupNames() {
    try {
        if (!sock || connectionStatus !== 'connected') return;
        const dbPool = db.getPool();
        const [groups] = await dbPool.execute(
            "SELECT id, chat_id, chat_name FROM chats WHERE account_id = ? AND chat_type = 'group'",
            [ACCOUNT_ID]
        );
        logger.info(`Sincronizando nomes de ${groups.length} grupos...`);
        let updated = 0;
        for (const group of groups) {
            try {
                const metadata = await sock.groupMetadata(group.chat_id);
                if (metadata && metadata.subject && metadata.subject !== group.chat_name) {
                    await dbPool.execute(
                        'UPDATE chats SET chat_name = ?, updated_at = NOW() WHERE id = ?',
                        [metadata.subject, group.id]
                    );
                    groupNameCache[group.chat_id] = metadata.subject;
                    updated++;
                    logger.info(`  Grupo atualizado: "${metadata.subject}"`);
                }
            } catch (err) {
                logger.debug(`  Erro grupo ${group.chat_id}: ${err.message}`);
            }
        }
        logger.info(`${updated} nomes de grupos atualizados.`);
    } catch (err) {
        logger.error(`Erro ao sincronizar grupos: ${err.message}`);
    }
}

// Gerar QR code como imagem base64 (data URL)
async function getQRImage() {
    if (!currentQR) return null;
    try {
        return await QRCode.toDataURL(currentQR, { width: 300, margin: 2 });
    } catch (err) {
        logger.error(`Erro ao gerar QR image: ${err.message}`);
        return null;
    }
}

// Enviar mensagem com mídia (imagem, vídeo, documento, áudio)
async function sendMediaMessage(jid, filePath, caption, mediaType) {
    if (!sock || connectionStatus !== 'connected') {
        throw new Error('WhatsApp não está conectado.');
    }
    if (!jid.includes('@')) {
        jid = jid + '@s.whatsapp.net';
    }
    const buffer = fs.readFileSync(filePath);
    const mime = require('mime-types').lookup(filePath) || 'application/octet-stream';
    const fileName = path.basename(filePath);

    let msgContent;
    if (mediaType === 'image' || mime.startsWith('image/')) {
        msgContent = { image: buffer, caption: caption || undefined, mimetype: mime };
    } else if (mediaType === 'video' || mime.startsWith('video/')) {
        msgContent = { video: buffer, caption: caption || undefined, mimetype: mime };
    } else if (mediaType === 'audio' || mime.startsWith('audio/')) {
        msgContent = { audio: buffer, mimetype: mime, ptt: true }; // ptt=true para voice note
    } else {
        msgContent = { document: buffer, mimetype: mime, fileName: fileName };
    }

    const result = await sock.sendMessage(jid, msgContent);
    return result;
}

// Enviar mensagem de texto (com suporte a reply via options)
async function sendTextMessage(jid, text, options = {}) {
    if (!sock || connectionStatus !== 'connected') {
        throw new Error('WhatsApp não está conectado.');
    }
    // Normalizar JID
    if (!jid.includes('@')) {
        jid = jid + '@s.whatsapp.net';
    }
    const result = await sock.sendMessage(jid, { text }, options);
    return result;
}

// Solicitar sync de mensagens recentes de um chat ao WhatsApp
// Usa fetchMessageHistory que dispara messaging-history.set com as mensagens
async function fetchChatMessages(jid, count = 50) {
    if (!sock || connectionStatus !== 'connected') {
        throw new Error('WhatsApp não está conectado.');
    }
    if (!jid.includes('@')) {
        jid = jid + '@s.whatsapp.net';
    }

    try {
        // Buscar a mensagem mais antiga que temos no banco para este chat
        const dbPool = db.getPool();
        const [chatRows] = await dbPool.execute(
            'SELECT id FROM chats WHERE account_id = ? AND chat_id = ?',
            [ACCOUNT_ID, jid]
        );
        if (chatRows.length === 0) {
            logger.info(`[fetchChatMessages] Chat ${jid} não existe no banco`);
            return { requested: false };
        }

        const chatDbId = chatRows[0].id;
        const [msgRows] = await dbPool.execute(
            'SELECT message_key, timestamp FROM messages WHERE chat_id = ? ORDER BY timestamp ASC LIMIT 1',
            [chatDbId]
        );

        if (msgRows.length > 0) {
            // Pedir ao WhatsApp mensagens anteriores à mais antiga que temos
            const oldestKey = { remoteJid: jid, id: msgRows[0].message_key };
            const oldestTsMs = msgRows[0].timestamp * 1000; // Converter para ms
            logger.info(`[fetchChatMessages] Solicitando ${count} msgs de ${jid} antes de ${oldestKey.id} (tsMs=${oldestTsMs})`);
            await sock.fetchMessageHistory(count, oldestKey, oldestTsMs);
        } else {
            // Sem mensagens - pedir as mais recentes
            const oldestKey = { remoteJid: jid, id: '' };
            logger.info(`[fetchChatMessages] Solicitando ${count} msgs de ${jid} (sem histórico local)`);
            await sock.fetchMessageHistory(count, oldestKey, 0);
        }

        // As mensagens chegarão via evento messaging-history.set e serão salvas automaticamente
        return { requested: true };
    } catch (err) {
        logger.error(`[fetchChatMessages] Erro: ${err.message}`);
        throw err;
    }
}

// Buscar mensagens RECENTES que possam ter sido perdidas por falhas de E2EE
// Usa a mensagem mais recente como âncora e pede N msgs antes dela
async function syncRecentMessages(jid, count = 50) {
    if (!sock || connectionStatus !== 'connected') {
        throw new Error('WhatsApp não está conectado.');
    }
    if (!jid.includes('@')) {
        jid = jid + '@s.whatsapp.net';
    }

    try {
        const dbPool = db.getPool();
        const [chatRows] = await dbPool.execute(
            'SELECT id FROM chats WHERE account_id = ? AND chat_id = ?',
            [ACCOUNT_ID, jid]
        );
        if (chatRows.length === 0) {
            logger.info(`[syncRecent] Chat ${jid} não existe no banco`);
            return { requested: false };
        }

        const chatDbId = chatRows[0].id;

        // Pegar a mensagem MAIS RECENTE como âncora
        const [msgRows] = await dbPool.execute(
            'SELECT message_key, timestamp FROM messages WHERE chat_id = ? ORDER BY timestamp DESC, id DESC LIMIT 1',
            [chatDbId]
        );

        if (msgRows.length > 0) {
            // Usar timestamp futuro (agora + 1 min) em ms para pegar tudo até o presente
            const anchorKey = { remoteJid: jid, id: msgRows[0].message_key, fromMe: true };
            const futureTsMs = Date.now() + 60000;
            logger.info(`[syncRecent] Solicitando ${count} msgs recentes de ${jid} (âncora: tsMs=${futureTsMs})`);
            await sock.fetchMessageHistory(count, anchorKey, futureTsMs);
        } else {
            // Sem mensagens, pedir as mais recentes
            const anchorKey = { remoteJid: jid, id: '' };
            logger.info(`[syncRecent] Solicitando ${count} msgs de ${jid} (sem histórico local)`);
            await sock.fetchMessageHistory(count, anchorKey, Date.now());
        }

        return { requested: true };
    } catch (err) {
        logger.error(`[syncRecent] Erro: ${err.message}`);
        throw err;
    }
}

// === Carregamento automático de histórico de mensagens ===

// Obter timestamp da mensagem mais antiga de um chat no banco
async function getOldestMessageTimestamp(jid) {
    const dbPool = db.getPool();
    const [chatRows] = await dbPool.execute(
        'SELECT id FROM chats WHERE account_id = ? AND chat_id = ?',
        [ACCOUNT_ID, jid]
    );
    if (chatRows.length === 0) return null;
    const [msgRows] = await dbPool.execute(
        'SELECT MIN(timestamp) as oldest_ts FROM messages WHERE chat_id = ? AND timestamp > 0',
        [chatRows[0].id]
    );
    return msgRows[0]?.oldest_ts || null;
}

// Contar mensagens de um chat no banco (para detectar quando batch foi processado)
async function getMessageCount(jid) {
    const dbPool = db.getPool();
    const [chatRows] = await dbPool.execute(
        'SELECT id FROM chats WHERE account_id = ? AND chat_id = ?',
        [ACCOUNT_ID, jid]
    );
    if (chatRows.length === 0) return 0;
    const [countRows] = await dbPool.execute(
        'SELECT COUNT(*) as cnt FROM messages WHERE chat_id = ?',
        [chatRows[0].id]
    );
    return countRows[0].cnt;
}

// Carregar histórico de mensagens até uma data alvo (loop com batches de 50)
async function loadHistoryUntil(jid, untilTimestamp) {
    const MAX_ITERATIONS = 30;
    const BATCH_SIZE = 50;
    const DELAY_BETWEEN_BATCHES_MS = 5000;
    const WAIT_FOR_MESSAGES_MS = 8000;
    const POLL_INTERVAL_MS = 500;

    if (!jid.includes('@')) jid = jid + '@s.whatsapp.net';

    // Verificar se já está carregando
    const existing = historyLoadState.get(jid);
    if (existing && existing.status === 'loading') {
        return { status: 'already_loading', iteration: existing.iteration };
    }

    // Se já carregou, verificar se ainda precisa
    if (existing && existing.status === 'done') {
        const currentOldest = await getOldestMessageTimestamp(jid);
        if (currentOldest && currentOldest <= untilTimestamp) {
            return { status: 'already_done', oldestTimestamp: currentOldest };
        }
    }

    if (!sock || connectionStatus !== 'connected') {
        return { status: 'error', error: 'WhatsApp nao conectado' };
    }

    // Verificar se já atingiu o target
    const initialOldest = await getOldestMessageTimestamp(jid);
    if (initialOldest && initialOldest <= untilTimestamp) {
        historyLoadState.set(jid, { status: 'done', iteration: 0, oldestTs: initialOldest });
        logger.info(`[loadHistory] ${jid} ja tem msgs desde ${new Date(initialOldest * 1000).toISOString()}`);
        return { status: 'already_at_target', oldestTimestamp: initialOldest };
    }

    historyLoadState.set(jid, { status: 'loading', iteration: 0, oldestTs: initialOldest });
    logger.info(`[loadHistory] Iniciando carga de historico de ${jid} ate ${new Date(untilTimestamp * 1000).toISOString()}`);

    try {
        for (let i = 0; i < MAX_ITERATIONS; i++) {
            historyLoadState.set(jid, {
                status: 'loading', iteration: i + 1,
                oldestTs: await getOldestMessageTimestamp(jid)
            });

            if (!sock || connectionStatus !== 'connected') {
                historyLoadState.set(jid, { status: 'error', iteration: i + 1, oldestTs: null });
                logger.warn(`[loadHistory] ${jid} abortado - desconectado na iteracao ${i + 1}`);
                return { status: 'error', error: 'Desconectado', iterations: i + 1 };
            }

            const countBefore = await getMessageCount(jid);

            logger.info(`[loadHistory] ${jid} iteracao ${i + 1}/${MAX_ITERATIONS} - solicitando ${BATCH_SIZE} msgs...`);
            try {
                await fetchChatMessages(jid, BATCH_SIZE);
            } catch (err) {
                logger.error(`[loadHistory] ${jid} fetchChatMessages erro: ${err.message}`);
                await new Promise(r => setTimeout(r, DELAY_BETWEEN_BATCHES_MS));
                continue;
            }

            // Esperar messaging-history.set processar (poll DB a cada 500ms, max 8s)
            let newMessagesArrived = false;
            const waitStart = Date.now();
            while (Date.now() - waitStart < WAIT_FOR_MESSAGES_MS) {
                await new Promise(r => setTimeout(r, POLL_INTERVAL_MS));
                const countAfter = await getMessageCount(jid);
                if (countAfter > countBefore) {
                    newMessagesArrived = true;
                    break;
                }
            }

            if (!newMessagesArrived) {
                const finalOldest = await getOldestMessageTimestamp(jid);
                historyLoadState.set(jid, { status: 'done', iteration: i + 1, oldestTs: finalOldest });
                logger.info(`[loadHistory] ${jid} concluido - sem mais msgs na iteracao ${i + 1}. Oldest: ${finalOldest ? new Date(finalOldest * 1000).toISOString() : 'nenhuma'}`);
                return { status: 'done_no_more', iterations: i + 1, oldestTimestamp: finalOldest };
            }

            const currentOldest = await getOldestMessageTimestamp(jid);
            if (currentOldest && currentOldest <= untilTimestamp) {
                historyLoadState.set(jid, { status: 'done', iteration: i + 1, oldestTs: currentOldest });
                logger.info(`[loadHistory] ${jid} target atingido na iteracao ${i + 1}! Oldest: ${new Date(currentOldest * 1000).toISOString()}`);
                return { status: 'done_target_reached', iterations: i + 1, oldestTimestamp: currentOldest };
            }

            // Rate limit entre batches
            if (i < MAX_ITERATIONS - 1) {
                await new Promise(r => setTimeout(r, DELAY_BETWEEN_BATCHES_MS));
            }
        }

        const finalOldest = await getOldestMessageTimestamp(jid);
        historyLoadState.set(jid, { status: 'done', iteration: MAX_ITERATIONS, oldestTs: finalOldest });
        logger.info(`[loadHistory] ${jid} max iteracoes (${MAX_ITERATIONS}) atingido. Oldest: ${finalOldest ? new Date(finalOldest * 1000).toISOString() : 'nenhuma'}`);
        return { status: 'done_max_iterations', iterations: MAX_ITERATIONS, oldestTimestamp: finalOldest };

    } catch (err) {
        historyLoadState.set(jid, { status: 'error', iteration: 0, oldestTs: null });
        logger.error(`[loadHistory] ${jid} erro fatal: ${err.message}`);
        return { status: 'error', error: err.message };
    }
}

// Processar fila de carregamento de histórico (um JID por vez)
async function processHistoryQueue() {
    if (historyLoadProcessing) return;
    historyLoadProcessing = true;

    while (historyLoadQueue.length > 0) {
        const { jid, untilTimestamp } = historyLoadQueue.shift();
        try {
            await loadHistoryUntil(jid, untilTimestamp);
        } catch (err) {
            logger.error(`[historyQueue] Erro ao processar ${jid}: ${err.message}`);
        }
    }

    historyLoadProcessing = false;
}

// Enfileirar carregamento de histórico (fire-and-forget)
function enqueueHistoryLoad(jid, untilTimestamp) {
    if (!jid.includes('@')) jid = jid + '@s.whatsapp.net';

    const existing = historyLoadState.get(jid);
    if (existing && (existing.status === 'loading' || existing.status === 'done')) {
        return { queued: false, reason: existing.status };
    }

    if (historyLoadQueue.some(item => item.jid === jid)) {
        return { queued: false, reason: 'already_queued' };
    }

    historyLoadQueue.push({ jid, untilTimestamp });
    logger.info(`[historyQueue] Enfileirado ${jid} (fila: ${historyLoadQueue.length})`);

    processHistoryQueue();

    return { queued: true, queueSize: historyLoadQueue.length };
}

// Status do carregamento de histórico de um JID
function getHistoryLoadStatus(jid) {
    if (!jid.includes('@')) jid = jid + '@s.whatsapp.net';
    const state = historyLoadState.get(jid);
    if (!state) return { status: 'not_started' };
    const inQueue = historyLoadQueue.some(item => item.jid === jid);
    return { ...state, inQueue };
}

// Graceful shutdown: salvar auth state antes do processo morrer
// PM2 envia SIGINT, depois SIGTERM. Sem isso, escritas pendentes se perdem
// e o auth_info fica corrompido, gerando PreKeyError no próximo start.
async function gracefulShutdown(signal) {
    logger.info(`[shutdown] Recebido ${signal}, salvando estado...`);
    try {
        if (currentSaveCreds) {
            await currentSaveCreds();
            logger.info('[shutdown] Auth state salvo com sucesso.');
        }
        if (sock) {
            sock.end(undefined);
        }
    } catch (err) {
        logger.error(`[shutdown] Erro ao salvar: ${err.message}`);
    }
    process.exit(0);
}
process.on('SIGINT', () => gracefulShutdown('SIGINT'));
process.on('SIGTERM', () => gracefulShutdown('SIGTERM'));

module.exports = {
    startConnection,
    getSocket,
    getStatus,
    getAccountId,
    getQR,
    getQRImage,
    getPairingCode,
    getConnectedJid,
    sendTextMessage,
    sendMediaMessage,
    syncAllGroupNames,
    fetchGroupName,
    fetchChatMessages,
    syncRecentMessages,
    fetchProfilePicUrl,
    enqueueHistoryLoad,
    getHistoryLoadStatus,
};
