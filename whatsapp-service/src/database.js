const mysql = require('mysql2/promise');

let pool = null;

function getPool() {
    if (!pool) {
        pool = mysql.createPool({
            host: process.env.DB_HOST || 'localhost',
            user: process.env.DB_USER || 'root',
            password: process.env.DB_PASSWORD || 'aaa',
            database: process.env.DB_NAME || 'whatsapp_atendimento',
            waitForConnections: true,
            connectionLimit: 10,
            charset: 'utf8mb4',
        });
    }
    return pool;
}

// Salvar ou atualizar contato
async function upsertContact(accountId, jid, name, phoneNumber, profilePicUrl) {
    const db = getPool();
    const hasPic = profilePicUrl !== undefined && profilePicUrl !== null;
    await db.execute(
        `INSERT INTO contacts (account_id, jid, name, phone_number, profile_picture_url, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, NOW(), NOW())
         ON DUPLICATE KEY UPDATE name = COALESCE(VALUES(name), name), phone_number = COALESCE(VALUES(phone_number), phone_number),
            ${hasPic ? 'profile_picture_url = VALUES(profile_picture_url),' : ''}
            updated_at = NOW()`,
        [accountId, jid, name || null, phoneNumber || null, profilePicUrl || null]
    );
}

// Buscar nome do contato no banco
async function getContactName(jid) {
    const db = getPool();
    try {
        const [rows] = await db.execute(
            `SELECT name FROM contacts WHERE jid = ? AND name IS NOT NULL LIMIT 1`,
            [jid]
        );
        return rows.length > 0 ? rows[0].name : null;
    } catch (err) {
        return null;
    }
}

// Salvar ou atualizar chat
// unreadCount: numero = atualizar, null = nao alterar (manter valor atual)
async function upsertChat(accountId, chatId, chatName, chatType, unreadCount) {
    const db = getPool();
    const shouldUpdateUnread = unreadCount !== null && unreadCount !== undefined;
    const [result] = await db.execute(
        `INSERT INTO chats (account_id, chat_id, chat_name, chat_type, unread_count, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, NOW(), NOW())
         ON DUPLICATE KEY UPDATE
            chat_name = COALESCE(VALUES(chat_name), chat_name),
            ${shouldUpdateUnread ? 'unread_count = VALUES(unread_count),' : ''}
            updated_at = NOW()`,
        [accountId, chatId, chatName || null, chatType || 'individual', unreadCount || 0]
    );

    // Retornar o ID do chat (insert ou existente)
    if (result.insertId > 0) {
        return result.insertId;
    }
    const [rows] = await db.execute(
        'SELECT id FROM chats WHERE account_id = ? AND chat_id = ?',
        [accountId, chatId]
    );
    return rows.length > 0 ? rows[0].id : null;
}

// Atualizar timestamp da última mensagem do chat
async function updateChatLastMessage(chatDbId, timestamp) {
    const db = getPool();
    await db.execute(
        'UPDATE chats SET last_message_timestamp = ?, updated_at = NOW() WHERE id = ?',
        [timestamp, chatDbId]
    );
}

// Salvar mensagem (com suporte a sent_by_user_id para rastrear atendente)
async function insertMessage(chatDbId, data) {
    const db = getPool();
    try {
        // message_key é o unique index, from_jid é NOT NULL
        await db.execute(
            `INSERT INTO messages (chat_id, message_key, from_jid, message_type, message_text, media_url, media_mime_type, is_from_me, sent_by_user_id, status, timestamp, quoted_message_id, quoted_text, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE status = COALESCE(VALUES(status), status)`,
            [
                chatDbId,
                data.messageId,
                data.fromJid || '',
                data.messageType || 'text',
                data.messageText || null,
                data.mediaUrl || null,
                data.mediaMimeType || null,
                data.isFromMe ? 1 : 0,
                data.sentByUserId || null,
                data.status || 'sent',
                data.timestamp || 0,
                data.quotedMessageId || null,
                data.quotedText || null,
            ]
        );
    } catch (err) {
        if (err.code !== 'ER_DUP_ENTRY') {
            console.error('Erro ao salvar mensagem:', err.message);
        }
    }
}

// Auto-criar conversa na fila quando chega mensagem de cliente
async function upsertConversa(chatDbId, accountId, clienteNumero, clienteNome) {
    const db = getPool();
    try {
        // Tentar buscar nome do contato salvo no banco se nao temos nome
        if (!clienteNome && clienteNumero) {
            const jidLike = clienteNumero.replace(/[^0-9]/g, '') + '@s.whatsapp.net';
            const [contactRows] = await db.execute(
                'SELECT name FROM contacts WHERE jid = ? AND name IS NOT NULL LIMIT 1',
                [jidLike]
            );
            if (contactRows.length > 0) {
                clienteNome = contactRows[0].name;
            }
        }

        console.log(`[upsertConversa] chatDbId=${chatDbId}, numero=${clienteNumero}, nome=${clienteNome}`);

        // Verificar se ja existe conversa aberta (aguardando ou em_atendimento) para este chat
        const [existing] = await db.execute(
            `SELECT id, cliente_nome FROM conversas WHERE chat_id = ? AND status IN ('aguardando', 'em_atendimento') LIMIT 1`,
            [chatDbId]
        );

        if (existing.length > 0) {
            // Atualizar ultima_msg_em e preencher nome se estava vazio
            if (!existing[0].cliente_nome && clienteNome) {
                await db.execute(
                    `UPDATE conversas SET ultima_msg_em = NOW(), updated_at = NOW(), cliente_nome = ? WHERE id = ?`,
                    [clienteNome, existing[0].id]
                );
            } else {
                await db.execute(
                    `UPDATE conversas SET ultima_msg_em = NOW(), updated_at = NOW() WHERE id = ?`,
                    [existing[0].id]
                );
            }
            console.log(`[upsertConversa] Atualizada conversa existente id=${existing[0].id}`);
            return existing[0].id;
        }

        // Criar nova conversa na fila
        console.log(`[upsertConversa] Criando nova conversa na fila para chat_id=${chatDbId}`);
        const [result] = await db.execute(
            `INSERT INTO conversas (chat_id, account_id, cliente_numero, cliente_nome, status, iniciada_em, ultima_msg_em, created_at, updated_at)
             VALUES (?, ?, ?, ?, 'aguardando', NOW(), NOW(), NOW(), NOW())`,
            [chatDbId, accountId || null, clienteNumero || '', clienteNome || null]
        );
        console.log(`[upsertConversa] Nova conversa criada id=${result.insertId}`);
        return result.insertId;
    } catch (err) {
        console.error('[upsertConversa] ERRO:', err.message, err.stack);
        return null;
    }
}

// Atualizar reação em uma mensagem
async function updateMessageReaction(messageKey, emoji, senderJid, timestamp) {
    const db = getPool();
    try {
        // Buscar reações atuais
        const [rows] = await db.execute(
            'SELECT reactions FROM messages WHERE message_key = ?',
            [messageKey]
        );
        if (rows.length === 0) return;

        let reactions = [];
        try {
            reactions = JSON.parse(rows[0].reactions || '[]');
        } catch (e) {
            reactions = [];
        }

        // Remover reação existente deste sender
        reactions = reactions.filter(r => r.senderJid !== senderJid);

        // Se emoji não vazio, adicionar nova reação
        if (emoji) {
            reactions.push({ emoji, senderJid, timestamp });
        }

        await db.execute(
            'UPDATE messages SET reactions = ? WHERE message_key = ?',
            [JSON.stringify(reactions), messageKey]
        );
    } catch (err) {
        console.error('Erro ao atualizar reação:', err.message);
    }
}

// Finalizar conversa aguardando quando chat foi lido (visualizado no WhatsApp Web/celular)
async function finalizarConversaLida(chatDbId) {
    const db = getPool();
    try {
        const [rows] = await db.execute(
            "SELECT id FROM conversas WHERE chat_id = ? AND status = 'aguardando' LIMIT 1",
            [chatDbId]
        );
        if (rows.length > 0) {
            await db.execute(
                "UPDATE conversas SET status = 'finalizada', finalizada_em = NOW(), updated_at = NOW() WHERE id = ?",
                [rows[0].id]
            );
            console.log(`[finalizarConversaLida] Conversa #${rows[0].id} finalizada (chat lido externamente)`);
        }
    } catch (err) {
        console.error('[finalizarConversaLida] Erro:', err.message);
    }
}

// Reabrir conversa na fila quando chat é marcado como "não lido" no WhatsApp Web
// Se já existe aguardando/em_atendimento, não faz nada.
// Se existe finalizada recente, reabre. Senão, cria nova.
async function reabrirConversa(chatDbId, accountId) {
    const db = getPool();
    try {
        // 1. Já existe conversa aberta? Não faz nada
        const [aberta] = await db.execute(
            `SELECT id FROM conversas WHERE chat_id = ? AND status IN ('aguardando', 'em_atendimento') LIMIT 1`,
            [chatDbId]
        );
        if (aberta.length > 0) {
            console.log(`[reabrirConversa] Conversa #${aberta[0].id} já está na fila, ignorando.`);
            return aberta[0].id;
        }

        // 2. Existe conversa finalizada? Reabrir a mais recente
        const [finalizada] = await db.execute(
            `SELECT id, cliente_nome FROM conversas WHERE chat_id = ? AND status = 'finalizada' ORDER BY id DESC LIMIT 1`,
            [chatDbId]
        );
        if (finalizada.length > 0) {
            await db.execute(
                `UPDATE conversas SET status = 'aguardando', finalizada_em = NULL, atendente_id = NULL, iniciada_em = NOW(), ultima_msg_em = NOW(), updated_at = NOW() WHERE id = ?`,
                [finalizada[0].id]
            );
            console.log(`[reabrirConversa] Conversa #${finalizada[0].id} (${finalizada[0].cliente_nome}) reaberta na fila (marcada como não lido).`);
            return finalizada[0].id;
        }

        // 3. Nenhuma conversa existe - criar nova (buscar dados do chat)
        const [chatInfo] = await db.execute(
            `SELECT ch.chat_id as jid, ch.chat_name, ch.chat_type FROM chats ch WHERE ch.id = ?`,
            [chatDbId]
        );
        if (chatInfo.length > 0) {
            const chat = chatInfo[0];
            const jid = chat.jid;
            const isGroup = chat.chat_type === 'group';
            const clienteNumero = isGroup ? jid : (jid.replace(/@.*/, '') || jid);
            const clienteNome = chat.chat_name || (isGroup ? jid : await getContactName(jid));
            return await upsertConversa(chatDbId, accountId, clienteNumero, clienteNome);
        }

        return null;
    } catch (err) {
        console.error('[reabrirConversa] Erro:', err.message);
        return null;
    }
}

// Buscar conversas aguardando com seus chat_id (JID) para verificacao periodica
async function getConversasAguardando(accountId) {
    const db = getPool();
    try {
        const [rows] = await db.execute(
            `SELECT c.id as conversa_id, c.chat_id as chat_db_id, ch.chat_id as jid
             FROM conversas c
             INNER JOIN chats ch ON ch.id = c.chat_id
             WHERE c.status = 'aguardando' AND c.account_id = ?`,
            [accountId]
        );
        return rows;
    } catch (err) {
        console.error('[getConversasAguardando] Erro:', err.message);
        return [];
    }
}

// Atualizar status da instância
async function updateAccountStatus(accountId, isConnected, ownerJid) {
    const db = getPool();
    const fields = ['is_connected = ?', 'updated_at = NOW()'];
    const values = [isConnected ? 1 : 0];

    if (isConnected) {
        fields.push('last_connection = NOW()');
    }
    if (ownerJid) {
        fields.push('owner_jid = ?');
        values.push(ownerJid);
    }

    values.push(accountId);
    await db.execute(
        `UPDATE whatsapp_accounts SET ${fields.join(', ')} WHERE id = ?`,
        values
    );
}

// Buscar chat pelo chat_id (JID)
async function findChatByJid(accountId, chatJid) {
    const db = getPool();
    const [rows] = await db.execute(
        'SELECT id FROM chats WHERE account_id = ? AND chat_id = ?',
        [accountId, chatJid]
    );
    return rows.length > 0 ? rows[0] : null;
}

// Buscar mensagens de um chat
async function getMessages(chatDbId, limit = 200) {
    const db = getPool();
    const [rows] = await db.execute(
        'SELECT * FROM messages WHERE chat_id = ? ORDER BY timestamp ASC, id ASC LIMIT ?',
        [chatDbId, limit]
    );
    return rows;
}

// Listar chats
async function getChats(accountId) {
    const db = getPool();
    const [rows] = await db.execute(
        'SELECT * FROM chats WHERE account_id = ? ORDER BY last_message_timestamp DESC',
        [accountId]
    );
    return rows;
}

// Contar registros
async function getCounts(accountId) {
    const db = getPool();
    const [chats] = await db.execute('SELECT COUNT(*) as c FROM chats WHERE account_id = ?', [accountId]);
    const [contacts] = await db.execute('SELECT COUNT(*) as c FROM contacts WHERE account_id = ?', [accountId]);
    const [messages] = await db.execute(
        'SELECT COUNT(*) as c FROM messages m JOIN chats ch ON ch.id = m.chat_id WHERE ch.account_id = ?',
        [accountId]
    );
    return {
        chats: chats[0].c,
        contacts: contacts[0].c,
        messages: messages[0].c,
    };
}

module.exports = {
    getPool,
    upsertContact,
    getContactName,
    upsertChat,
    updateChatLastMessage,
    insertMessage,
    upsertConversa,
    finalizarConversaLida,
    reabrirConversa,
    getConversasAguardando,
    updateMessageReaction,
    updateAccountStatus,
    findChatByJid,
    getMessages,
    getChats,
    getCounts,
};
