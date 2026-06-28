<?php

return [
    'enabled' => env('BLOCKCHAIN_ENABLED', false),

    'rpc_url' => env('BLOCKCHAIN_RPC_URL', 'http://127.0.0.1:8545'),

    // First Hardhat/Ganache dev account — never use in production.
    'private_key' => env('BLOCKCHAIN_PRIVATE_KEY'),

    'project_root' => dirname(base_path()),

    'anchor_script' => 'blockchain/scripts/anchor-event.js',

    'status_script' => 'blockchain/scripts/chain-status.js',
];
