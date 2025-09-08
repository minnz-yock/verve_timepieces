<?php
if (!isset($_SESSION)) session_start();
require_once "../dbconnect.php";
require_once "favorites_util.php";

/* ---------- Utilities ---------- */
function h($v)
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
function format_money($n)
{
    return '$' . number_format((float)$n, 2);
}

/* ---------- Read GET ---------- */
$brandIds       = isset($_GET['brand'])        ? array_map('intval', (array)$_GET['brand']) : [];
$categoryIds    = isset($_GET['category'])     ? array_map('intval', (array)$_GET['category']) : [];
$caseMatIds     = isset($_GET['case_material']) ? array_map('intval', (array)$_GET['case_material']) : [];
$genderIds      = isset($_GET['gender'])       ? array_map('intval', (array)$_GET['gender']) : [];
$dialColorIds   = isset($_GET['dial_color'])   ? array_map('intval', (array)$_GET['dial_color']) : [];
$sizeRanges     = isset($_GET['size'])         ? array_map('strval', (array)$_GET['size']) : [];
$minPrice       = isset($_GET['min_price'])    && $_GET['min_price'] !== '' ? max(0, (float)$_GET['min_price']) : null;
$maxPrice       = isset($_GET['max_price'])    && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;
$sort           = isset($_GET['sort']) ? $_GET['sort'] : 'latest'; // latest | price_asc | price_desc

/* ---------- DB min/max price ---------- */
$mm = $conn->query("SELECT MIN(price) AS minp, MAX(price) AS maxp FROM products")->fetch(PDO::FETCH_ASSOC);
$globalMin = isset($mm['minp']) ? (float)$mm['minp'] : 0;
$globalMax = isset($mm['maxp']) ? (float)$mm['maxp'] : 10000;

/* ---------- Query builder ---------- */
function build_where(array $state, string $excludeFacet = null)
{
    $w = [];
    $p = [];

    if ($excludeFacet !== 'brand' && !empty($state['brand'])) {
        $in  = implode(',', array_fill(0, count($state['brand']), '?'));
        $w[] = "p.brand_id IN ($in)";
        $p   = array_merge($p, $state['brand']);
    }
    if ($excludeFacet !== 'category' && !empty($state['category'])) {
        $in  = implode(',', array_fill(0, count($state['category']), '?'));
        $w[] = "p.category_id IN ($in)";
        $p   = array_merge($p, $state['category']);
    }
    if ($excludeFacet !== 'case_material' && !empty($state['case_material'])) {
        $in  = implode(',', array_fill(0, count($state['case_material']), '?'));
        $w[] = "p.case_material_id IN ($in)";
        $p   = array_merge($p, $state['case_material']);
    }
    if ($excludeFacet !== 'gender' && !empty($state['gender'])) {
        $in  = implode(',', array_fill(0, count($state['gender']), '?'));
        $w[] = "p.gender_id IN ($in)";
        $p   = array_merge($p, $state['gender']);
    }
    if ($excludeFacet !== 'dial_color' && !empty($state['dial_color'])) {
        $in  = implode(',', array_fill(0, count($state['dial_color']), '?'));
        $w[] = "p.dial_color_id IN ($in)";
        $p   = array_merge($p, $state['dial_color']);
    }
    if ($excludeFacet !== 'size' && !empty($state['size'])) {
        $parts = [];
        foreach ($state['size'] as $token) {
            switch ($token) {
                case 'lt29':
                    $parts[] = "p.case_size < 29";
                    break;
                case '30_34':
                    $parts[] = "(p.case_size >= 30 AND p.case_size <= 34.99)";
                    break;
                case '35_37':
                    $parts[] = "(p.case_size >= 35 AND p.case_size <= 37.99)";
                    break;
                case '38_40':
                    $parts[] = "(p.case_size >= 38 AND p.case_size <= 40.99)";
                    break;
                case '41_43':
                    $parts[] = "(p.case_size >= 41 AND p.case_size <= 43.99)";
                    break;
                case 'gte44':
                    $parts[] = "p.case_size >= 44";
                    break;
            }
        }
        if ($parts) $w[] = '(' . implode(' OR ', $parts) . ')';
    }
    if ($excludeFacet !== 'price') {
        if ($state['min_price'] !== null) {
            $w[] = "p.price >= ?";
            $p[] = (float)$state['min_price'];
        }
        if ($state['max_price'] !== null) {
            $w[] = "p.price <= ?";
            $p[] = (float)$state['max_price'];
        }
    }

    $sql = $w ? ('WHERE ' . implode(' AND ', $w)) : '';
    return [$sql, $p];
}

/* ---------- Build current where ---------- */
$STATE = [
    'brand' => $brandIds,
    'category' => $categoryIds,
    'case_material' => $caseMatIds,
    'gender' => $genderIds,
    'dial_color' => $dialColorIds,
    'size' => $sizeRanges,
    'min_price' => $minPrice,
    'max_price' => $maxPrice,
];
list($whereSql, $whereParams) = build_where($STATE);

/* ---------- Fetch products ---------- */
$orderBy = "p.product_id DESC";
if ($sort === 'price_asc')   $orderBy = "p.price ASC";
if ($sort === 'price_desc')  $orderBy = "p.price DESC";

$sql = "SELECT p.product_id, p.product_name, p.price, p.image_url, p.case_size,
               b.brand_name, c.cat_name
        FROM products p
        JOIN brands b ON p.brand_id = b.brand_id
        JOIN categories c ON p.category_id = c.category_id
        $whereSql ORDER BY $orderBy";
$stmt = $conn->prepare($sql);
$stmt->execute($whereParams);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Facets + counts ---------- */
$brands = $conn->query("SELECT brand_id, brand_name FROM brands ORDER BY brand_name")->fetchAll(PDO::FETCH_ASSOC);
$categories = $conn->query("SELECT category_id, cat_name FROM categories ORDER BY cat_name")->fetchAll(PDO::FETCH_ASSOC);
$materials = $conn->query("SELECT case_material_id, material FROM case_materials ORDER BY material")->fetchAll(PDO::FETCH_ASSOC);
$genders = $conn->query("SELECT gender_id, gender FROM genders ORDER BY gender")->fetchAll(PDO::FETCH_ASSOC);
$dialColors = $conn->query("SELECT dial_color_id, dial_color FROM dial_colors ORDER BY dial_color")->fetchAll(PDO::FETCH_ASSOC);

function facet_counts_group($conn, $STATE, $facet, $table, $id, $name)
{
    list($w, $p) = build_where($STATE, $facet);
    $sql = "SELECT t.$id AS id, t.$name AS label, COUNT(*) AS cnt
          FROM products p JOIN $table t ON p.$id = t.$id
          $w GROUP BY t.$id, t.$name ORDER BY t.$name";
    $st = $conn->prepare($sql);
    $st->execute($p);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $map = [];
    foreach ($rows as $r) {
        $map[(int)$r['id']] = (int)$r['cnt'];
    }
    return $map;
}
$brandCounts   = facet_counts_group($conn, $STATE, 'brand', 'brands', 'brand_id', 'brand_name');
$catCounts     = facet_counts_group($conn, $STATE, 'category', 'categories', 'category_id', 'cat_name');
$matCounts     = facet_counts_group($conn, $STATE, 'case_material', 'case_materials', 'case_material_id', 'material');
$genderCounts  = facet_counts_group($conn, $STATE, 'gender', 'genders', 'gender_id', 'gender');
$colorCounts   = facet_counts_group($conn, $STATE, 'dial_color', 'dial_colors', 'dial_color_id', 'dial_color');

/* Size counts */
list($wSize, $pSize) = build_where($STATE, 'size');
$sqlSize = "SELECT
  SUM(CASE WHEN p.case_size < 29 THEN 1 ELSE 0 END)        AS lt29,
  SUM(CASE WHEN p.case_size >= 30 AND p.case_size <= 34.99 THEN 1 ELSE 0 END) AS r30_34,
  SUM(CASE WHEN p.case_size >= 35 AND p.case_size <= 37.99 THEN 1 ELSE 0 END) AS r35_37,
  SUM(CASE WHEN p.case_size >= 38 AND p.case_size <= 40.99 THEN 1 ELSE 0 END) AS r38_40,
  SUM(CASE WHEN p.case_size >= 41 AND p.case_size <= 43.99 THEN 1 ELSE 0 END) AS r41_43,
  SUM(CASE WHEN p.case_size >= 44 THEN 1 ELSE 0 END)       AS gte44
  FROM products p $wSize";
$stz = $conn->prepare($sqlSize);
$stz->execute($pSize);
$sz = $stz->fetch(PDO::FETCH_ASSOC) ?: ['lt29' => 0, 'r30_34' => 0, 'r35_37' => 0, 'r38_40' => 0, 'r41_43' => 0, 'gte44' => 0];

function checked($arr, $id)
{
    return in_array((string)$id, array_map('strval', $arr)) ? 'checked' : '';
}
function activeBadge($selected)
{
    return $selected ? 'filter-active' : '';
}
function qs_without($key)
{
    $q = $_GET;
    unset($q[$key]);
    return http_build_query($q);
}

$favSet = fav_get_ids($conn); // <-- fixed helper name
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Watches — Browse</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">


    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet"> <!-- for bi-search, bi-cart3 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #fff;
            color: #222;
        }

        .filters-wrap {
            position: sticky;
            top: 0;
            z-index: 100;
            background: #fff;
            border-bottom: 1px solid #eee;
        }

        .filters-bar {
            gap: .5rem;
            padding: 1rem .75rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-pill .btn {
            border: 1px solid #111;
            border-radius: 6px;
            padding: .5rem .75rem;
            font-weight: 600;
        }

        .filter-pill .dropdown-menu {
            width: 320px;
            max-height: 360px;
            overflow: auto;
            border: 1px solid #111;
            border-radius: 8px;
            padding: .5rem .75rem;
        }

        .filter-section-title {
            font-size: .85rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #666;
            margin: .25rem 0 .5rem;
        }

        .option-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .35rem .25rem;
            border-radius: 6px;
        }

        .option-row:hover {
            background: #f5f5f5;
        }

        .option-row label {
            flex: 1;
            cursor: pointer;
        }

        .count-badge {
            font-size: .8rem;
            color: #666;
        }

        .apply-row {
            display: flex;
            gap: .5rem;
            padding-top: .5rem;
            border-top: 1px dashed #ddd;
            margin-top: .5rem;
        }

        .apply-row .btn {
            flex: 1;
        }

        .filter-active {
            background: #111 !important;
            color: #fff !important;
        }

        .divider-v {
            width: 1px;
            height: 28px;
            background: #ddd;
            margin: 0 .25rem;
        }

        .btn-reset {
            border-color: #c00;
            color: #c00;
        }

        .btn-reset:hover {
            background: #c00;
            color: #fff;
        }

        .grid {
            padding: 1.25rem .75rem;
        }

        .product-card {
            border: 0;
            background: #fff;
            text-align: center;
        }

        .img-box {
            width: 100%;
            aspect-ratio: 1 / 1;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 12px;
            display: grid;
            place-items: center;
            overflow: hidden;
        }

        .img-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .brand {
            margin-top: .65rem;
            font-weight: 800;
            font-size: .9rem;
            letter-spacing: .6px;
            color: #000;
            text-transform: uppercase;
            text-align: center;
        }

        .model {
            font-size: .98rem;
            color: #333;
            margin: .15rem 0 .4rem;
            min-height: 2.2em;
            font-weight: 600;
            text-align: center;
        }

        .price {
            font-weight: 700;
            color: #000;
            font-size: 1rem;
            text-align: center;
        }

        .results-count {
            padding: .25rem .75rem;
            color: #666;
        }

        /* hearts */
        .cardwrap {
            position: relative;
        }

        .fav-pin {
            position: absolute;
            top: 10px;
            left: 10px;
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #fff;
            border: 1px solid #111;
            cursor: pointer;
            z-index: 5;
        }

        .fav-pin.active {
            background: #111;
            color: #fff;
        }

        /* Price slider */
        .price-slider {
            padding: .25rem 0 .75rem;
        }

        .range-wrap {
            position: relative;
            height: 32px;
        }

        .range-wrap input[type=range] {
            position: absolute;
            left: 0;
            right: 0;
            width: 100%;
            pointer-events: none;
            -webkit-appearance: none;
            background: transparent;
        }

        .range-wrap input[type=range]::-webkit-slider-thumb {
            pointer-events: auto;
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #111;
        }

        .range-wrap input[type=range]::-moz-range-thumb {
            pointer-events: auto;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #111;
            border: 0;
        }

        .range-track {
            height: 6px;
            background: #eee;
            border-radius: 4px;
            position: relative;
        }

        .range-fill {
            position: absolute;
            height: 6px;
            background: #111;
            border-radius: 4px;
        }
    </style>
</head>

<body>

    <div class="row">
        <?php include 'navbarnew.php'; ?>
    </div>

    <form class="filters-wrap" method="get" id="filtersForm">
        <!-- hidden fields for Price (only submit when Apply is clicked) -->
        <input type="hidden" name="min_price" id="min_price_field" <?= $minPrice === null ? 'disabled' : 'value="' . h($minPrice) . '"' ?>>
        <input type="hidden" name="max_price" id="max_price_field" <?= $maxPrice === null ? 'disabled' : 'value="' . h($maxPrice) . '"' ?>>

        <div class="filters-bar">
            <!-- Sort By -->
            <div class="dropdown filter-pill">
                <button class="btn <?= $sort !== 'latest' ? 'filter-active' : '' ?>" type="button" data-bs-toggle="dropdown">Sort By</button>
                <div class="dropdown-menu">
                    <div class="option-row"><input class="form-check-input me-2" type="radio" name="sort" id="sort_latest" value="latest" <?= $sort === 'latest' ? 'checked' : '' ?>><label class="form-check-label" for="sort_latest">Latest</label></div>
                    <div class="option-row"><input class="form-check-input me-2" type="radio" name="sort" id="sort_low" value="price_asc" <?= $sort === 'price_asc' ? 'checked' : '' ?>><label class="form-check-label" for="sort_low">Lowest price</label></div>
                    <div class="option-row"><input class="form-check-input me-2" type="radio" name="sort" id="sort_high" value="price_desc" <?= $sort === 'price_desc' ? 'checked' : '' ?>><label class="form-check-label" for="sort_high">Highest price</label></div>
                    <div class="apply-row"><a class="btn btn-outline-secondary" href="?<?= h(qs_without('sort')) ?>">Clear</a><button class="btn btn-dark" type="submit">Apply</button></div>
                </div>
            </div>

            <!-- Brand -->
            <div class="dropdown filter-pill">
                <button class="btn <?= activeBadge(!empty($brandIds)) ?>" type="button" data-bs-toggle="dropdown">Brand</button>
                <div class="dropdown-menu">
                    <div class="filter-section-title">Brand</div>
                    <?php foreach ($brands as $b): $id = (int)$b['brand_id'];
                        $cnt = $brandCounts[$id] ?? 0; ?>
                        <div class="option-row">
                            <div><input class="form-check-input me-2" type="checkbox" name="brand[]" id="brand_<?= $id ?>" value="<?= $id ?>" <?= checked($brandIds, $id) ?>><label class="form-check-label" for="brand_<?= $id ?>"><?= h($b['brand_name']) ?></label></div>
                            <span class="count-badge"><?= $cnt ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="apply-row"><a class="btn btn-outline-secondary" href="?<?= h(qs_without('brand')) ?>">Clear</a><button class="btn btn-dark" type="submit">Apply</button></div>
                </div>
            </div>

            <!-- Price -->
            <div class="dropdown filter-pill">
                <button class="btn <?= activeBadge($minPrice !== null || $maxPrice !== null) ?>" type="button" data-bs-toggle="dropdown">Price</button>
                <div class="dropdown-menu">
                    <div class="filter-section-title">Price range</div>
                    <div class="price-slider">
                        <div class="range-track" id="track">
                            <div class="range-fill" id="fill"></div>
                        </div>
                        <div class="range-wrap">
                            <input type="range" id="minRange" min="<?= (int)$globalMin ?>" max="<?= (int)$globalMax ?>" step="1">
                            <input type="range" id="maxRange" min="<?= (int)$globalMin ?>" max="<?= (int)$globalMax ?>" step="1">
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col"><label class="form-label small">Min price</label>
                            <div class="input-group"><input type="number" class="form-control" id="minPrice" min="0" value="<?= h($minPrice !== null ? $minPrice : $globalMin) ?>"><span class="input-group-text">USD</span></div>
                        </div>
                        <div class="col"><label class="form-label small">Max price</label>
                            <div class="input-group"><input type="number" class="form-control" id="maxPrice" min="0" value="<?= h($maxPrice !== null ? $maxPrice : $globalMax) ?>"><span class="input-group-text">USD</span></div>
                        </div>
                    </div>
                    <div class="apply-row"><button class="btn btn-outline-secondary" type="button" id="priceClearBtn">Clear</button><button class="btn btn-dark" type="button" id="priceApplyBtn">Apply</button></div>
                </div>
            </div>

            <!-- Size -->
            <div class="dropdown filter-pill">
                <button class="btn <?= activeBadge(!empty($sizeRanges)) ?>" type="button" data-bs-toggle="dropdown">Size</button>
                <div class="dropdown-menu">
                    <div class="filter-section-title">Case size</div>
                    <?php
                    $sizeOptions = [
                        ['lt29', '< 29mm', $sz['lt29'] ?? 0],
                        ['30_34', '30–34mm', $sz['r30_34'] ?? 0],
                        ['35_37', '35–37mm', $sz['r35_37'] ?? 0],
                        ['38_40', '38–40mm', $sz['r38_40'] ?? 0],
                        ['41_43', '41–43mm', $sz['r41_43'] ?? 0],
                        ['gte44', '≥ 44mm', $sz['gte44'] ?? 0],
                    ];
                    foreach ($sizeOptions as $op):
                    ?>
                        <div class="option-row">
                            <div><input class="form-check-input me-2" type="checkbox" name="size[]" id="size_<?= h($op[0]) ?>" value="<?= h($op[0]) ?>" <?= checked($sizeRanges, $op[0]) ?>>
                                <label class="form-check-label" for="size_<?= h($op[0]) ?>"><?= h($op[1]) ?></label>
                            </div>
                            <span class="count-badge"><?= (int)$op[2] ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="apply-row"><a class="btn btn-outline-secondary" href="?<?= h(qs_without('size')) ?>">Clear</a><button class="btn btn-dark" type="submit">Apply</button></div>
                </div>
            </div>

            <!-- Case Material -->
            <div class="dropdown filter-pill">
                <button class="btn <?= activeBadge(!empty($caseMatIds)) ?>" type="button" data-bs-toggle="dropdown">Case Material</button>
                <div class="dropdown-menu">
                    <div class="filter-section-title">Case Material</div>
                    <?php foreach ($materials as $m): $id = (int)$m['case_material_id'];
                        $cnt = $matCounts[$id] ?? 0; ?>
                        <div class="option-row">
                            <div><input class="form-check-input me-2" type="checkbox" name="case_material[]" id="mat_<?= $id ?>" value="<?= $id ?>" <?= checked($caseMatIds, $id) ?>>
                                <label class="form-check-label" for="mat_<?= $id ?>"><?= h($m['material']) ?></label>
                            </div>
                            <span class="count-badge"><?= $cnt ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="apply-row"><a class="btn btn-outline-secondary" href="?<?= h(qs_without('case_material')) ?>">Clear</a><button class="btn btn-dark" type="submit">Apply</button></div>
                </div>
            </div>

            <!-- Dial Color -->
            <div class="dropdown filter-pill">
                <button class="btn <?= activeBadge(!empty($dialColorIds)) ?>" type="button" data-bs-toggle="dropdown">Dial Color</button>
                <div class="dropdown-menu">
                    <div class="filter-section-title">Dial Color</div>
                    <?php foreach ($dialColors as $dc): $id = (int)$dc['dial_color_id'];
                        $cnt = $colorCounts[$id] ?? 0; ?>
                        <div class="option-row">
                            <div><input class="form-check-input me-2" type="checkbox" name="dial_color[]" id="dc_<?= $id ?>" value="<?= $id ?>" <?= checked($dialColorIds, $id) ?>>
                                <label class="form-check-label" for="dc_<?= $id ?>"><?= h($dc['dial_color']) ?></label>
                            </div>
                            <span class="count-badge"><?= $cnt ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="apply-row"><a class="btn btn-outline-secondary" href="?<?= h(qs_without('dial_color')) ?>">Clear</a><button class="btn btn-dark" type="submit">Apply</button></div>
                </div>
            </div>

            <!-- Gender -->
            <div class="dropdown filter-pill">
                <button class="btn <?= activeBadge(!empty($genderIds)) ?>" type="button" data-bs-toggle="dropdown">Gender</button>
                <div class="dropdown-menu">
                    <div class="filter-section-title">Gender</div>
                    <?php foreach ($genders as $g): $id = (int)$g['gender_id'];
                        $cnt = $genderCounts[$id] ?? 0; ?>
                        <div class="option-row">
                            <div><input class="form-check-input me-2" type="checkbox" name="gender[]" id="gen_<?= $id ?>" value="<?= $id ?>" <?= checked($genderIds, $id) ?>>
                                <label class="form-check-label" for="gen_<?= $id ?>"><?= h($g['gender']) ?></label>
                            </div>
                            <span class="count-badge"><?= $cnt ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="apply-row"><a class="btn btn-outline-secondary" href="?<?= h(qs_without('gender')) ?>">Clear</a><button class="btn btn-dark" type="submit">Apply</button></div>
                </div>
            </div>

            <!-- Category -->
            <div class="dropdown filter-pill">
                <button class="btn <?= activeBadge(!empty($categoryIds)) ?>" type="button" data-bs-toggle="dropdown">Category</button>
                <div class="dropdown-menu">
                    <div class="filter-section-title">Category</div>
                    <?php foreach ($categories as $c): $id = (int)$c['category_id'];
                        $cnt = $catCounts[$id] ?? 0; ?>
                        <div class="option-row">
                            <div><input class="form-check-input me-2" type="checkbox" name="category[]" id="cat_<?= $id ?>" value="<?= $id ?>" <?= checked($categoryIds, $id) ?>>
                                <label class="form-check-label" for="cat_<?= $id ?>"><?= h($c['cat_name']) ?></label>
                            </div>
                            <span class="count-badge"><?= $cnt ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="apply-row"><a class="btn btn-outline-secondary" href="?<?= h(qs_without('category')) ?>">Clear</a><button class="btn btn-dark" type="submit">Apply</button></div>
                </div>
            </div>

            <div class="divider-v"></div>
            <a class="btn btn-outline-danger btn-reset" href="view_products.php">Reset</a>
            <div class="results-count ms-auto"><?= count($products) ?> watches found</div>
        </div>
    </form>

    <div class="grid container-fluid">
        <?php if (empty($products)): ?>
            <div class="text-center text-muted py-5">No products match these filters.</div>
        <?php else: ?>
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4">
                <?php foreach ($products as $p): $pid = (int)$p['product_id'];
                    $fav = in_array($pid, $favSet, true); ?>
                    <div class="col">
                        <div class="cardwrap">
                            <button class="fav-pin js-fav <?= $fav ? 'active' : '' ?>" data-id="<?= $pid ?>" aria-label="Toggle favorite"><i class="fa-regular fa-heart"></i></button>
                            <a class="card-link" href="product_details.php?id=<?= $pid ?>" style="text-decoration:none; color:inherit; display:block;">
                                <div class="product-card">
                                    <div class="img-box">
                                        <?php if (!empty($p['image_url'])): ?>
                                            <img src="<?= h($p['image_url']) ?>" alt="<?= h($p['product_name']) ?>">
                                        <?php else: ?>
                                            <img src="../images/placeholder_watch.png" alt="No image">
                                        <?php endif; ?>
                                    </div>
                                    <div class="brand"><?= h(strtoupper($p['brand_name'])) ?></div>
                                    <div class="model"><?= h($p['product_name']) ?></div>
                                    <div class="price"><?= format_money($p['price']) ?></div>
                                </div>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /* price slider */
        (function() {
            const min = <?= json_encode((int)$globalMin) ?>,
                max = <?= json_encode((int)$globalMax) ?>;
            const minBox = document.getElementById('minPrice'),
                maxBox = document.getElementById('maxPrice');
            const r1 = document.getElementById('minRange'),
                r2 = document.getElementById('maxRange'),
                fill = document.getElementById('fill');
            const clamp = (v, lo, hi) => Math.min(Math.max(v, lo), hi);
            r1.value = clamp(Number(minBox.value || min), min, max);
            r2.value = clamp(Number(maxBox.value || max), min, max);

            function syncBoxes() {
                let a = +r1.value,
                    b = +r2.value;
                if (a > b) {
                    [a, b] = [b, a];
                }
                minBox.value = a;
                maxBox.value = b;
                update();
            }

            function syncRanges() {
                let a = clamp(+minBox.value || min, min, max),
                    b = clamp(+maxBox.value || max, min, max);
                if (a > b) {
                    [a, b] = [b, a];
                }
                r1.value = a;
                r2.value = b;
                update();
            }

            function update() {
                const a = (+r1.value - min) / (max - min) * 100,
                    b = (+r2.value - min) / (max - min) * 100;
                fill.style.left = Math.min(a, b) + '%';
                fill.style.right = (100 - Math.max(a, b)) + '%';
            }
            r1.addEventListener('input', syncBoxes);
            r2.addEventListener('input', syncBoxes);
            minBox.addEventListener('change', syncRanges);
            maxBox.addEventListener('change', syncRanges);
            update();
        })();

        /* Price Apply/Clear -> only submit if clicked */
        (function() {
            const form = document.getElementById('filtersForm');
            const minBox = document.getElementById('minPrice'),
                maxBox = document.getElementById('maxPrice');
            const minHidden = document.getElementById('min_price_field'),
                maxHidden = document.getElementById('max_price_field');
            document.getElementById('priceApplyBtn').addEventListener('click', () => {
                minHidden.disabled = false;
                maxHidden.disabled = false;
                minHidden.value = minBox.value;
                maxHidden.value = maxBox.value;
                form.submit();
            });
            document.getElementById('priceClearBtn').addEventListener('click', () => {
                minHidden.disabled = true;
                maxHidden.disabled = true;
                minHidden.value = '';
                maxHidden.value = '';
                form.submit();
            });
        })();

        /* favorites toggle (updates navbar badge if present) */
        function updateFavBadge(n) {
            const b = document.getElementById('favCountBadge');
            if (!b) return;
            if (n > 0) {
                b.textContent = n;
                b.classList.remove('d-none');
            } else {
                b.textContent = '';
                b.classList.add('d-none');
            }
        }
        document.querySelectorAll('.js-fav').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                e.stopPropagation();
                const id = btn.dataset.id;
                const fd = new FormData();
                fd.append('product_id', id);
                const r = await fetch('toggle_favorite.php', {
                    method: 'POST',
                    body: fd
                });
                const j = await r.json();
                if (j.ok) {
                    btn.classList.toggle('active', j.status === 'added');
                    updateFavBadge(j.count);
                }
            });
        });
    </script>
</body>

</html>