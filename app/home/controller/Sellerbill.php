<?php

namespace app\home\controller;
use think\facade\View;
use think\facade\Db;
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
class  Sellerbill extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/sellerbill.lang.php');
    }

    /**
     * 结算列表
     *
     */
    public function index() {
        $bill_model = model('bill');
        $condition = array();
        $condition[] = array('ob_store_id','=',session('store_id'));

        $ob_no = input('param.ob_no');
        if (preg_match('/^20\d{5,12}$/', $ob_no)) {
            $condition[] = array('ob_no','=',$ob_no);
        }
        $bill_state = intval(input('bill_state'));
        if ($bill_state) {
            $condition[] = array('ob_state','=',$bill_state);
        }

        
        $bill_list = $bill_model->getOrderbillList($condition, '*', 12, 'ob_state asc,ob_no asc');
        View::assign('bill_list', $bill_list);
        View::assign('show_page', $bill_model->page_info->render());

        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('Sellerbill');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('seller_slide');
        return View::fetch($this->template_dir.'index');
    }

    /**
     * 查看结算单详细
     *
     */
    public function show_bill() {
        $ob_no = input('param.ob_no');
        if (!$ob_no) {
            $this->error('参数错误');
        }
        
        $bill_model = model('bill');
        $bill_info = $bill_model->getOrderbillInfo(array('ob_no' => $ob_no,'ob_store_id'=>session('store_id')));
        if (!$bill_info) {
            $this->error('参数错误');
        }
        
        $order_condition = array();
        $order_condition[] = array('ob_no','=',$ob_no);
        $order_condition[] = array('order_state','=',ORDER_STATE_SUCCESS);
        $order_condition[] = array('store_id','=',$bill_info['ob_store_id']);

        $query_start_date = input('get.query_start_date');
        $query_end_date = input('get.query_end_date');
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_start_date);
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_end_date);
        $start_unixtime = $if_start_date ? strtotime($query_start_date) : null;
        $end_unixtime = $if_end_date ? (strtotime($query_end_date)+86399) : null;
        if ($if_start_date) {
            if($if_start_date){
                $order_condition[] = array('finnshed_time','>=', $start_unixtime);
            }
        }
        if ($if_end_date) {
            if($if_end_date){
                $order_condition[] = array('finnshed_time','<=', $end_unixtime);
            }
        }
        $query_order_no = input('get.query_order_no');
        $type = input('param.type');
        if ($type == 'cost') {
            //机构费用
            $storecost_model = model('storecost');
            $cost_condition = array();
            $cost_condition[] = array('storecost_store_id','=',$bill_info['ob_store_id']);
            $cost_condition[] = array('storecost_time','between',[$bill_info['ob_startdate'],$bill_info['ob_enddate']]);

            $store_cost_list = $storecost_model->getStorecostList($cost_condition, 20);

            //取得机构名字
            $store_info = model('store')->getStoreInfoByID($bill_info['ob_store_id']);
            View::assign('cost_list', $store_cost_list);
            View::assign('store_info', $store_info);
            View::assign('show_page', $storecost_model->page_info->render());
            
            $sub_tpl_name = 'show_cost_list';
            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('Sellerbill');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('seller_slide');
        } else{
            if (preg_match('/^\d{8,20}$/', $query_order_no)) {
                $order_condition[] = array('order_sn','=',$query_order_no);
            }
            $vrorder_model = model('vrorder');
            $order_list = $vrorder_model->getVrorderList($order_condition, 20,'SUM(ROUND(order_amount*commis_rate/100,2)) AS commis_amount,SUM(ROUND(refund_amount*commis_rate/100,2)) AS return_commis_amount,order_amount,refund_amount,order_sn,buyer_name,add_time,finnshed_time,order_id');
            foreach($order_list as $key => $val){
                if(!$val['order_id']){
                    $order_list=array();
                    break;
                }
                //分销佣金
                $inviter_info=Db::name('orderinviter')->where(array('orderinviter_order_id' => $key, 'orderinviter_valid' => 1, 'orderinviter_order_type' => 1))->field('SUM(orderinviter_money) AS ob_inviter_totals')->find();
                $order_list[$key]['inviter_amount']= ds_price_format($inviter_info['ob_inviter_totals']);
            }
            View::assign('order_list', $order_list);
            View::assign('show_page', $vrorder_model->page_info->render());

            $sub_tpl_name = 'show_vrorder_list';
            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('Sellerbill');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('seller_slide');
        }
        View::assign('bill_info', $bill_info);

        return View::fetch($this->template_dir.$sub_tpl_name);
    }

    
    /**
     * 打印结算单
     *
     */
    public function bill_print() {
        $ob_no = input('param.ob_no');
        if (!$ob_no) {
            $this->error('参数错误');
        }

        $bill_model = model('bill');
        $condition = array();
        $condition[] = array('ob_no','=',$ob_no);
        $condition[] = array('ob_store_id','=',session('store_id'));
        $condition[] = array('ob_state','=',BILL_STATE_SUCCESS);
        $bill_info = $bill_model->getOrderbillInfo($condition);
        if (!$bill_info) {
            $this->error('参数错误');
        }

        View::assign('bill_info', $bill_info);
        return View::fetch($this->template_dir.'bill_print');
    }

    /**
     * 机构确认出账单
     *
     */
    public function confirm_bill() {
            $ob_no = input('param.ob_no');
            if (!$ob_no) {
                ds_json_encode(10001,lang('param_error'));
            }
            $bill_model = model('bill');
            $condition = array();
            $condition[] = array('ob_no','=',$ob_no);
            $condition[] = array('ob_store_id','=',session('store_id'));
            $condition[] = array('ob_state','=',BILL_STATE_CREATE);
            $bill_info=$bill_model->getOrderbillInfo($condition);
            if(!$bill_info){
                ds_json_encode(10001,lang('bill_is_not_exist'));
            }
        if(request()->isPost()){
            $update = $bill_model->editOrderbill(array('ob_state' => BILL_STATE_STORE_COFIRM,'ob_seller_content'=>input('post.ob_seller_content')), $condition);
            if ($update) {
                ds_json_encode(10000,lang('ds_common_op_succ'));
            } else {
                ds_json_encode(10001,lang('ds_common_op_fail'));
            }
        }else{
            
            return View::fetch($this->template_dir.'bill_confirm');
        }
    }



    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_type 导航类型
     * @param string $menu_key 当前导航的menu_key
     * @return
     */
    function getSellerItemList() {
        $ob_no = input('param.ob_no');
        if (request()->action()=='index') {
            $menu_array = array(
                array(
                    'name' => 'list',
                    'text' => lang('physical_settlement'),
                    'url' => url('Sellerbill/index')
                ),
            );
        }else if(request()->action()=='show_bill'){
            $menu_array = array(
                array(
                    'name' => 'order_list',
                    'text' => '订单列表',
                    'url' => url('Sellerbill/show_bill', ['ob_no' => $ob_no])
                ),
                array(
                    'name' => 'vrorder_list',
                    'text' => '虚拟订单列表',
                    'url' => url('Sellerbill/show_bill', ['type'=>'vrorder','ob_no' => $ob_no])
                ),
                array(
                    'name' => 'cost_list',
                    'text' => '促销费用',
                    'url' => url('Sellerbill/show_bill', ['type'=>'cost','ob_no' => $ob_no])
                ),
                
            );
        }
        return $menu_array;
    }

}

?>
