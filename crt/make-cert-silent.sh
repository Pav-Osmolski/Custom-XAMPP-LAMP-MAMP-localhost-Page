#!/bin/bash

DOMAIN="$1"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
OPENSSL="$SCRIPT_DIR/../bin/openssl"
TEMPLATE="$SCRIPT_DIR/cert-template.conf"
TEMP_CONF="$SCRIPT_DIR/cert.conf"
OUT_DIR="$SCRIPT_DIR/$DOMAIN"
LOG_FILE="$SCRIPT_DIR/cert.log"

# Ensure domain is provided
if [ -z "$DOMAIN" ]; then
  echo "[ERROR] No domain provided." | tee "$LOG_FILE"
  exit 1
fi

echo "Generating cert for $DOMAIN" | tee "$LOG_FILE"

# Check required files
if [ ! -f "$OPENSSL" ]; then
  echo "[ERROR] OpenSSL not found at $OPENSSL" | tee -a "$LOG_FILE"
  exit 1
fi

if [ ! -f "$TEMPLATE" ]; then
  echo "[ERROR] Template config not found at $TEMPLATE" | tee -a "$LOG_FILE"
  exit 1
fi

mkdir -p "$OUT_DIR"

# Replace {{DOMAIN}} and create temporary config
sed "s/{{DOMAIN}}/$DOMAIN/g" "$TEMPLATE" > "$TEMP_CONF"

# Generate certificate
"$OPENSSL" req -config "$TEMP_CONF" -new -sha256 -newkey rsa:2048 -nodes \
  -keyout "$OUT_DIR/server.key" -x509 -days 365 -out "$OUT_DIR/server.crt" \
  -subj "/emailAddress=youremail@example.com/C=US/ST=MO/L=Union/O=Company/CN=$DOMAIN" \
  >> "$LOG_FILE" 2>&1

EXIT_CODE=$?

rm -f "$TEMP_CONF"

if [ $EXIT_CODE -eq 0 ]; then
  MSG="[OK] Certificate and key created in: $OUT_DIR"
else
  MSG="[FAIL] OpenSSL failed with exit code $EXIT_CODE"
fi

echo "$MSG" | tee -a "$LOG_FILE"
exit $EXIT_CODE
