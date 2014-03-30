<?php
$uploadHandler = "handler.php";
$max_file_size = 3000000; // size in bytes
?>
<form id="Upload" action="<?php print $uploadHandler; ?>" enctype="multipart/form-data" method="POST">
    <h1>
        Upload form
    </h1>
    <p>
        <input type="hidden" name="MAX_FILE_SIZE" value="<?php print $max_file_size; ?>">
    </p>
    <p>
        <label for="file">File to upload:</label>
        <input id="file" type="file" name="file">
    </p>
    <p>
        <label for="submit">Press to...</label>
        <input id="submit" type="submit" name="submit" value="Upload me!">
    </p>
</form>