<?php
function ds_set() {
    global $data_file, $checklist;
    if (!$f = fopen($data_file, 'wb')) {
        return false;
    }
    if (!flock($f, LOCK_EX)) {
        return false;
    }
    fputs($f, serialize($checklist));
    flock($f, LOCK_UN);
    fclose($f);
    return true;
}
function ds_get() {
    global $data_file;
    if (!file_exists($data_file)) {
        ds_set(array());
        return false;
    }
    $data = unserialize(file_get_contents($data_file));
    return $data;
}
function format_timestamp($time) {
    $timeset = array(
        31536000 => 'year',
        2592000  => 'month',
        86400    => 'day',
        3600     => 'hour',
        60       => 'minute',
        1        => 'second');
    $time = time() - $time;
    if ($time < 2) {
        return 'just now';
    }
    foreach ($timeset as $secs => $unit) {
        $d = $time / $secs;
        if ($d >= 1) {
            $r = intval($d);
            return $r .' '. $unit .' ago';
        }
    }
}
function format_date($format, $timestamp = null) {
    global $timezone;
    if ($timestamp == null) {
        $timestamp = time();
    }
    $timestamp = $timestamp + timezone_offset_get(new DateTimeZone($timezone), new DateTime());
    return date($format, $timestamp);
}
function clean_str($text) {
    return stripslashes(htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8'));
}
function paginate($total_items, $current_page, $url, $per_page) {
    $mid_range = 5;
    $total_page = ceil($total_items / $per_page);
    if ($total_page == 0) {
        echo '';
        return;
    }
    $start_range = $current_page - floor($mid_range / 2);
    $end_range = $current_page + floor($mid_range / 2);
    if ($start_range <= 0) {
        $end_range += abs($start_range) + 1;
        $start_range = 1;
    }
    if ($end_range > $total_page) {
        $start_range -= $end_range - $total_page;
        $end_range = $total_page;
    }
    $range = range($start_range, $end_range);
    $start_item = (($current_page - 1) * $per_page) + 1;
    $end_item = $current_page * $per_page;
    if ($end_item > $total_items) {
        $end_item = $total_items;
    }
    $html = '<div  class="paginate"><span>Task '. $start_item .' - '. $end_item .' of '. $total_items .':</span> <ul>';
    if ($current_page == 1) {
        $html .= '<li><span>&laquo; Prev</span></li>';
    } else {
        $html .= '<li><a href="'. sprintf($url, $current_page - 1) .'">&laquo; Prev</a></li>';
    }
    $i = 1;
    while ($i <= $total_page) {
        if ($start_range > 2 && $i == $start_range) {
            $html .= '<li><span class="page_dot">...</span></li>';
        }
        if ($i == 1 || $i == $total_page || in_array($i, $range)) {
            if ($i == $current_page) {
                $html .= '<li><span class="page_current">'. $i .'</span></li>';
            } else {
                $html .= '<li><a href="'. sprintf($url, $i) .'">'. $i .'</a></li>';
            }
        }
        if ($range[$mid_range - 1] < $total_page && $i == $range[$mid_range - 1]) {
            $html .= '<li><span class="page_dot">...</span></li>';
        }
        $i++;
    }
    if ($current_page == $total_page) {
        $html .= '<li><span>Next &raquo;</span></li>';
    } else {
        $html .= '<li><a href="'. sprintf($url, $current_page + 1).'">Next &raquo;</a></li>';
    }
    $html .= '</ul></div>';
    echo $html;
}
date_default_timezone_set('GMT');
$data_file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'data.dat';
$timezone = 'Asia/Kuala_Lumpur';
$per_page = 25;
$sys_msg = '';
$status = array(0 => 'Not started', 1 => 'In progress', 2 => 'Done', 3 => 'Archived');
$priority = array(1 => 'Low', 2 => 'Normal', 3 => 'High');
$input = array_map('urldecode', $_GET) + $_POST;
$checklist = ds_get();
$input['do'] =@ clean_str($input['do']);
$input['task'] =@ clean_str($input['task']);
$input['remark'] =@ clean_str($input['remark']);
$input['status'] =@ (int) $input['status'];
$input['priority'] =@ (int) $input['priority'];
$input['id'] =@ (int) $input['id'];
$input['pg'] =@ (int) $input['pg'];
if ($input['pg'] == 0) {
    $input['pg'] = 1;
}
if (in_array($input['do'], array('save', 'update'))) {
    if (strlen($input['task']) < 5) {
        $sys_msg = 'Min. length for new task is 5 characters.';
    }
    if (strlen($input['task']) > 500) {
        $sys_msg = 'Max. length for new task is 500 characters.';
    }
}
if (empty($input['priority'])) {
    $input['priority'] = 2;
}
if (in_array($input['do'], array('edit', 'update', 'delete'))) {
    if ($input['id'] == 0) {
        $sys_msg = 'Task ID is required';
    }
}
if (!empty($sys_msg)) {
    if ($input['do'] == 'save') {
        $input['do'] = 'add';
    }
    if ($input['do'] == 'update') {
        $input['do'] = 'edit';
    }
} else {
    switch ($input['do']) {
        case 'save':
            if (!isset($checklist['counter']) || empty($checklist['counter'])) {
                $checklist['counter'] = 0;
            }
            $checklist['counter']++;
            $timestamp = time();
            $checklist['checklist'][$checklist['counter']] = array(
                'id' => $checklist['counter'],
                'task' => $input['task'],
                'remark' => $input['remark'],
                'timestamp' => $timestamp,
                'status' => $input['status'],
                'priority' => $input['priority'],
                'last_update' => $timestamp
            );
            break;
        case 'edit':
            if (isset($checklist['checklist'][$input['id']])) {
                $input['task'] = $checklist['checklist'][$input['id']]['task'];
                $input['status'] = $checklist['checklist'][$input['id']]['status'];
                $input['remark'] = $checklist['checklist'][$input['id']]['remark'];
                $input['priority'] = $checklist['checklist'][$input['id']]['priority'];
            } else {
                $sys_msg = 'Task not found';
            }
            break;
        case 'update':
            if (isset($checklist['checklist'][$input['id']])) {
                $checklist['checklist'][$input['id']]['task'] = $input['task'];
                $checklist['checklist'][$input['id']]['status'] = $input['status'];
                $checklist['checklist'][$input['id']]['remark'] = $input['remark'];
                $checklist['checklist'][$input['id']]['priority'] = $input['priority'];
                $checklist['checklist'][$input['id']]['last_update'] = time();
                $sys_msg = 'Task saved';
            } else {
                $sys_msg = 'Task not found';
            }
            break;
        case 'delete':
            if (isset($checklist['checklist'][$input['id']])) {
                unset($checklist['checklist'][$input['id']]);
                $sys_msg = 'Task #'. $input['id'] .' deleted';
            }
            break;
    }
}
ds_set();
$total_checklist = 0;
if (isset($checklist['checklist'])) {
    $total_checklist = count($checklist['checklist']);
    if ($total_checklist > 0) {
        krsort($checklist['checklist']);
        $sort = array();
        $task_id = array();
        foreach ($checklist['checklist'] as $id => $task) {
            $task_id[$id] = $task['id'];
            $sort[$id] = $task['priority'] - $task['status'];
            $checklist['checklist'][$id]['sort'] = $sort[$id];
        }
        array_multisort($sort, SORT_DESC, SORT_NUMERIC, $task_id, SORT_DESC, SORT_NUMERIC, $checklist['checklist']);
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
  <title>Checklist</title>
  <meta http-equiv="content-type" content="text/html;charset=utf-8" />
  <meta name="generator" content="Geany 0.19.1" />
  <link rel="shortcut icon" type="image/png" href="favicon.png" rel="icon" />
  <link rel="stylesheet" href="style.css" type="text/css" media="screen, projection" />
  <style type="text/css">
    .container { width: 760px; }
    .menu { margin: 5px 0; }
    .tasklist, .tasklist td, .tasklist th { border: 1px solid #ccc; border-collapse: collapse; }
    .tasklist td h5, .tasklist td p { margin: 0; }
    .tasklist td h5 { font-weight: normal; color: #222; }
    .tasklist td p { color: #888; }
    .tasklist tr:hover td { background: #f9f9fa; }
    .col, .col-opt, .col-ico { text-align: center; }
    .col { width: 100px; }
    .col-opt { width: 60px; }
    .col-ico { width: 16px; padding: 2px 5px; }
    .lbl { width: 50px; text-align: right; }
    .task_name { width: 600px; }
    .task_remark { height: 40px; }
    .del { color: #8a1f11; }
    .del:hover { color: #fbc2c4; }
    .paginate { float: left; margin: 0 0 15px; }
    .paginate ul, .paginate li { list-style: none; display: inline; margin: 0; padding: 0; }
    .paginate li a, .paginate li span { padding: 2px 6px; }
  </style>
  <script type="text/javascript">
    function delete_task(id) {
      if (confirm('Are you sure to delete this task?')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('delete_task').submit();
      }
      return false;
    }
  </script>
</head>

<body>
  <?php if (!empty($sys_msg) && ($input['do'] == 'save' || $input['do'] == 'update')): ?>
    <script type="text/javascript">
      (function() {
        setTimeout(function() {
          window.location.href = 'index.php';
        }, 2000);
      })();
    </script>
  <?php endif; ?>
  <div class="container">
    <p class="menu">
      <a href="index.php">Home</a> &middot;
      <a href="index.php?do=add">Add task</a>
    </p>
    <?php if (!empty($sys_msg)): ?>
      <p class="notice"><?php echo $sys_msg; ?></p>
    <?php endif; ?>
    <?php if ($input['do'] == 'add' || $input['do'] == 'edit'): ?>
      <form action="index.php" method="post">
        <?php if ($input['do'] == 'add'): ?>
          <input type="hidden" name="do" value="save" />
        <?php elseif ($input['do'] == 'edit'): ?>
          <input type="hidden" name="do" value="update" />
          <input type="hidden" name="id" value="<?php echo $input['id']; ?>" />
        <?php endif; ?>
        <table>
          <tr>
            <td class="lbl">Task:</td>
            <td><input type="text" class="task_name" name="task" value="<?php echo $input['task']; ?>" /></td>
          </tr>
          <tr>
            <td class="lbl">Remark:</td>
            <td><textarea name="remark" class="task_remark" cols="50" rows="5"><?php echo $input['remark']; ?></textarea></td>
          </tr>
          <tr>
            <td class="lbl">Status:</td>
            <td>
              <select name="status">
                <?php foreach ($status as $id => $stat): ?>
                  <option value="<?php echo $id; ?>" <?php if ($id == $input['status']): ?>selected="selected"<?php endif; ?>><?php echo $stat; ?></option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
          <tr>
            <td class="lbl">Priority:</td>
            <td>
              <select name="priority">
                <?php foreach ($priority as $id => $pr): ?>
                  <option value="<?php echo $id; ?>" <?php if ($id == $input['priority']): ?>selected="selected"<?php endif; ?>><?php echo $pr; ?></option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
          <tr>
            <td class="lbl">&nbsp;</td>
            <td><input type="submit" value="Submit" /></td>
          </tr>
        </table>
      </form>
    <?php endif; ?>
    <table class="tasklist">
      <tr>
        <th class="col-ico">&nbsp;</th>
        <th>Task</th>
        <th class="col">Status</th>
        <th class="col">Priority</th>
        <th class="col-opt">Options</th>
      </tr>
      <?php if (isset($checklist['checklist']) && !empty($checklist['checklist'])): ?>
        <?php
        $checklist['checklist'] = array_slice($checklist['checklist'], ($input['pg'] - 1) * $per_page, $per_page);
        foreach ($checklist['checklist'] as $cl): ?>
          <tr>
            <td class="col-ico"><img src="img/<?php echo $cl['priority'] .'-'. $cl['status']; ?>.png" /></td>
            <td>
              <h5 title="Added <?php echo format_date('D, j M Y, g:i a', $cl['timestamp']); ?> (<?php echo format_timestamp($cl['timestamp']); ?>)"><?php echo $cl['task']; ?></h5>
              <?php if (!empty($cl['remark'])): ?><p><?php echo nl2br($cl['remark']); ?></p><?php endif; ?>
            </td>
            <td class="col"><abbr title="<?php echo format_date('D, j M Y, g:i a', $cl['last_update']); ?> (<?php echo format_timestamp($cl['last_update']); ?>)"><?php echo $status[$cl['status']]; ?></abbr></td>
            <td class="col"><?php echo $priority[$cl['priority']]; ?></td>
            <td class="col-opt">
              <a href="index.php?do=edit&id=<?php echo $cl['id']; ?>">Edit</a> &middot;
              <a href="index.php" class="del" onclick="return delete_task(<?php echo $cl['id']; ?>);" title="Delete task">&times;</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="5">No task</td></tr>
      <?php endif; ?>
    </table>
    <?php paginate($total_checklist, $input['pg'], 'index.php?pg=%d', $per_page); ?>
    <form action="index.php" id="delete_task" method="post">
      <input type="hidden" name="id" id="delete_id" value="" />
      <input type="hidden" name="do" value="delete" />
    </form>
  </div>
</body>

</html>