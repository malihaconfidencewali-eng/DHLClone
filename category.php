<?php
// category.php
// Lists articles filtered by category. Uses JS navigation to move between files.

$dbHost = 'localhost';
$dbName = 'dbjntzvawbigoa';
$dbUser = 'ueyhm8rqreljw';
$dbPass = 'gutn2hie5vxa';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    die("DB connection failed: " . htmlspecialchars($e->getMessage()));
}

$cat = isset($_GET['cat']) ? trim($_GET['cat']) : '';
if ($cat === '') {
    // If no category specified, redirect to home using JS — but server can't do JS; we'll show a message and a JS redirect
    $fallback = true;
} else {
    $fallback = false;
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE category = :cat ORDER BY created_at DESC");
    $stmt->execute([':cat' => $cat]);
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function esc($s) { return htmlspecialchars($s); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Pulse News — Category <?php echo esc($cat ?: ''); ?></title>
<style>
:root{--accent:#c91f37;--muted:#666;--bg:#fafbfc;--card:#fff;--maxw:1100px}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial;color:#111}
.container{max-width:var(--maxw);margin:24px auto;padding:0 16px}
.header{display:flex;align-items:center;justify-content:space-between}
.logo{background:var(--accent);color:#fff;padding:10px;border-radius:8px;font-weight:700}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;margin-top:18px}
.card{background:var(--card);border-radius:12px;overflow:hidden;box-shadow:0 8px 24px rgba(5,10,20,0.04)}
.card img{width:100%;height:150px;object-fit:cover;display:block}
.card .meta{padding:12px}
.btn{background:var(--accent);color:#fff;padding:8px 12px;border-radius:8px;border:none;cursor:pointer}
.topbar{display:flex;gap:12px;align-items:center;justify-content:space-between}
@media (max-width:700px){ .topbar{flex-direction:column;align-items:flex-start} }
</style>
</head>
<body>
<div class="container">
  <div class="topbar">
    <div style="display:flex;gap:12px;align-items:center">
      <div class="logo" onclick="goHome()" style="cursor:pointer">Pulse</div>
      <div>
        <h2 style="margin:0">Category: <?php echo esc($cat ?: 'All'); ?></h2>
        <div style="color:var(--muted)">Latest stories in <?php echo esc($cat ?: 'news'); ?></div>
      </div>
    </div>
    <div>
      <button class="btn" onclick="goHome()">Home</button>
    </div>
  </div>

  <?php if ($fallback): ?>
    <div style="margin-top:40px;background:#fff;padding:18px;border-radius:12px;box-shadow:0 8px 20px rgba(0,0,0,0.04)">
      <p style="margin:0">No category specified. Redirecting to homepage...</p>
    </div>
    <script>setTimeout(()=>{window.location='index.php'},800);</script>
  <?php else: ?>
    <?php if (empty($articles)): ?>
      <div style="margin-top:24px;padding:18px;background:#fff;border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,0.04)">
        No articles found in this category yet.
      </div>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($articles as $a): ?>
          <article class="card">
            <img src="<?php echo esc($a['image_url'] ?: 'https://via.placeholder.com/600x350.png?text=News'); ?>" alt="">
            <div class="meta">
              <div style="color:var(--accent);font-weight:700;font-size:13px"><?php echo esc($a['category']); ?></div>
              <h3 style="margin:6px 0;cursor:pointer" onclick="goArticle(<?php echo (int)$a['id']; ?>)"><?php echo esc($a['title']); ?></h3>
              <div style="color:var(--muted);font-size:13px">By <?php echo esc($a['author']); ?> · <?php echo date('M d, Y', strtotime($a['created_at'])); ?></div>
              <p style="margin-top:8px;color:var(--muted)"><?php echo esc(substr($a['content'], 0, 120)); ?>...</p>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <div style="height:36px"></div>
  <footer style="text-align:center;color:var(--muted)">© <?php echo date('Y'); ?> Pulse News — Category view</footer>
</div>

<script>
function goArticle(id){ window.location = 'article.php?id=' + encodeURIComponent(id); }
function goHome(){ window.location = 'index.php'; }
</script>
</body>
</html>
