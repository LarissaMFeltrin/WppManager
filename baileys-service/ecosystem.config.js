module.exports = {
    apps: [
        {
            name: 'baileys-wpp-evolution',
            script: 'src/server.js',
            cwd: __dirname,
            instances: 1,
            autorestart: true,
            watch: false,
            max_memory_restart: '500M',
            env: {
                NODE_ENV: 'production',
                PORT: 3001,
            },
            error_file: './logs/error.log',
            out_file: './logs/out.log',
            log_date_format: 'YYYY-MM-DD HH:mm:ss',
        },
    ],
};
