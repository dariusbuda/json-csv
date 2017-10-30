<!DOCTYPE html>
<html lang="en">
<head>
  <title>LOGS</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
</head>
<body>

<?php
/**
 * Created by PhpStorm.
 * User: Darius Buda
 * Date: 10/25/2017
 * Time: 9:59 PM
 */

require_once(__DIR__.'/classes/Problem0.php');
?>
<?php
$logs = Log::printLogs();
if(!empty($logs)) {
    ?>
    <div class="container">
        <h2>LOGS</h2>
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>JSON URL</th>
                <th>OUTPUT FILE</th>
                <th>START</th>
                <th>END</th>
                <th>Obs</th>
            </tr>
            </thead>
            <?php
            foreach ($logs as $log) {
                ?>
                <tr>
                    <td><?php echo $log['jsonURL']; ?></td>
                    <td><?php echo $log['outputFile']; ?></td>
                    <td><?php echo $log['start_time'] ?></td>
                    <td><?php echo $log['end_time'] ?></td>
                    <td><?php echo $log['flag'] ?></td>
                </tr>
                <?php
            }
            ?>
        </table>
    </div>
    <?php
}
else {
    echo "No logs found";
}
?>

</body>
</html>


