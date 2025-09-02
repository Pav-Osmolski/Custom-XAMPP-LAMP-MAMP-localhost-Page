#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
OPENSSL="$SCRIPT_DIR/../bin/openssl"
TEMPLATE="$SCRIPT_DIR/cert-template.conf"
TEMP_CONF="$SCRIPT_DIR/cert.conf"
LOG_FILE="$SCRIPT_DIR/cert.log"

# ---- Prompt for inputs ----
read -p "Enter primary domain [default: localhost]: " DOMAIN
DOMAIN=${DOMAIN:-localhost}

read -p "Extra SANs (comma/space separated, optional): " EXTRA

read -p "Validity in days [default: 365]: " DAYS
DAYS=${DAYS:-365}

OUT_DIR="$SCRIPT_DIR/$DOMAIN"
SUBJECT="/emailAddress=youremail@example.com/C=US/ST=MO/L=Union/O=Company/CN=$DOMAIN"

echo "Generating cert for $DOMAIN" | tee "$LOG_FILE"

# ---- Checks ----
[ ! -f "$OPENSSL" ] && { echo "[ERROR] OpenSSL not found"; exit 1; }
[ ! -f "$TEMPLATE" ] && { echo "[ERROR] Template config not found"; exit 1; }

mkdir -p "$OUT_DIR"

# Replace {{DOMAIN}}
sed "s/{{DOMAIN}}/$DOMAIN/g" "$TEMPLATE" > "$TEMP_CONF"

# ---- Build SAN list ----
SAN_LIST="DNS:$DOMAIN,DNS:localhost,IP:127.0.0.1"
EXTRA=${EXTRA//,/ }  # replace commas with spaces
for tok in $EXTRA; do
  [ -n "$tok" ] && SAN_LIST="$SAN_LIST,DNS:$tok"
done

# ---- Generate cert ----
"$OPENSSL" req \
  -config   "$TEMP_CONF" \
  -new \
  -sha256 \
  -newkey   rsa:2048 \
  -nodes \
  -keyout   "$OUT_DIR/server.key" \
  -x509 \
  -days     "$DAYS" \
  -out      "$OUT_DIR/server.crt" \
  -subj     "$SUBJECT" \
  -addext   "subjectAltName=$SAN_LIST" \
  >> "$LOG_FILE" 2>&1

EXIT_CODE=$?
rm -f "$TEMP_CONF"

if [ $EXIT_CODE -eq 0 ]; then
  echo "[OK] Certificate and key created in: $OUT_DIR" | tee -a "$LOG_FILE"
else
  echo "[FAIL] OpenSSL failed with exit code $EXIT_CODE" | tee -a "$LOG_FILE"
fi

exit $EXIT_CODE
