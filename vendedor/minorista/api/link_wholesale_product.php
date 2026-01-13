<?php
require __DIR__.'/../../../config.php';
require __DIR__.'/../../../_inc/pricing.php';
csrf_check();

$seller = require_seller_kind($pdo, 'minorista', '/vendedor/login.php');

header('Content-Type: application/json; charset=utf-8');

$productId = (int)($_POST['product_id'] ?? 0);
$linkedId = (int)($_POST['linked_product_id'] ?? 0);

if (!$productId || !$linkedId) {
  http_response_code(400);
  echo json_encode(['error' => 'Datos incompletos.']);
  exit;
}

$st = $pdo->prepare("SELECT sp.id FROM store_products sp JOIN stores s ON s.id=sp.store_id WHERE sp.id=? AND s.seller_id=? LIMIT 1");
$st->execute([$productId, (int)$seller['id']]);
if (!$st->fetch()) {
  http_response_code(403);
  echo json_encode(['error' => 'Acceso denegado.']);
  exit;
}

$existsSt = $pdo->prepare("
  SELECT sps.id
  FROM store_product_wholesale_sources sps
  WHERE sps.store_product_id = ? AND sps.wholesale_store_product_id = ? AND sps.enabled = 1
  LIMIT 1
");
$existsSt->execute([$productId, $linkedId]);
if ($existsSt->fetch()) {
  http_response_code(409);
  echo json_encode(['error' => 'Ya está vinculado.']);
  exit;
}

$ppSt = $pdo->prepare("
  SELECT sp.id, sp.title, sp.sku, sp.universal_code, sp.own_stock_qty,
         s.id AS store_id, s.name AS store_name, s.markup_percent,
         sel.display_name AS seller_name,
         COALESCE(SUM(GREATEST(ws.qty_available - ws.qty_reserved,0)),0) AS provider_stock
  FROM store_products sp
  JOIN stores s ON s.id = sp.store_id
  JOIN sellers sel ON sel.id = s.seller_id
  LEFT JOIN store_product_sources sps ON sps.store_product_id = sp.id AND sps.enabled=1
  LEFT JOIN warehouse_stock ws ON ws.provider_product_id = sps.provider_product_id
  WHERE sp.id=? AND sp.status='active'
    AND s.store_type='wholesale' AND s.status='active'
    AND sel.wholesale_status='approved'
  GROUP BY sp.id, sp.title, sp.sku, sp.universal_code, sp.own_stock_qty,
           s.id, s.name, s.markup_percent, sel.display_name
  HAVING (provider_stock + sp.own_stock_qty) > 0
  LIMIT 1
");
$ppSt->execute([$linkedId]);
$pp = $ppSt->fetch();
if (!$pp) {
  http_response_code(400);
  echo json_encode(['error' => 'Producto mayorista inválido o sin stock.']);
  exit;
}

try {
  $pdo->prepare("INSERT INTO store_product_wholesale_sources(store_product_id,wholesale_store_product_id,enabled) VALUES(?,?,1)")
      ->execute([$productId, $linkedId]);
} catch (Throwable $e) {
  $existsSt->execute([$productId, $linkedId]);
  if ($existsSt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'Ya está vinculado.']);
    exit;
  }
  http_response_code(500);
  echo json_encode(['error' => 'No se pudo vincular.']);
  exit;
}

$stock = (int)$pp['own_stock_qty'] + (int)$pp['provider_stock'];
$store = [
  'markup_percent' => $pp['markup_percent'],
  'store_type' => 'wholesale',
];
$price = current_sell_price($pdo, $store, $pp);
$name = $pp['seller_name'] ?: $pp['store_name'];

$response = [
  'ok' => true,
  'item' => [
    'id' => (int)$pp['id'],
    'title' => (string)$pp['title'],
    'sku' => (string)($pp['sku'] ?? ''),
    'universal_code' => (string)($pp['universal_code'] ?? ''),
    'price' => $price > 0 ? (float)$price : null,
    'wholesale_name' => (string)($name ?? ''),
    'stock' => $stock,
  ],
];

echo json_encode($response);
