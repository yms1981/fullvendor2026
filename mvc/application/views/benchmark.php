<?php if(!empty($benchmarkmessages)) :?>
<div class="container">
    <div class="card ">
        <div class="card-header" >
            <h4>Benchmarks (<?=count($benchmarkmessages)?>) </h4>
        </div>
        <div class="card-body">
            <?php
                foreach ($benchmarkmessages as $benchmarkmessage) {
                    echo "<div class='alert alert-info'>".$benchmarkmessage."</div>";
                }
         ?>
        </div>
    </div>
</div>
<?php endif; ?>
