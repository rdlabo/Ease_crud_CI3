
<div class="page-header">
    <h1><small><?php echo $title; ?></small></h1>
</div>
<form name="form" class="form-horizontal" novalidate="novalidate" method="post" action="<?php echo current_url(); ?>">
    <?php

    foreach($form as $value){
        if($value["label"]){
            echo "    <div class='form-group'>\n";
            echo "        <label class='col-sm-3  col-xs-12 control-label'>";
            echo $value["label"]."</label>\n";
            echo "        <div class='col-sm-6 col-xs-8'>".$value["data"]."</div>\n";
            echo "        <div class='col-sm-3 col-xs-4'>\n";

            echo '            <select class="form-control" name=dispose-'.$value["name"].'>'."\n";
            echo '                <option value="1">含む</option>'."\n";
            echo '                <option value="2">完全一致</option>'."\n";
            echo '                <option value="3">前方一致</option>'."\n";
            echo '                <option value="4">後方一致</option>'."\n";
            echo '                <option value="5">以上</option>'."\n";
            echo '                <option value="6">以下</option>'."\n";
            echo '            </select>'."\n";

            echo "        </div>\n";
            echo "    </div>\n";
        }else{
            echo "    ${value["data"]}\n";
        }
    }

    ?>
    <div class="form-group">
        <div class="col-sm-offset-3 col-sm-9">
            <button type="submit" class="btn btn-default">検索</button>
        </div>
    </div>
</form>
