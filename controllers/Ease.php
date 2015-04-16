<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ease extends CI_Controller {

    function __construct()
    {
        parent::__construct();
        $this->load->library(
            array(
                'ease_crud',
                'pagination',
                'session',
                'user_agent',
            )
        );
        $this->load->helper('url');

        /**
         * CRUDの設定
         */
        $this->table = "users";
        $this->tablelabel = "ユーザ";
        // column名を日本語に変換
        $this->label = array(
            "id"        =>  "",
            "name"      =>  "氏名",
            "sex"       =>  "性別",
            "birth"     =>  "誕生日",
            "birthtime" =>  "生誕時間",
            "birthplace"=>  array(
                "label"         =>  "生誕地",
                "placeholder"   =>  "例：兵庫県神戸市",
            ),
            "ip"        =>  "エイジェント",
            "uid"       =>  "作成者",
            "del_flg"   =>  "削除フラグ",
            "t_stamp"   =>  "作成日時"
        );
        // columnをselect-boxに変換
        $this->select = array(
            "sex" => array(
                1   =>"男性",
                2   =>"女性"
            ),
            "del_flg" => array(
                0   =>"有効",
                1   =>"無効"
            ),
        );
        // list表示の時に隠すcolumn
        $this->list_hidden = array("birthtime","birthplace","ip");
        // index()のURL
        $this->url = base_url("ease");
        // listで1ページあたりの表示件数
        $this->per = 10;
    }

    /**
     * 転送用
     */
    public function index()
    {
        // 一覧に転送
        redirect($this->url."/lists");
    }

    /**
     * リスト表示
     */
	public function lists($page=0)
	{
        $partials = array();

        /* ここからEase_crud */
        $structures = $this->ease_crud->structure($this->table,$this->list_hidden,$this->select);    // table構造を取得
        $partials["structure"] = $this->ease_crud->format_label($structures,$this->label);           // table構造からthを作成
        $partials["data"] = $this->ease_crud->lists($structures,$this->per,$page,"del_flg=0");       // tableのデータを取得
        $partials["pagination"] = $this->ease_crud->lists_page($this->url."/lists",$this->ease_crud->lists_count($this->table,"del_flg=0"),$this->per);// paginationの作成
        /* ここまでEase_crud */

        $partials += array("ci_url"=>$this->url);
        $partials["title"] = $this->tablelabel."一覧";
        $this->load->view('common/header', $partials);
        $this->load->view('common/nav', $partials);
        $this->load->view('ease_crud/list', $partials);
        $this->load->view('common/footer', $partials);
	}

    /**
     * リスト表示
     */
    public function del_lists($page=0)
    {
        $partials = array();

        /* ここからEase_crud */
        $structures = $this->ease_crud->structure($this->table,$this->list_hidden,$this->select);    // table構造を取得
        $partials["structure"] = $this->ease_crud->format_label($structures,$this->label);           // table構造からthを作成
        $partials["data"] = $this->ease_crud->lists($structures,$this->per,$page,"del_flg=1");       // tableのデータを取得
        $partials["pagination"] = $this->ease_crud->lists_page($this->url."/del_lists",$this->ease_crud->lists_count($this->table,"del_flg=1"),$this->per);// paginationの作成
        /* ここまでEase_crud */

        $partials += array("ci_url"=>$this->url);
        $partials["title"] = $this->tablelabel."削除一覧";
        $this->load->view('common/header', $partials);
        $this->load->view('common/nav', $partials);
        $this->load->view('ease_crud/list', $partials);
        $this->load->view('common/footer', $partials);
    }



    /**
     * 検索フォーム
     */
    public function search()
    {
        $partials = array();

        /* ここからEase_crud */
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // 検索が実行された場合は検索内容をsessionに保存
            $this->session->set_userdata(array("search"=>$_POST));
            redirect($this->url."/search_lists");
        }else{
            // 通常時はsessionを破棄
            $this->session->unset_userdata('search');
        }
        $structures = $this->ease_crud->structure($this->table,array(),$this->select);                          // table構造を取得
        $partials["form"] = $this->ease_crud->format_form_array($structures,$this->label,"form-control",TRUE);  // table構造から検索ボックスを作成
        /* ここまでEase_crud */

        $partials += array("ci_url"=>$this->url);
        $partials["title"] = $this->tablelabel."検索";
        $this->load->view('common/header', $partials);
        $this->load->view('common/nav', $partials);
        $this->load->view('ease_crud/search', $partials);
        $this->load->view('common/footer', $partials);
    }

    /**
     * 検索結果
     * @param int $page
     */
    public function search_lists($page=0)
    {
        $partials = array();

        /* ここからEase_crud */
        $structures = $this->ease_crud->structure($this->table,$this->list_hidden,$this->select);
        $partials["structure"] = $this->ease_crud->format_label($structures,$this->label);

        // 検索結果からデータを取得
        $session = $this->session->all_userdata();
        $partials["data"] = $this->ease_crud->lists($structures,$this->per,$page,$this->ease_crud->format_search_sql($session));

        // paginationを作成
        $partials["pagination"] = $this->ease_crud->lists_page($this->url."search_lists",$this->ease_crud->lists_count($this->table,$this->ease_crud->format_search_sql($session)),$this->per);
        /* ここまでEase_crud */

        $partials += array("ci_url"=>$this->url);
        $partials["title"] = $this->tablelabel."検索結果";
        $this->load->view('common/header', $partials);
        $this->load->view('common/nav', $partials);
        $this->load->view('ease_crud/list', $partials);
        $this->load->view('common/footer', $partials);
    }


    /**
     * 作成・更新
     */
    public function create($primary_key=NULL)
    {
        $partials = array();

        /* ここからEase_crud */
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // 隠してたcolumnに値を入れる場合、POSTに直接入れる　例：$_POST["ip"] = $this->agent->agent_string();
            $_POST["ip"] = $this->agent->agent_string();
            if($this->ease_crud->sign($this->table)){
                // 登録更新成功
                $partials["alert"] = "success";
            }else{
                // 登録更新失敗
                $partials["alert"] = "false";
            }
        }else{
            $partials["alert"] = false;
        }

        // table構造を取得。データが存在しない場合はexit()
        if(!$structures = $this->ease_crud->structure($this->table,array("t_stamp"),$this->select,$primary_key)){
            exit("その操作は許可されておりません");
        }
        $partials["form"] = $this->ease_crud->format_form_array($structures,$this->label,"form-control");    // table構造からformを作成
        /* ここまでEase_crud */


        if(!$primary_key){
            $partials["title"] = $this->tablelabel."新規作成";
        }else{
            $partials["title"] = $this->tablelabel."更新";
        }
        $partials += array("ci_url"=>$this->url);
        $this->load->view('common/header', $partials);
        $this->load->view('common/nav', $partials);
        $this->load->view('ease_crud/create', $partials);
        $this->load->view('common/footer', $partials);
    }
}






