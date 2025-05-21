<?php
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_task'])) {
        $task = trim($_POST['task']);
        if (!empty($task)) {
            $stmt = $conn->prepare("INSERT INTO tasks (task) VALUES (?)");
            $stmt->bind_param("s", $task);
            $stmt->execute();
            $stmt->close();
        }
    }
    if (isset($_POST['done_task'])) {
        $id = intval($_POST['task_id']);
        $stmt = $conn->prepare("UPDATE tasks SET completed = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    if (isset($_POST['delete_task'])) {
        $id = intval($_POST['task_id']);
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$result = $conn->query("SELECT * FROM tasks ORDER BY id DESC");

$totalTasksResult = $conn->query("SELECT COUNT(*) as total FROM tasks");
$totalTasksRow = $totalTasksResult->fetch_assoc();
$totalTasks = (int)$totalTasksRow['total'];

$completedTasksResult = $conn->query("SELECT COUNT(*) as completed FROM tasks WHERE completed = 1");
$completedTasksRow = $completedTasksResult->fetch_assoc();
$completedTasks = (int)$completedTasksRow['completed'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>To Do List</title>
  <link rel="stylesheet" href="style.css" />
  <style>

    #progress-bar-container {
      width: 100%;
      background-color: #c8c6b1; 
      border-radius: 10px;
      height: 25px;
      overflow: hidden;
      margin-bottom: 20px;
    }

    #progress-bar {
      height: 100%;
      background-color: #6e753f; 
      width: 0%;
      transition: width 0.4s ease;
    }
    
    #progress-text {
      text-align: center;
      font-weight: bold;
      color #6e753f;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>

  <h1> 🍵 Welcome User, What's On Your Mind Today?</h1>

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
          <label for="task">🍵 Enter Your Task: </label>
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
            <th>#</th>
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

  <script>

    const totalTasks = <?= $totalTasks ?>;
    const completedTasks = <?= $completedTasks ?>;

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
  </script>

</body>
</html>

<?php 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_all_tasks'])) {
    $conn->query("DELETE FROM tasks");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$conn->close(); 
?>
