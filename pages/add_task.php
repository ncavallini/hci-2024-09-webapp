<h1 class="text-center">Add Task</h1>
<form action="actions/tasks/add.php" method="POST">
    <label for="title">Title *</label>
    <input type="text" name="title" class="form-control" required>
    <br> 
    <label for="due_date">Date & Time *</label>
    <input type="datetime-local" name="due_date" class="form-control" required>
    <br>
    <label for="location">Location</label>
    <input type="text" name="location" class="form-control">
    <br>
    <label for="group">Group</label>
    <select name="group" id="group_select" class="form-select">
        <option value="0">Personal</option>
        <?php
            # FETCH GROUPS HERE
        ?>
    </select>
    <br>
    <label for="description">Description</label>
    <textarea name="description" class="form-control" rows="5"></textarea>
    <br>
    <label for="estimated_load">Estimated load <span id="estimated_load_span">(5/10)</span></label>
    <input type="range" name="estimated_load" id="estimated_load" min="0" max="10" step="1" class="form-range">
    <br>
    <br>
    <button type="submit" class="btn btn-primary">Add</button>
</form>

<script>
    const range = document.getElementById("estimated_load");
    const span = document.getElementById("estimated_load_span");
    range.addEventListener("input", () => {
        span.innerHTML = "(" + range.value + "/10" + ")";
    });
</script>