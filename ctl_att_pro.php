<?php

namespace client_app\control;

use common\model\mod_pro;
use common\model\mod_record_type_search;
use common\model\pub_mod_table;
use common\model\mod_common;
use kali\core\kali;
use kali\core\db;
use kali\core\req;
use kali\core\tpl;
use kali\core\util;
use kali\core\lib\cls_msgbox;
use kali\core\lib\cls_page;
use kali\core\lib\cls_validate;
use common\model\pub_mod_func;

/**
 * detail: 关注信息
 * Date: 2019/3/21
 * Time: 15：10
 */
class ctl_att_pro
{

    public function __construct()
    {
        $this->userinfo = kali::$auth->user;

        $this->mo_type = mod_record_type_search::$mo_type;  //公用经营信息类型

        $this->object_type = mod_record_type_search::$object_type;  //APP类别

        $this->att_pro_type = mod_record_type_search::$att_pro_type;  //关注项目类别 多了公司跟app

        tpl::assign('att_pro_type', $this->att_pro_type);

        tpl::assign('object_type', $this->object_type);

        tpl::assign('mo_type', $this->mo_type);

    }

    /**
     * 增加一个关注项目
     */
    public function add()
    {
        $posts = req::$posts;
        $is_head = req::item('is_head');
        //需入表的数组
        $insert_data = [];
        if (!empty($posts)) {
            $insert_data['id'] = util::random('web'); //生成主表唯一id，客户端不暴露明文id给客户看，提高安全性
            //公司名称选择未知时应把app作为表头传递
            if (!empty($is_head) == '1') {
                if (empty($posts['app_type'][0]))
                {
                    cls_msgbox::show('系统提示', "请选择App类型", -1);
                }

                if(empty($posts['app_name'][0]))
                {
                    cls_msgbox::show('系统提示', "请填写App名称", -1);
                }
                if (cls_validate::instance()->maxlength($posts['app_name'][0], 50) === false) {
                    cls_msgbox::show('系统提示', "名称不能大于50个字", -1);
                }
                $rows = db::select("id")
                        ->from(pub_mod_table::ATT_PRO)
                        ->where("cname",$posts['app_name'][0])
                        ->and_where("head_app_type",'!=',$posts['app_type'][0])
                        ->and_where("member_id",'=',$this->userinfo['uid'])
                        ->as_row()
                        ->execute();
                if(!empty($rows))
                {
                    cls_msgbox::show('系统提示', "App名称已存在，请更换其他名称", -1);
                }
                $app_content_info['content'] = $posts['app_name'][0];
                $app_content_info['type'] = 17; //app
                $app_content_info['infor_type'] = 9; //涉及的项目类型为app
                $app_content_info['infor_table_id'] = $insert_data['id'];
                mod_pro::add_vlist($app_content_info); //插入预警表
                $insert_data['cname']         = $posts['app_name'][0];//$posts['app_name'][0];
                $insert_data['head_app_type'] = $posts['app_type'][0];

            } else {
                //公司名称确定时，公司名称作为表头传值
                if (cls_validate::instance()->required($posts['cname']) == false) {
                    cls_msgbox::show('系统提示', "公司名称不能为空", -1);
                }

                if (cls_validate::instance()->maxlength($posts['cname'], 50) === false) {
                    cls_msgbox::show('系统提示', "公司名称不能超过50个字符", -1);
                }

                $insert_data['cname'] = $posts['cname'];
                $cname_info['content'] = $insert_data['cname'];
                $cname_info['infor_type'] = 8;  //涉及的项目类型为公司名称
                $cname_info['type'] = 19;  //tmd非得分个公司类型出来（这里的类型是在mod_record_type_search::$client_type上提取的）
                $cname_info['infor_table_id'] = $insert_data['id'];
                mod_pro::add_vlist($cname_info);  //插入预警表
                //同时需插入app名称
                $app_info = [];
                if (!empty($posts['app_type'][0]) && !empty($posts['app_name'][0])) {
                    foreach ($posts['app_type'] as $app_k => $app_v) {
                        if(!empty($app_v) && !empty($posts['app_name'][$app_k]))
                        {
                            $app_info[$app_k]['app_key'] = !empty($app_v) ? $app_v : 0;
                            $app_info[$app_k]['app_name'] = $posts['app_name'][$app_k];
                            $app_content_info['content'] = $posts['app_name'][$app_k];
                            $app_content_info['type'] = 17;
                            $app_content_info['pro_app_type'] = $app_v;
                            $app_content_info['infor_type'] = 9;
                            $app_content_info['infor_table_id'] = $insert_data['id'];
                            mod_pro::add_vlist($app_content_info); //插入预警表
                        }
                    }
                }
                $app_json = json_encode($app_info, JSON_UNESCAPED_UNICODE);
            }


            //格式化附加信息数组，方便处理编辑时的数据

            $extra_info = [];
            if (!empty($posts['extra_content'][0]) && !empty($posts['extra_type'][0])) {
                foreach ($posts['extra_type'] as $kk => $vv) {
                    if(!empty($vv) && !empty($posts['extra_content'][$kk]))
                    {
                        $extra_info[$kk]['type'] = !empty($vv) ? $vv : 0;
                        $extra_info[$kk]['content'] = $posts['extra_content'][$kk];
                        //插入预警表
                        $extra_content_info['content'] = $posts['extra_content'][$kk];
                        $extra_content_info['type'] = $vv;
                        $extra_content_info['infor_type'] = 10; //涉及的项目类型为附加信息
                        $extra_content_info['infor_table_id'] = $insert_data['id'];
                        mod_pro::add_vlist($extra_content_info); //插入预警表
                    }
                }
            }

            //数据不完整就不插入了
            $extra_json = !empty($extra_info) ? json_encode($extra_info, JSON_UNESCAPED_UNICODE) : '';
            $app_json = !empty($app_info) ? json_encode($app_info, JSON_UNESCAPED_UNICODE) : '';
            $insert_data['app_info'] = $app_json;
            $insert_data['extra_info'] = $extra_json;
            $insert_data['is_head'] = !empty($posts['is_head']) ? $posts['is_head'] : 0;//time();
            //获取创建人信息；
            $insert_data['create_user'] = $this->userinfo['uid'];
            $insert_data['create_time'] = time();
            $insert_data['member_id'] = $this->userinfo['uid'];  //有可能涉及权限问题，这里多加一个member_id
            $list = db::insert(pub_mod_table::ATT_PRO)->set($insert_data)->execute();
            if ($list > 0) {

                cls_msgbox::show('系统提示', "成功添加关注项目", "?ct=att_pro&ac=add");
            }

        }

        tpl::display('att_pro_add.tpl');

    }

    /**
     * 查看关注项目列表
     */
    public function index()
    {
        $cname = req::item('cname', '');
        $is_monitor = req::item('is_monitor', '');
        $is_shield = req::item('is_shield', '');
        $is_case = req::item('is_case', '');
        $order_by = req::item('orderBy', 'create_time');
        $sort = req::item('sort', 'desc');
        $where[] = ['delete_time', '=', 0];  //默认where
        //父级权限或旗下的子级权限才能看到数据
        if ($this->userinfo['parent_id'] == '0') {
            $uid = mod_common::get_parent_uid($this->userinfo['uid']);
            $where[] = ['member_id', 'in', $uid];

        } elseif ($this->userinfo['parent_id'] != '0' && $this->userinfo['iswhole'] == '1') {  //拥有跟母账号一样的权限
            $uid = mod_common::get_user_uid($this->userinfo['parent_id']);
            $where[] = ['member_id', 'in', $uid];
        } else {
            $where[] = ['member_id', '=', $this->userinfo['uid']];
        }
        if (!empty($cname)) {
            $where[] = ['cname', 'like', "%$cname%"];
        }
        //是否监控
        if (!empty($is_monitor)) {
            $where[] = ['is_monitor', '=', "$is_monitor"];
        }
        //是否屏蔽信息
        if (!empty($is_shield)) {
            $where[] = ['shield_count', '=', "$is_shield"];
        }
        //是否涉案
        if ($is_case==1) {
            $where[] = ['case_count', '=', 0];
        }elseif($is_case==2)
        {
            $where[] = ['case_count', '>', 0];
        }
        //检测监控状态

        //分页
        $row = db::select('Count(*) AS `count`')
            ->from(pub_mod_table::ATT_PRO)
            ->where($where)
            ->as_row()
            ->execute();
        $data['pages'] = cls_page::make($row['count'], 10);
        //显示所有项目结果；
        $data['list'] = db::select("id,cname,app_info,is_head,extra_info,is_monitor,case_count,shield_count,member_id,create_time")
            ->from(pub_mod_table::ATT_PRO)
            ->where($where)
            ->limit($data['pages']['page_size'])
            ->offset($data['pages']['offset'])
            ->order_by($order_by, $sort)
            ->execute();
        if(!empty($data['list']))
        {
            foreach($data['list'] as $v)
            {
                self::check_monitor($v['id']);
            }
        }
        tpl::assign('data', $data);
        tpl::assign('is_case', $is_case);
        tpl::assign('is_shield', $is_shield);
        tpl::assign('is_monitor', $is_monitor);
        tpl::display('att_pro_index.tpl');

    }

    /**
     *查看单条关注项目详情
     */
    public function detail()
    {
        $id = req::get('id');
        $show = req::get('show');
        $rows = db::select()->from(pub_mod_table::ATT_PRO)->where('id', '=', $id)->as_row()->execute();
//        echo "<pre />";
//        print_r($rows);
        //默认条件，检测是否涉案
        $where[] = [pub_mod_table::WARNING_INFO . '.member_id', '=', $this->userinfo['uid']];
        $where[] = [pub_mod_table::WARNING_INFO . '.is_pro', '=', 1];
        $where[] = [pub_mod_table::WARNING_INFO . '.infor_table_id', '=', $id];
        //直接获取预警表关联数据，判断是否被监控
        $case_count = db::select("Count(*) as count")
            ->from(pub_mod_table::WARNING_INFO)
            ->join(pub_mod_table::CASE)
            ->on(pub_mod_table::WARNING_INFO . '.public_case', '=', pub_mod_table::CASE . '.id')
            ->where($where)
            ->as_row()
            ->execute();
        $rows['case_count'] = $case_count['count']>0?1:0;
        if ($rows['is_head'] != 1) {
            //is_head==1才有app信息
            $app_list = db::select("content,type,id,monitor_start")
                ->from(pub_mod_table::TARGET_VIGILANT)
                ->where("infor_table_id", "=", $id)
                ->and_where("infor_type", "=", 9)
                ->execute();
            //好像不太科学
            if (!empty($app_list)) {
                $json_app_arr = json_decode($rows['app_info'], true);

                foreach ($app_list as $k => $v) {
                    $app_list[$k]['app_key'] = $json_app_arr[$k]['app_key'];
                }
                $rows['app_info'] = $app_list;
            } else {
                $rows['app_info'] = '';
            }
            $company_info = db::select("content,type,id,monitor_start")
                ->from(pub_mod_table::TARGET_VIGILANT)
                ->where("infor_table_id", "=", $id)
                ->and_where("infor_type", "=", 8)
                ->as_row()
                ->execute();
            if(!empty($company_info))
            {
                $rows['company_info'] = $company_info;
            }else{
                $rows['company_info'] = '';
            }
        }else{
            //有且只有一条App信息
            $app_one = db::select("content,type,id,monitor_start")
                ->from(pub_mod_table::TARGET_VIGILANT)
                ->where("infor_table_id", "=", $id)
                ->and_where("infor_type", "=", 9)
                ->as_row()
                ->execute();
            if(!empty($app_one))
            {
                $rows['app_one'] = $app_one;
            }else{
                $rows['app_one'] = '';
            }
        }
        //附加信息部份
        $extra_list = db::select("content,type,id,monitor_start")
            ->from(pub_mod_table::TARGET_VIGILANT)
            ->where("infor_table_id", "=", $id)
            ->and_where("infor_type", "=", 10)
            ->execute();

        //附加信息
        $rows['extra_info'] = $extra_list;

        tpl::assign('rows', $rows);


        if ($show == 1) {
            $search['type'] = req::item('type');
            $search['content'] = req::item('content');


            //echo $id;
            //匹配信息类型
            if (!empty($search['type'])) {
                $where[] = [pub_mod_table::WARNING_INFO . '.type', '=', $search['type']];
            }

            if (!empty($search['content'])) {
                $where[] = [pub_mod_table::WARNING_INFO . '.content', 'like', "%$search[content]%"];
            }
            //本人的关注项目的预警信息

            $count = db::select("Count(*) as count")
                ->from(pub_mod_table::WARNING_INFO)
                ->join(pub_mod_table::CASE)
                ->on(pub_mod_table::WARNING_INFO . '.public_case', '=', pub_mod_table::CASE . '.id')
                ->where($where)
                ->as_row()
                ->execute();
            $pages = cls_page::make($count['count'], 20);
            //预警规则出来的关注信息
            $warn_list['list'] = db::select(
                "
                        cm_warning_info.content,cm_warning_info.public_case,cm_case.name,cm_warning_info.target_type,cm_case.id,
                        cm_warning_info.time,cm_case.casetype,cm_case.case_des,cm_warning_info.type,cm_warning_info.infor_table_id
                        "
            )
                ->from(pub_mod_table::WARNING_INFO)
                ->join(pub_mod_table::CASE)
                ->on(pub_mod_table::WARNING_INFO . '.public_case', '=', pub_mod_table::CASE . '.id')
                ->where($where)
                ->order_by('cm_warning_info.time','desc')
                ->limit($pages['page_size'])
                ->offset($pages['offset'])
                ->execute();
            tpl::assign('warn_list', $warn_list);
            tpl::assign('pages', $pages);
            tpl::assign('search', $search);
            tpl::display('att_pro_warn.tpl');
        } else {

            tpl::display('att_pro_detail.tpl');
        }


    }

    /**
     * 批量暂停监控/开始监控
     */
    public function stop_monitor()
    {
        $ids = req::item('ids', 0);
        $is_monitor = req::item('is_monitor', '');
        $is_all = req::item('all',0);
        if($is_all==1)
        {
            $sta = req::item('sta','');
            $id = req::item('id');
            if($sta==-1)
            {
                $res1 = db::update(pub_mod_table::ATT_PRO)->set(['is_monitor' => -1])->where('id', $id)->execute();
                $res2 = db::update(pub_mod_table::TARGET_VIGILANT)->set(['monitor_start' => 1])->where('infor_table_id', $id)->execute();
                cls_msgbox::show('系统提示', "已暂停监控此项目", -1);
            }elseif($sta==1)
            {
                $res1 = db::update(pub_mod_table::ATT_PRO)->set(['is_monitor' => 1])->where('id', $id)->execute();
                $res2 = db::update(pub_mod_table::TARGET_VIGILANT)->set(['monitor_start' => 0])->where('infor_table_id', $id)->execute();
                cls_msgbox::show('系统提示', "已开始监控此项目", -1);
            }else{
                cls_msgbox::show('系统提示', "操作错误");
            }


        }
        if (empty($ids)) {
            cls_msgbox::show('系统提示', "请至少选择一个选项提交", -1);
        }
        $msg = $is_monitor == '1' ? '成功批量监控' : '已批量停止监控';
        foreach ($ids as $k => $v) {
            //开始监控
            if ($is_monitor == '1') {
                //主表
                $res1 = db::update(pub_mod_table::ATT_PRO)->set(['is_monitor' => 1])->where('id', $v)->execute();
                $res2 = db::update(pub_mod_table::TARGET_VIGILANT)->set(['monitor_start' => 0])->where('infor_table_id', $v)->execute();

            } elseif ($is_monitor == '-1') //停止监控
            {
                $res1 = db::update(pub_mod_table::ATT_PRO)->set(['is_monitor' => -1])->where('id', $v)->execute();
                $res2 = db::update(pub_mod_table::TARGET_VIGILANT)->set(['monitor_start' => 1])->where('infor_table_id', $v)->execute();

            }
        }

            cls_msgbox::show('系统提示', $msg, -1);


    }
    /**
     *修改关项目信息
     */
    public function edit()
    {
        //获取显示到页面修改的数据；
        $posts = req::$posts;
        $id = req::item('id');
        $rows = db::select()->from(pub_mod_table::ATT_PRO)->where('id', '=', $id)->as_row()->execute();
        //需要修改的数据；
        $update_data = [];
        //$update_data['cname'] = !empty($posts['cname']) ? $posts['cname'] : '';
        if (!empty($posts)) {
            //APP名称信息；
            $app_info = [];
            if ($rows['is_head'] == 1) {
                //输入的是app名称
                if (!empty($posts['cname'])) {
                    //删除之前的数据再插入
                    $res = db::delete(pub_mod_table::TARGET_VIGILANT)
                        ->where('infor_table_id', '=', $id)
                        ->and_where('member_id', '=', $rows['member_id'])
                        //->and_where('infor_type', '=', 9)
                        //->and_where('type', '=', 17)
                        //->and_where('is_pro', '=', 1)
                        ->execute();
                    //需要修改公司名称
                    $update_data['cname'] = $posts['cname'];  //有且只有一个
                    $update_data['head_app_type'] = $posts['app_type'][0];  //有且只有一个
                    $cname_info['content'] = $update_data['cname'];
                    $cname_info['infor_type'] = 9;  //涉及的项目类型为公司名称(这个是app类型)
                    $cname_info['type'] = 19;  //tmd非得分个公司类型出来（这里的类型是在mod_record_type_search::$client_type上提取的）
                    $cname_info['infor_table_id'] = $rows['id'];//$update_data['id'];
                    mod_pro::add_vlist($cname_info);  //插入预警表
                }
            } else {
                //删除所有再执行插入
                $res = db::delete(pub_mod_table::TARGET_VIGILANT)
                    ->where('infor_table_id', '=', $id)
                    ->and_where('member_id', '=', $rows['member_id'])
                    //->and_where('infor_type', '=', 8)
                    //->and_where('is_pro', '=', 1)
                    ->execute();
                //exit;
                //需要修改公司名称
                $update_data['cname'] = $posts['cname'];
                $cname_info['content'] = $update_data['cname'];
                $cname_info['infor_type'] = 8;  //涉及的项目类型为公司名称
                $cname_info['type'] = 19;  //tmd非得分个公司类型出来（这里的类型是在mod_record_type_search::$client_type上提取的）
                $cname_info['infor_table_id'] = $rows['id'];//$update_data['id'];
                mod_pro::add_vlist($cname_info);  //插入预警表
                //app
                if (!empty($posts['app_type'][0]) && !empty($posts['app_name'][0])) {

                    foreach ($posts['app_type'] as $app_k => $app_v) {
                        if(!empty($app_v) && !empty($posts['app_name'][$app_k]))
                        {
                            $app_info[$app_k]['app_key'] = !empty($app_v) ? $app_v : 0;
                            $app_info[$app_k]['app_name'] = $posts['app_name'][$app_k];
                            $app_content_info['type'] = 17;
                            $app_content_info['infor_type'] = 9;
                            $app_content_info['pro_app_type'] = $app_v;
                            $app_content_info['content'] = $posts['app_name'][$app_k];
                            $app_content_info['member_id'] = $rows ['member_id'];
                            $app_content_info['infor_table_id'] = $id;
                            $res = mod_pro::add_vlist($app_content_info); //修改预警表
                        }
                    }
                }
            }

            //附加信息
            $extra_info = [];

            if (!empty($posts['extra_type'][0]) && !empty($posts['extra_content'][0])) {

                foreach ($posts['extra_type'] as $kk => $vv) {
                    if(!empty($vv) && !empty($posts['extra_content'][$kk])) {
                        $extra_info[$kk]['type'] = !empty($vv) ? $vv : '';
                        $extra_info[$kk]['content'] = $posts['extra_content'][$kk];
                        $cname_info['infor_type'] = 10;  //涉及的项目类型为公司名称
                        $cname_info['type'] = $vv;
                        $cname_info['infor_table_id'] = $id;
                        $cname_info['content'] = $posts['extra_content'][$kk];
                        //$cname_info['member_id'] = $rows ['member_id'];
                        mod_pro::add_vlist($cname_info);  //修改预警表
                    }
                }

            }


            //针对不完整的数据不给予修改；
            $app_json = !empty($app_info) ? json_encode($app_info, JSON_UNESCAPED_UNICODE) : '';
            $extra_json = !empty($extra_info) ? json_encode($extra_info, JSON_UNESCAPED_UNICODE) : '';
            $update_data['app_info'] = $app_json;
            $update_data['extra_info'] = $extra_json;
            //获取创建人信息；
            $update_data['create_user'] = $this->userinfo['uid'];
            //更新修改时间
            $update_data['update_time'] = time();
            $update_data['member_id'] = $this->userinfo['uid'];  //有可能涉及权限问题，这里多加一个member_id
            //更新数据
            $row = db::update(pub_mod_table::ATT_PRO)->set($update_data)->where('id', '=', $id)->execute();
            if ($row > 0) {
                cls_msgbox::show('系统提示', "成功修改关注项目", '?ct=att_pro&ac=detail&id='.$id);
            }

        }

        $rows['app_info'] = json_decode($rows['app_info'], true);
        //pub_mod_func::pr($list['app_info']);
        $rows['extra_info'] = json_decode($rows['extra_info'], true);


        tpl::assign('rows', $rows);
        tpl::display('att_pro_edit.tpl');

    }


    /**
     * 暂停/开始监控
     **/
    public function monitor_start()
    {
        $sta = req::item('sta', '');
        $id = req::item('id', '');
        if ($sta == 1) {
            //停止监控
            $msg = '已停止监控';

        } elseif ($sta == 0) {
            //开始监控
            $msg = '开始监控';
        }
        $res = db::update(pub_mod_table::TARGET_VIGILANT)->set(['monitor_start' => $sta])->where('id', $id)->execute();
        if ($res) {
            cls_msgbox::show('系统提示', $msg, -1);
        } else {
            cls_msgbox::show('系统提示', '执行失败', -1);
        }

    }

    /**
     * 检测是否有监控的单条信息
     */
    public static function check_monitor($id)
    {
        //查询是否存在有监控的数据
        $data = db::select("id")
                ->from(pub_mod_table::TARGET_VIGILANT)
                ->where("infor_table_id",$id)
                ->and_where("monitor_start",0)
                ->and_where("is_pro",1)
                ->execute();
        //有则修改监控状态
        if(!empty($data))
        {
            db::update(pub_mod_table::ATT_PRO)->set(['is_monitor'=>1])->where(["id"=>$id])->execute();
        }else{
            db::update(pub_mod_table::ATT_PRO)->set(['is_monitor'=>-1])->where(["id"=>$id])->execute();
        }

    }

    /**
     *新增动态关注项目列表；
     */
    public function dynamic()
    {
        $search['type'] = req::item('type');
        $search['content'] = req::item('content');
        $search['cname'] = req::item('cname');
        $search['case_id'] = req::item('case_id');
        //默认搜索条件
        $where[] = [pub_mod_table::WARNING_INFO . '.is_pro', '=', 1];
        //父级权限或旗下的子级权限才能看到数据
        if ($this->userinfo['parent_id'] == '0') {
            $uid = mod_common::get_parent_uid($this->userinfo['uid']);
            $where[] = [pub_mod_table::WARNING_INFO . '.member_id', 'in', $uid];

        } elseif ($this->userinfo['parent_id'] != '0' && $this->userinfo['iswhole'] == '1') {  //拥有跟母账号一样的权限
            $uid = mod_common::get_user_uid($this->userinfo['parent_id']);
            $where[] = [pub_mod_table::WARNING_INFO . '.member_id', 'in', $uid];
        } else {
            $where[] = [pub_mod_table::WARNING_INFO . '.member_id', '=', $this->userinfo['uid']];
        }
        //搜索实例编号
        if(!empty($search['case_id']))
        {
            $real_id = ltrim($search['case_id'], 'C') - 10000;
            $where[] = [pub_mod_table::CASE.'.id', "=", $real_id];
        }
        //搜索信息类型
        if (!empty($search['type'])) {
            $where[] = [pub_mod_table::WARNING_INFO . '.type', '=', $search['type']];
        }
        //关注项目
        if(!empty($search['cname']))
        {
            $where[] = [pub_mod_table::ATT_PRO.'.cname','like',"%$search[cname]%"];
        }

        if (!empty($search['content'])) {
            $where[] = [pub_mod_table::WARNING_INFO . '.content', 'like', "%$search[content]%"];
        }
        //所有关注项目的预警信息
        $count = db::select("Count(*) as count")
            ->from(pub_mod_table::WARNING_INFO)
            ->join(pub_mod_table::CASE)
            ->on(pub_mod_table::WARNING_INFO . '.public_case', '=', pub_mod_table::CASE . '.id')
            ->join(pub_mod_table::ATT_PRO)
            ->on(pub_mod_table::WARNING_INFO . '.infor_table_id', '=', pub_mod_table::ATT_PRO . '.id')
            ->where($where)
            ->as_row()
            ->execute();
        $pages = cls_page::make($count['count'], 20);
        //预警规则出来的关注信息(多条数据看起来一样实际上匹配到的是不同的实例来的，所以不涉及去从的问题)
        $warn_list['list'] = db::select(
            "cm_warning_info.content,cm_warning_info.public_case,cm_case.name,cm_warning_info.target_type,cm_case.id,
                    cm_warning_info.time,cm_case.casetype,cm_case.case_des,cm_warning_info.type,cm_warning_info.infor_table_id,
                    cm_att_pro.cname,cm_warning_info.is_read,cm_warning_info.id
                   "
        )
            ->from(pub_mod_table::WARNING_INFO)
            ->join(pub_mod_table::CASE)
            ->on(pub_mod_table::WARNING_INFO . '.public_case', '=', pub_mod_table::CASE . '.id')
            ->join(pub_mod_table::ATT_PRO)
            ->on(pub_mod_table::WARNING_INFO . '.infor_table_id', '=', pub_mod_table::ATT_PRO . '.id')
            ->where($where)
            ->limit($pages['page_size'])
            ->offset($pages['offset'])
            ->execute();
        tpl::assign('warn_list', $warn_list);
        tpl::assign('pages', $pages);
        tpl::assign('search', $search);
        tpl::display('att_pro_dynamic.tpl');

    }
    //ajax修改是否已阅状态
    public function ajax_is_read()
    {
        $id = req::item('id',0);
        if(empty($id)) exit("empty id");
        db::update(pub_mod_table::WARNING_INFO)->set(['is_read'=>1])->where('id',$id)->execute();
        //先任何情况都返回1给前端
        echo 1;
        exit;
    }
}