#!/bin/bash
# Reassemble the cracked Carrom Disc Pool APK from split parts
# Usage: ./reassemble.sh
# Output: Carrom.Disc.Pool_19.5.1_cracked.apk

set -e

OUT="Carrom.Disc.Pool_19.5.1_cracked.apk"

cat carrom_cracked.part01 carrom_cracked.part02 carrom_cracked.part03 > "$OUT"

echo "Reassembled: $OUT"
ls -la "$OUT"
echo "SHA256: $(sha256sum "$OUT" | cut -d' ' -f1)"
