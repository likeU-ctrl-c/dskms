<?php

namespace app\admin\controller;
use think\facade\View;
use think\facade\Lang;
/**
 * ============================================================================
 * DSKMS多用户商城
 * ============================================================================
 * 版权所有 2014-2028 长沙德尚网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.csdeshang.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 控制器
 */
class Feedback extends AdminControl
{
    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        Lang::load(base_path() . 'admin/lang/'.config('lang.default_lang').'/feedback.lang.php');
    }
    /**
     * 意见反馈
     */
    public function flist(){
        $feedback_model = model('feedback');
        $feedback_list = $feedback_model->getFeedbackList(array(), 10);

       View::assign('feedback_list', $feedback_list);
       View::assign('show_page', $feedback_model->page_info->render());
       $this->setAdminCurItem('index');
       return View::fetch('index');
    }

    /**
     * 删除
     */
    public function del(){
        $feedback_model = model('feedback');
        $feedback_id = input('param.feedback_id');
        $feedback_id_array = ds_delete_param($feedback_id);
        $condition = array();
        $condition[] = array('fb_id','in', $feedback_id_array);
        $result = $feedback_model->delFeedback($condition);
        if ($result){
            ds_json_encode(10000, lang('ds_common_op_succ'));
        }else {
            ds_json_encode(10001, lang('ds_common_op_fail'));
        }
    }
    protected function getAdminItemList() {
        $menu = array(
            array(
                'text' => lang('ds_feedback'), 'name' => 'index', 'url' => ''
            ),
        );
        return $menu;
    }
}