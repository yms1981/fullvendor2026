<?php
/**
 * @var object $listItem the product item form the list for a given record
 * @var mixed $value the field value for the listItem
 */

?>
<?php
$tags = explode(',', $value);
$trimmedTags = array_map('trim', $tags);
if (!empty($trimmedTags)) {
    foreach ($trimmedTags as $tag):
        if (!empty($tag)) {
            ?>
            <span class="label label-default"><?= $tag ?></span>            
            <?php
        }

    endforeach;
}
?>
