<h1 class="text-center">Welcome, <?php echo $_SESSION['user']['first_name'] ?></h1>
<br>
<div class="list-group">
  <a href="index.php?page=visualize" class="text-center list-group-item list-group-item-action active"><i class="fa-solid fa-chart-pie"></i> Visualize</a>
</div>

<!-- <div style="width: 100%; height: 500px; background-color: lightgray;">Home Widgets</div> -->

<div class="row row-cols-1 row-cols-md-2 g-4">
  <?php
  $sql = "SELECT m.group_id, g.name FROM membership m JOIN groups g ON m.group_id = g.group_id WHERE m.username = ?";
  $stmt = $dbconnection->prepare($sql);
  $stmt->execute([Auth::user()['username']]);
  $groups = $stmt->fetchAll();
  foreach ($groups as $group) {
    echo "<div class='col'>";
    echo "<div class='card'>";
    echo "<div class='card-body'>";
    echo "<p class='h5'>{$group['name']}</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
  }

  ?>
</div>



<div class="list-group">
  <a href="index.php?page=manage" class="text-center list-group-item list-group-item-action active"><i class="fa-solid fa-gear"></i> Manage</a>
</div>