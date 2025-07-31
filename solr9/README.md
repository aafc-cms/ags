Baseline setup steps.

# Grab the jumpstart for solr9 from the latest possible search_api_solr drupal module.
cp ../html/modules/contrib/search_api_solr/jump-start/solr9 . -pr
# Blast the synonyms for unsupported languages.
`cd cloud-config-set;`
`rm synonyms_p* synonyms_t* synonyms_s* synonyms_d* synonyms_uk.txt synonyms_a* synonyms_b* synonyms_c* synonyms_el.txt synonyms_es.txt synonyms_fa.txt synonyms_fi.txt synonyms_ga.txt synonyms_h* synonyms_n* synonyms_i* synonyms_l* synonyms_r* synonyms_et.txt`
`cd ../config-set;`
# Repeat rm of synonyms step (above).
# Now blast the stopwords for unsupported languages.
`cd ../cloud-config-set;`
`rm stopwords_p* stopwords_t* stopwords_s* stopwords_d* stopwords_uk.txt stopwords_a* stopwords_b* stopwords_c* stopwords_el.txt stopwords_es.txt stopwords_fa.txt stopwords_fi.txt stopwords_ga.txt stopwords_h* stopwords_n* stopwords_i* stopwords_l* stopwords_r* stopwords_et.txt stoptags_ja.txt stopwords_ko.txt`
`cd ../config-set;`
# Repeat rm of stopwords step (above).
# Now blast the protwords for unsupported languages.
`cd ../cloud-config-set;`
`rm protwords_p* protwords_t* protwords_s* protwords_d* protwords_uk.txt protwords_a* protwords_b* protwords_c* protwords_el.txt protwords_es.txt protwords_fa.txt protwords_fi.txt protwords_ga.txt protwords_h* protwords_n* protwords_i* protwords_l* protwords_r* protwords_et.txt`
`cd ../config-set;`
# Repeat rm of protwords step (above).
# Now blast the accents for unsupported languages.
`cd ../cloud-config-set;`
`rm accents_p* accents_t* accents_s* accents_d* accents_uk.txt accents_a* accents_b* accents_c* accents_el.txt accents_es.txt accents_fa.txt accents_fi.txt accents_ga.txt accents_h* accents_n* accents_i* accents_l* accents_r* accents_et.txt`
`cd ../config-set;`
# Repeat rm of accents step (above).
# Now blast the nouns for unsupported languages.
`cd ../cloud-config-set;`
`rm nouns_p* nouns_t* nouns_s* nouns_d* nouns_uk.txt nouns_a* nouns_b* nouns_c* nouns_el.txt nouns_es.txt nouns_fa.txt nouns_fi.txt nouns_h* nouns_n* nouns_i* nouns_r* nouns_et.txt`
`cd ../config-set;`
# Repeat rm of nouns step (above).


# Manually remove configurations for languages other than _und _en _fr in schema_extra_fields.xml, schema_extra_types.xml and solrconfig_extra.xml.


# In order to test the solr core you must map it to the expected folder in the solr container (normally docker-compose.yml).
