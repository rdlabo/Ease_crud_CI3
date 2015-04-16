<nav class="navbar navbar-default">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="<?php echo base_url();?>">Ease_crud</a>
        </div>

        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">ユーザ <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo base_url();?>ease/create">新規作成</a></li>
                        <li><a href="<?php echo base_url();?>ease/lists">一覧</a></li>
                        <li><a href="<?php echo base_url();?>ease/search">検索</a></li>
                        <li><a href="<?php echo base_url();?>ease/del_lists">削除済み</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>