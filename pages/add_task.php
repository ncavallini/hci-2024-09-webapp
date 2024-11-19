<h1 class="text-center">Add Task</h1>
<form action="actions/tasks/add" method="POST">
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
    <label for="estimated_load">Estimated load</label>
    <input type="range" name="estimated_load" min="0" max="10" step="1" class="form-range">
    <br>
    <button type="submit" class="btn btn-primary">Add</button>
</form>