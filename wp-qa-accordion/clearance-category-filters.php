<?php
/**
 * Hierarchical product_cat filters for Clearance-only products.
 *
 * Features:
 * - Builds cached JSON: clearance-filtered tree (only branches that actually have Clearance products).
 * - Intended for placement on Clearance category page.
 * - Forces product archive queries to include only products that belong to Clearance (ID 23866).
 * - Uses unique IDs/classes to avoid conflicts with other filter widgets on the same page.
 *
 * @package theme-or-plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/**
 * Fixed term ID for "Clearance" category.
 * Per request: hard-coded to 23866.
 */
const MD_CLEARANCE_TERM_ID = 23866;

/**
 * Get term ID by slug safely.
 *
 * @param string $slug Slug to look up.
 * @return int|null
 */
function md_clearance_get_term_id_by_slug( $slug ) : ?int {
  $term = get_term_by( 'slug', $slug, 'product_cat' );
  return ( $term && ! is_wp_error( $term ) ) ? (int) $term->term_id : null;
}

/**
 * Build children tree for product_cat (up to 5 levels).
 *
 * @param int $parent_id Parent term ID.
 * @param int $level     Current level (1..5).
 * @return array
 */
function md_clearance_get_child_categories_tree( int $parent_id, int $level = 1 ) : array {
  if ( $level > 5 ) {
    return [];
  }

  $children = get_categories( [
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
    'parent'     => $parent_id,
  ] );

  $result = [];

  foreach ( $children as $child ) {
    $result[] = [
      'id'       => (int) $child->term_id,
      'name'     => $child->name,
      'slug'     => $child->slug,
      'children' => md_clearance_get_child_categories_tree( (int) $child->term_id, $level + 1 ),
    ];
  }

  return $result;
}

/**
 * Check whether at least one product has BOTH:
 * - branch term ($branch_term_id)
 * - context term ($context_term_id), i.e. "Clearance".
 *
 * Uses a cheap existence query (limit 1).
 *
 * @param int $branch_term_id  Branch term ID.
 * @param int $context_term_id Context term ID.
 * @return bool
 */
function md_clearance_branch_has_product_in_context( int $branch_term_id, int $context_term_id ) : bool {
  $q = new WP_Query( [
    'post_type'           => 'product',
    'post_status'         => 'publish',
    'fields'              => 'ids',
    'posts_per_page'      => 1,
    'no_found_rows'       => true,
    'ignore_sticky_posts' => true,
    'tax_query'           => [
      'relation' => 'AND',
      [
        'taxonomy'         => 'product_cat',
        'field'            => 'term_id',
        'terms'            => [ $branch_term_id ],
        'include_children' => true,
        'operator'         => 'IN',
      ],
      [
        'taxonomy'         => 'product_cat',
        'field'            => 'term_id',
        'terms'            => [ $context_term_id ],
        'include_children' => true,
        'operator'         => 'IN',
      ],
    ],
  ] );

  return ( $q->have_posts() );
}

/**
 * Recursively prune a category tree to nodes that intersect with $context_term_id.
 * A node is kept if:
 * - it has at least one intersecting product, OR
 * - any of its children is kept.
 *
 * @param array $nodes            Tree nodes.
 * @param int   $context_term_id  Context term ID to intersect with.
 * @return array
 */
function md_clearance_filter_tree_by_context( array $nodes, int $context_term_id ) : array {
  $out = [];

  foreach ( $nodes as $node ) {
    $children      = isset( $node['children'] ) && is_array( $node['children'] ) ? $node['children'] : [];
    $kept_children = ! empty( $children ) ? md_clearance_filter_tree_by_context( $children, $context_term_id ) : [];
    $keep_self     = md_clearance_branch_has_product_in_context( (int) $node['id'], $context_term_id );

    if ( $keep_self || ! empty( $kept_children ) ) {
      $node['children'] = $kept_children;
      $out[] = $node;
    }
  }

  return $out;
}

/**
 * Build and save JSON file:
 * - cat-filters-clearance.json (tree pruned by intersection with Clearance term ID)
 *
 * Saved in wp-content/ so front can fetch via content_url().
 *
 * @return void
 */
function md_clearance_build_filters_json() : void {
  $root_slug = 'body-kits';
  $root_id   = md_clearance_get_term_id_by_slug( $root_slug );
  if ( ! $root_id ) {
    return;
  }

  $tree_full = md_clearance_get_child_categories_tree( $root_id );
  $dir       = WP_CONTENT_DIR;

  // Save Clearance-filtered tree, if Clearance term exists.
  $clearance_id = (int) MD_CLEARANCE_TERM_ID;
  $term_ok      = get_term( $clearance_id, 'product_cat' );
  if ( $term_ok && ! is_wp_error( $term_ok ) ) {
    $tree_clearance = md_clearance_filter_tree_by_context( $tree_full, $clearance_id );
    file_put_contents(
      trailingslashit( $dir ) . 'cat-filters-clearance.json',
      wp_json_encode( $tree_clearance, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
    );
  }
}

/**
 * Trigger builder by URL (?update_clearance_filters=1) and via cron every 30 minutes.
 * NOTE: This is admin-ish, but we're not doing nonce here because it's dev-only trigger.
 * If you expose on production, wrap it with capability check.
 */
add_action( 'init', function () {
  // phpcs:ignore WordPress.Security.NonceVerification
  if ( isset( $_GET['update_clearance_filters'] ) ) {
    md_clearance_build_filters_json();
    wp_die( 'Clearance categories updated.' );
  }
} );

/**
 * Add a 30-minute cron schedule.
 *
 * @param array $schedules WP cron schedules.
 * @return array
 */
add_filter( 'cron_schedules', function ( $schedules ) {
  $schedules['half_hour'] = [
    'interval' => 1800,
    'display'  => __( 'Every 30 Minutes', 'md' ),
  ];
  return $schedules;
} );

if ( ! wp_next_scheduled( 'md_update_wc_categories_clearance_cron' ) ) {
  wp_schedule_event( time(), 'half_hour', 'md_update_wc_categories_clearance_cron' );
}

add_action( 'md_update_wc_categories_clearance_cron', 'md_clearance_build_filters_json' );

/**
 * Whether current request is in "Clearance context".
 *
 * Clearance context means:
 * - We are on a product_cat archive that is under /product-category/clearance/,
 *   OR the queried term itself is "clearance" (or MD_CLEARANCE_TERM_ID),
 *   OR we're on a single product that has the Clearance term.
 *
 * We also parse REQUEST_URI for safety because on deep paths,
 * Woo may set queried_object = leaf term (like "a4"), not literally "clearance".
 *
 * @return bool
 */
function md_is_clearance_context() : bool {
  // Category archive cases.
  if ( function_exists( 'is_product_category' ) && is_product_category() ) {
    $q = get_queried_object();

    if ( $q instanceof WP_Term && 'product_cat' === $q->taxonomy ) {
      // Direct match on "clearance" or Clearance term ID.
      if ( 'clearance' === $q->slug || (int) $q->term_id === (int) MD_CLEARANCE_TERM_ID ) {
        return true;
      }

      // Is current category a descendant of Clearance?
      $clearance = get_term( (int) MD_CLEARANCE_TERM_ID, 'product_cat' );
      if ( $clearance && ! is_wp_error( $clearance ) ) {
        if ( term_is_ancestor_of( (int) $clearance->term_id, (int) $q->term_id, 'product_cat' ) ) {
          return true;
        }
      }
    }

    // Extra safety: look at the raw URL path.
    $path = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
    if ( str_contains( $path, '/product-category/clearance/' ) ) {
      return true;
    }
  }

  // Single product cases.
  if ( function_exists( 'is_product' ) && is_product() ) {
    if (
      has_term( (int) MD_CLEARANCE_TERM_ID, 'product_cat' ) ||
      has_term( 'clearance', 'product_cat' )
    ) {
      return true;
    }
  }

  return false;
}

/**
 * Force Clearance restriction on product archives in Clearance context.
 *
 * Goal:
 * - When browsing a product category tree that lives under /product-category/clearance/...,
 *   show ONLY products that ALSO have the Clearance term (ID MD_CLEARANCE_TERM_ID),
 *   even if they match the subcategory but are not actually Clearance.
 *
 * How:
 * - We run on pre_get_posts at a very late priority (999) so that:
 *   - WooCommerce has already prepared its main query.
 *   - FiboFilters or other filters plugins (which also mess with tax_query) have already done their thing.
 *
 * Then we take the existing `tax_query` (which might already include conditions from those plugins)
 * and we append an extra AND condition that the product must belong to Clearance term.
 *
 * @param WP_Query $query Query object.
 * @return void
 */
function md_force_clearance_tax_query( $query ) : void {
  // Front only.
  if ( is_admin() ) {
    return;
  }

  // Only main loop.
  if ( ! $query->is_main_query() ) {
    return;
  }

  // Only on product category archives.
  if ( ! ( function_exists( 'is_product_category' ) && $query->is_tax( 'product_cat' ) && is_product_category() ) ) {
    return;
  }

  // Only if we're in Clearance mode.
  if ( ! md_is_clearance_context() ) {
    return;
  }

  $clearance_id = (int) MD_CLEARANCE_TERM_ID;

  // Make sure status is 'publish', just in case filters/plugins messed with that.
  $query->set( 'post_status', 'publish' );
  $query->set( 'post_type', 'product' );

  // Get whatever FiboFilters / Woo / others already set.
  $tax_query = $query->get( 'tax_query' );

  if ( ! is_array( $tax_query ) ) {
    $tax_query = [];
  }

  // Ensure top-level relation is AND, because we want intersection.
  if ( ! isset( $tax_query['relation'] ) ) {
    $tax_query['relation'] = 'AND';
  }

  // Add our Clearance requirement.
  $tax_query[] = [
    'taxonomy'         => 'product_cat',
    'field'            => 'term_id',
    'terms'            => [ $clearance_id ],
    'include_children' => true,
    'operator'         => 'IN',
  ];

  $query->set( 'tax_query', $tax_query );
}
add_action( 'pre_get_posts', 'md_force_clearance_tax_query', 999 );

/**
 * Shortcode renderer: prints 5-level cascading selects for Clearance.
 *
 * @return void
 */
function md_display_clearance_category_filters() : void {
  $json_basename = 'cat-filters-clearance.json';
  $json_fs       = trailingslashit( WP_CONTENT_DIR ) . $json_basename;
  $json_url      = trailingslashit( content_url() ) . $json_basename;

  if ( ! file_exists( $json_fs ) ) {
    echo '<p>' . esc_html__( 'Filters not found', 'md' ) . '</p>';
    return;
  }

  $json_data  = file_get_contents( $json_fs ); // phpcs:ignore WordPress.WP.AlternativeFunctions
  $categories = json_decode( $json_data, true );
  if ( ! is_array( $categories ) ) {
    echo '<p>' . esc_html__( 'Error loading categories.', 'md' ) . '</p>';
    return;
  }

  $placeholders = [
    1 => 'Select by Make',
    2 => 'Select by Model',
    3 => 'Select by Version',
    4 => 'Select by Generation',
    5 => 'Select by Type',
  ];

  /**
   * Generate a select for a given level.
   *
   * @param array $cats  Cats for this level.
   * @param int   $level Level number 1..5.
   * @param array $ph    Placeholder map (1..5).
   * @return string HTML.
   */
  $generate_select = function ( array $cats, int $level, array $ph ) : string {
    $select_id = 'md-clearance-category-level-' . $level;
    $html  = '<select id="' . esc_attr( $select_id ) . '" name="' . esc_attr( $select_id ) . '" class="md-clearance-category-filter">';
    $html .= '<option value="">' . esc_html( $ph[ $level ] ) . '</option>';

    foreach ( $cats as $cat ) {
      $name = isset( $cat['name'] ) ? $cat['name'] : '';
      $slug = isset( $cat['slug'] ) ? $cat['slug'] : '';
      $html .= '<option value="' . esc_attr( $slug ) . '" data-name="' . esc_attr( $name ) . '">' . esc_html( $name ) . '</option>';
    }

    $html .= '</select>';
    return $html;
  };
  ?>
  <div class="md-clearance-category-filter-form-wrapper">
    <div class="container">
      <form class="md-clearance-category-filter-form" data-md-clearance="form">
        <?php
        echo $generate_select( $categories, 1, $placeholders ); // phpcs:ignore WordPress.Security.EscapeOutput
        for ( $i = 2; $i <= 5; $i++ ) :
          ?>
          <select
            id="md-clearance-category-level-<?php echo (int) $i; ?>"
            name="md-clearance-category-level-<?php echo (int) $i; ?>"
            class="md-clearance-category-filter"
            disabled
          >
            <option value="">
              <?php echo esc_html( $placeholders[ $i ] ); ?>
            </option>
          </select>
        <?php endfor; ?>

        <button type="button" class="md-clearance-search-button" disabled>
          <?php echo esc_html__( 'Search', 'md' ); ?>
        </button>
        <p class="md-clearance-search-url" style="margin-top:10px;font-size:14px;"></p>
      </form>
    </div>
  </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const wrapper = document.querySelector('.md-clearance-category-filter-form-wrapper');
  if (!wrapper) {
    return;
  }

  const selects = Array.from(wrapper.querySelectorAll('.md-clearance-category-filter'));
  const lvl1 = wrapper.querySelector('#md-clearance-category-level-1');
  const searchButton = wrapper.querySelector('.md-clearance-search-button');
  const searchUrlText = wrapper.querySelector('.md-clearance-search-url');

  // Forced base for results
  const FORCE_CATEGORY_BASE = 'https://maxtondesignireland.ie/product-category/';
  const BODY_KITS_SEGMENT = 'body-kits/';
  const CLEARANCE_SEGMENT = 'clearance/';

  // URL for clearance tree
  const jsonUrlClearance = <?php echo wp_json_encode( esc_url( $json_url . '?v=2026-01-26' ) ); ?>;

  // Placeholders for each level index 0..4
  const placeholders = ['Select by Make', 'Select by Model', 'Select by Version', 'Select by Generation', 'Select by Type'];
  const noResultsText = ['No make', 'No model', 'No version', 'No generation', 'No type'];

  let clearanceTree = null;

  // ---------------- DATA HELPERS ----------------
  function ensureTreeLoaded() {
    if (clearanceTree) {
      return Promise.resolve();
    }

    return fetch(jsonUrlClearance)
      .then(r => r.json())
      .then(data => { clearanceTree = data || []; });
  }

  function getTree() {
    return clearanceTree || [];
  }

  function getChildrenForPath(pathSlugs) {
    const tree = getTree();

    if (pathSlugs.length === 0) {
      // top level options
      return tree;
    }

    let currentList = tree;
    let currentNode = null;
    for (const slug of pathSlugs) {
      currentNode = null;
      for (const node of currentList) {
        if (node.slug === slug) {
          currentNode = node;
          break;
        }
      }
      if (!currentNode) {
        return []; // invalid path
      }
      currentList = Array.isArray(currentNode.children) ? currentNode.children : [];
    }

    return Array.isArray(currentList) ? currentList : [];
  }

  // ---------------- SELECT HELPERS ----------------
  function resetAllSelects() {
    selects.forEach((sel, idx) => {
      sel.innerHTML = `<option value="">${placeholders[idx]}</option>`;
      sel.disabled = (idx !== 0); // only first enabled
    });
  }

  function populateLevel1() {
    const rootOptions = getChildrenForPath([]); // top level
    lvl1.innerHTML = `<option value="">${placeholders[0]}</option>`;

    if (rootOptions.length) {
      rootOptions.forEach(cat => {
        lvl1.innerHTML += `<option value="${cat.slug}" data-name="${cat.name}">${cat.name}</option>`;
      });
      lvl1.disabled = false;
    } else {
      lvl1.innerHTML = `<option value="">${noResultsText[0]}</option>`;
      lvl1.disabled = false;
    }
  }

  function repopulateBelow(levelIndex) {
    // collect slugs up to "levelIndex"
    const chosenSlugs = [];
    selects.forEach((sel, idx) => {
      if (idx <= levelIndex && sel.value) {
        chosenSlugs.push(sel.value);
      }
    });

    // rebuild deeper levels from tree
    for (let nextLevel = levelIndex + 1; nextLevel < selects.length; nextLevel++) {
      const pathForThisLevel = chosenSlugs.slice(0, nextLevel);
      const targetSelect = selects[nextLevel];
      const kids = getChildrenForPath(pathForThisLevel);

      if (kids.length) {
        targetSelect.innerHTML = `<option value="">${placeholders[nextLevel]}</option>`;
        kids.forEach(cat => {
          targetSelect.innerHTML += `<option value="${cat.slug}" data-name="${cat.name}">${cat.name}</option>`;
        });
        targetSelect.disabled = false;
      } else {
        targetSelect.innerHTML = `<option value="">${noResultsText[nextLevel]}</option>`;
        targetSelect.disabled = true;
      }
    }
  }

  function getChosenSlugs() {
    const arr = [];
    selects.forEach(sel => {
      if (sel.value) {
        arr.push(sel.value);
      }
    });
    return arr;
  }

  function updateSearchControls() {
    const slugs = getChosenSlugs();

    const prefix = CLEARANCE_SEGMENT + BODY_KITS_SEGMENT;

    const fullPath = FORCE_CATEGORY_BASE + prefix + slugs.join('/');

    searchUrlText.innerHTML = `<a href="${fullPath}" target="_blank" rel="noopener">${fullPath}</a>`;
    searchButton.setAttribute('data-url', fullPath);
    searchButton.disabled = slugs.length === 0;
  }

  // When user clicks Search → go
  searchButton.addEventListener('click', function () {
    const url = this.getAttribute('data-url');
    if (url) {
      window.location.href = url;
    }
  });

  // When user changes any select
  selects.forEach((sel, idx) => {
    sel.addEventListener('change', function () {
      repopulateBelow(idx);
      updateSearchControls();
    });
  });

  // ---------------- PRESELECTION FROM URL ----------------
  /**
   * getPathSlugsFromURL()
   * We look at current URL to guess:
   * - collect slugs after body-kits/ as [make, model, ...]
   */
  function getPathSlugsFromURL() {
    const seg = window.location.pathname.split('/').filter(Boolean); // no empty
    // Example:
    // /product-category/clearance/body-kits/audi/a4/
    // find 'product-category' and 'body-kits'
    const pcIndex = seg.indexOf('product-category');
    const bkIndex = seg.indexOf('body-kits');

    if (pcIndex === -1 || bkIndex === -1) {
      return [];
    }

    // everything after 'body-kits' are our slugs
    return seg.slice(bkIndex + 1).filter(s => !!s);
  }

  /**
   * applyPreselectionFromSlugs(slugs)
   * 1. Fill level 0 options (Make), pick slugs[0] if exists
   * 2. For each deeper level, build options using repopulateBelow()
   *    and set its value to slugs[n] if present and exists
   *
   * NOTE: We call this AFTER ensureTreeLoaded() so we have data.
   */
  function applyPreselectionFromSlugs(slugs) {
    // Start clean
    resetAllSelects();
    populateLevel1();

    // Nothing to preselect? we're done
    if (!slugs.length) {
      updateSearchControls();
      return;
    }

    // 1) Set level 0 (Make)
    if (slugs[0]) {
      lvl1.value = slugs[0];
    }

    // 2) Walk deeper
    for (let level = 0; level < selects.length - 1; level++) {
      repopulateBelow(level); // build next levels based on chosen so far

      const nextLevel = level + 1;
      const wantedSlug = slugs[nextLevel];

      if (!wantedSlug) {
        break;
      }

      const targetSelect = selects[nextLevel];
      if ([...targetSelect.options].some(opt => opt.value === wantedSlug)) {
        targetSelect.value = wantedSlug;
      } else {
        // URL references deeper slug that's not present in this tree.
        break;
      }
    }

    // Final refresh of deeper levels
    const lastPreLevel = Math.min(slugs.length - 1, selects.length - 1);
    repopulateBelow(lastPreLevel);

    updateSearchControls();
  }

  // ---------------- FIRST LOAD FLOW ----------------
  ensureTreeLoaded().then(() => {
    const slugs = getPathSlugsFromURL();
    applyPreselectionFromSlugs(slugs);
  });
});
</script>

  <?php
}
add_shortcode( 'category_filters_clearance', 'md_display_clearance_category_filters' );

/**
 * Auto-render the Clearance filter on Clearance category archives (including descendants).
 *
 * This ensures the widget shows on /product-category/clearance/ and any deeper path
 * under that branch after filtering is applied.
 */
add_action( 'woocommerce_before_shop_loop', function () {
  if ( function_exists( 'is_product_category' ) && is_product_category() && md_is_clearance_context() ) {
    echo do_shortcode( '[category_filters_clearance]' );
  }
}, 5 );

/**
 * Front-end styles.
 */
add_action( 'wp_footer', function () { ?>
  <style>
    .md-clearance-category-filter-form-wrapper {
      background-color:#fff;
    }
    .md-clearance-category-filter-form {
      position: relative;
      display:flex;
      margin:0;
      padding:20px 0;
      gap:10px;
      align-items:center;
      flex-wrap:wrap;
    }
    .md-clearance-category-filter {
      flex:1 1 180px;
      border:1px solid #666;
      border-radius:3px;
      padding:.5rem 1rem;
      transition:all .3s;
      background:#fff;
      color:#000;
      font-family:poppins,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif !important;
      font-weight:300;
      font-size:13px;
    }

    @media(max-width: 992px) {
      .md-clearance-category-filter {
        flex:1 1 40px
      }
    }

    .md-clearance-search-url {
      display:none;
    }
    .md-clearance-category-filter-form .md-clearance-search-button {
      height: 38px;
      margin: 0 0 2px 0;
      border-radius: 3px;
      background:#ff0000;
      color:#fff;
      font-size:14px;
      padding:6px 40px;
      border:0;
      cursor:pointer;
    }
    .md-clearance-category-filter-form .md-clearance-search-button:hover {
      opacity:.9;
    }

    @media ( max-width: 992px ) {
      .md-clearance-category-filter-form {
        flex-direction:column;
        align-items:stretch;
      }
      .md-clearance-category-filter {
        width:100%;
      }
      .md-clearance-category-filter[disabled] {
        display:none;
      }
    }

    @media ( max-width: 478px ) {
      .woocommerce ul.products[class*=columns-] li.product,
      .woocommerce-page ul.products[class*=columns-] li.product {
        width: 100%;
        float: none;
        margin: 0 0 0.5rem 0;
      }
    }
  </style>
<?php }, 999 );
