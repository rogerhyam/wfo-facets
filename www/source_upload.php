<?php

require_once('../include/Importer.php');


// the upload on the source page
if($_POST && isset($_FILES["input_file"]) && Authorisation::canEditSourceData($source_id) && $_FILES["input_file"]["type"] == 'text/csv'){

    // we save the file by the user id and source.
    $now = time();
    $input_file_path = "../data/session_data/user_{$user['id']}/source_{$source_id}";
    @mkdir($input_file_path, 0777,true);
    $input_file_path .= "/$now.csv";
    move_uploaded_file($_FILES["input_file"]["tmp_name"], $input_file_path);

    // load it all in the session because we will run through 
    // these things in ajax calls.
    $importer = new Importer($input_file_path, isset($_POST["overwrite_checkbox"]) ? true : false, $source_id, $facet_value);
    $_SESSION['importer'] = serialize($importer);

}else{
    unset($_SESSION['importer']);
    $importer = false;
}


?>
<p class="lead">
    Use this tool to upload name lists in bulk.
</p>
<p>The format for uploading files is very simple.
    The file should be a CSV file with the first column containing ten digit WFO IDs (e.g. wfo-0000878441).
    All other columns will be ignored.
    Any values in the first column that aren't valid WFO IDs will be ignored.
    If you don't have WFO IDs for your names yet you can add them to the CSV using the <a
        href="https://list.worldfloraonline.org/matching.php">name matching tool available
        on the WFO Plant List API</a>.
    Files generated by that tool can be upload directly to this tool.
</p>
<p>
    If you check the "Overwrite existing list" box then the current list will be deleted and replaced by
    the one in the file you upload.
    If you do not check the box then new names in the file will be added to the list and names that are already in the
    list will be ignored.
</p>
<p>
    Names that the facet service doesn't already contain will load slower than names that have already been scored for
    some other facet as they need to be checked.
</p>


<?php

    if(Authorisation::canEditSourceData($source_id)){
        if($importer){
?>
<div id="upload_progress_bar">
    <div class="alert alert-warning" role="alert"><strong>Uploading ... </strong></div>
</div>
<div>
    <a href="source.php?tab=upload-tab&source_id=<?php echo $source_id ?>">Cancel</a>
</div>
<script>
// call the progress bar every second till it is complete
const div = document.getElementById('upload_progress_bar');

function callProgressBar() {
    fetch("source_upload_progress.php")
        .then((response) => response.json())
        .then((json) => {
            div.innerHTML =
                `<div class="alert alert-${json.level}" role="alert">${json.message}</div>`;
            if (!json.complete) callProgressBar();
        });
}
callProgressBar();
</script>

<?php
        }else{
?>

<form method="POST" action="source.php" enctype="multipart/form-data">
    <input type="hidden" name="tab" value="upload-tab" />
    <input type="hidden" name="source_id" value="<?php echo $source_id ?>" />

    <div class="mb-3">
        <label for="input_file" class="form-label">Select a file for upload.</label>
        <input class="form-control" type="file" id="input_file" name="input_file"
            onchange="if(this.value) document.getElementById('upload_button').disabled = false;">
    </div>

    <div class="mb-3">
        <input class="form-check-input" type="checkbox" value="" id="overwrite_checkbox" name="overwrite_checkbox"
            value="overwrite"
            onchange="let el = document.getElementById('upload_button'); if(this.checked){el.innerHTML = 'Upload & Overwrite'; el.classList.remove('btn-primary'); el.classList.add('btn-danger'); }else{ el.innerHTML = 'Upload & Add'; el.classList.remove('btn-danger'); el.classList.add('btn-primary'); };">
        <label class="form-check-label" for="overwrite_checkbox">
            Overwrite existing list.
        </label>
    </div>
    <button type="submit" disabled="true" class="btn btn-primary" id="upload_button">Upload & Add</button>
</form>


<?php
        }// rendering form
    }else{ // can use form
?>
<div class="alert alert-danger" role="alert">Sorry, you don't have rights to upload to this list.</div>
<?php
    } // can't edit form
?>