<?php
session_start();

// Checks if the user is logged in 
if (!isset($_SESSION['user_id'])) {
    die("No user session found. Please login.");
}

$user_id = $_SESSION['user_id'];
error_log("Current session user_id: " . $user_id);

date_default_timezone_set("Asia/Kolkata");
$day = date("l");      
$date = date("d");     
$month = date("F");    
$year = date("Y");     
$time = date("h:i A");

$conn = new mysqli("localhost", "root", "", "todolist");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Debug log before queries
error_log("Fetching tasks for user_id: " . $user_id);

// Clear all tasks for this user (Finish Day)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_all_tasks'])) {
    error_log("Clearing all tasks for user_id: " . $user_id);
    $stmt = $conn->prepare("DELETE FROM tasks WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_task'])) {
        $task = trim($_POST['task']);
        if (!empty($task)) {
            error_log("Adding task for user_id: $user_id Task: $task");
            $stmt = $conn->prepare("INSERT INTO tasks (task, user_id) VALUES (?, ?)");
            $stmt->bind_param("si", $task, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    if (isset($_POST['done_task'])) {
        $id = intval($_POST['task_id']);
        error_log("Marking task done for user_id: $user_id Task ID: $id");
        $stmt = $conn->prepare("UPDATE tasks SET completed = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    if (isset($_POST['delete_task'])) {
        $id = intval($_POST['task_id']);
        error_log("Deleting task for user_id: $user_id Task ID: $id");
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch tasks belonging only to this user
$stmt = $conn->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Count total and completed tasks for this user
$totalTasksResult = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE user_id = ?");
$totalTasksResult->bind_param("i", $user_id);
$totalTasksResult->execute();
$totalTasksRes = $totalTasksResult->get_result();
$totalTasksRow = $totalTasksRes->fetch_assoc();
$totalTasks = (int)$totalTasksRow['total'];
$totalTasksResult->close();

$completedTasksResult = $conn->prepare("SELECT COUNT(*) as completed FROM tasks WHERE user_id = ? AND completed = 1");
$completedTasksResult->bind_param("i", $user_id);
$completedTasksResult->execute();
$completedTasksRes = $completedTasksResult->get_result();
$completedTasksRow = $completedTasksRes->fetch_assoc();
$completedTasks = (int)$completedTasksRow['completed'];
$completedTasksResult->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>To Do List</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>

  <h1> 🫧 Welcome, What's On Your Mind Today?</h1><br><br>

  <div class="parent">
    <div id="time">
      <h2><?= htmlspecialchars($day) ?></h2> 
      <span class="circle-number"><?= htmlspecialchars($date) ?></span>
      <h3><?= htmlspecialchars($month) ?></h3>
    </div>

    <div id="progress">
      <div id="bar">
        <div id="progress-text"></div>
        <div id="progress-bar-container">
          <div id="progress-bar"></div>
        </div>
      </div>

      <div id="entry">
        <form action="" method="POST">
          <label for="task">🫧 Enter Your Task: </label>
          <input type="text" name="task" id="task" required />
          <button type="submit" name="add_task" class="btn">+</button>
        </form>
      </div>
    </div>
  </div>

  <br /><br />

  <div class="task">
    <?php if ($result->num_rows > 0): ?>
      <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <thead>
          <tr>
            <th>No.</th>
            <th>Task</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $count = 1;
          while ($row = $result->fetch_assoc()): 
            $completedClass = $row['completed'] ? 'completed' : '';
          ?>
          <tr>
            <td><?= $count++ ?></td>
            <td class="<?= $completedClass ?>"><?= htmlspecialchars($row['task']) ?></td>
            <td>
              <?php if (!$row['completed']): ?>
                <form method="POST" action="" onsubmit="return confirm('Mark this task as done?');" style="display:inline;">
                  <input type="hidden" name="task_id" value="<?= $row['id'] ?>">
                  <button type="submit" name="done_task" class="done-btn">Done</button>
                </form>
              <?php else: ?>
                <span>✓ Done</span>
              <?php endif; ?>

              <form method="POST" action="" onsubmit="return confirm('Delete this task?');" style="display:inline;">
                <input type="hidden" name="task_id" value="<?= $row['id'] ?>">
                <button type="submit" name="delete_task" class="delete-btn">Delete</button>
              </form>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p style="text-align:center; color:#6e753f;">No tasks yet. Add one above!</p>
    <?php endif; ?>
  </div>

  <div class="buttonclass">
    <button onclick="finishDay()">Finish Day</button>
  </div>

  <div class="buttonclass">
    <button onclick="logout()">Logout</button>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const totalTasks = <?= (int)$totalTasks ?>;
      const completedTasks = <?= (int)$completedTasks ?>;

      function getProgressPercent(total, completed) {
        if (total === 0) return 0;
        return Math.round((completed / total) * 100);
      }

      function updateProgressBar(total, completed) {
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        const percent = getProgressPercent(total, completed);
        progressBar.style.width = percent + '%';
        progressText.textContent = `${percent}% Completed (${completed} of ${total} tasks)`;
      }

      updateProgressBar(totalTasks, completedTasks);
    });

    function finishDay() {
      if(confirm('Are you sure you want to clear all tasks for today?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'clear_all_tasks';
        input.value = '1';

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
      }
    }

    function logout() {
      window.location.href = 'sessionout.php';
    }
  </script>

</body>
</html>

<?php 
$conn->close(); 
?>
