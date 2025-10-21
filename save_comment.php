<?php
$conn = new mysqli("localhost", "ueyhm8rqreljw", "gutn2hie5vxa", "dbjntzvawbigoa");

$article_id = $_POST['article_id'];
$author = $_POST['author'];
$comment = $_POST['comment'];

$conn->query("INSERT INTO comments(article_id, author, comment) VALUES('$article_id','$author','$comment')");

// Return the new comment (HTML) to show instantly
echo "<div class='comment-box'>
        <strong>".htmlspecialchars($author)."</strong>
        <p>".htmlspecialchars($comment)."</p>
      </div>";
?>
