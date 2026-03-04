/**
 * MySQL Auth State para Baileys
 * Substitui useMultiFileAuthState por armazenamento MySQL transacional.
 * Previne corrupção de sessions E2EE em crashes do processo.
 */
const { proto, initAuthCreds, BufferJSON } = require('@whiskeysockets/baileys');

/**
 * @param {import('mysql2/promise').Pool} pool
 * @param {number} accountId
 * @returns {Promise<{ state: import('@whiskeysockets/baileys').AuthenticationState, saveCreds: () => Promise<void> }>}
 */
async function useMySQLAuthState(pool, accountId) {

    async function readData(keyType, keyId = '') {
        const [rows] = await pool.execute(
            'SELECT key_data FROM baileys_auth WHERE account_id = ? AND key_type = ? AND key_id = ?',
            [accountId, keyType, keyId]
        );
        if (rows.length === 0) return null;
        try {
            return JSON.parse(rows[0].key_data, BufferJSON.reviver);
        } catch (e) {
            return null;
        }
    }

    async function writeData(keyType, keyId, data) {
        const json = JSON.stringify(data, BufferJSON.replacer);
        await pool.execute(
            `INSERT INTO baileys_auth (account_id, key_type, key_id, key_data)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE key_data = VALUES(key_data), updated_at = NOW()`,
            [accountId, keyType, keyId, json]
        );
    }

    async function removeData(keyType, keyId) {
        await pool.execute(
            'DELETE FROM baileys_auth WHERE account_id = ? AND key_type = ? AND key_id = ?',
            [accountId, keyType, keyId]
        );
    }

    // Carregar credentials
    const creds = (await readData('creds')) || initAuthCreds();

    return {
        state: {
            creds,
            keys: {
                get: async (type, ids) => {
                    const data = {};
                    // Buscar em batch para performance
                    if (ids.length === 0) return data;
                    const placeholders = ids.map(() => '?').join(',');
                    const [rows] = await pool.execute(
                        `SELECT key_id, key_data FROM baileys_auth WHERE account_id = ? AND key_type = ? AND key_id IN (${placeholders})`,
                        [accountId, type, ...ids]
                    );
                    for (const row of rows) {
                        try {
                            let value = JSON.parse(row.key_data, BufferJSON.reviver);
                            if (type === 'app-state-sync-key' && value) {
                                value = proto.Message.AppStateSyncKeyData.fromObject(value);
                            }
                            data[row.key_id] = value;
                        } catch (e) {
                            // Ignorar dados corrompidos
                        }
                    }
                    return data;
                },
                set: async (data) => {
                    // Usar transação para garantir atomicidade
                    const conn = await pool.getConnection();
                    try {
                        await conn.beginTransaction();
                        for (const category in data) {
                            for (const id in data[category]) {
                                const value = data[category][id];
                                if (value) {
                                    const json = JSON.stringify(value, BufferJSON.replacer);
                                    await conn.execute(
                                        `INSERT INTO baileys_auth (account_id, key_type, key_id, key_data)
                                         VALUES (?, ?, ?, ?)
                                         ON DUPLICATE KEY UPDATE key_data = VALUES(key_data), updated_at = NOW()`,
                                        [accountId, category, id, json]
                                    );
                                } else {
                                    await conn.execute(
                                        'DELETE FROM baileys_auth WHERE account_id = ? AND key_type = ? AND key_id = ?',
                                        [accountId, category, id]
                                    );
                                }
                            }
                        }
                        await conn.commit();
                    } catch (e) {
                        await conn.rollback();
                        throw e;
                    } finally {
                        conn.release();
                    }
                }
            }
        },
        saveCreds: async () => {
            await writeData('creds', '', creds);
        }
    };
}

/**
 * Migra dados de auth_info/ (JSON files) para MySQL.
 * Roda apenas uma vez, quando a tabela está vazia para a conta.
 */
async function migrateFromFiles(pool, accountId, authDir) {
    const fs = require('fs');
    const path = require('path');

    // Verificar se já tem dados no MySQL
    const [countRows] = await pool.execute(
        'SELECT COUNT(*) as cnt FROM baileys_auth WHERE account_id = ?',
        [accountId]
    );
    if (countRows[0].cnt > 0) {
        return { migrated: false, reason: 'MySQL already has data' };
    }

    // Verificar se o diretório existe
    if (!fs.existsSync(authDir)) {
        return { migrated: false, reason: 'auth dir not found' };
    }

    const files = fs.readdirSync(authDir).filter(f => f.endsWith('.json'));
    if (files.length === 0) {
        return { migrated: false, reason: 'no JSON files found' };
    }

    let count = 0;
    const conn = await pool.getConnection();
    try {
        await conn.beginTransaction();
        for (const file of files) {
            const filePath = path.join(authDir, file);
            try {
                const content = fs.readFileSync(filePath, 'utf-8');
                // Validar que é JSON válido
                JSON.parse(content);

                if (file === 'creds.json') {
                    await conn.execute(
                        `INSERT INTO baileys_auth (account_id, key_type, key_id, key_data) VALUES (?, 'creds', '', ?)
                         ON DUPLICATE KEY UPDATE key_data = VALUES(key_data)`,
                        [accountId, content]
                    );
                } else {
                    // Formato: {type}-{id}.json → extrair type e id
                    // Ex: pre-key-1.json, session-554499054050-1.0.json, app-state-sync-key-AAAA.json
                    const baseName = file.replace('.json', '');
                    // Identificar o tipo pela lista conhecida
                    const knownTypes = ['pre-key', 'session', 'sender-key', 'sender-key-memory', 'app-state-sync-key', 'app-state-sync-version'];
                    let keyType = null;
                    let keyId = null;
                    for (const t of knownTypes) {
                        if (baseName.startsWith(t + '-')) {
                            keyType = t;
                            keyId = baseName.substring(t.length + 1);
                            break;
                        }
                    }
                    if (!keyType) continue; // Arquivo desconhecido

                    // Reverter fixFileName: __ → /, - pode ser : (mas cuidado para não quebrar IDs legítimos)
                    keyId = keyId.replace(/__/g, '/');

                    await conn.execute(
                        `INSERT INTO baileys_auth (account_id, key_type, key_id, key_data) VALUES (?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE key_data = VALUES(key_data)`,
                        [accountId, keyType, keyId, content]
                    );
                }
                count++;
            } catch (e) {
                // Pular arquivos inválidos
                console.warn(`[mysql-auth] Skipping invalid file: ${file} - ${e.message}`);
            }
        }
        await conn.commit();
    } catch (e) {
        await conn.rollback();
        throw e;
    } finally {
        conn.release();
    }

    return { migrated: true, count };
}

module.exports = { useMySQLAuthState, migrateFromFiles };
