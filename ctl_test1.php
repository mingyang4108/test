<?php
/**
 * 框架使用方式
 * 1.定义namespace
 * 2.use 用到的类
 */
namespace app\control;
use kali\core\kali; //框架核心文件类
use kali\core\db;   //数据库操作类
use kali\core\tpl;  //模版类

/**
 * 路由格式为?ct=test1&ax=xxx,定义一个control文件格式为ctl_xxxx.php 那么类名也应该为ctl_xxxx
 * ac为xxx
 * Class ctl_test1
 * @package app\control*/
class ctl_test1
{
    //简单数据列表实现(这时访问路由的方式为?ct=test1&ac=index)
    public function index()
    {
        //tpl模版输出(具体使用方式参考文档)

        $arr[] = array(
            'name'=>'name',
            'sex'=>'男',
            'sta'=>'1',
            'nickname'=>'阿里巴巴',
        );
        //传递变量
        tpl::assign('list',$arr);
        //输出template下面的模版(先在template下面建立一个文件)
        tpl::display('test1.index.tpl');
    }


    //简单数据列表实现(这时访问路由的方式为?ct=test1&ac=index)
    public function edit()
    {
        //tpl模版输出(具体使用方式参考文档)

        $arr[] = array(
            'name'=>'name',
            'sex'=>'男',
            'sta'=>'1',
            'nickname'=>'阿里巴巴',
        );
        //传递变量
        tpl::assign('list',$arr);
        //输出template下面的模版(先在template下面建立一个文件)
        tpl::display('test1.index.tpl');
    }

}

