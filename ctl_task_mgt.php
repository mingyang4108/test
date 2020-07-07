<?php
namespace app\control;

use kali\core\db;
use kali\core\req;
use kali\core\tpl;
use app\model\mod_case;
use kali\core\lib\cls_auth;
use kali\core\lib\cls_page;
use app\model\mod_host_unit;
use kali\core\lib\cls_msgbox;
use kali\core\kali;
use common\model\mod_common;
use app\model\mod_util;


/**
 * @desc 任务管理
 * @date 2018-01-31
 * @author ChrisBlack
 * @version $Id$
 */
class ctl_task_mgt
{
    protected $main_table     = "";
    protected $feedback_table = "";
    protected $aim_table      = "";
    protected $userinfo       = "";
    protected $task_status    = array();
    protected $is_accepted    = array();

    public function __construct()
    {
        //任务管理主要数据表
        $this->main_table     = '#PB#_task_mgt';
        //任务回馈数据表
        $this->feedback_table = '#PB#_task_mgt_feedback';
        //任务目标数据表
        $this->aim_table      = '#PB#_task_mgt_aims';
        //当前操作用户数据
        $this->userinfo       = kali::$auth->user;
        //任务状态
        $this->task_status    = array('wait'=>'待受理',  'progress'=>'处理中', 'void'=>'已作废', 'off'=>'已挂起', 'fail'=>'已失败', 'finished'=>'已完成');

        //受理状态
        $this->is_accepted    = array('0'=>'未受理', '1'=>'已受理');



    }


    /**
     * @feature 入口文件，显示所有任务
     *  任务列表下，未受理，处理中，已挂起，已失败，已废弃，已完成任务
     *  包含搜索
     */
    public function index()
    {
        $task_back_url = mod_util::uri_string();
        setcookie('task_back_url',$task_back_url);
        $this->_list();
    }

    public function list_wait()
    {
        $task_back_url = mod_util::uri_string();
        setcookie('task_back_url',$task_back_url);
        $this->_list();
    }

    public function list_progress()
    {
        $task_back_url = mod_util::uri_string();
        setcookie('task_back_url',$task_back_url);
        $this->_list();
    }

    public function list_off()
    {
        $task_back_url = mod_util::uri_string();
        setcookie('task_back_url',$task_back_url);
        $this->_list();
    }

    public function list_fail()
    {
        $task_back_url = mod_util::uri_string();
        setcookie('task_back_url',$task_back_url);
        $this->_list();
    }
    public function list_void()
    {
        $task_back_url = mod_util::uri_string();
        setcookie('task_back_url',$task_back_url);
        $this->_list();
    }

    public function list_finished()
    {
        $task_back_url = mod_util::uri_string();
        setcookie('task_back_url',$task_back_url);
        $this->_list();
    }

    private function _list()
    {

        $title           = '任务列表';
        $status          = req::item("status");
        $status_order    = req::item("status_order");
        $search_accepted = req::item("is_accepted");
        $search_title    = req::item("task_title");
        $search_id       = req::item("accepted_id");
        $where           = array();
        $ac              = 'index';
        $page_size       = req::item('page_size', mod_common::PAGE_SIZE);

        //搜索條件
        if(!empty($status))
        {
            $where[] = array('status', '=', $status);
        }
        if($status != 'void' || empty($status))
        {//除废弃状态外，其余都不显示废弃状态任务
            $where[] = array('status', '!=', 'void');
        }
        if($search_accepted!="")
        {
            $where[] = array('is_accepted', '=', $search_accepted);
        }
//        if($search_accepted!="")
//        {
//            $where[] = array('status', '=', $search_accepted);
//        }
        if(!empty($search_title))
        {
            $where[] = array('name', 'like', "%{$search_title}%");
        }
        if(!empty($search_id))
        {//搜索受理人關鍵字
            $result = db::select('uid')->from('#PB#_admin')->where('username', 'like', "%{$search_id}%")->execute();
            $all_id = array();
            if($result) {
                foreach ($result as $val)
                {
                    $all_id[] = $val["uid"];
                }
                $where[] = array('accepted', 'in', $all_id);
            }
            else
            {//搜索受理人关键字无匹配资料时，顯示無資料
                $where[] =array('id', '=', '-1');
            }
        }
        //计算总笔数
        $row = db::select('count(*) AS `count`')->from('#PB#_task_mgt')->where($where)->as_row()->execute();
        $pages = cls_page::make($row['count'],$page_size);
        //任务列表
        $columns_arr = array('id', 'name', 'case_id', 'accepted', 'is_accepted', 'claim', 'aims_num', 'remarks', 'status', 'create_user', 'create_time');
        $list_temp = db::select($columns_arr)->from('#PB#_task_mgt')->where($where);
        if(!empty($status_order))
        {
            $order = ($status_order == 'desc') ? 'desc' : 'asc';
            $list_temp->order_by('status', $order);
            $status_order  = ($status_order == 'desc') ? 'asc' : 'desc';
        }
        else
        {
            $list_temp->order_by('create_time', 'desc');
        }
        $list = $list_temp->limit($pages['page_size'])->offset($pages['offset'])->execute();
        if(!empty($list))
        {
            foreach ($list as $k=>$val)
            {//任务相关案例信息
                $case = db::select(array('id', 'name'))->from('#PB#_case')
                    ->where('id', '=', $val["case_id"])->as_row()->execute();
                $list[$k]["case_name"] = (!empty($case["name"])) ? $case["name"] : '-';
                $list[$k]["is_accepted"] = $this->is_accepted[$val["is_accepted"]];
                $list[$k]["status"] = $this->task_status[$val["status"]];
                $create = db::select("username")->from('#PB#_admin')->where('uid', '=', $val["create_user"])->as_row()->execute();
                $list[$k]["create_name"] = $create["username"];
            }
        }
        tpl::assign("task_status",$this->task_status);
        tpl::assign("search_accepted", $search_accepted);
        tpl::assign("search_title", $search_title);
        tpl::assign("search_id", $search_id);
        tpl::assign("status_order", $status_order);
        tpl::assign('list', $list);
        tpl::assign('pages', $pages['show']);
        tpl::assign('title', $title);
        tpl::assign("status", $status);
        tpl::display('task_mgt.index.tpl');
    }


    /**
     * @feature 新增任务
     *
     */
    public function add()
    {
        $title = '发布任务';
        if(!empty(req::$posts))
        {
            $post_data = req::$posts;
            //计算任务目标总数
            $post_data["aims_num"] = count($post_data["task_target"]);
            $post_data["host_mine"] = json_encode($post_data["host_mine"]);
            $info_data = $this->check($post_data);
            if(!is_array($info_data))
            {
                cls_msgbox::show('系统提示', $info_data, '-1');
                exit();
            }
            db::start();
            if(!empty($post_data["accepted"]))
            {//当有受理人时，受理状态改为已受理
                $info_data["status"] = 'progress';
                $info_data["is_accepted"] = '1';
            }
            else
            {//没有受理人，便为待受理状态
                $info_data["status"] = 'wait';
                $info_data["is_accepted"] = '0';
            }
            //取得任务ID
            $task_target = $info_data["task_target"];
            unset(
                $info_data["gourl"],
                $info_data["task_require"],
                $info_data["task_remark"],
                $info_data["task_target"],
                $info_data["csrf_token_name"]
            );
            list($insert_id, $affects) = db::insert('#PB#_task_mgt')->set($info_data)->execute();
            if(!$insert_id)
            {
                cls_msgbox::show('系统提示', '发布失败', '-1');
                db::rollback();
            }
            //写入任务目标数据表
            foreach($task_target as $val)
            {
                if(empty($val))
                {//当任务目标没有填写资料时
                    continue;
                }
                if(!empty($post_data["accepted"]))
                {//任务目标同任务，有受理人时，状态改为已受理
                    $aims_data['aim_status'] = 'progress';
                }
                else
                {
                    $aims_data['aim_status'] = 'wait';
                }
                $aims_data['task_id']     = $insert_id;
                $aims_data['content']     = $val;
                $aims_data['create_time'] = $info_data['create_time'];
                $aims_data['create_user'] = $info_data['create_user'];
                $aims_data['update_time'] = $info_data['update_time'];
                $aims_data['update_user'] = $info_data['update_user'];
                list($aims_insert_id, $affects) = db::insert('#PB#_task_mgt_aims')->set($aims_data)->execute();
                if(!$aims_insert_id)
                {
                    cls_msgbox::show('系统提示', '生成任务目标失败', '-1');
                    db::rollback();
                }

            }
            db::commit();
            kali::$auth->save_admin_log("新增发布任务 {$insert_id}");
            $gourl = "?ct=task_mgt&ac=add"; //req::item('gourl', "?ct=task_mgt&ac=detail&id={$insert_id}");
            cls_msgbox::show('系统提示', "添加成功", $gourl);

        }
        else
        {
            $hname = $this->host_unit_mine();
            $host_mine=[];
            if(!empty($hname))
            {
                foreach($hname["results"] as $val)
                {
                    $host_mine[$val["id"]] = $val["text"];
                }
            }
            $date   = date("Y-m-d") ;
            $timestamp = KALI_TIMESTAMP;
            $token = md5('unique_salt' . $timestamp);
            tpl::assign('host_mine', $host_mine);
            tpl::assign('timestamp', $timestamp);
            tpl::assign('token', $token);
            tpl::assign('date', $date);
            tpl::assign("title", $title);
            tpl::display('task_mgt.add.tpl');
        }
    }


    /**
     *任务细节，由任务列表页进入
     *所有状态任务细节，皆进入此页
     */
    public function detail()
    {//typeback：返回需要的页面类型，如任务列表||我下发的任务，status：返回及资料状态
        $id               = req::item("id");
        $case_id          = req::item("case_id");
        $status           = req::item("status");
        $typeback         = req::item("typeback","list_arrange");
        $feed_date_order  = req::item("feed_date_order");
        $order            = array("`#PB#_task_mgt_feedback`.`create_time`", 'desc');
        $situation         = req::item('situation');
        if($situation == "nocase")
        {
            cls_msgbox::show('系统提示', '实例已删除', '-1');
            exit();
        }
        $columns_mg  = array("#PB#_task_mgt.id", "#PB#_task_mgt.name as mgname","host_mine",
                             "case_id", "accepted", "is_accepted", "claim", "aims_num",
                             "remarks", "#PB#_task_mgt.status", "#PB#_task_mgt.create_user",
                             "#PB#_task_mgt.create_time", "#PB#_task_mgt.update_user", "#PB#_task_mgt.update_time");
        $columns_cs  = array("#PB#_case.name");
        $columns_arr = array_merge($columns_mg, $columns_cs);
        //任务基础数据
        $data        = db::select($columns_arr)->from("#PB#_task_mgt")
                       ->join("#PB#_case", 'LEFT')->on('#PB#_task_mgt.case_id', '=', '#PB#_case.id')
                       ->where("#PB#_task_mgt.id", '=', $id)->as_row()->execute();

        $admin_name  = db::select('username')->from('#PB#_admin')->where('uid', '=', $data['create_user'])->as_row()->execute();
        $data["username"] = $admin_name["username"];
        //受理人数据
        $admin_data  = db::select(array('uid', 'username'))->from('#PB#_admin')->where('uid', '=', $data["accepted"])->as_row()->execute();
        //任务目标
        $columns_arr = array("id", "task_id", "content", "aim_status", "username", "#PB#_task_mgt_aims.create_time");
        $aim_data = db::select($columns_arr)->from("#PB#_task_mgt_aims")->join("#PB#_admin", 'LEFT')
                       ->on("#PB#_task_mgt_aims.create_user", '=', "#PB#_admin.uid")
                       ->where("#PB#_task_mgt_aims.task_id", '=', $id)->execute();
        //目前在库受理人列表
        $admin_all     = db::select(array('uid', 'username'))->from('#PB#_admin')->execute();
        $host_unit     = mod_case::get_host_unit_mine();

        //我方單位
        $host_select   = json_decode($data["host_mine"]);
        if(!empty($host_select))
        {
//            $host_mine     = db::select("name")->from('#PB#_host_unit_mine')->where("id", "IN", $host_select)->execute();
//            foreach($host_mine as $val)
//            {
//                $hname[] = $val["name"];
//            }
//            $host_str      = implode("， ",$hname);
//            tpl::assign("host_select", $host_str);
             tpl::assign("host_select", $host_select);
             tpl::assign("host_unit", $host_unit);

        }
        else
        {
            tpl::assign('host_select', '');
        }
        $status_option = $this->task_status;
        $status_sel    = $status_option[$data["status"]];//已选择状态

        $submission_disabled = ($data['status'] == "finished") ? "disabled" : "";//已完成任务，按钮disabled
        $accepted_disable    = (empty($data['accepted'])) ? "display:none;" : "";//未受理任务，按钮隐藏

        if(!empty($aim_data))
        {
            foreach($aim_data as $k=>$val)
            {
                $nums = db::select("count(*) as `num`")->from('#PB#_task_mgt_feedback')
                    ->where("`#PB#_task_mgt_feedback`.`task_id`", '=', $val['id'])->as_row()->execute();
                $aim_data[$k]["nums"] = $nums["num"];
                $aim_data[$k]["status_name"] = array_key_exists($val["aim_status"],$status_option)?$status_option[$val["aim_status"]]:'';
                //反馈信息数据
                //$columns_arr = array("`id`", "`task_id`", "`cm_task_mgt_feedback`.`content`", "`create_user`", "`create_time`", "`#PB#_admin`.`username`");
                $tmp = db::select(
                    "cm_task_mgt_feedback.id,cm_task_mgt_feedback.task_id,cm_task_mgt_feedback.content,
                            cm_task_mgt_feedback.create_user,cm_task_mgt_feedback.create_time,cm_admin.username")
                        ->from("`#PB#_task_mgt_feedback`")
                        ->join("`#PB#_admin`", 'LEFT')
                        ->on("`#PB#_task_mgt_feedback`.`create_user`", '=', "`#PB#_admin`.`uid`")
                        ->where("`#PB#_task_mgt_feedback`.`task_id`", "=", $val["id"])
                        ->order_by("`#PB#_task_mgt_feedback`.`create_time`", 'desc')
                        ->limit(1)
                        ->as_row()
                        ->execute();
                $aim_data[$k]["feed_content"] = $tmp["content"];
                $aim_data[$k]["feed_date"] = $tmp["create_time"];
            }
        }
        $new_update = "";
        if($data["update_time"] != $data["create_time"])
        {
            $new_update = db::select('username')->from('#PB#_admin')->where('uid', '=', $data["update_user"])->as_row()->execute();
        }
        if(empty($data))
        {
            cls_msgbox::show('系统提示', '任务不存在！', '-1');
            exit();
        }
        if(empty($data["case_id"]))
        {
            cls_msgbox::show('系统提示', '任务未归属任何实例！', '-1');
            exit();
        }
        //实例名称取用主要实例名称，若没有才使用暂时名称
        $case_name = (empty($data["name"])) ? '' : $data["name"];
        //当前页面为实例=>任务安排时
        if($typeback=="arrange")
        {
            tpl::assign('backurl', "?ct=task_mgt&ac=list_arrange&case_id={$data['case_id']}");
            tpl::assign('typeback', 'arrange');
        }
        else
        {

            $backurl = !empty($_COOKIE['task_back_url'])?"?".$_COOKIE['task_back_url']:"?ct=task_mgt&ac={$typeback}&status={$status}&case_id={$case_id}";
            tpl::assign('backurl', $backurl);
            tpl::assign('typeback', '');
        }
        if(!empty($admin_all))
        {
            foreach($admin_all as $val)
            {//所有管理者名单列表
                $admin_option[$val["uid"]] = $val["username"];
                tpl::assign("admin_option", $admin_option);
            }
        }

        $task_status = $this->task_status;
        if(!empty($admin_data["uid"]))
        {//有受理人时，选择项的"待受理"拿掉
            $temp = array_shift($task_status);
        }
        //....id不存在的话实例那边不是会有判断么 ..为什么要这些多余的代码还容易出错
        //$is_deleted = db::select('isdeleted')->from('#PB#_case')->where('id', '=' ,$data["case_id"])->as_row()->execute();
        //$caselink = ($is_deleted["isdeleted"]==='0') ? "?ct=case&ac=detail&case_id={$data["case_id"]}" : "?ct=task_mgt&ac=detail&id={$id}&situation=nocase";
        $caselink = "?ct=case&ac=detail&case_id={$data["case_id"]}&task={$data['id']}";
        tpl::assign("caselink", $caselink);
        tpl::assign("aims_num", $data["aims_num"]);
        tpl::assign("typeback", $typeback);
        tpl::assign("status", $status);
        tpl::assign("i", "1");
        tpl::assign("sub_disabled", $submission_disabled);
        tpl::assign("accepted_disabled", $accepted_disable);
        tpl::assign('feed_date_order', $feed_date_order);
        tpl::assign('new_update', $new_update);
        tpl::assign('aims', $aim_data);
        tpl::assign('task_status', $task_status);
        tpl::assign("status_sel", $status_sel);
        tpl::assign('admin_data', $admin_data);
        tpl::assign('case_name', $case_name);
        tpl::assign('case_id', $data["case_id"]);
        tpl::assign('data', $data);
        tpl::assign('title', '任务详情');
        tpl::display('task_mgt.detail.tpl');
    }


    /**
     *转换受理人
     */
    public function transfer_admin()
    {
        $task_id  = req::item("id");
        $orig_id  = req::item("orig");
        $admin_id = req::item("admin_id");
        $typeback = req::item("typeback");

        db::start();
        $orig_status = db::select('status')->from('#PB#_task_mgt')->where('id', '=', $task_id)->as_row()->execute();
        if($orig_status["status"] == "wait")
        {
        $arr["status"] = 'progress';
        }

        $arr["accepted"]    = $admin_id;
        $arr["update_user"] = $this->userinfo['uid'];
        $arr["update_time"] = KALI_TIMESTAMP;
        $result = db::update('#PB#_task_mgt')->set($arr)->where('id', '=', $task_id)->execute();
        if(!$result)
        {
            cls_msgbox::show('系统提示', '任务转让失败', '-1');
            db::rollback();
        }

        $arr1["task_id"]       = $task_id;
        $arr1["update_user"]   = $this->userinfo["uid"];
        $arr1["transfer_from"] = $orig_id;
        $arr1["transfer_to"]   = $admin_id;
        $arr1["transfer_time"] = KALI_TIMESTAMP;
        list($result2, $affect) = db::insert('#PB#_task_mgt_transfer')->set($arr1)->execute();
        if(!$affect)
        {
            cls_msgbox::show('系统提示', '转让记录失败', '-1');
            db::rollback();
        }
        db::commit();

        kali::$auth->save_admin_log("转换受理人 {$orig_id} 为 {$admin_id}");
        cls_msgbox::show("受理人转让", "受理人转让完成", "?ct=task_mgt&ac=detail&typeback={$typeback}&id={$task_id}");
    }


    /**
     *任务安排列表 from 《实例》
     */
    public function list_arrange()
    {
        $case_id = req::item("case_id");
        //task_back_url
        setcookie('task_back_url','');
        $title           = '任务列表';
        $status_order    = req::item('status_order', '');
        $status          = req::item("status");
        $search_accepted = req::item("is_accepted");
        $search_title    = req::item("task_title");
        $search_id       = req::item("accepted_id");
        $order           = 'desc';
        $where           = array();
        $page_size       = req::item('page_size', mod_common::PAGE_SIZE);

        //搜索條件
        $where[] = array("case_id", "=", $case_id);
        if(!empty($status))
        {
            $where[] = array('status', '=', $status);
        }
        if($status != 'void')
        {
            $where[] = array('status', '!=', 'void');
        }
        if($search_accepted!="")
        {
            $where[] = array('is_accepted', '=', $search_accepted);
        }
        if(!empty($search_title))
        {
            $where[] = array('name', 'LIKE', "%{$search_title}%");
        }
        if(!empty($search_id))
        {
            $result = db::select('uid')->from('#PB#_admin')->where('username', 'like', "%{$search_id}%")
                      ->or_where('realname', 'like', "%{$search_id}%")->execute();
            $all_id = array();
            if($result) {
                foreach ($result as $val)
                {
                    $all_id[] = $val["uid"];
                }
                $where[] = array('accepted', 'in', $all_id);
            }
            else
            {
                $where[] = array('admin_id', '=', '-1');
            }
        }
        $row         = db::select('count(*) AS `count`')->from('#PB#_task_mgt')->where($where)->as_row()->execute();
        $pages       = cls_page::make($row['count'], $page_size);
        $columns_arr = array('id', 'name', 'case_id', 'accepted', 'is_accepted', 'claim', 'aims_num', 'remarks', 'status', 'create_user', 'create_time');
        $list_temp   = db::select($columns_arr)->from('#PB#_task_mgt')->where($where);


        if(!empty($status_order)){
            $order         = $status_order == 'desc' ? 'desc' : 'asc';
            $status_order  = $status_order == 'desc' ? 'asc' : 'desc';
            $list_temp     = $list_temp->order_by('status', $order);
        }
        else
        {
            $list_temp = $list_temp->order_by('create_time', 'desc');
        }
        $list = $list_temp->limit($pages['page_size'])->offset($pages['offset'])->execute();

        if(!empty($list))
        {
            foreach ($list as $k=>$val)
            {
                $case = db::select(array('id', 'name'))->from('#PB#_case')->where('id', '=', $val["case_id"])->as_row()->execute();
                $list[$k]["case_name"] = (!empty($case['name'])) ? $case['name'] : '';
                $list[$k]["is_accepted"] = $this->is_accepted[$val["is_accepted"]];
                $list[$k]["status"] = $this->task_status[$val["status"]];
                $name = db::select("username")->from('#PB#_admin')->where('uid', '=', $list[$k]["create_user"])->as_row()->execute();
                $list[$k]["create_name"] = $name["username"];
            }
        }
        tpl::assign("search_accepted", $search_accepted);
        tpl::assign("search_title", $search_title);
        tpl::assign("search_id", $search_id);
        tpl::assign("ct", "task");
        tpl::assign("case_id", $case_id);
        tpl::assign('list', $list);
        tpl::assign('pages', $pages['show']);
        tpl::assign('title', $title);
        tpl::assign('status_order', $status_order);
        tpl::display('task_mgt.list_arrange.tpl');
    }


    /**
     *新增任务安排 from 《实例》
     */
    public function arrange()
    {
        $case_id = req::item("case_id");
        if(!empty(req::$posts))
        {
            $post_data = req::$posts;
            $post_data["host_mine"] = req::item('host_mine', []);
            //计算任务目标总数
            $post_data["aims_num"] = count($post_data["task_target"]);
            $post_data["host_mine"] = json_encode($post_data["host_mine"]);
            $info_data = $this->check($post_data);
            if (!is_array($info_data))
            {
                cls_msgbox::show('系统提示', $info_data, '-1');
                exit();
            }

            db::start();
            if(!empty($post_data["accepted"]))
            {//当有受理人时，受理状态改为已受理
                $info_data["status"] = 'progress';
                $info_data["is_accepted"] = '1';
            }
            else
            {//没有受理人，便为待受理状态
                $info_data["status"] = 'wait';
                $info_data["is_accepted"] = '0';
            }
            //取得任务ID
            $task_target = $info_data["task_target"];
            unset($info_data["ct"], $info_data["ac"], $info_data["task_require"], $info_data["task_remark"], $info_data["task_target"]);
            list($insert_id, $affects) = db::insert('#PB#_task_mgt')->set($info_data)->execute();
            if(!$insert_id)
            {
                cls_msgbox::show('系统提示', '发布失败', '-1');
                db::rollback();
            }
            //写入任务目标数据表
            foreach ($task_target as $val)
            {
                if(empty($val))
                {//当任务目标没有填写资料时
                    continue;
                }
                if(!empty($post_data["accepted"]))
                {//任务目标同任务，有受理人时，状态改为已受理
                    $aims_data['aim_status'] = 'progress';
                }
                else
                {
                    $aims_data['aim_status'] = 'wait';
                }
                $aims_data['task_id']     = $insert_id;
                $aims_data['content']     = $val;
                $aims_data['create_time'] = $info_data['create_time'];
                $aims_data['create_user'] = $info_data['create_user'];
                $aims_data['update_time'] = $info_data['update_time'];
                $aims_data['update_user'] = $info_data['update_user'];
                list($aims_insert_id, $affects) = db::insert('#PB#_task_mgt_aims')->set($aims_data)->execute();
                if(!$aims_insert_id)
                {
                    cls_msgbox::show('系统提示', '生成任务目标失败', '-1');
                    db::rollback();
                }
            }
            db::commit();
            kali::$auth->save_admin_log("任务安排发布 {$insert_id}");
            $gourl = req::item('gourl', "?ct=task_mgt&ac=list_arrange&case_id={$case_id}");
            cls_msgbox::show('系统提示', "添加成功", $gourl);
        }
        else
        {
            $hname = $this->host_unit_mine();
            if(!empty($hname))
            {
                foreach($hname["results"] as $val)
                {
                    $host_mine[$val["id"]] = $val["text"];
                }
            }

            $date   = date("Y-m-d") ;
            $timestamp = KALI_TIMESTAMP;
            $token = md5('unique_salt' . $timestamp);
            tpl::assign('host_mine', $host_mine);

            tpl::assign("i", "0");
            tpl::assign("case_id", $case_id);
            tpl::assign('timestamp', $timestamp);
            tpl::assign('token', $token);
            tpl::assign('date', $date);
            tpl::display("task_mgt.arrange.tpl");
        }
    }


    /**
     *编辑修改任务
     */
    public function edit()
    {
        $id        = req::item("id");
        $typeback  = req::item("typeback");
        $status    = req::item("status");
        $case_id   = req::item("case_id",0);
        $save_data = req::$posts;
        if(!empty($save_data))
        {
            $post_data = req::$posts;
            //原始任务目标综数
            $res    = db::select('aims_num')->from('#PB#_task_mgt')->where('id', '=', $id)->as_row()->execute();
            $number = $res['aims_num'];
            //计算表单任务目标总数
            $post_data["aims_num"] = count($post_data["task_target"]);
            $post_data["host_mine"]= !empty($post_data["host_mine"])?json_encode($post_data["host_mine"]):'';
            $info_data             = $this->check($post_data);
            if(!is_array($info_data))
             {
                cls_msgbox::show('系统提示', $info_data, '-1');
                exit();
            }

            db::start();
            $aims_num = count($info_data["task_target"]);
            $info_data["aims_num"] = $aims_num;
            $orig    = db::select('accepted')->from('#PB#_task_mgt')->where('id', '=', $id)->as_row()->execute();
            $pstatus = "";

            if(empty($orig["accepted"]) && $post_data["accepted"]!=$orig["accepted"])
            {
                $info_data["is_accepted"] = '1';
                $info_data["status"]      = 'progress';
                $pstatus                  = 'progress';
                $status                   = 'progress';
            }

            //先更新任务主数据
            $task_target = $info_data["task_target"];
            unset(
                $info_data["create_user"],
                $info_data['csrf_token_name'],
                $info_data["create_time"],
                $info_data["gourl"],
                $info_data["task_require"],
                $info_data["task_remark"],
                $info_data["task_target"],
                $info_data["hi"]
            ); //编辑时不更新建立者
            $result = db::update('#PB#_task_mgt')->set($info_data)->where('id', '=', $id)->execute();
            if(!$result)
            {
                cls_msgbox::show('系统提示', '更新失败', '-1');
                db::rollback();
            }

            //更新及新增任务目标数据表
            foreach($task_target as $index=>$val)
            {
                if(empty($val))
                {
                    continue;
                }

                if($index<$number)
                {//本段为更新旧有任务目标
                    $all = db::select('id')->from('#PB#_task_mgt_aims')->where('task_id', '=', $id)
                           ->order_by('id', 'asc')->limit(1)->offset($index)->execute();
                    foreach($all as $val)
                    {
                        if($pstatus == 'progress')
                        {
                            $arr = array("content"=>$task_target[$index], "aim_status"=>$pstatus,
                                         "update_user"=>$this->userinfo["uid"], "update_time"=>KALI_TIMESTAMP);
                        }
                        else
                        {
                            $arr = array("content"=>$task_target[$index], "update_user"=>$this->userinfo["uid"], "update_time"=>KALI_TIMESTAMP);
                        }
                        $res = db::update('#PB#_task_mgt_aims')->set($arr)->where('id', '=', $val["id"])->execute();
                        if(!$res)
                        {
                            cls_msgbox::show('系统提示', '更新任务目标失败', '-1');
                            db::rollback();
                        }
                    }
                }
                else
                {//本段为新增部分
                    if(!empty($info_data["accepted"]))
                    {
                        $aims_data['aim_status']     = 'progress';
                    }

                    $aims_data['task_id']     = $id;
                    $aims_data['content']     = $val;
                    $aims_data["create_user"] = $info_data["update_user"];
                    $aims_data["create_time"] = $info_data["update_time"];
                    $aims_data['update_time'] = $info_data['update_time'];
                    $aims_data['update_user'] = $info_data['update_user'];
                    list($aims_insert_id, $affects) = db::insert('#PB#_task_mgt_aims')->set($aims_data)->execute();
                    if(!$aims_insert_id)
                    {
                        cls_msgbox::show('系统提示', '新增任务目标失败', '-1');
                        db::rollback();
                    }
                }
            }
            db::commit();
            $gourl = "?ct=task_mgt&ac=detail&case_id={$case_id}&id={$id}";//($status == "arrange") ? "?ct=task_mgt&ac=list_arrange&case_id={$case_id}" : "?ct=task_mgt&ac={$typeback}&status={$status}";

            kali::$auth->save_admin_log("储存编辑任务 {$id}");
            cls_msgbox::show('系统提示', "编辑成功", $gourl);
        }
        else
        {
            $columns_mg = array("#PB#_task_mgt.id", "#PB#_task_mgt.name", "#PB#_task_mgt.case_id", "#PB#_task_mgt.host_mine","#PB#_task_mgt.accepted", "#PB#_task_mgt.claim", "#PB#_task_mgt.remarks", "#PB#_task_mgt.status");
            $columns_cs = array("#PB#_case.`name` AS name1");
            $columns_arr= array_merge($columns_mg, $columns_cs);
            $data       = db::select($columns_arr)->from('#PB#_task_mgt')->join('#PB#_case', 'LEFT')
                          ->on('#PB#_task_mgt.case_id', '=', '#PB#_case.id')->where('#PB#_task_mgt.id', '=', $id)->as_row()->execute();
            $all_case   = db::select(array('id', 'name'))->from('#PB#_case')->execute();
            $admin      = db::select(array('uid', 'username'))->from('#PB#_admin')->execute();
            $aims       = db::select(array('id', 'task_id', 'content'))->from('#PB#_task_mgt_aims')
                          ->where('task_id', '=', $id)->order_by('id', 'asc')->execute();
            if(!empty($all_case))
            {
                foreach ($all_case as $val)
                {
                    $name = empty($val["name"]) ? '' : $val["name"];
                    $case_all[$val["id"]] = $name;
                }
            }

            $admin_all[0] = '请选择';
            if(!empty($admin))
            {
                foreach ($admin as $val)
                {
                    $admin_all[$val["uid"]] = $val["username"];
                }
            }
            $case_name = $data['name1'];

            if($typeback=="arrange")
            {
                tpl::assign('backurl', "?ct=task_mgt&ac=arrange&id={$id}");
            }
            else
            {
                tpl::assign('backurl', "?ct=task_mgt&ac={$typeback}&status={$status}");
            }
            //$host_mine = db::select("id,name")->from('#PB#_host_unit_mine')->execute();
//            $hname = $this->host_unit_mine();
//
//            if(!empty($host_mine))
//            {
//                foreach($host_mine as $val)
//                {
//                    $hname[$val["id"]] = $val["name"];
//                }
//                $host_select = json_decode($data["host_mine"]);
//                tpl::assign("host_mine", $hname);
//                tpl::assign("host_select", $host_select);
//            }
            $hname = $this->host_unit_mine();

            if(!empty($hname))
            {
                foreach($hname["results"] as $val)
                {
                    $host_mine[$val["id"]] = $val["text"];
                }
                $host_select = json_decode($data["host_mine"]);
                tpl::assign("host_mine", $host_mine);
                tpl::assign("host_select", $host_select);
            }
            else
            {
                tpl::assign("host_mine", '');
                tpl::assign("host_select", '');
            }

            tpl::assign("i", "0");
            tpl::assign('admin', $admin_all);
            tpl::assign("case_all", $case_all);
            tpl::assign("case_name", $case_name);
            tpl::assign("data", $data);
            tpl::assign("id", $id);
            tpl::assign("aims", $aims);
            tpl::display("task_mgt.edit.tpl");
        }
    }


    /**
     *新增任务回馈
     */
    public function add_feed()
    {
        $id        = req::item('id');
        $task_id   = req::item("task_id");
        $feed_data = req::$posts;
        $type                = req::item("typeback");
        $status              = req::item("status");
        
        if(!empty($feed_data))
        {
            $data                = array();
            $data["task_id"]     = $id;
            $data["content"]     = $feed_data["task_feed"];
            $user                = $this->userinfo;
            $data["create_user"] = $user["uid"];
            $data["create_time"] = KALI_TIMESTAMP;
            $data["update_user"] = $data["create_user"];
            $data["update_time"] = $data["create_time"];

            db::start();
            list($insert_id, $affects) = db::insert('#PB#_task_mgt_feedback')->set($data)->execute();
            if($affects<1)
            {
                kali::$auth->save_admin_log("任务管理，反馈增加失败{$id}");
                cls_msgbox::show("系统提示", "反馈任务生成失败", "-1");
                db::rollback();
                exit();
            }
            else
            {
                $topid              = db::select('task_id')->from('#PB#_task_mgt_aims')->where('id', '=', $task_id)->as_row()->execute();
                $arr["update_user"] = $user["uid"];
                $arr["update_time"] = KALI_TIMESTAMP;
                $res                = db::update('#PB#_task_mgt')->set($arr)->where('id', '=', $topid["task_id"])->execute();
                db::commit();
                kali::$auth->save_admin_log("新增任务回馈 {$insert_id}，任务目标{$task_id}");
                cls_msgbox::show("任务提示", "反馈新增成功","?ct=task_mgt&ac=detail&id={$task_id}&status={$status}&typeback={$type}");
            }
        }
        else
        {
            $all_feeds = db::select(array('id', 'task_id', 'content', 'create_user', 'create_time'))
                         ->from('#PB#_task_mgt_feedback')->where('task_id', '=', $id)
                         ->order_by('create_time', 'desc')->execute();
            $task = db::select('content')->from('#PB#_task_mgt_aims')->where('id', '=', $id)->as_row()->execute();
            $name = $task["content"];
            tpl::assign('typeback', $type);
            tpl::assign('status', $status);
            tpl::assign('all', $all_feeds);
            tpl::assign('task_name', $name);
            tpl::assign('id', $id);
            tpl::assign('task_id', $task_id);
            tpl::display('task_mgt.add_feed.tpl');
        }
    }


    /**
     *受理无受理人任务
     */
    public function accept_mission()
    {
        $id                  = req::item("id");
        $status              = req::item("status");
        $type                = req::item('typeback');
        $data["accepted"]    = kali::$auth->user['uid'];
        $data["is_accepted"] = '1';
        $data["update_user"] = $data["accepted"];
        $data["update_time"] = KALI_TIMESTAMP;
        $data["status"]      = 'progress';

        db::start();
        $result = db::update('#PB#_task_mgt')->set($data)->where('id', '=', $id)->execute();
        if(!$result)
        {
            db::rollback();
            kali::$auth->save_admin_log("受理任务失败 {$id}");
            cls_msgbox::show('受理任务', '任务受理失败', "-1");
        }
        $res = db::select('id')->from('#PB#_task_mgt_aims')->where('task_id', '=', $id)->execute();
        if(!empty($res))
        {
            foreach($res as $val)
            {
                $arr = array('aim_status'=>'progress');
                $res1 = db::update('#PB#_task_mgt_aims')->set($arr)->where('id', '=', $val['id'])->execute();
                if(!$res1)
                {
                    db::rollback();
                    kali::$auth->save_admin_log("更新任务目标状态失败 {$id}");
                    cls_msgbox::show('受理任务', '任务目标状态修改失败', "-1");
                }
            }
        }
        db::commit();

        kali::$auth->save_admin_log("受理任务 {$id}");
        cls_msgbox::show('受理任务', '任务受理成功', "?ct=task_mgt&ac=detail&status={$status}&typeback={$type}&id={$id}");
    }


    /**
     *我下发的任务
     */
    private function _all_published()
    {
        $task_back_url = "";
        $title           = '下发的任务';
        $status_order    = req::item('status_order', '');
        $search_accepted = req::item("is_accepted");
        $search_title    = req::item("task_title");
        $search_id       = req::item("accepted_id");
        $status          = req::item("status");
        $typeback        = req::item("typeback");
        $order           = ' ORDER BY `id` desc';
        $where           = array();
        $ct              = 'published';
        $page_size       = req::item('page_size', mod_common::PAGE_SIZE);
        //搜索條件
        $where[] = array('`create_user`',  '=', $this->userinfo["uid"]);
        if(!empty($status))
        {
            $where[] = array('status', '=', $status);
        }
        if($status != 'void' || empty($status))
        {
            $where[] = array('status', '!=', 'void');
        }
        if($search_accepted!="")
        {
            $where[] = array('is_accepted', '=', $search_accepted);
        }
        if(!empty($search_title))
        {
            $where[] = array('name', 'LIKE', "%{$search_title}%");
        }
        if(!empty($search_id))
        {
            $result = db::select('uid')->from('#PB#_admin')->where('username', 'like', "%{$search_id}%")
                      ->or_where('realname', 'like', "%{$search_id}%")->execute();
            $all_id = array();
            if($result) {
                foreach ($result as $val)
                {
                    $all_id[] = $val["uid"];
                }
                $where[] = array('accepted', 'IN', $all_id);
            }
            else
            {
                $where[] =array('id', '=', '-1');
            }
        }
        $row = db::select('count(*) as `count`')->from('#PB#_task_mgt')->where($where)->as_row()->execute();
        $pages = cls_page::make($row['count'], $page_size);

        $columns_arr = array('id', 'name', 'case_id', 'accepted', 'is_accepted', 'claim', 'aims_num', 'remarks', 'status', 'create_user', 'create_time');
        $list_tmp = db::select($columns_arr)->from('#PB#_task_mgt')->where($where);
        //排序条件
        if(!empty($status_order)){
            $order = $status_order == 'desc' ? 'desc' : 'asc';
            $list_tmp = $list_tmp->order_by('status', $order);
            $status_order  = $status_order == 'desc' ? 'asc' : 'desc';
        }
        else
        {
            $list_tmp = $list_tmp->order_by('create_time', 'desc');
        }
        $list = $list_tmp->limit($pages['page_size'])->offset($pages['offset'])->execute();
        if(!empty($list))
        {
            foreach ($list as $k=>$val)
            {
                $case = db::select(array('id', 'name'))->from('#PB#_case')->where('id', '=', $val['case_id'])->as_row()->execute();
                $list[$k]["case_name"] = (!empty($case['name'])) ? $case["name"] : '';
                $list[$k]["is_accepted"] = $this->is_accepted[$val["is_accepted"]];
                $list[$k]["status"] = $this->task_status[$val["status"]];
                $create = db::select("username")->from('#PB#_admin')->where('uid', '=', $val["create_user"])->as_row()->execute();
                $list[$k]["create_name"] = $create["username"];
            }
        }

        tpl::assign("search_accepted", $search_accepted);
        tpl::assign("search_title", $search_title);
        tpl::assign("search_id", $search_id);
        tpl::assign("typeback", $typeback);
        tpl::assign("status", $status);
        tpl::assign('list', $list);
        tpl::assign('pages', $pages['show']);
        tpl::assign('title', $title);
        tpl::assign('status_order', $status_order);
        tpl::display('task_mgt.published.tpl');
    }

    public function published()
    {
        $task_back_url = mod_util::uri_string();
        setcookie('task_back_url',$task_back_url);
        $this->_all_published();
    }
    public function published_wait()
    {
        $task_back_url = mod_util::uri_string();
        setcookie('task_back_url',$task_back_url);
        $this->_all_published();
    }

    public function published_progress()
    {
        $task_back_url = mod_util::uri_string();
        setcookie('task_back_url',$task_back_url);
        $this->_all_published();
    }

    public function published_off()
    {
        $task_back_url = mod_util::uri_string();
        setcookie('task_back_url',$task_back_url);
        $this->_all_published();
    }

    public function published_fail()
    {
        $task_back_url = mod_util::uri_string();
        setcookie('task_back_url',$task_back_url);
        $this->_all_published();
    }

    public function published_void()
    {
        $task_back_url = mod_util::uri_string();
        setcookie('task_back_url',$task_back_url);
        $this->_all_published();
    }

    public function published_finished()
    {
        $task_back_url = mod_util::uri_string();
        setcookie('task_back_url',$task_back_url);
        $this->_all_published();
    }

    /**
     *我受理的任务
     */
    private function _all_accepted()
    {
        $title           = '受理的任务';
        $status_order    = req::item('status_order', '');
        $search_title    = req::item("task_title");
        $search_id       = req::item("accepted_id");
        $status          = req::item("status");
        $typeback        = req::item("typeback");
        $where           = array();
        $ct              = 'accepted';
        $page_size       = req::item('page_size', mod_common::PAGE_SIZE);
        //搜索條件
        $where[] = array('accepted', '=', $this->userinfo["uid"]);
        if($status != 'void')
        {
            $where[] = array('status', '!=', 'void');
        }
        if(!empty($search_title))
        {
            $where[] = array('name', 'LIKE', "%{$search_title}%");
        }
        if(!empty($status))
        {
            $where[] = array('status', '=', $status);
        }
        $row   = db::select('count(*) AS `count`')->from('#PB#_task_mgt')->where($where)->as_row()->execute();
        $pages = cls_page::make($row['count'], $page_size);
        $columns_arr = array('id', 'name', 'case_id', 'accepted', 'is_accepted', 'claim', 'aims_num', 'remarks', 'status', 'create_user', 'create_time');
        $list_tmp = db::select($columns_arr)->from('#PB#_task_mgt')->where($where);

        //排序条件
        if(!empty($status_order) && empty($status)){
            $order = $status_order == 'desc' ? 'DESC' : 'ASC';
            $list_tmp = $list_tmp->order_by('status', $order);
            $status_order  = $status_order == 'desc' ? 'asc' : 'desc';
        }
        else
        {
            $list_tmp =$list_tmp->order_by('create_time', 'desc');
        }
        $list = $list_tmp->limit($pages['page_size'])->offset($pages['offset'])->execute();
        if(!empty($list))
        {
            foreach ($list as $k=>$val)
            {
                $case = db::select(array('id', 'name'))->from('#PB#_case')->where('id', '=', $val["case_id"])->as_row()->execute();
                $list[$k]["case_name"] = (!empty($case["name"])) ? $case["name"] : '';
                $list[$k]["is_accepted"] = $this->is_accepted[$val["is_accepted"]];
                $list[$k]["status"] = $this->task_status[$val["status"]];
                $create = db::select("username")->from('#PB#_admin')->where('uid', '=', $val["create_user"])->as_row()->execute();
                $list[$k]["create_name"] = $create["username"];
            }
        }

        tpl::assign("search_title", $search_title);
        tpl::assign("typeback", $typeback);
        tpl::assign("status", $status);
        tpl::assign('list', $list);
        tpl::assign('pages', $pages['show']);
        tpl::assign('title', $title);
        tpl::assign('status_order', $status_order);
        tpl::display('task_mgt.accepted.tpl');
    }

    public function accepted()
    {
     $this->_all_accepted();
    }

    public function accepted_progress()
    {
        $this->_all_accepted();
    }

    public function accepted_off()
    {
        $this->_all_accepted();
    }

    public function accepted_fail()
    {
        $this->_all_accepted();
    }

    public function accepted_void()
    {
        $this->_all_accepted();
    }

    public function accepted_finished()
    {
        $this->_all_accepted();
    }

    /**
     *编辑变更任务状态·
     */
    public function change_status()
    {
        $id        = req::item('id');
        $status    = req::item('status');
        $subaction = req::item('subac');
        $typeback  = req::item("typeback");
        $aims_num  = req::item("nums");
        $topid = db::select('task_id')->from('#PB#_task_mgt_aims')->where('id', '=', $id)->as_row()->execute();

        if($subaction == "change_aim")
        {//修改任务目标状态
            //计算已完成任务目标数量
            $count_finsh = db::select('count(*) AS `count_finish`')->from('#PB#_task_mgt_aims')->where('task_id', '=', $topid["task_id"])
                           ->and_where('aim_status', '=', 'finished')->as_row()->execute();
            //计算已完成+已废弃任务目标数量
            $count_finsh_and_void = db::select('count(*) AS `count_finish`')->from('#PB#_task_mgt_aims')->where('task_id', '=', $topid["task_id"])
                                    ->and_where_open()->where('aim_status', '=', 'finished')->or_where('aim_status', '=', 'void')
                                    ->and_where_close()->as_row()->execute();
            $main_status = "";
            switch ($status){//先检查送出的状态
                case "finished":
                    if(($count_finsh_and_void["count_finish"]+1) == $aims_num)
                    {
                        $main_status = 'finished';
                    }
                    break;
                case "void":
                    $void_num = db::select('count(*) AS `count_status`')->from('#PB#_task_mgt_aims')->where('task_id', '=', $topid["task_id"])
                                ->and_where('aim_status', '=', 'void')->as_row()->execute();
                    if((($count_finsh_and_void["count_finish"]+1) == $aims_num) && ($count_finsh["count_finish"]>=1))
                    {
                        $main_status = 'finished';
                    }
                    elseif(($void_num['count_status']+1) == $aims_num)
                    {
                        $main_status = 'void';
                    }
                    break;
                case "off":
                case "fail":
                    $status_num = db::select('count(*) as `count_status`')->from('#PB#_task_mgt_aims')->where('task_id', '=', $topid["task_id"])
                                  ->and_where('aim_status', '=', $status)->as_row()->execute();
                    if(($status_num["count_status"]+1) == $aims_num)
                    {
                        $main_status = $status;
                    }
                    break;
            }

            db::start();
            if($main_status == "finished")
            {
                $main_arr = array('status' => 'finished');
                $res = db::update('#PB#_task_mgt')->set($main_arr)->where('id', '=', $topid["task_id"])->execute();
                if(!$res)
                {
                    kali::$auth->save_admin_log("主任务状态在任务目标皆为完成及废弃下修改失败{$id}");
                    cls_msgbox::show('系统信息', '任务状态修改失败', -1);
                    exit();
                }
            }
            elseif(($main_status == 'off') || ($main_status == 'fail') || ($main_status == 'void'))
            {
                $main_arr = array('status' => $status);
                $res1 = db::update('#PB#_task_mgt')->set($main_arr)->where('id', '=', $topid['task_id'])->execute();
                if(!$res1)
                {
                    kali::$auth->save_admin_log("主任务状态在任务目标为全废弃，全挂起或全失败下修改失败{$id}");
                    cls_msgbox::show('系统信息', '任务状态修改失败', -1);
                    exit();
                }
            }

            $arr1 = array("aim_status"=>$status);
            $result = db::update('#PB#_task_mgt_aims')->set($arr1)->where('id', '=', $id)->execute();
            if(!$result)
            {
                kali::$auth->save_admin_log("任务目标状态变更失败{$id}");
                cls_msgbox::show('系统信息', '任务目标状态修改失败', -1);
                exit();
            }

            $arr["update_user"] = $this->userinfo["uid"];
            $arr["update_time"] = KALI_TIMESTAMP;
            $result2 = db::update('#PB#_task_mgt')->set($arr)->where('id', '=', $topid['task_id'])->execute();
            if(!$result2)
            {
                kali::$auth->save_admin_log("任务更新日期及更新者变更失败{$id}");
                cls_msgbox::show('系统信息', '任务更新日期及更新者无法变更', -1);
                exit();
            }
            db::commit();

            $gourl = ($typeback == "arrange") ? "?ct=task_mgt&ac=detail&id={$topid['task_id']}&typeback=arrange&status={$status}" : "?ct=task_mgt&ac=detail&id={$topid['task_id']}&typeback={$typeback}&status={$status}";
            kali::$auth->save_admin_log("任务目标状态变更{$id}");
            cls_msgbox::show('任务目标', '状态修改成功', $gourl);
        }
        else
        {//修改大任务状态
            db::start();
            $arr['status']      = $status;
            $arr['update_user'] = $this->userinfo['uid'];
            $arr['update_time'] = KALI_TIMESTAMP;
            $result = db::update('#PB#_task_mgt')->set($arr)->where('id', '=', $id)->execute();
            if(!$result)
            {
                cls_msgbox::show('系统信息', '状态修改失败', -1);
                db::rollback();

            }
            db::commit();

            $gourl = ($typeback == "arrange") ? "?ct=task_mgt&ac=detail&id={$id}&typeback=arrange&status={$status}" : "?ct=task_mgt&ac=detail&id={$id}&status={$status}&typeback={$typeback}";
            kali::$auth->save_admin_log("任务状态变更{$id}");
            cls_msgbox::show('修改任务状态', '修改成功', $gourl);
        }
    }


    /**
     * @feature ajax返回JSON资料
     *
     */
    public function ajax_select()
    {
        $type        = req::item('type');
        $id_column   = req::item('idcol','id');
        $name_column = req::item('namecol', 'name');
        $keyword     = req::item('q','');
        $id_column   = $id_column=='admin_id'?'uid':$id_column;  //admin_id改成uid啦 直接这里替换一下
        $table       = "#PB#_".$type;
        if(!empty($keyword))
        {
            $data = db::select(array($id_column, $name_column))->from($table)->where($name_column, 'like', "%{$keyword}%")->execute();
        }
        else
        {
            $data = db::select(array($id_column, $name_column))->from($table)->execute();
        }
        $return = array();
        $return['results'][]=array("id"=>"0", "text"=>"请选择");
        foreach($data as $k=>$v)
        {
            $return['results'][] = array("id" => $v["{$id_column}"], "text" => $v["{$name_column}"]);
        }
        echo json_encode($return,JSON_UNESCAPED_UNICODE);
    }

    /**
     * 返回我放单位
     */
    public function host_unit_mine()
    {
        $keywords = req::item('q','');
        $where = array(
            array('status','=','1'),
            array('isdeleted','=','0'),
        );
        $data = db::select("id,name,parent_path")->from('#PB#_host_unit_mine')->where($where)->execute();

        //数组模糊搜索
        if(!empty($keywords))
        {
            foreach($data as $keys=>$values)
            {
                if (strstr( $values['name'], $keywords ) !== false ){
                    $arr2[$keys]['id'] = $data[$keys]['id'];
                    $arr2[$keys]['name'] = $data[$keys]['name'];
                    $arr2[$keys]['parent_path'] = !empty($data[$keys]['parent_path'])?$data[$keys]['parent_path']:'';
                }
            }
            $arr2 =  array_values($arr2);
        }else{
            $arr2 = $data;
        }
        $return = array();
        if(!empty($arr2))
        {
            foreach($arr2 as $k=>$v)
            {
                $parent_top = explode(',', $v['parent_path']);
                $parent_top_name = mod_host_unit::get_mine_name($parent_top[count($parent_top) - 1]);
                if(!empty($parent_top_name))
                {
                    $return['results'][$k]['id']=$v['id'];
                    $return['results'][$k]['text']=$parent_top_name.' 下属 '.$v['name'];
                }else{
                    $return['results'][$k]['id']=$v['id'];
                    $return['results'][$k]['text']=$v['name'];
                }

            }
        }


        //echo json_encode($return,JSON_UNESCAPED_UNICODE);
        return $return;
    }

    /**
     * @feature 过滤资料
     * @param $odata
     * @return mixed
     */
    public function check($odata)
    {
        $data = $odata;
        if($odata["name"]="")
        {
            return '必须输入任务标题';
        }
        if($odata["case_id"]="")
        {
            return "未输入归属实例";
        }
        if(!empty($odata["task_require"]))
        {
            $data["claim"] = $odata["task_require"];
        }
        else
        {
            return '必须输入任务任务要求';
        }

        if(!empty($odata["task_remark"]))
        {
            $data["remarks"] =$odata["task_remark"];
        }
        else
        {
            return '必须输入任务备注';
        }
        if(empty($odata["task_target"]))
        {
            return '至少输入一笔任务目标';
        }
        $data['create_time'] = KALI_TIMESTAMP;
        $data['create_user'] = kali::$auth->user['uid'];
        $data['update_time'] = KALI_TIMESTAMP;
        $data['update_user'] = kali::$auth->user['uid'];

        return $data;
    }

//    //获取主办单位基础数据
//    public  function _get_unit()
//    {
//        $where = array(
//            array('isdeleted','=',0),
//            array('status','=',1),
//        );
//        // $arr = db::get_all("select * from `#PB#_host_unit` where `isdeleted`='0' and `status`='1'");
//        $arr = db::select("id,name,parent_path")->from('#PB#_host_unit')->where($where)->execute();
//        $unit_arr = mod_host_unit::get_all_name('#PB#_host_unit');
//        $data = array();
//        if(!empty($arr))
//        {
//            foreach($arr as $k=>$v){
//                $parent_top = explode(',', $v['parent_path']);
//
//                $parent_top_name = mod_host_unit::get_key_value($parent_top[count($parent_top) - 1],$unit_arr);
//                if($parent_top_name)
//                {
//                    $data[$v['id']]=$parent_top_name.' 下属 '.$v['name'];
//                }else{
//                    $data[$v['id']]=$v['name'];
//                }
//
//            }
//        }
//        return $data;
//    }
}
