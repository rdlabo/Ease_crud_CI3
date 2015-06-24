<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Ease_crud
{

	function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->database();
        $this->ci->load->library('form_validation');
        $this->ci->load->library('pagination');

        // 整数暗号のkey。Intを入力。
        $this->key = 13;
	}

    /**
     * 書き込みフォーム作成 / 一覧ラベル作成
     * @param $table
     * @param array $exclude_row
     * @param array $select_group
     * @param null $primary_key
     * @return array
     */
    function structure($table,$exclude_row=array(),$select_group=array(),$primary_key=NULL)
    {
        $sql = "SHOW FULL COLUMNS FROM $table";
        $query = $this->ci->db->query($sql);
        $structure = $query->result_array();
        $content = array();
        $pri = NULL;

        foreach($structure as $re){
            //ループデータを変数に格納
            $n_name = $re["Field"];
            $n_comment = $re["Comment"];

            if($re['Key']=="PRI"){
                $pri = $re["Field"];
            }

            if(in_array($n_name , $exclude_row)) {
                // 処理なし
            }else{

                if($re["Null"]=="NO"){	//NULLが非許可の場合
                    $required = "required";
                }else{
                    $required = "";
                }

                // 処理変更時
                if(!empty($primary_key)){
                    $sql = "SELECT * FROM ${table} WHERE ${pri} = ?";
                    $data = array($primary_key);
                    $query = $this->ci->db->query($sql,$data);
                    if(!$read_data = $query->row_array()){
                        return FALSE;
                    }
                    $existing = $read_data[$n_name];
                }else{
                    $existing = NULL;
                }

                if (array_key_exists($n_name , $select_group)){	//selectの場合
                    $select = array();
                    foreach($select_group[$n_name] as $select_label => $select_value){
                        if($select_label==$existing){
                            $select[] = array(
                                "value"     => $select_label,
                                "label"     => $select_value,
                                "selected"  => "SELECTED"
                            );
                        }else{
                            $select[] = array(
                                "value" => $select_label,
                                "label" => $select_value,
                            );
                        }
                    }

                    $re['Type'] = "SELECT";
                }else{
                    $select = NULL;
                }

                if(!$existing){
                    $existing = $re['Default'];
                }

                $content[] = array(
                    "type"          =>  $re['Type'],
                    "key"           =>  $re['Key'],
                    "name"          =>  $n_name,
                    "value"         =>  $existing,
                    "placeholder"   =>  $n_comment,
                    "required"      =>  $required,
                    "select"        =>  $select,
                    "default"       =>  $re['Default'],
                    "extra"         =>  $re['Extra']
                );
                unset($required,$n_name,$existing,$n_comment);
            }
        }

        if(!empty($primary_key)){
            $content[] = array(
                "type"          =>  "hidden",
                "name"          =>  "primary_key",
                "value"         =>  "$pri:$primary_key",
                "key"           =>  NULL,
                "extra"         =>  NULL
            );
        }

        return array("table"=>$table,"structure"=>$content);
    }


    /**
     * 一覧データ作成用
     * @param $structures
     * @param int $display
     * @param int $current
     * @param null $add_sql
     * @return array
     */
    function lists($structures,$display=10,$current=0,$add_sql=NULL)
    {
        foreach($structures["structure"] as $structure){
            if($structure["key"]=="PRI"){
                $pri = $structure["name"];
                break;
            }
        }

        //データの呼び出し
        $sql = "SELECT * FROM ${structures["table"]}";

        if($add_sql){
            $sql .= " WHERE ";
            $sql .= $add_sql;
        }

        if(!empty($pri)){
            $sql .= " ORDER BY $pri DESC ";
        }

        //$current = $current * $display;
        $sql .= " LIMIT ${current},${display}";

        $query = $this->ci->db->query($sql);
        $table_data = $query->result_array();

        //コンテンツの作成
        $content = array();
        $line = 0;
        foreach($table_data as $data){
            foreach($structures["structure"] as $structure){
                $column = $structure["name"];
                if(is_array($structure["select"])){
                    $value = "";
                    foreach($structure["select"] as $select){
                        if(stristr($data[$column],"," )){
                            $checked = explode(",",$data[$column]);
                            foreach($checked as $check){
                                if($check == $select["value"]){
                                    $value .= $select["label"].",";
                                }
                            }
                        }else{
                            if($data[$column] == $select["value"]){
                                $value = $select["label"];
                            }
                        }
                    }
                }else{
                    $value = $data[$column];
                }

                $content[$line][] = array_merge($structure, array("value"=>$value));
            }
            $line++;
        }

        return $content;
    }

    /**
     * 全データの行数をカウント
     * @param $table
     * @param null $add_sql
     * @return mixed
     */
    function lists_count($table,$add_sql=NULL){
        //データの呼び出し
        $sql = "SELECT COUNT(*) as count FROM $table";

        if($add_sql){
            $sql .= " WHERE ";
            $sql .= $add_sql;
        }

        $query = $this->ci->db->query($sql);
        $count = $query->row_array();
        return $count["count"];
    }

    /**
     * 書き込みメソッド
     * @param $table
     * @return bool
     */
    function sign($table){
        $structures = $this->structure($table);
        $sign_data = array();

        // postのチェック
        foreach($_POST as $label=>$values){
            if(is_array($values)){
                $data = "";
                foreach($values as $value){
                    $data .= $value.",";
                }
                $label = str_replace("[]","", $label);
                $_POST[$label] = $data;
            }
        }

        // 構造のチェック
        foreach($structures["structure"] as $structure){
            $column = $structure["name"];

            if($structure["extra"]=="auto_increment"){
                continue;
            }

            $bind = "trim";
            if(stristr($structure["type"], "int")){
                $bind .= "|numeric";
            }

            if($structure["required"] == "required" AND !isset($structure["default"])){
                $bind .= "|required";
            }

            if($structure["type"]=="date"){
                $name = $structure["name"];
                $_POST[$name] = explode(",",$_POST[$name]);
                if($_POST[$name][1]<10){
                    $_POST[$name][1] = "0".$_POST[$name][1];
                }
                if($_POST[$name][2]<10){
                    $_POST[$name][2] = "0".$_POST[$name][2];
                }
                $_POST[$name] = date("Y-m-d", strtotime($_POST[$name][0]."/".$_POST[$name][1]."/".$_POST[$name][2]));
            }


            if($structure["type"]=="time"){
                $name = $structure["name"];
                $_POST[$name] = explode(",",$_POST[$name]);
                if($_POST[$name][0]<10){
                    $_POST[$name][0] = "0".$_POST[$name][0];
                }
                if($_POST[$name][1]<10){
                    $_POST[$name][1] = "0".$_POST[$name][1];
                }
                $_POST[$name] = date("H:i", strtotime($_POST[$name][0].":".$_POST[$name][1]));
            }

            $this->ci->form_validation->set_rules($structure["name"], $structure["name"], $bind);

            if(isset($_POST[$column])){
                $sign_data[$column] = $_POST[$column];
            }
        }

        if ($this->ci->form_validation->run()) {
            if(empty($_POST["primary_key"])){
                $this->ci->db->insert($table, $sign_data);
                return TRUE;
            }elseif($_POST["primary_key"]){
                $pri = explode(":",$_POST["primary_key"]);
                unset($_POST["primary_key"]);
                $this->ci->db->where($pri[0], $pri[1]);
                $this->ci->db->update($table, $sign_data);
                return TRUE;
            }else{
                return FALSE;
            }
        }else{
            return FALSE;
        }
    }

    /*--------------------------------------------------------------------------------------------------------
    // 整形系
    --------------------------------------------------------------------------------------------------------*/

    /**
     * 検索ボックスの作成
     * @param array $post
     * @return string
     */
    function format_search_sql($post=array()){
        $where = "";
        if(isset($post["search"])){
            if(is_array($post["search"])){
                foreach($post["search"] as $label => $value){
                    if (!stristr($label, "dispose-") AND !empty($value)){
                        $where .= $label;
                        $type = $post["search"]["dispose-".$label];

                        if($type==1){
                            // 含む
                            $where .= " LIKE '%";
                            $where .= $value;
                            $where .= "%' AND ";

                        }elseif($type==2){
                            // 前方一致
                            $where .= " LIKE '";
                            $where .= $value;
                            $where .= "%' AND ";
                        }elseif($type==3){
                            // 後方一致
                            $where .= " LIKE '%";
                            $where .= $value;
                            $where .= "' AND ";
                        }elseif($type==4){
                            // 完全一致
                            $where .= "=";
                            $where .= "'".$value."' AND ";
                        }elseif($type==5){
                            // 大なりイコール
                            $where .= "<=";
                            $where .= "'".$value."' AND ";
                        }elseif($type==6){
                            // 小なりイコール
                            $where .= ">=";
                            $where .= "'".$value."' AND ";
                        }

                    }
                }
            }
        }
        return substr($where, 0, -4);
    }

    /**
     * tableのlabel作成
     * @param $structures
     * @param array $labels
     * @param null $tr_class
     * @param null $th_class
     * @return string
     */
    function format_label($structures,$labels=array(),$tr_class=NULL,$th_class=NULL)
    {
        $content = "";
        $content .= "<tr";
        if($tr_class){
            $content .= " class='${tr_class}'";
        }
        $content .= ">";
        foreach($structures["structure"] as $str){

            if(array_key_exists($str['name'], $labels)){
                $label = $str['name'];
                $label = $labels[$label];
                if(is_array($label)){
                    $label = $label["label"];
                }
            }else{
                $label = $str['name'];
            }

            $content .= "<th";
            if($th_class){
                $content .= " class='${th_class}'";
            }
            $content .= ">";
            $content .= $label;
            $content .= "</th>";
        }
        $content .= "</tr>";

        return $content;
    }

    /**
     * formのinputなどに整形してarrayで返す
     * @param $structures
     * @param array $labels
     * @param null $input_class
     * @param bool $search
     * @return array
     */
    function format_form_array($structures,$labels=array(),$input_class=NULL,$search=FALSE,$checkbox=array())
    {
        $data = array();
        foreach($structures["structure"] as $str){

            if($str["extra"]=="auto_increment"){
                continue;
            }

            $placeholder = "";
            if(array_key_exists($str['name'], $labels)){
                $label = $str['name'];
                $label = $labels[$label];
                if(is_array($label)){
                    $json = json_encode($label);
                    $fromjson = json_decode($json);
                    if(isset($fromjson->label)){
                        $label = $fromjson->label;
                    }
                    if(isset($fromjson->placeholder)){
                        $placeholder = $fromjson->placeholder;
                    }
                    //print_r($placeholder);
                }
            }else{
                $label = $str['name'];
            }

            if($search){
                $str['value'] = null;
            }

            if ($str["type"]=="SELECT"){	//selectの場合

                $tmp_input ="";
                if (in_array($str['name'],$checkbox)){    // チェックボックス
                    $tmp_input .= "<div class='checkbox'>";
                    $array_value = explode (",",$str['value']);
                    foreach($str["select"] as $select){
                        if(array_search($select["value"], $array_value)!== false){
                            $tmp_input .= "<label class='checkbox' style='margin:0 10px 10px 0;display:inline-block;'><input type='checkbox' name='${str['name']}[]' value='${select["value"]}' checked> ${select["label"]}</label>";
                        }else{
                            $tmp_input .= "<label class='checkbox' style='margin:0 10px 10px 0;display:inline-block;'><input type='checkbox' name='${str['name']}[]' value='${select["value"]}'> ${select["label"]}</label>";
                        }
                    }
                    $tmp_input .= "</div>";
                }else{
                    $tmp_input = "<select class='${input_class}' name='${str['name']}' ${str['required']}>";
                    $tmp_input .= "<option value=''>選択してください</option>";
                    foreach($str["select"] as $select){
                        if($str['value'] == $select["value"]){
                            $tmp_input .= "<option value='${select["value"]}' ng-value='${select["value"]}' ng-selected='true' selected>${select["label"]}</option>";
                        }else{
                            $tmp_input .= "<option value='${select["value"]}' ng-value='${select["value"]}'>${select["label"]}</option>";
                        }
                    }
                    $tmp_input .= "</select>";
                }

                $input = array(
                    "label"     =>  $label,
                    "type"      =>  $str['type'],
                    "name"      =>  $str['name'],
                    "data"      =>  $tmp_input,
                    "required"  =>  $str['required'],
                );
                unset($tmp_input);

            } elseif ($str["type"]=="datetime" or $str["type"]=="timestamp")
            {	//フォーマットdatetimeの場合
                $input = array(
                    "label"  => $label,
                    "type"   =>$str['type'],
                    "name"   =>$str['name'],
                    "data"  => "<input type='datetime-local' class='${input_class}' name='${str['name']}' ng-init=\"${str['name']}:'${str['value']}'\" ng-model='${str['name']}' value='${str['value']}' ${str['required']} placeholder='${placeholder}' />",
                    "required"=>$str['required'],
                );
            } elseif ($str["type"]=="date")
            {	//フォーマットdateの場合

                if(!empty($str['value'])){
                    $day = explode("-",$str['value']);
                }else{
                    $day = array();
                }
                $y = "<option value=''>年</option>";
                $m = "<option value=''>月</option>";
                $d = "<option value=''>日</option>";
                for($i=1960;$i<2051;$i++){
                    if(!isset($day[0]) AND date("Y")==$i) {
                        $checked = "selected";
                    }elseif(isset($day[0]) AND $day[0]==$i){
                        $checked = "selected";
                    }else{
                        $checked = "";
                    }
                    $y .= "<option value='${i}' ${checked}>${i}年</option>";
                }
                for($i=1;$i<13;$i++){
                    if(isset($day[1]) AND intval($day[1])==$i){
                        $checked = "selected";
                    }else{
                        $checked = "";
                    }
                    $m .= "<option value='${i}' ${checked}>${i}月</option>";
                }

                for($i=1;$i<32;$i++){
                    if(isset($day[2]) AND intval($day[2])==$i){
                        $checked = "selected";
                    }else{
                        $checked = "";
                    }
                    $d .= "<option value='${i}' ${checked}>${i}日</option>";
                }

                $input = array(
                    "label"=> $label,
                    "type"   =>$str['type'],
                    "name"   =>$str['name'],
                    "data"  =>  "<div class='row'><div class='col-xs-4 small-4 columns'><select class='${input_class}' name='${str['name']}[0]' ${str['required']}>${y}</select></div>
                                 <div class='col-xs-4 small-4 columns'><select class='${input_class}' name='${str['name']}[1]' ${str['required']}>${m}</select></div>
                                 <div class='col-xs-4 small-4 columns'><select class='${input_class}' name='${str['name']}[2]' ${str['required']}>${d}</select></div></div>",
                    //"data"=> "<input type='date' class='${input_class}' name='${str['name']}' ng-init=\"${str['name']}='${str['value']}'\" ng-model='${str['name']}' value='${str['value']}' ${str['required']}  placeholder='${placeholder}' />",
                    "required"=>$str['required'],
                );
            } elseif ($str["type"]=="time")
            {	//フォーマットtimeの場合


                if(!empty($str['value'])){
                    $time = explode(":",$str['value']);
                }else{
                    $time = array();
                }
                $h = "<option value=''>時</option>";
                $m = "<option value=''>分</option>";
                for($i=1;$i<25;$i++){
                    if(isset($time[0]) AND $time[0]==$i){
                        $checked = "selected";
                    }else{
                        $checked = "";
                    }
                    $h .= "<option value='${i}' ${checked}>${i}時</option>";
                }
                for($i=1;$i<61;$i++){
                    if(isset($time[1]) AND intval($time[1])==$i){
                        $checked = "selected";
                    }else{
                        $checked = "";
                    }
                    $m .= "<option value='${i}' ${checked}>${i}分</option>";
                }


                $input = array(
                    "label"=> $label,
                    "type"   =>$str['type'],
                    "name"   =>$str['name'],
                    "data"  =>  "<div class='row'><div class='col-xs-4 small-4 columns'><select class='${input_class}' name='${str['name']}[0]' ${str['required']}>${h}</select></div>
                                 <div class='col-xs-4 small-4 columns'><select class='${input_class}' name='${str['name']}[1]' ${str['required']}>${m}</select></div><div class='small-4 columns'></div></div>",
                    //"data"=> "<input type='time' class='${input_class}' name='${str['name']}' ng-init=\"${str['name']}='${str['value']}'\" ng-model='${str['name']}' value='${str['value']}' ${str['required']}  placeholder='${placeholder}' />",
                    "required"=>$str['required'],
                );
            } elseif (stristr($str["type"], "int"))
            {	//フォーマットintの場合
                $input = array(
                    "label"=> $label,
                    "type"   =>$str['type'],
                    "name"   =>$str['name'],
                    "data"=> "<input type='number' class='${input_class}' name='${str['name']}' ng-init=\"${str['name']}='${str['value']}'\" ng-model='${str['name']}' value='${str['value']}' ${str['required']}  placeholder='${placeholder}' />",
                    "required"=>$str['required'],
                );
            } elseif($str["type"]== "text")
            {
                $input = array(
                    "label"=> $label,
                    "type"   =>$str['type'],
                    "name"   =>$str['name'],
                    "data"=> "<textarea class='${input_class}' name='${str['name']}' ng-init=\"${str['name']}='${str['value']}'\" ng-model='${str['name']}' ${str['required']} rows='5'  placeholder='${placeholder}'>${str['value']}</textarea>",
                    "required"=>$str['required'],
                );
            } elseif($str["type"]== "hidden") {
                $input = array(
                    "label"=> "",
                    "type"   =>$str['type'],
                    "name"   =>$str['name'],
                    "data"=> "<input type='hidden' name='${str['name']}' ng-init=\"${str['name']}='${str['value']}'\" ng-model='${str['name']}' value='${str['value']}' />",
                    "required"=> NULL,
                );
            } else{
                $input = array(
                    "label"=> $label,
                    "type"   =>$str['type'],
                    "name"   =>$str['name'],
                    "data"=> "<input type='text' class='${input_class}' name='${str['name']}' ng-init=\"${str['name']}='${str['value']}'\" ng-model='${str['name']}' value='${str['value']}' ${str['required']}  placeholder='${placeholder}' />",
                    "required"=>$str['required'],
                );
            }

            $data[] = $input;
        }
        return $data;
    }

    /**
     * ページネーション作成
     * @param $url
     * @param $rows
     * @param int $per
     * @return mixed
     */
    function lists_page($url,$rows,$per=10)
    {
        $config['base_url'] = $url;
        $config['total_rows'] = $rows;
        $config['per_page'] = $per;
        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>'."\n";
        $config['cur_tag_open'] = '<li class="active"><span>';
        $config['cur_tag_close'] = '<span class="sr-only">(current)</span></span></li>';
        $config['prev_link'] = '&lt;';
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';
        $config['next_link'] = '&gt;';
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';
        $config['first_link'] = FALSE;
        $config['last_link'] = "最後";
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';
        $this->ci->pagination->initialize($config);
        return $this->ci->pagination->create_links();
    }


    /*--------------------------------------------------------------------------------------------------------
    // オプション
    --------------------------------------------------------------------------------------------------------*/

    /**
     * 整数暗号化エンコード
     * @param $str
     * @return bool|int|string
     */
    function change_id ($str) {
        if(!is_numeric($str)){
            return false;
        }

        return ($str * 48 + 100000) * $this->key;
    }

    /**
     * 整数暗号化デコード
     * @param $str
     * @return bool|float
     */
    function restore_id ($str) {
        if(!is_numeric($str)){
            return false;
        }

        return ($str/$this->key - 100000) / 48;
    }


}
