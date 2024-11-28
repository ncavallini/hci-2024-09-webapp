<h1 class="text-center">Add Group</h1>
<form action="actions/groups/add.php" method="POST" id="add_group" onsubmit="onFormSubmit(event)">
    <label for="name">Group Name *</label>
    <input type="text" class="form-control" name="name" id="name" required>
    <br>
    <h2>Members</h2>
    <br>
    <div class="table-responsive">
        <table class="table" id="members-table">
            <thead>
                <tr>
                    <th>Select?</th>
                    <th>Name</th>
                    <th>Username</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $currentUsername = Auth::user()['username'];
                $currentFullName = Auth::user()['first_name'] . " " . Auth::user()['last_name'];
                $users = $dbconnection->query("SELECT * FROM users");

                // Display the current user as a checked, disabled option
                echo "<tr>";
                echo "<td><input type='checkbox' checked disabled class='form-check-input' x-user='$currentUsername'></td>";
                echo "<td>" . htmlspecialchars($currentFullName) . "</td>";
                echo "<td>" . htmlspecialchars($currentUsername) . "</td>";
                echo "</tr>";

                // Display other users
                foreach ($users as $user) {
                    if ($user['username'] === $currentUsername) continue; // Skip current user (already displayed)
                    echo "<tr>";
                    echo "<td><input type='checkbox' class='form-check-input' x-user='" . htmlspecialchars($user['username']) . "'></td>";
                    echo "<td>" . htmlspecialchars($user['first_name'] . " " . $user['last_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <br>
    <button type="submit" class="btn btn-primary">Add</button>
</form>

<script>
    function onFormSubmit(event) {
        event.preventDefault(); // Prevent default form submission

        // Collect selected users
        let usersToAdd = [];
        const checkboxes = Array.from(document.querySelectorAll("[x-user]"));

        // Add selected users
        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                usersToAdd.push(checkbox.getAttribute("x-user"));
            }
        });

        // Ensure the current user is included
        const currentUser = "<?php echo $currentUsername; ?>";
        if (!usersToAdd.includes(currentUser)) {
            usersToAdd.push(currentUser);
        }

        // Add hidden input to include members in the submission
        const form = document.getElementById("add_group");
        const membersInput = document.createElement("input");
        membersInput.type = "hidden";
        membersInput.name = "members";
        membersInput.value = JSON.stringify(usersToAdd);
        form.appendChild(membersInput);

        // Submit the form
        form.submit();
    }

    $(document).ready(function () {
        // Initialize DataTable for the members table
        $('#members-table').DataTable();
    });
</script>
