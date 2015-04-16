<div class="page-header">
    <h1><small><?php echo $title; ?></small></h1>
</div>
<table class="table table-bordered table-striped">
<?php

    echo $structure;

    foreach($data as $values){
        echo "<tr>";
        foreach($values as $value){
            if($value["key"]=="PRI"){
                echo "<td style='width:80px;text-align:center;'>";
                echo "<a href='".$ci_url."/create/${value["value"]}'><button type='button' class='btn btn-primary btn-xs'>修正</button></a>";
                echo "</td>";
            }else{
                echo "<td>";
                echo $value["value"];
                echo "</td>";
            }
        }
        echo "</tr>";
    }

?>
</table>
<nav>
    <ul class="pagination">
        <?php echo $pagination; ?>
    </ul>
</nav>