#!/bin/sh
set -eu

LOG_FILE="/logs/telegram_cron.log"
INTERVAL="${ALERT_INTERVAL_SECONDS:-86400}"

run_alerts() {
  timestamp="$(date '+%Y-%m-%d %H:%M:%S %Z')"
  echo "[$timestamp] Ejecutando alertas" >> "$LOG_FILE"
  if response="$(curl -fsS --max-time 45 "$ALERT_URL" 2>&1)"; then
    printf '%s\n' "$response" >> "$LOG_FILE"
    echo "[$timestamp] Resultado: OK" >> "$LOG_FILE"
  else
    status=$?
    printf '%s\n' "$response" >> "$LOG_FILE"
    echo "[$timestamp] Resultado: ERROR code=$status" >> "$LOG_FILE"
  fi
  echo >> "$LOG_FILE"
}

while true; do
  run_alerts
  sleep "$INTERVAL"
done
