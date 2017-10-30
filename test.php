<!DOCTYPE html>
<html lang="en">
<head>
    <title>CONVERT JSON</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
</head>

<body>
<div style="padding: 25px;">
    <?php
    /**
     * Created by PhpStorm.
     * User: Darius Buda
     * Date: 10/22/2017
     * Time: 9:05 PM
     */

    require_once(__DIR__.'/config/config.php');
    require_once(__DIR__.'/classes/Problem0.php');

    try {
        if (!empty($_POST)) {
            $problem0 = new Problem0($_CONFIG_EXPORT_TYPES);
            $ret = $problem0->run($_POST);

            $rows = !empty($ret['rows']) ? $ret['rows'] : 0;
            $outputFile = $_POST['outputFolder'].'/'.$_POST['outputFileName'].'.'.$_POST['outputType'];
            $outputType = $_POST['outputType'];
            header("Location: " . $_SERVER['REQUEST_URI'] . "?done=1&r=$r&f=$outputFile&t=$outputType");
            exit();
        }
        elseif(!empty($_GET['done'])) {
            ?>
            <div class="alert alert-success">
                <strong>DONE!</strong><?php if($_GET['r']) strtoupper($_GET['outputType']).' contains '.$_GET['r'].' rows' ?> <br/> You can find the converted file in: <?php  echo $_GET['f'] ?> or download it <a target="_blank" href="download.php?f=<?php echo $_GET['f']; ?>">here</a>
            </div>
            <?php
        }
        ?>

        <form action="test.php" method="post" class="form-horizontal">
            <input type="hidden" name="jsonFolderPath" value="<?php echo $_CONFIG_JSON_FOLDER_PATH ?>"/>
            <input type="hidden" name="outputFolder" value="<?php echo $_CONFIG_OUTPUT_FOLDER ?>"/>
            <input type="hidden" name="outputType" value="" id="outputType"/>

            <div class="form-group">
                <label class="control-label col-sm-2" for="jsonURL">JSON SOURCE:</label>

                <div class="col-sm-10">
                    <input type="text" class="form-control" id="jsonURL" name="jsonURL" required="true">
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-2" for="jsonURL">CONVERTED FILENAME:</label>

                <div class="col-sm-10">
                    <input type="text" class="form-control" id="outputFileName" name="outputFileName" required="true">
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <?php
                    foreach($_CONFIG_EXPORT_TYPES as $type => $data) {
                    ?>
                        <button type="submit" class="btn btn-default"
                                onclick="document.getElementById('outputType').value = '<?php echo $type; ?>'">
                            <?php echo $data['download_text'] ?>
                        </button>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </form>

        <div class="col-sm-offset-2 col-sm-10">
            <a target="_blank" href="logs.php" class="btn btn-info" role="button">LOGS</a>
            <a target="_blank" href="check_for_updates.php" class="btn btn-info" role="button">CHECK FOR UPDATES</a>
        </div>
        <div class="col-sm-offset-2 col-sm-10">

        </div>
        <?php
    }
    catch(Exception $ex) {
        ?>
        <div class="alert alert-danger">
            <strong>Error!</strong><?php echo $ex->getMessage();?>
        </div>
        <?php
    }
    ?>
</div>
</body>

</html>