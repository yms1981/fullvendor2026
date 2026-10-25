
    <button class="btn btn-danger" data-toggle="tooltip" title="Delete" onclick="deleteCategory(<?= @$v->cat_id ?>)">
        <i class="mdi mdi-delete"></i>
    </button>
    <?php } else {?>
        <button class="btn btn-info" data-toggle="tooltip" title="Not Permisson">
        Default Permission
    </button>
    <?php } ?>

    </td>
</tr>
<?php $i++; ?>
<?php endforeach ?>
