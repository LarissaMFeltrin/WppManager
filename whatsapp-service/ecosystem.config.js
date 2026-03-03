// PM2 ecosystem config para multi-instancia WhatsApp.
// Cada app corresponde a uma linha na tabela whatsapp_accounts.
// Uso: pm2 start ecosystem.config.js
// Para adicionar nova instancia: adicionar nova entrada e rodar pm2 start novamente.
module.exports = {
  apps: [
    {
      name: 'wpp-suporte',
      script: 'src/server.js',
      env: {
        PORT: 3000,
        ACCOUNT_ID: 3,
        AUTH_DIR: './auth_info',
        PHONE_NUMBER: '5544988050924',
        DB_HOST: '127.0.0.1',
        DB_USER: 'root',
        DB_PASSWORD: 'aaa',
        DB_NAME: 'whatsapp_atendimento',
        LOG_LEVEL: 'info',
      },
    },
    // Exemplo para futuras instancias:
    // {
    //   name: 'wpp-comercial',
    //   script: 'src/server.js',
    //   env: {
    //     PORT: 3001,
    //     ACCOUNT_ID: 4,
    //     AUTH_DIR: './auth_info_4',
    //     PHONE_NUMBER: '',
    //     DB_HOST: '127.0.0.1',
    //     DB_USER: 'root',
    //     DB_PASSWORD: 'aaa',
    //     DB_NAME: 'whatsapp_atendimento',
    //     LOG_LEVEL: 'info',
    //   },
    // },
  ],
};
