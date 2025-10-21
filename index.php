<?php
// index.php
// Homepage: featured, breaking, trending, search, thumbnails.
// DB credentials (edit $dbHost if your host is not localhost)
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

// Create tables if not exists
$pdo->exec("
CREATE TABLE IF NOT EXISTS articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content TEXT NOT NULL,
    category VARCHAR(100) NOT NULL,
    author VARCHAR(100) DEFAULT 'Staff',
    image_url VARCHAR(500) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    views INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Seed sample articles if empty (only on empty table)
$count = $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
if ($count == 0) {
    $sample = [
        [
            'title' => 'Global Markets Rally After Tech Gains',
            'slug' => 'global-markets-rally-after-tech-gains',
            'content' => 'Markets around the world showed strong momentum as major tech firms reported surprising earnings...',
            'category' => 'Business',
            'author' => 'A. Reporter',
            'image_url' => 'https://via.placeholder.com/900x500.png?text=Markets'
        ],
        [
            'title' => 'Breakthrough Battery Tech Promises Longer Range',
            'slug' => 'breakthrough-battery-tech-promises-longer-range',
            'content' => 'A new battery chemistry may allow electric cars to travel much longer distances on a single charge...',
            'category' => 'Technology',
            'author' => 'Tech Desk',
            'image_url' => 'https://via.placeholder.com/900x500.png?text=Battery+Tech'
        ],
        [
            'title' => 'Local Team Wins Championship in Thrilling Finale',
            'slug' => 'local-team-wins-championship-in-thrilling-finale',
            'content' => 'In front of a packed stadium, the underdogs took home the trophy after a dramatic overtime.',
            'category' => 'Sports',
            'author' => 'Sports Desk',
            'image_url' => 'https://via.placeholder.com/900x500.png?text=Championship'
        ],
        [
            'title' => 'New Art Festival Brings Color to the City',
            'slug' => 'new-art-festival-brings-color-to-the-city',
            'content' => 'Street artists and craft makers turned the downtown into an open-air gallery this weekend...',
            'category' => 'Entertainment',
            'author' => 'Culture Beat',
            'image_url' => 'https://via.placeholder.com/900x500.png?text=Art+Festival'
        ],
    ];

    $stmt = $pdo->prepare("INSERT INTO articles (title, slug, content, category, author, image_url) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($sample as $a) {
        $stmt->execute([$a['title'], $a['slug'], $a['content'], $a['category'], $a['author'], $a['image_url']]);
    }
}

// Handle search
$searchQuery = '';
$articles = [];
if (isset($_GET['q']) && trim($_GET['q']) !== '') {
    $searchQuery = trim($_GET['q']);
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE title LIKE :q OR content LIKE :q ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([':q' => "%$searchQuery%"]);
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Featured: latest 1-2 articles
    $featured = $pdo->query("SELECT * FROM articles ORDER BY created_at DESC LIMIT 1")->fetchAll(PDO::FETCH_ASSOC);
    // Latest list
    $articles = $pdo->query("SELECT * FROM articles ORDER BY created_at DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
    // Trending (most views)
    $trending = $pdo->query("SELECT * FROM articles ORDER BY views DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
}

// Load categories (distinct)
$categories = $pdo->query("SELECT DISTINCT category FROM articles")->fetchAll(PDO::FETCH_COLUMN);

function esc($s) { return htmlspecialchars($s); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Pulse News — Home</title>
<style>
/* Internal CSS — polished, modern responsive layout */
:root{
  --accent:#c91f37;
  --muted:#666;
  --bg:#f5f6f8;
  --card:#ffffff;
  --maxw:1100px;
  font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:#111;line-height:1.45}
.container{max-width:var(--maxw);margin:28px auto;padding:0 18px}
.header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px}
.brand{display:flex;align-items:center;gap:12px}
.logo{background:var(--accent);color:#fff;padding:10px 14px;border-radius:6px;font-weight:700;letter-spacing:0.6px}
.searchbar{flex:1;display:flex;align-items:center;gap:8px;margin-left:8px}
.searchbar input{flex:1;padding:10px 12px;border-radius:8px;border:1px solid #e1e4e8;background:#fff}
.searchbtn{padding:10px 14px;border-radius:8px;border:none;background:var(--accent);color:#fff;cursor:pointer}
.nav{display:flex;gap:10px;align-items:center}
.nav button{background:none;border:none;padding:8px 10px;border-radius:6px;cursor:pointer}
.nav button:hover{background:#fff}
.hero{display:grid;grid-template-columns:1fr 360px;gap:18px;margin-bottom:18px}
.card{background:var(--card);border-radius:12px;overflow:hidden;box-shadow:0 6px 20px rgba(12,20,30,0.06)}
.hero .featured img{width:100%;height:320px;object-fit:cover;display:block}
.featured .meta{padding:16px}
.meta h2{margin:0 0 8px;font-size:20px}
.meta p{margin:0;color:var(--muted)}
.side-list{padding:14px}
.side-list h4{margin:0 0 8px}
.side-item{display:flex;gap:10px;padding:10px 0;border-bottom:1px dashed #eee}
.side-item img{width:72px;height:52px;object-fit:cover;border-radius:8px}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;margin-top:18px}
.thumb img{width:100%;height:140px;object-fit:cover;display:block}
.thumb .info{padding:10px}
.cat-bar{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
.cat-pill{padding:8px 12px;background:#fff;border-radius:999px;cursor:pointer;border:1px solid #f0f0f0}
.footer{margin:28px 0;text-align:center;color:var(--muted);font-size:14px;padding-bottom:40px}
@media (max-width:900px){
  .hero{grid-template-columns:1fr}
  .hero .featured img{height:220px}
}
</style>
</head>
<body>
<div class="container">
  <header class="header">
    <div class="brand">
      <div class="logo" onclick="goHome()" style="cursor:pointer">Pulse</div>
      <div style="font-weight:600">News</div>
      <div style="color:var(--muted);margin-left:6px">— Real stories, fast</div>
    </div>

    <div class="searchbar">
      <form id="searchForm" onsubmit="doSearch(event)">
        <input id="q" name="q" type="search" placeholder="Search news, e.g., battery, championship..." value="<?php echo esc($searchQuery); ?>">
        <button class="searchbtn" type="submit">Search</button>
      </form>
    </div>

    <nav class="nav" aria-label="top navigation">
      <button onclick="goCategory('World')">World</button>
      <button onclick="goCategory('Business')">Business</button>
      <button onclick="goCategory('Technology')">Technology</button>
      <button onclick="goCategory('Sports')">Sports</button>
      <button onclick="goCategory('Entertainment')">Entertainment</button>
    </nav>
  </header>

  <main>
    <section class="hero">
      <div class="featured card">
        <?php
          // show latest article as featured
          $feat = $pdo->query("SELECT * FROM articles ORDER BY created_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
          if ($feat):
        ?>
        <div class="featured-img">
          <img src="<?php echo esc($feat['image_url'] ?: 'https://via.placeholder.com/900x500.png?text=Featured'); ?>" alt="">
        </div>
        <div class="meta">
          <div style="color:var(--accent);font-weight:700"><?php echo esc($feat['category']); ?></div>
          <h2 id="featTitle"><?php echo esc($feat['title']); ?></h2>
          <p style="margin-top:8px;color:var(--muted)">By <?php echo esc($feat['author']); ?> · <?php echo date('M d, Y', strtotime($feat['created_at'])); ?></p>
          <p style="margin-top:10px"><?php echo esc(substr($feat['content'],0,200)); ?>...</p>
          <div style="margin-top:12px">
            <button class="searchbtn" onclick="goArticle(<?php echo (int)$feat['id']; ?>)">Read full</button>
          </div>
        </div>
        <?php else: ?>
          <div style="padding:18px">No featured article yet.</div>
        <?php endif; ?>
      </div>

      <aside>
        <div class="card side-list">
          <h4>Trending</h4>
          <?php
            $tr = $pdo->query("SELECT * FROM articles ORDER BY views DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($tr as $t):
          ?>
            <div class="side-item">
              <img src="<?php echo esc($t['image_url'] ?: 'https://via.placeholder.com/80'); ?>" alt="">
              <div style="flex:1">
                <div style="font-weight:700;cursor:pointer" onclick="goArticle(<?php echo (int)$t['id']; ?>)"><?php echo esc($t['title']); ?></div>
                <div style="color:var(--muted);font-size:13px"><?php echo esc($t['category']); ?> · <?php echo date('M d', strtotime($t['created_at'])); ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="height:12px"></div>

        <div class="card side-list">
          <h4>Categories</h4>
          <div style="margin-top:10px" class="cat-bar">
            <?php foreach ($categories as $c): ?>
              <div class="cat-pill" onclick="goCategory('<?php echo esc($c); ?>')"><?php echo esc($c); ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      </aside>
    </section>

    <section>
      <div style="display:flex;justify-content:space-between;align-items:center;margin:12px 0">
        <h3 style="margin:0">Latest</h3>
        <div style="color:var(--muted)"><?php echo $searchQuery ? "Search results for \"".esc($searchQuery)."\"" : "Top stories" ?></div>
      </div>

      <div class="grid">
        <?php foreach ($articles as $a): ?>
          <article class="thumb card" style="overflow:hidden">
            <img src="<?php echo esc($a['image_url'] ?: 'https://via.placeholder.com/400x240.png?text=News'); ?>" alt="">
            <div class="info">
              <div style="color:var(--accent);font-weight:700;font-size:13px"><?php echo esc($a['category']); ?></div>
              <h4 style="margin:6px 0;cursor:pointer" onclick="goArticle(<?php echo (int)$a['id']; ?>)"><?php echo esc($a['title']); ?></h4>
              <div style="color:var(--muted);font-size:13px">By <?php echo esc($a['author']); ?> · <?php echo date('M d', strtotime($a['created_at'])); ?></div>
              <p style="margin-top:8px;color:var(--muted)"><?php echo esc(substr($a['content'],0,120)); ?>...</p>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <footer class="footer">
    © <?php echo date('Y'); ?> Pulse News — Built with PHP • Responsive • JS navigation
  </footer>
</div>

<script>
/* Navigation JS (file redirection must use JS per requirements) */
function goArticle(id){
  // navigate to article.php?id=...
  window.location = 'article.php?id=' + encodeURIComponent(id);
}
function goCategory(cat){
  window.location = 'category.php?cat=' + encodeURIComponent(cat);
}
function goHome(){ window.location = 'index.php'; }
function doSearch(e){
  e.preventDefault();
  var q = document.getElementById('q').value.trim();
  window.location = 'index.php' + (q ? '?q=' + encodeURIComponent(q) : '');
}
</script>
</body>
</html>
