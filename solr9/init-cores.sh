#!/usr/bin/env bash
set -euo pipefail

docker-entrypoint.sh solr-foreground &   # start Solr in bg
pid=$!

# Wait until Solr is up (try both known paths; otherwise curl-loop)
if [ -x /opt/solr/docker/scripts/wait-for-solr.sh ]; then
  /opt/solr/docker/scripts/wait-for-solr.sh --max-attempts 60 --solr-url http://localhost:8983/solr
elif [ -x /opt/docker-solr/scripts/wait-for-solr.sh ]; then
  /opt/docker-solr/scripts/wait-for-solr.sh --max-attempts 60 --solr-url http://localhost:8983/solr
else
  echo "wait-for-solr.sh not found; using curl loop..."
  for i in {1..60}; do
    if curl -sf http://localhost:8983/solr/admin/info/system?wt=json >/dev/null; then
      break
    fi
    sleep 2
  done
fi

CORES=${CORES:-"agrisource"}
# IMPORTANT: point to conf/ directly
CONF_PATH=${CONF_PATH:-/opt/solr/server/solr/configsets/agrisource}

for core in $CORES; do
  CORE_DIR="/var/solr/data/$core"
  if [ ! -d "$CORE_DIR" ]; then
    # Use create_core and pass the conf dir
    /opt/solr/bin/solr create_core -c "$core" -d "$CONF_PATH"
    echo "Created core: $core using $CONF_PATH"
  else
    echo "Core already exists: $core"
    # self-heal if conf/ is missing or empty
    if [ ! -d "$CORE_DIR/conf" ] || [ -z "$(ls -A "$CORE_DIR/conf" 2>/dev/null || true)" ]; then
      mkdir -p "$CORE_DIR/conf"
      cp -a "$CONF_PATH"/. "$CORE_DIR/conf/"
      echo "Repaired $core/conf from $CONF_PATH"
      curl -s "http://localhost:8983/solr/admin/cores?action=RELOAD&core=$core" >/dev/null || true
    fi
  fi
done

wait "$pid"    # keep container in foreground

