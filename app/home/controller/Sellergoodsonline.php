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
class  Sellergoodsonline extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/sellergoodsadd.lang.php');
        $this->template_dir = 'default/seller/sellergoodsadd/';
    }

    public function index() {
        $this->goods_list();
    }

    /**
     * 出售中的商品列表
     */
    public function goods_list() {
        $goods_model = model('goods');

        $condition = array();
        $condition[]=array('store_id','=',session('store_id'));

        $storegc_id = intval(input('get.storegc_id'));
        if ($storegc_id > 0) {
            $condition[]=array('goods_stcids','like', '%,' . $storegc_id . ',%');
        }
        $keyword = trim(input('get.keyword'));
        $search_type = trim(input('get.search_type'));
        if (trim($keyword) != '') {
            switch ($search_type) {
                case 0:
                    $condition[]=array('goods_name','like', '%' . trim($keyword) . '%');
                    break;
                case 1:
                    $condition[]=array('goods_serial','like','%' . trim($keyword) . '%');
                    break;
                case 2:
                    $condition[]=array('goods_id','=',intval($keyword));
                    break;
            }
        }
        $goods_list = $goods_model->getGoodsOnlineList($condition, '*', 10);
        foreach($goods_list as $key => $val){
            $val['goods_type']=1;
            if($val['goods_lock']){
                $goods_info=$goods_model->getGoodsInfoAndPromotionById($val['goods_id']);
       
                if(isset($goods_info['xianshi_info']) && $goods_info['xianshi_info']){
                    $val['goods_type']=3;
                }
            }
            $goods_list[$key]['goods_type_cn'] = get_order_goodstype($val['goods_type']);
        }
        View::assign('show_page', $goods_model->page_info->render());
        View::assign('goods_list', $goods_list);

        // 商品分类
        $store_goods_class = model('storegoodsclass')->getClassTree(array('store_id' => session('store_id'), 'storegc_state' => '1'));
        View::assign('store_goods_class', $store_goods_class);

        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellergoodsonline');
        $this->setSellerCurItem();
        echo View::fetch($this->template_dir . 'store_goods_list_online');
        exit;
    }

    /**
     * 编辑商品页面
     */
    public function edit_goods() {
        $goods_id = intval(input('param.goods_id'));
        if ($goods_id <= 0) {
            $this->error(lang('param_error'));
        }
        $goods_model = model('goods');
        $goods_info = $goods_model->getGoodsInfoByID($goods_id);
        if (empty($goods_info) || $goods_info['store_id'] != session('store_id') || $goods_info['goods_lock'] == 1) {
            $this->error('您的商品不存在，或商品已被锁定，请联系管理员删除抢购解除锁定');
        }

        $where = array('goods_id' => $goods_id, 'store_id' => session('store_id'));
        if ($goods_info['mobile_body'] != '') {
            $goods_info['mb_body'] = unserialize($goods_info['mobile_body']);
            if (is_array($goods_info['mb_body'])) {
                $mobile_body = '[';
                foreach ($goods_info['mb_body'] as $val) {
                    $mobile_body .= '{"type":"' . $val['type'] . '","value":"' . $val['value'] . '"},';
                }
                $mobile_body = rtrim($mobile_body, ',') . ']';
            }
            $goods_info['mobile_body'] = $mobile_body;
        }
        View::assign('goods', $goods_info);

        $class_id = intval(input('param.class_id'));
        if ($class_id > 0) {
            $goods_info['gc_id'] = $class_id;
        }
        $goods_class = model('goodsclass')->getGoodsclassLineForTag($goods_info['gc_id']);
        View::assign('goods_class', $goods_class);


        // 实例化机构商品分类模型
        $store_goods_class = model('storegoodsclass')->getClassTree(array('store_id' => session('store_id'), 'storegc_state' => '1'));
        View::assign('store_goods_class', $store_goods_class);
        //处理商品所属分类
        $store_goods_class_tmp = array();
        if (!empty($store_goods_class)) {
            foreach ($store_goods_class as $k => $v) {
                $store_goods_class_tmp[$v['storegc_id']] = $v;
                if (isset($v['child'])) {
                    foreach ($v['child'] as $son_k => $son_v) {
                        $store_goods_class_tmp[$son_v['storegc_id']] = $son_v;
                    }
                }
            }
        }
        $goods_info['goods_stcids'] = trim($goods_info['goods_stcids'], ',');
        $goods_stcids = empty($goods_info['goods_stcids']) ? array() : explode(',', $goods_info['goods_stcids']);
        $goods_stcids_tmp = $goods_stcids_new = array();
        if (!empty($goods_stcids)) {
            foreach ($goods_stcids as $k => $v) {
                if (isset($store_goods_class_tmp[$v])) {
                    $storegc_parent_id = $store_goods_class_tmp[$v]['storegc_parent_id'];
                } else {
                    $storegc_parent_id = 0;
                }
                //分类进行分组，构造为array('1'=>array(5,6,8));
                if ($storegc_parent_id > 0) {//如果为二级分类，则分组到父级分类下
                    $goods_stcids_tmp[$storegc_parent_id][] = $v;
                } elseif (empty($goods_stcids_tmp[$v])) {//如果为一级分类而且分组不存在，则建立一个空分组数组
                    $goods_stcids_tmp[$v] = array();
                }
            }
            foreach ($goods_stcids_tmp as $k => $v) {
                if (!empty($v) && count($v) > 0) {
                    $goods_stcids_new = array_merge($goods_stcids_new, $v);
                } else {
                    $goods_stcids_new[] = $k;
                }
            }
        }
        View::assign('store_class_goods', $goods_stcids_new);

        // 关联版式
        $plate_list = model('storeplate')->getStoreplateList(array('store_id' => session('store_id')), 'storeplate_id,storeplate_name,storeplate_position');
        $plate_list = array_under_reset($plate_list, 'storeplate_position', 2);
        View::assign('plate_list', $plate_list);


        View::assign('edit_goods_sign', true);
        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellergoodsonline');
        $this->setSellerCurItem('edit_goods');
        return View::fetch($this->template_dir . 'store_goods_add_step2');
    }

    /**
     * 编辑商品保存
     */
    public function edit_save_goods() {

        $goods_id = intval(input('param.goods_id'));
        if (!request()->isPost() || $goods_id <= 0) {
            ds_json_encode(10001, lang('store_goods_index_goods_edit_fail'));
        }

        $gc_id = intval(input('post.cate_id'));

        // 验证商品分类是否存在且商品分类是否为最后一级
        $data = model('goodsclass')->getGoodsclassForCacheModel();
        if (!isset($data[$gc_id]) || isset($data[$gc_id]['child']) || isset($data[$gc_id]['childchild'])) {
            ds_json_encode(10001, lang('store_goods_index_again_choose_category1'));
        }

        // 三方机构验证是否绑定了该分类
        if (!check_platform_store()) {
            //商品分类 提供批量显示所有分类插件
            $storebindclass_model = model('storebindclass');
            $goods_class = model('goodsclass')->getGoodsclassForCacheModel();
            $condition = array();
            $condition[] = array('store_id', '=', session('store_id'));
            $class_2 = isset($goods_class[$gc_id]['gc_parent_id'])?$goods_class[$gc_id]['gc_parent_id']:0;
            $class_1 = isset($goods_class[$class_2]['gc_parent_id'])?$goods_class[$class_2]['gc_parent_id']:0;
            $condition_class_1 = array(array('class_1', '=', ($class_1 > 0) ? $class_1 : (($class_2 > 0) ? $class_2 : $gc_id)));
            $condition_class_2 = array(array('class_2', '=', ($class_1 > 0) ? $class_2 : (($class_2 > 0) ? $gc_id : 0)));
            $condition_class_3 = array(array('class_3', '=', ($class_1 > 0 && $class_2 > 0) ? $gc_id : 0));
            $bind_info = $storebindclass_model->getStorebindclassInfo(array_merge($condition,$condition_class_1,$condition_class_2,$condition_class_3));
            if (empty($bind_info)) {
                $condition_class_3 = array(array('class_3', '=',0));
                $bind_info = $storebindclass_model->getStorebindclassInfo(array_merge($condition,$condition_class_1,$condition_class_2,$condition_class_3));
                if (empty($bind_info)) {
                    $condition_class_2 = array(array('class_2', '=', 0));
                    $condition_class_3 = array(array('class_3', '=', 0));
                    $bind_info = $storebindclass_model->getStorebindclassInfo(array_merge($condition,$condition_class_1,$condition_class_2,$condition_class_3));
                    if (empty($bind_info)) {
                        $condition_class_1 = array(array('class_1', '=', 0));
                        $condition_class_2 = array(array('class_2', '=', 0));
                        $condition_class_3 = array(array('class_3', '=', 0));
                        $bind_info = $storebindclass_model->getStorebindclassInfo(array_merge($condition,$condition_class_1,$condition_class_2,$condition_class_3));
                        if (empty($bind_info)) {
                            ds_json_encode(10001, lang('store_goods_index_again_choose_category2'));
                        }
                    }
                }
            }
        }
        // 分类信息
        $goods_class = model('goodsclass')->getGoodsclassLineForTag(intval(input('post.cate_id')));
        $goods_model = model('goods');

        $update_common = array();
        $update_common['goods_name'] = input('post.g_name');
        $update_common['goods_advword'] = input('post.g_jingle');
        $update_common['gc_id'] = $gc_id;
        $update_common['gc_id_1'] = isset($goods_class['gc_id_1']) ? intval($goods_class['gc_id_1']) : 0;
        $update_common['gc_id_2'] = isset($goods_class['gc_id_2']) ? intval($goods_class['gc_id_2']) : 0;
        $update_common['gc_id_3'] = isset($goods_class['gc_id_3']) ? intval($goods_class['gc_id_3']) : 0;
        $update_common['gc_name'] = input('post.cate_name');
        $update_common['goods_image'] = input('post.image_path');
        $update_common['goods_bg_image'] = input('post.bg_image_path');
        $update_common['goods_price'] = floatval(input('post.g_price'));
        $update_common['goods_serial'] = input('post.g_serial');
        $goods_body=input('post.goods_body');
            $goods_body=preg_replace_callback("/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i", function ($matches) {
                return strip_tags($matches[2]);
            }, $goods_body);
            $update_common['goods_body'] = $goods_body;
        // 序列化保存手机端商品描述数据
        $mobile_body = input('post.m_body');
        if ($mobile_body != '') {
            $mobile_body = str_replace('&quot;', '"', $mobile_body);
            $mobile_body = json_decode($mobile_body, true);
            if (!empty($mobile_body)) {
                $mobile_body = serialize($mobile_body);
            } else {
                $mobile_body = '';
            }
        }
        $update_common['mobile_body'] = $mobile_body;
        $update_common['goods_commend'] = intval(input('post.g_commend'));
        $update_common['goods_state'] = ($this->store_info['store_state'] != 1) ? 0 : intval(input('post.g_state'));            // 机构关闭时，商品下架
        $update_common['goods_verify'] = (config('ds_config.goods_verify') == 1) ? 10 : 1;
        $update_common['goods_vat'] = intval(input('post.g_vat'));
        $update_common['goods_edittime'] = TIMESTAMP;

        $sellergoodsonline_validate = ds_validate('sellergoodsonline');
        if (!$sellergoodsonline_validate->scene('edit_save_goods')->check($update_common)) {
            ds_json_encode('10001', $sellergoodsonline_validate->getError());
        }

        //查询机构商品分类
        $goods_stcids_arr = array();
        $sgcate_id_array = input('post.sgcate_id/a'); #获取数组
        if (!empty($sgcate_id_array)) {
            $sgcate_id_arr = array();
            foreach ($sgcate_id_array as $k => $v) {
                $sgcate_id_arr[] = intval($v);
            }
            $sgcate_id_arr = array_unique($sgcate_id_arr);
            $condition = array();
            $condition[] = array('store_id','=',session('store_id'));
            $condition[] = array('storegc_id','in',$sgcate_id_arr);
            $condition[] = array('storegc_state','=',1);
            $store_goods_class = model('storegoodsclass')->getStoregoodsclassList($condition);
            if (!empty($store_goods_class)) {
                foreach ($store_goods_class as $k => $v) {
                    if ($v['storegc_id'] > 0) {
                        $goods_stcids_arr[] = $v['storegc_id'];
                    }
                    if ($v['storegc_parent_id'] > 0) {
                        $goods_stcids_arr[] = $v['storegc_parent_id'];
                    }
                }
                $goods_stcids_arr = array_unique($goods_stcids_arr);
                sort($goods_stcids_arr);
            }
        }
        if (empty($goods_stcids_arr)) {
            $update_common['goods_stcids'] = '';
        } else {
            $update_common['goods_stcids'] = ',' . implode(',', $goods_stcids_arr) . ',';
        }
        $update_common['plateid_top'] = intval(input('post.plate_top')) > 0 ? intval(input('post.plate_top')) : '';
        $update_common['plateid_bottom'] = intval(input('post.plate_bottom')) > 0 ? intval(input('post.plate_bottom')) : '';
        $update_common['is_platform_store'] = in_array(session('store_id'), model('store')->getOwnShopIds()) ? 1 : 0;

        // 开始事务
        Db::startTrans();
        try{


        // 商品加入上架队列
        if (!empty(input('post.starttime'))) {
            $selltime = strtotime(input('post.starttime')) + intval(input('post.starttime_H')) * 3600 + intval(input('post.starttime_i')) * 60;
            if ($selltime > TIMESTAMP) {
                $this->addcron(array('cron_exetime' => $selltime, 'cron_value' => serialize(intval($goods_id)), 'cron_type' => 'editProducesOnline'), true);
            }
        }
        // 添加操作日志
        $this->recordSellerlog('编辑商品，平台货号：' . $goods_id);

        $return = $goods_model->editGoods($update_common, array('goods_id' => $goods_id, 'store_id' => session('store_id')));
        } catch (\Exception $e){
            Db::rollback();
            ds_json_encode(10001,$e->getMessage());
        }
        //提交事务
        Db::commit();
        ds_json_encode(10000, lang('ds_common_op_succ'));
    }

    /**
     * 课程列表
     */
    public function list_courses() {
        $goods_id = intval(input('param.goods_id'));
        if ($goods_id <= 0) {
            $this->error(lang('param_error'), url('Seller/index'));
        }
        $goods_model = model('goods');
        $goods = $goods_model->getGoodsInfoByID($goods_id);
        if ($goods['store_id'] != session('store_id') || $goods['goods_lock'] == 1) {
            $this->error(lang('param_error'), url('Seller/index'));
        }

        //获取此商品的课程列表
        $goodscourses_model = model('goodscourses');
        $condition = array();
        $condition[] = array('goods_id','=',$goods_id);
        $goodscourses_list = $goodscourses_model->getGoodscoursesList($condition);
        View::assign('goodscourses_list', $goodscourses_list);

        View::assign('goods_id', $goods_id);

        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellergoodsonline');
        $this->setSellerCurItem('list_courses');
        return View::fetch($this->template_dir . 'store_goods_add_step3');
    }
    
    /*
     * 课程目录
     */
    function course_class_list(){
        $goods_id=intval(input('param.goods_id'));
        if(!$goods_id){
            ds_json_encode(10001, lang('param_error'));
        }
        $goodscourses_class_model=model('goodscourses_class');
        $goodscourses_class_list=$goodscourses_class_model->getGoodscoursesClassList(array(array('goods_id','=',$goods_id),array('store_id','=',$this->store_info['store_id'])));
        View::assign('goodscourses_class_list',$goodscourses_class_list);
        return View::fetch($this->template_dir . 'course_class');
    }
    
    /*
     * 新增课程目录
     */
    function course_class_add(){
        $goodscourses_class_model=model('goodscourses_class');
        $goodscourses_class_name=input('param.goodscourses_class_name');
        $goods_id=intval(input('param.goods_id'));
        if(!$goods_id || !$goodscourses_class_name){
            ds_json_encode(10001, lang('param_error'));
        }
        $goodscourses_class_id=$goodscourses_class_model->addGoodscoursesClass(array(
            'goodscourses_class_name'=>$goodscourses_class_name,
            'goods_id'=>$goods_id,
            'store_id'=>$this->store_info['store_id'],
        ));
        ds_json_encode(10000, lang('ds_common_op_succ'),array('goodscourses_class_id'=>$goodscourses_class_id));
    }
    
    /*
     * 删除课程目录
     */
    function course_class_del(){
        $goodscourses_class_id=intval(input('param.goodscourses_class_id'));
        if(!$goodscourses_class_id){
            ds_json_encode(10001, lang('param_error'));
        }
        $goodscourses_class_model=model('goodscourses_class');
        $goodscourses_class_model->delGoodscoursesClass(array(array('goodscourses_class_id','=',$goodscourses_class_id),array('store_id','=',$this->store_info['store_id'])));
        ds_json_encode(10000, lang('ds_common_op_succ'));
    }
    
    /*
     * 排序课程目录
     */
    function course_class_sort(){
        $sort=input('param.goodscourses_class_list/a');
        if(!$sort){
            ds_json_encode(10001, lang('param_error'));
        }
        $goodscourses_class_model=model('goodscourses_class');
        foreach($sort as $val){
            if(!$val['goodscourses_class_sort'] || !$val['goodscourses_class_id']){
                ds_json_encode(10001, lang('param_error'));
            }
            $goodscourses_class_model->editGoodscoursesClass(array('goodscourses_class_sort'=>$val['goodscourses_class_sort']),array(array('goodscourses_class_id','=',$val['goodscourses_class_id'])));
        }
        ds_json_encode(10000, lang('ds_common_op_succ'));
    }
    /**
     * 添加课程
     */
    function add_courses() {
        $goods_id = intval(input('param.goods_id'));
        $goods_model = model('goods');
        $goods = $goods_model->getGoodsInfoByID($goods_id);
        if (!request()->isPost()) {
            $goodscourses = array(
                'goodscourses_free'=>1
            );
            View::assign('baidu_pan_url', urlencode(str_replace('/index.php', '', HOME_SITE_URL).'/seller_courseware/baidu_pan_auth'));
            View::assign('goodscourses', $goodscourses);
            View::assign('goods', $goods);
            View::assign('if_baidu_pan', $this->store_info['baidu_pan_access_token']?1:0);
            
        
        $condition=array();
        $condition[]=array('store_id','=',$this->store_info['store_id']);
        $condition[]=array('video_type','in',['aliyun','tencent']);
        $total_cloud_space=Db::name('videoupload')->where($condition)->sum('videoupload_size');
        $condition=array();
        $condition[]=array('store_id','=',$this->store_info['store_id']);
        $condition[]=array('video_type','=','local');
        $total_local_space=Db::name('videoupload')->where($condition)->sum('videoupload_size');
        
        View::assign('total_cloud_space', $total_cloud_space);
        View::assign('total_local_space', $total_local_space);
        View::assign('cloud_limit', $this->store_grade['storegrade_cloud_limit']);
        View::assign('local_limit', $this->store_grade['storegrade_local_limit']);
            
            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('sellergoodsonline');
            $this->setSellerCurItem('add_courses');
            return View::fetch($this->template_dir . 'add_courses');
        } else {
            //判断当前ID 是否属于机构
            $goods_id = intval(input('param.goods_id'));
            if ($goods_id <= 0) {
                ds_json_encode(10001, lang('param_error'));
            }
            
            if ($goods['store_id'] != session('store_id') || $goods['goods_lock'] == 1) {
                ds_json_encode(10001, lang('param_error'));
            }
            $pand_id=input('post.pand_id/a');
            //录入
            $data = array(
                'goods_id' => input('param.goods_id'),
                'store_id'=> session('store_id'),
                'goodscourses_free'=> intval(input('post.goodscourses_free')),
                'goodscourses_exper'=> intval(input('post.goodscourses_exper')),
                'goodscourses_name' => input('post.goodscourses_name'),
                'goodscourses_sort' => input('post.goodscourses_sort'),
                'goodscourses_type' => 0,
                'goodscourses_type_id' => input('post.videoupload_id'),
                'goodscourses_class_id'=> intval(input('post.goodscourses_class_id')),
                'goodscourses_class_name' => input('post.goodscourses_class_name'),
                'baidu_pan_fsids'=>json_encode($pand_id),
            );
            $goodscourses_model = model('goodscourses');
            $result = $goodscourses_model->addGoodscourses($data);
            if ($result) {
                ds_json_encode(10000, lang('添加成功'));
            }
        }
    }
    
    /**
     * 编辑课程
     */
    function edit_courses()
    {
        $goods_id = intval(input('param.goods_id'));
        $store_id = intval(input('param.store_id'));
        $goodscourses_id = intval(input('param.goodscourses_id'));
        
        $goods_model = model('goods');
        $goods = $goods_model->getGoodsInfoByID($goods_id);
        $condition = array();
        $condition[] = array('goods_id','=',$goods_id);
        $condition[] = array('store_id','=',session('store_id'));
        $condition[] = array('goodscourses_id','=',$goodscourses_id);
        $goodscourses_model = model('goodscourses');
        $goodscourses = $goodscourses_model->getOneGoodscourses($condition);
        if(empty($goodscourses)){
            $this->error(lang('param_error'));
        }
        $goodscourses['baidu_pan_fsids']=json_decode($goodscourses['baidu_pan_fsids'],true);
        if (!request()->isPost()) {
            $goodscourses['goodscourses_url']='';
            if($goodscourses['goodscourses_type']==0){
                $videoupload_model=model('videoupload');
                $videoupload_info=$videoupload_model->getOneVideoupload(array(array('videoupload_id', '=', $goodscourses['goodscourses_type_id'])));
                if($videoupload_info){
                    $url = $videoupload_model->getVideoExpire($videoupload_info);
                    $goodscourses['goodscourses_url']=$url;
                }
            }
            View::assign('baidu_pan_url', urlencode(str_replace('/index.php', '', HOME_SITE_URL).'/seller_courseware/baidu_pan_auth'));
            View::assign('goodscourses', $goodscourses);
            View::assign('goods', $goods);
            View::assign('if_baidu_pan', $this->store_info['baidu_pan_access_token']?1:0);
            
        
        $condition=array();
        $condition[]=array('store_id','=',$this->store_info['store_id']);
        $condition[]=array('video_type','in',['aliyun','tencent']);
        $total_cloud_space=Db::name('videoupload')->where($condition)->sum('videoupload_size');
        $condition=array();
        $condition[]=array('store_id','=',$this->store_info['store_id']);
        $condition[]=array('video_type','=','local');
        $total_local_space=Db::name('videoupload')->where($condition)->sum('videoupload_size');
        
        View::assign('total_cloud_space', $total_cloud_space);
        View::assign('total_local_space', $total_local_space);
        View::assign('cloud_limit', $this->store_grade['storegrade_cloud_limit']);
        View::assign('local_limit', $this->store_grade['storegrade_local_limit']);
            
            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('sellergoodsonline');
            $this->setSellerCurItem('add_courses');
            return View::fetch($this->template_dir . 'add_courses');
        } else {
            $pand_id=input('post.pand_id/a');
            $data = array(
                'goodscourses_free'=> intval(input('post.goodscourses_free')),
                'goodscourses_exper'=> intval(input('post.goodscourses_exper')),
                'goodscourses_name' => input('post.goodscourses_name'),
                'goodscourses_sort' => input('post.goodscourses_sort'),
                'goodscourses_type' => 0,
                'goodscourses_type_id' => input('post.videoupload_id'),
                'goodscourses_class_id'=> intval(input('post.goodscourses_class_id')),
                'goodscourses_class_name' => input('post.goodscourses_class_name'),
                'baidu_pan_fsids'=>json_encode($pand_id),
            );

            $result = $goodscourses_model->editGoodscourses($condition,$data);
            ds_json_encode(10000, lang('修改成功'));
        }
    }

    /**
     * 删除课程
     */
    function del_courses(){
        $goods_id = intval(input('param.goods_id'));
        $store_id = intval(input('param.store_id'));
        $goodscourses_id = intval(input('param.goodscourses_id'));
        $condition = array();
        $condition[] = array('goods_id','=',$goods_id);
        $condition[] = array('store_id','=',session('store_id'));
        $condition[] = array('goodscourses_id','=',$goodscourses_id);
        $goodscourses_model = model('goodscourses');
        $result = $goodscourses_model->delGoodscourses($condition);
        if($result){
            ds_json_encode(10000, '删除成功');
        }else{
            ds_json_encode(10001, '参数错误');
        }
    }
    
    

    /**
     * 编辑分类
     */
    public function edit_class() {
        // 实例化商品分类模型
        $goodsclass_model = model('goodsclass');
        // 商品分类
        $goods_class = $goodsclass_model->getGoodsclass(session('store_id'));

        View::assign('goods_class', $goods_class);

        View::assign('goods_id', input('param.goods_id'));
        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellergoodsonline');
        $this->setSellerCurItem('edit_class');
        View::assign('edit_goods_sign', true);
        return View::fetch($this->template_dir . 'store_goods_add_step1');
    }

    /**
     * 删除商品
     */
    public function drop_goods() {
        $goods_id = input('param.goods_id');
        $goods_id = $this->checkRequestGoodsId($goods_id);
        $goods_id_array = explode(',', $goods_id);

        $goods_model = model('goods');
        $where = array();
        $where[] = array('goods_id','in', $goods_id_array);
        $where[] = array('store_id','=', session('store_id'));
        $return = $goods_model->delGoodsNoLock($where);

        if ($return) {
            // 添加操作日志
            $this->recordSellerlog('删除商品，平台货号：' . $goods_id);
            ds_json_encode(10000, lang('store_goods_index_goods_del_success'));
        } else {
            ds_json_encode(10001, lang('store_goods_index_goods_del_fail'));
        }
    }

    /**
     * 商品下架
     */
    public function goods_unshow() {
        $goods_id = $this->checkRequestGoodsId(input('param.goods_id'));
        $goods_id_array = explode(',', $goods_id);
        $goods_model = model('goods');
        $where = array();
        $where[] = array('goods_id','in', $goods_id_array);
        $where[] = array('store_id','=', session('store_id'));
        $return = model('goods')->editProducesOffline($where);
        if ($return) {
            // 添加操作日志
            $this->recordSellerlog('商品下架，平台货号：' . $goods_id);
            ds_json_encode(10000, lang('store_goods_index_goods_unshow_success'));
        } else {
            ds_json_encode(10001, lang('store_goods_index_goods_unshow_fail'));
        }
    }

    /**
     * 设置广告词
     */
    public function edit_jingle() {
        if (request()->isPost()) {
            $goods_id = $this->checkRequestGoodsId(input('param.goods_id'));
            $goods_id_array = explode(',', $goods_id);
            $where = array();
            $where[] = array('goods_id','in', $goods_id_array);
            $where[] = array('store_id','=', session('store_id'));
        
            $update = array('goods_advword' => trim(input('post.g_jingle')));
            $return = model('goods')->editProducesNoLock($where, $update);
            if ($return) {
                // 添加操作日志
                $this->recordSellerlog('设置广告词，平台货号：' . $goods_id);
                ds_json_encode(10000, lang('ds_common_op_succ'));
            } else {
                ds_json_encode(10001, lang('ds_common_op_fail'));
            }
        }
        $goods_id = $this->checkRequestGoodsId(input('param.goods_id'));

        return View::fetch($this->template_dir . 'edit_jingle');
    }

    /**
     * 设置关联版式
     */
    public function edit_plate() {
        if (request()->isPost()) {
            $goods_id = $this->checkRequestGoodsId(input('post.goods_id'));
            $goods_id_array = explode(',', $goods_id);
            
            $where = array();
            $where[] = array('goods_id','in', $goods_id_array);
            $where[] = array('store_id','=', session('store_id'));
            
            $update = array();
            $update['plateid_top'] = intval(input('post.plate_top')) > 0 ? intval(input('post.plate_top')) : '';
            $update['plateid_bottom'] = intval(input('post.plate_bottom')) > 0 ? intval(input('post.plate_bottom')) : '';
            $return = model('goods')->editGoods($update, $where);
            if ($return) {
                // 添加操作日志
                $this->recordSellerlog('设置关联版式，平台货号：' . $goods_id);
                ds_json_encode(10000, lang('ds_common_op_succ'));
            } else {
                ds_json_encode(10001, lang('ds_common_op_fail'));
            }
        } else {
            $goods_id = $this->checkRequestGoodsId(input('param.goods_id'));
            $plateid_bottom = Db::name('goods')->where(array('goods_id' => $goods_id))->field('plateid_bottom,plateid_top')->find();
            View::assign('plateid', $plateid_bottom);
            // 关联版式
            $plate_list = model('storeplate')->getStoreplateList(array('store_id' => session('store_id')), 'storeplate_id,storeplate_name,storeplate_position');

            $plate_list = array_under_reset($plate_list, 'storeplate_position', 2);
            View::assign('plate_list', $plate_list);

            return View::fetch($this->template_dir . 'edit_plate');
        }
    }

    /**
     * 推荐搭配
     */
    public function add_combo() {
        $goods_id = intval(input('param.goods_id'));
        if ($goods_id <= 0) {
            $this->error(lang('param_error'), url('Seller/index'));
        }
        $goods_model = model('goods');
        $goods_info = $goods_model->getGoodsInfoByID($goods_id);
        if (empty($goods_info) || $goods_info['store_id'] != session('store_id')) {
            $this->error(lang('param_error'), url('Seller/index'));
        }

        $goods_array = $goods_model->getGoodsListForPromotion(array(array('goods_id' ,'=', $goods_id)), '*', 0, 'combo');
        View::assign('goods_array', $goods_array);

        // 推荐组合商品列表
        $combo_list = model('goodscombo')->getGoodscomboList(array('goods_id' => $goods_id));
        $combo_goodsid_array = array();
        if (!empty($combo_list)) {
            foreach ($combo_list as $val) {
                $combo_goodsid_array[] = $val['combo_goodsid'];
            }
        }

        $combo_goods_array = $goods_model->getGoodsList(array(array('goods_id','in', $combo_goodsid_array)), 'goods_id,goods_name,goods_image,goods_price');
        $combo_goods_list = array();
        if (!empty($combo_goods_array)) {
            foreach ($combo_goods_array as $val) {
                $combo_goods_list[$val['goods_id']] = $val;
            }
        }

        $combo_array = array();
        foreach ($combo_list as $val) {
            $combo_array[$val['goods_id']][] = $combo_goods_list[$val['combo_goodsid']];
        }
        View::assign('combo_array', $combo_array);


        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellergoodsonline');
        $this->setSellerCurItem();
        return View::fetch($this->template_dir . 'store_goods_edit_add_combo');
    }

    /**
     * 保存赠品
     */
    public function save_combo() {
        if (!request()->isPost()) {
            ds_json_encode(10001, lang('param_error'));
        }

        $combo_array = input('post.combo/a'); #获取数组

        $goods_id = intval(input('param.goods_id'));
        if ($goods_id <= 0) {
            ds_json_encode(10001, lang('param_error'));
        }

        $goods_model = model('goods');
        $goodscombo_model = model('goodscombo');

        // 验证商品是否存在
        $goods_list = $goods_model->getGoodsListForPromotion(array(array('goods_id' ,'=', $goods_id), array('store_id' ,'=', session('store_id'))), 'goods_id', 0, 'combo');
        if (empty($goods_list)) {
            ds_json_encode(10001, lang('param_error'));
        }
        // 删除该商品原有赠品
        $goodscombo_model->delGoodscombo(array('goods_id' => $goods_id));
        // 商品id
        $goodsid_array = array();
        foreach ($goods_list as $val) {
            $goodsid_array[] = $val['goods_id'];
        }

        $insert = array();
        if (!empty($combo_array)) {
            foreach ($combo_array as $key => $val) {

                $owner_gid = intval($key);  // 主商品id
                // 验证主商品是否为本机构商品,如果不是本店商品继续下一个循环
                if (!in_array($owner_gid, $goodsid_array)) {
                    continue;
                }
                $val = array_unique($val);
                foreach ($val as $v) {
                    $combo_gid = intval($v); // 礼品id
                    // 验证推荐组合商品是否为本机构商品，如果不是本店商品继续下一个循环
                    $combo_info = $goods_model->getGoodsInfoByID($combo_gid);
                    if ($combo_info['store_id'] != session('store_id') || $owner_gid == $combo_gid) {
                        continue;
                    }

                    $array = array();
                    $array['goods_id'] = $owner_gid;
                    $array['goods_id'] = $goods_id;
                    $array['combo_goodsid'] = $combo_gid;
                    $insert[] = $array;
                }
            }
            // 插入数据
            $goodscombo_model->addGoodscomboAll($insert);
        }
        ds_json_encode(10000, lang('ds_common_save_succ'));
    }

    /**
     * 搜索商品（添加赠品/推荐搭配)
     */
    public function search_goods() {
        $condition = array();
        $condition[]=array('store_id','=',session('store_id'));
        $name = input('param.name');
        if ($name) {
            $condition[]=array('goods_name','like', '%' . $name . '%');
        }
        $goods_model = model('goods');
        $goods_list = $goods_model->getGoodsList($condition, '*', 5);
        View::assign('show_page', $goods_model->page_info->render());
        View::assign('goods_list', $goods_list);
        echo View::fetch($this->template_dir . 'store_goods_edit_search_goods');
        exit;
    }

    /**
     * 验证goods_id
     */
    private function checkRequestGoodsId($goods_ids) {
        if (!preg_match('/^[\d,]+$/i', $goods_ids)) {
            ds_json_encode(10001, lang('param_error'));
        }
        return $goods_ids;
    }

    /**
     * ajax获取商品列表
     */
    public function get_goods_list_ajax() {
        $goods_id = input('param.goods_id');
        if ($goods_id <= 0) {
            echo 'false';
            exit();
        }
        $goods_model = model('goods');
        $goods_list = $goods_model->getGoodsList(array('store_id' => session('store_id'), 'goods_id' => $goods_id), 'goods_id,store_id,goods_price,goods_serial,goods_image');
        if (empty($goods_list)) {
            echo 'false';
            exit();
        }
        echo json_encode($goods_list);
    }

    /**
     *    栏目菜单
     */
    function getSellerItemList() {
        $item_list = array(
            array(
                'name' => 'goods_list',
                'text' => '出售中的商品',
                'url' => url('Sellergoodsonline/index'),
            ),
        );
        if (request()->action() !== 'goods_list' && request()->action() !== 'index' ) {
            $item_list[] = array(
                'name' => 'edit_goods',
                'text' => '编辑商品',
                'url' => url('Sellergoodsonline/edit_goods', ['goods_id' => input('param.goods_id')]),
            );
            $item_list[] = array(
                'name' => 'list_courses',
                'text' => '课程列表',
                'url' => url('Sellergoodsonline/list_courses', ['goods_id' => input('param.goods_id')]),
            );
            $item_list[] = array(
                'name' => 'add_courses',
                'text' => '添加课程',
                'url' => url('Sellergoodsonline/add_courses', ['goods_id' => input('param.goods_id')]),
            );
            $item_list[] = array(
                'name' => 'add_combo',
                'text' => '推荐组合',
                'url' => url('Sellergoodsonline/add_combo', ['goods_id' => input('param.goods_id')]),
            );
            $item_list[] = array(
                'name' => 'edit_class',
                'text' => '选择分类',
                'url' => url('Sellergoodsonline/edit_class', ['goods_id' => input('param.goods_id')]),
            );
        }
        return $item_list;
    }

}

?>
