<?php
require __DIR__.'/../../../config.php';
require __DIR__.'/../../../_inc/pricing.php';

$seller = require_seller_kind($pdo, 'minorista', '/vendedor/login.php');

header('Content-Type: application/json; charset=utf-8');

$productId = (int)($_GET['product_id'] ?? 0);
$q = trim((string)($_GET['q'] ?? ''));

if (!$productId) {
  http_response_code(400);
  echo json_encode(['error' => 'Producto inválido.']);
  exit;
}

$st = $pdo->prepare("SELECT sp.id FROM store_products sp JOIN stores s ON s.id=sp.store_id WHERE sp.id=? AND s.seller_id=? LIMIT 1");
$st->execute([$productId, (int)$seller['id']]);
if (!$st->fetch()) {
  http_response_code(403);
  echo json_encode(['error' => 'Acceso denegado.']);
  exit;
}

if ($q === '' || mb_strlen($q) < 2) {
  echo json_encode([
    'items' => [],
    'empty_reason' => 'no_results',
  ]);
  exit;
}

$like = "%{$q}%";
$prefix = "{$q}%";
$params = [$productId, $like, $like, $like];
$conditions = [
  'sp.title LIKE ?',
  'sp.sku LIKE ?',
  'sp.universal_code LIKE ?',
];
$orderParts = [];
$orderParams = [];
$isUniversalCode = preg_match('/^\d{8,14}$/', $q) === 1;
if ($isUniversalCode) {
  $conditions[] = 'sp.universal_code = ?';
  $params[] = $q;
  $orderParts[] = '(sp.universal_code = ?) DESC';
  $orderParams[] = $q;
}
$orderParts[] = '(sp.title = ?) DESC';
$orderParts[] = '(sp.title LIKE ?) DESC';
$orderParts[] = 'sp.title ASC';
$orderParams[] = $q;
$orderParams[] = $prefix;

$sql = "
  SELECT sp.id, sp.title, sp.sku, sp.universal_code, sp.own_stock_qty,
         s.id AS store_id, s.name AS store_name, s.markup_percent,
         sel.display_name AS seller_name,
         COALESCE(SUM(GREATEST(ws.qty_available - ws.qty_reserved,0)),0) AS provider_stock
  FROM store_products sp
  JOIN stores s ON s.id = sp.store_id
  JOIN sellers sel ON sel.id = s.seller_id
  LEFT JOIN store_product_sources sps ON sps.store_product_id = sp.id AND sps.enabled=1
  LEFT JOIN warehouse_stock ws ON ws.provider_product_id = sps.provider_product_id
  LEFT JOIN store_product_wholesale_sources sws
    ON sws.wholesale_store_product_id = sp.id AND sws.store_product_id = ? AND sws.enabled=1
  WHERE s.store_type='wholesale' AND s.status='active'
    AND sp.status='active'
    AND sel.wholesale_status='approved'
    AND sws.id IS NULL
    AND (".implode(' OR ', $conditions).")
  GROUP BY sp.id, sp.title, sp.sku, sp.universal_code, sp.own_stock_qty,
           s.id, s.name, s.markup_percent, sel.display_name
  HAVING (provider_stock + sp.own_stock_qty) > 0
  ORDER BY ".implode(', ', $orderParts)."
  LIMIT 20
";

$searchSt = $pdo->prepare($sql);
$searchSt->execute(array_merge($params, $orderParams));
$items = $searchSt->fetchAll();

$out = [];
foreach ($items as $item) {
  $stock = (int)$item['own_stock_qty'] + (int)$item['provider_stock'];
  $store = [
    'markup_percent' => $item['markup_percent'],
    'store_type' => 'wholesale',
  ];
  $price = current_sell_price($pdo, $store, $item);
  $name = $item['seller_name'] ?: $item['store_name'];
  $out[] = [
    'id' => (int)$item['id'],
    'title' => (string)$item['title'],
    'sku' => (string)($item['sku'] ?? ''),
    'universal_code' => (string)($item['universal_code'] ?? ''),
    'price' => $price > 0 ? (float)$price : null,
    'wholesale_name' => (string)($name ?? ''),
    'stock' => $stock,
  ];
}

$emptyReason = '';
if (!$out) {
  $checkSql = "
    SELECT sp.id
    FROM store_products sp
    JOIN stores s ON s.id = sp.store_id
    JOIN sellers sel ON sel.id = s.seller_id
    LEFT JOIN store_product_sources sps ON sps.store_product_id = sp.id AND sps.enabled=1
    LEFT JOIN warehouse_stock ws ON ws.provider_product_id = sps.provider_product_id
    LEFT JOIN store_product_wholesale_sources sws
      ON sws.wholesale_store_product_id = sp.id AND sws.store_product_id = ? AND sws.enabled=1
    WHERE s.store_type='wholesale' AND s.status='active'
      AND sp.status='active'
      AND sel.wholesale_status='approved'
      AND sws.id IS NULL
    GROUP BY sp.id, sp.own_stock_qty
    HAVING (COALESCE(SUM(GREATEST(ws.qty_available - ws.qty_reserved,0)),0) + sp.own_stock_qty) > 0
    LIMIT 1
  ";
  $checkSt = $pdo->prepare($checkSql);
  $checkSt->execute([$productId]);
  $emptyReason = $checkSt->fetch() ? 'no_results' : 'all_linked';
}

echo json_encode([
  'items' => $out,
  'empty_reason' => $emptyReason,
]);
