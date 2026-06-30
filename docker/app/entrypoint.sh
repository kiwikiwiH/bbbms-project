#!/bin/bash
set -euo pipefail

cd /app/tarrlok

if [ -z "${APP_KEY:-}" ]; then
    export APP_KEY="base64:$(openssl rand -base64 32)"
fi

touch .env
if ! grep -q '^APP_KEY=' .env 2>/dev/null; then
    echo "APP_KEY=${APP_KEY}" >> .env
fi

echo "Waiting for MySQL at ${DB_HOST:-mysql}..."
until php -r "
    try {
        new PDO(
            'mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '3306') . ';dbname=' . getenv('DB_DATABASE'),
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD'),
            [PDO::ATTR_TIMEOUT => 2]
        );
        exit(0);
    } catch (Throwable \$e) {
        exit(1);
    }
" 2>/dev/null; do
    sleep 2
done
echo "MySQL is ready."

RPC_URL="${BLOCKCHAIN_RPC_URL:-http://blockchain:8545}"
echo "Waiting for blockchain at ${RPC_URL}..."
until php -r "
    \$ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => 'Content-Type: application/json', 'content' => '{\"jsonrpc\":\"2.0\",\"method\":\"eth_blockNumber\",\"params\":[],\"id\":1}', 'timeout' => 2]]);
    \$body = @file_get_contents(getenv('BLOCKCHAIN_RPC_URL') ?: 'http://blockchain:8545', false, \$ctx);
    exit(\$body && str_contains(\$body, 'result') ? 0 : 1);
" 2>/dev/null; do
    sleep 2
done
echo "Blockchain node is ready."

echo "Deploying BloodBank contract..."
cd /app/blockchain
BLOCKCHAIN_RPC_URL="${RPC_URL}" npm run deploy
cd /app/tarrlok

DEPLOYED_AT=$(php -r 'echo json_decode(file_get_contents("/app/blockchain/deployments/local.json"))->deployedAt;')
MARKER=/app/tarrlok/storage/.last_contract_deploy

if [ ! -f "${MARKER}" ]; then
    echo "First start — running migrations and seeding demo data..."
    php artisan migrate --force
    php artisan db:seed --force
elif [ "$(cat "${MARKER}")" != "${DEPLOYED_AT}" ]; then
    echo "Blockchain was reset — refreshing demo data with new on-chain hashes..."
    php artisan migrate:fresh --seed --force
else
    php artisan migrate --force
fi

echo "${DEPLOYED_AT}" > "${MARKER}"

chown -R www-data:www-data storage bootstrap/cache
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "Tarrlok is ready at ${APP_URL:-http://localhost:8080}"
exec apache2-foreground
