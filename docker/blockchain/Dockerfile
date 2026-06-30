FROM node:20-bookworm-slim

WORKDIR /app/blockchain

COPY blockchain/ ./
RUN npm run compile

EXPOSE 8545

HEALTHCHECK --interval=5s --timeout=5s --retries=12 --start-period=10s \
  CMD node -e "fetch('http://127.0.0.1:8545',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({jsonrpc:'2.0',method:'eth_blockNumber',params:[],id:1})}).then(r=>r.json()).then(j=>process.exit(j.result?0:1)).catch(()=>process.exit(1))"

CMD ["npx", "hardhat", "node", "--hostname", "0.0.0.0"]
