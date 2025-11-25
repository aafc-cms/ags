
solr-data must be owned by 8983:8983

ensure solr-data is owned by 8983:8983 before running `docker-compose up -d`

##Dictionary setup:

curl http://172.17.1.2:8983/solr/agrisource/suggest?suggest.build=true&suggest.dictionary=en
curl http://172.17.1.2:8983/solr/agrisource/suggest?suggest.build=true&suggest.dictionary=fr
curl http://172.17.1.2:8983/solr/agrisource/suggest?suggest.build=true&suggest.dictionary=und

curl http://172.17.1.2:8983/solr/corename/suggest?suggest.build=true&suggest.dictionary=en
curl http://172.17.1.2:8983/solr/corename/suggest?suggest.build=true&suggest.dictionary=fr
curl http://172.17.1.2:8983/solr/corename/suggest?suggest.build=true&suggest.dictionary=und

**Operational considerations**

After re-indexing, ensure that cron is triggered for search_api and search_api_solr, otherwise possibly result in "suggester was not built" error.
