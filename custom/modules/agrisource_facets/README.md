# Pages (This label may change later) facets: combine “page” + “landing_page” into one checkbox

This custom solution lets the **Content type** facet show a single checkbox called **Pages (This label may change later)** while still filtering Solr by the real bundles (`page` + `landing_page`). It preserves WCAG-friendly **Facets-as-blocks** and avoids Views exposed filters.

**Tested with:**

- Drupal core **11.1.8**
- **Facets 3.0.0**
- **Search API 1.38**
- **search_api_solr 4.0.x-dev**
- Apache Solr **9.9.0**

---

## What’s in here

1) **UI combiner** — Facets build processor  
   `CombineTypeValues` (build weight **12**)  
   - Hides the raw values `page` and `landing_page`  
   - Shows one virtual option **Pages (This label may change later)** with the **sum** of their counts  
   - Normalizes the facet state so the checkbox renders/behaves correctly (sets active items/filters to the virtual value when appropriate)

2) **URL toggle fix** — Facets post-URL build processor  
   `AgrisourceUrlFix` (build weight **20**)  
   - When `?f[]=type:agrisource` is already in the URL, forces the Pages (This label may change later) link to be a **remove** link (dedupes existing parameters)  
   - Prevents `f[]=type:agrisource` from being appended repeatedly on subsequent clicks

3) **Backend rewrite** — Search API query alter (in-place)  
   `agrisource_facets_search_api_query_alter()`  
   - Finds any condition `type = agrisource` that Facets injects  
   - Mutates that condition **in place** to `type IN ["page","landing_page"]`  
   - Works inside nested groups (e.g., alongside `type = news` in OR groups)  
   - Ensures Solr is never asked for a non-existent bundle value

---

## Files

- `custom/modules/agrisource_facets/src/Plugin/facets/processor/CombineTypeValues.php`
- `custom/modules/agrisource_facets/src/Plugin/facets/processor/AgrisourceUrlFix.php`
- `custom/modules/agrisource_facets/agrisource_facets.module` (contains `hook_search_api_query_alter()` and `hook_module_implements_alter()`)

> We intentionally **do not** ship any event subscribers. Timing across core/Search API/Solr events is brittle; the alter + processors are deterministic.

---

## Facet configuration

Facet: **Content type** (machine name: `type`)  
Widget: **Checkbox**  
Source: your search view block

Processors (order matters):

```yaml
processor_configs:
  combine_type_values:
    processor_id: combine_type_values
    weights:
      build: 12
    settings:
      facet_id: type
      virtual_value: agrisource
      virtual_label: Pages
      source_values: [page, landing_page]

  url_processor_handler:
    processor_id: url_processor_handler
    weights:
      build: 15
    settings: { }

  agrisource_url_fix:
    processor_id: agrisource_url_fix
    weights:
      build: 20
    settings: { }
```

Other facet settings (typical):

- `field_identifier: type`
- `query_operator: or`
- Show counts: **on**

---

## How it works (flow)

1) User loads `/en/search` with no params → facet lists **Pages (This label may change later)**, **News**, **Employment Opportunities**.  
2) User checks **Pages (This label may change later)** → URL gets `?f[0]=type:agrisource`.  
3) **Query alter** sees `type = agrisource` in the condition tree and rewrites it **in place** to `type IN ["page","landing_page"]`.  
4) **CombineTypeValues** shows one checkbox with the sum of counts and marks the virtual value active so the UI stays in sync.  
5) On the **second click**, **AgrisourceUrlFix** forces the URL to **remove** the `type:agrisource` param instead of adding another one (fixes duplication).

---

## Install / update

1) Put the two processors and the `.module` in `custom/modules/agrisource_facets/`.  
2) Configure the **Content type** facet as above (or import the YAML).  
3) Ensure the Search API index id is `content` and the field id is `type`.  
4) Clear caches:
   ```bash
   drush cr
   ```
5) (Optional) Export config:
   ```bash
   drush cex -y
   ```

> No reindex is required: everything happens at query/build time.

---

## Troubleshooting

- **Duplicate `f[]=type:agrisource` on repeated clicks**  
  Make sure `AgrisourceUrlFix` is enabled and has **build: 20** (after `url_processor_handler` at 15).

- **Pages (This label may change later) checkbox doesn’t look selected when filtering**  
  Confirm `CombineTypeValues` is enabled at **build: 12** and that it normalizes both `activeItems` and `activeFilters`.

- **Empty results when Pages (This label may change later) is checked**  
  Verify the `.module` alter is present and mutates `type = agrisource` to `IN ["page","landing_page"]`. Also confirm your index’s type field id is literally `type`.

- **“Only Pages (This label may change later)” appears when no filters selected**  
  That usually means something is forcing a `type` condition globally. Ensure the `.module` alter only rewrites existing `type = agrisource` (it shouldn’t add any filter by itself).

---

## Extending

- To group more bundles under Pages (This label may change later):  
  - Update `source_values` in `CombineTypeValues` settings, and  
  - Update the mapping in the `.module` alter (the `setValue([...])` array).

- To create **another** grouped option (e.g., “Publications”), copy `CombineTypeValues` with a different `virtual_value`/`label` and add another small in-place rewrite block for `type = publications` → `IN([bundle_a,bundle_b,…])`.

---

## Quick test plan

- `/en/search` → facet shows **Pages (This label may change later)**, **News**, **Employment Opportunities**.  
- Click **Pages (This label may change later)** → URL has exactly **one** `type:Pages (This label may change later)`; results include Page + Landing page.  
- Click **Pages (This label may change later)** again → URL removes the param; results reset.  
- Check **Pages (This label may change later) + News** → results are the union of those types; uncheck **Pages (This label may change later)** leaves News intact.  
- Pagination and “Show all” reset links still behave.

---

## Notes for reviewers

- We deliberately avoid Views exposed filters to keep WCAG score and use Facets blocks.  
- No schema changes; safe to deploy across environments.  
- All code paths are idempotent and bail out quickly when Pages (This label may change later) isn’t involved.  
- Logging statements in processors are minimal and safe to disable if desired.

---

## License / ownership

Custom code for the Pages (This label may change later) project. © Your org / client. Adjust as needed.
