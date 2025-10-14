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

# Cores to create (space-separated). You can set CORES via environment.
CORES=${CORES:-"agrisource"}
CONF_PATH=${CONF_PATH:-/opt/solr/server/solr/configsets/agrisource/}

for core in $CORES; do
  if [ ! -d "/var/solr/data/$core" ]; then
    /opt/solr/bin/solr create -c "$core" -d "$CONF_PATH"
    echo "Created core: $core"
  else
    echo "Core already exists: $core"
  fi
done

wait "$pid"    # keep container in foreground

