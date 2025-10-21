<?php
// Database connection
$conn = new mysqli("localhost", "ueyhm8rqreljw", "gutn2hie5vxa", "dbjntzvawbigoa");

// Article fetch
$id = $_GET['id'];
$article = $conn->query("SELECT * FROM articles WHERE id=$id")->fetch_assoc();

// Comments fetch
$comments = $conn->query("SELECT * FROM comments WHERE article_id=$id ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo $article['title']; ?></title>
  <style>
    body {font-family: Arial; margin:0; padding:0; background:#f4f4f4;}
    header {background:#c00; color:#fff; padding:15px; text-align:center;}
    .container {width:90%; margin:auto; padding:20px;}
    .jump {background:#c00; color:#fff; padding:10px; border:none; cursor:pointer; margin:10px 0;}
    .comments {margin-top:40px; background:#fff; padding:20px; border-radius:8px;}
    .comment-box {margin-bottom:15px; border-bottom:1px solid #ddd; padding-bottom:10px;}
    form textarea {width:100%; height:80px; padding:10px;}
    form input, form button {padding:10px; margin-top:10px;}
    form button {background:#c00; color:#fff; border:none; cursor:pointer;}
  </style>
</head>
<body>
<header>
  <h1><?php echo $article['title']; ?></h1>
</header>
<div class="container">
  <p><?php echo $article['content']; ?></p>
  
  <!-- Jump button -->
  <button class="jump" onclick="jumpToComments()">💬 Jump to Comments</button>
  
  <!-- Comments Section -->
  <div class="comments" id="commentSection">
    <h2>Comments</h2>
    
    <form id="commentForm">
      <input type="hidden" name="article_id" value="<?php echo $id; ?>">
      <input type="text" name="author" placeholder="Your name" required><br>
      <textarea name="comment" placeholder="Write a comment..." required></textarea><br>
      <button type="submit">Post Comment</button>
    </form>
    
    <div id="commentList">
      <?php while($c = $comments->fetch_assoc()): ?>
        <div class="comment-box">
          <strong><?php echo $c['author']; ?></strong>
          <p><?php echo $c['comment']; ?></p>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
</div>

<script>
// Scroll to comment section
function jumpToComments(){
  document.getElementById("commentSection").scrollIntoView({behavior:"smooth"});
}

// Submit comment via AJAX
document.getElementById("commentForm").addEventListener("submit", function(e){
  e.preventDefault();
  let formData = new FormData(this);
  
  fetch("save_comment.php", {
    method:"POST",
    body: formData
  })
  .then(res => res.text())
  .then(data => {
    document.getElementById("commentList").insertAdjacentHTML("afterbegin", data);
    this.reset();
    jumpToComments();
  });
});
</script>
</body>
</html>
