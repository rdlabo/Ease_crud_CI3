<?php
    if($alert=="success"){
        echo '<div class="alert alert-success">登録更新を行いました</div>';
    }elseif($alert=="false"){
        echo '<div class="alert alert-danger">登録更新できませんでした。入力内容をご確認ください</div>';
    }
?>

<div class="page-header">
    <h1><small><?php echo $title; ?></small></h1>
</div>
<form name="form" class="form-horizontal" novalidate="novalidate" method="post" action="<?php echo current_url(); ?>">
    <?php

        foreach($form as $value){
            if($value["label"]){
                echo "    <div class='form-group'>\n";
                echo "        <label class='col-sm-3 control-label text-left'>";
                echo $value["label"];
                if($value["required"]=="required"){
                    echo "<span class='label label-danger' style='position:relative;top:-2px;left:5px;'>必須</span>";
                }else{
                    echo "<span class='label label-success' style='position:relative;top:-2px;left:5px;'>任意</span>";
                }
                echo "</label>\n";
                echo "        <div class='col-sm-5'>".$value["data"]."</div>\n";
                echo "        <div class='col-sm-4'>\n";

                if($value["required"]=="required"){
                    echo '            <p class="form-control-static" ng-show="form.'.$value["name"].'.$valid"><span class="glyphicon glyphicon-ok-sign"></span></p>'."\n";
                }

                if($value["type"]=="datetime"){
                    echo '            <p class="form-control-static" ng-show="form.'.$value["name"].'.$error.date"><span class="glyphicon glyphicon-ban-circle"></span> 0000/00/00T00:00:00 の形式でご入力下さい</p>'."\n";
                }

                if($value["type"]=="date"){
                    echo '            <p class="form-control-static" ng-show="form.'.$value["name"].'.$error.date"><span class="glyphicon glyphicon-ban-circle"></span> 0000/00/00 の形式でご入力下さい</p>'."\n";
                }

                if($value["type"]=="time"){
                    echo '            <p class="form-control-static" ng-show="form.'.$value["name"].'.$error.time"><span class="glyphicon glyphicon-ban-circle"></span> 00:00 の形式でご入力下さい</p>'."\n";
                }

                echo "        </div>\n";
                echo "    </div>\n";
            }else{
                echo "    ${value["data"]}\n";
            }
        }

    ?>

    <div class="form-group">
        <div class="col-sm-offset-3 col-sm-9">
            <button type="submit" class="btn btn-default" ng-disabled="form.$invalid">登録更新</button>
        </div>
    </div>

</form>
