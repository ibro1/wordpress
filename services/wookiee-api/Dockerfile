FROM node:20-alpine

# ca-certificates ensures Node.js can verify TLS chains for all external APIs
# (Anthropic uses roots not bundled in Alpine's default minimal CA set)
RUN apk add --no-cache ca-certificates

WORKDIR /app

COPY package.json ./
RUN npm install --omit=dev

COPY src ./src
COPY public ./public

ENV PORT=3000
EXPOSE 3000

CMD ["npm", "start"]
