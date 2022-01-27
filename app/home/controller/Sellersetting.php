<?php

namespace app\home\controller;
use think\facade\View;
use think\Image;
use think\facade\Lang;
use think\facade\Db;
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
class  Sellersetting extends BaseSeller {

    const MAX_MB_SLIDERS = 5;

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/sellersetting.lang.php');
    }

    /*
     * 机构设置
     */

    public function setting() {
        /**
         * 实例化模型
         */
        $store_model = model('store');

        $store_id = session('store_id'); //当前机构ID
        /**
         * 获取机构信息
         */
        $store_info = $store_model->getStoreInfoByID($store_id);

        $if_miniprocode=$this->getMiniProCode(1);
        View::assign('miniprogram_code',$if_miniprocode?(UPLOAD_SITE_URL . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR . session('store_id').'/miniprogram_code.png'):'');
        /**
         * 保存机构设置
         */
        if (request()->isPost()) {
            /**
             * 更新入库
             */
            $param = array(
                'store_qq' => input('post.store_qq'),
                'store_ww' => input('post.store_ww'),
                'store_phone' => input('post.store_phone'),
                'store_mainbusiness' => input('post.store_mainbusiness'),
                'store_keywords' => input('post.seo_keywords'),
                'store_description' => input('post.seo_description')
            );


            if (!empty(input('post.store_name'))) {
                $store = $store_model->getStoreInfo(array('store_name' => input('param.store_name')));
                //机构名存在,则提示错误
                if (!empty($store) && ($store_id != $store['store_id'])) {
                    $this->error(lang('please_change_another_name'));
                }
                $param['store_name'] = input('post.store_name');
            }
            //机构名称修改处理
            if (input('param.store_name') != $store_info['store_name'] && !empty(input('post.store_name'))) {
                $condition = array();
                $condition[]=array('store_id','=',$store_id);
                $update = array();
                $update['store_name'] = input('param.store_name');
                Db::name('goods')->where($condition)->update($update);
            }

            $this->getMiniProCode(1);
            $store_model->editStore($param, array('store_id' => $store_id));
            $this->success(lang('ds_common_save_succ'), url('Sellersetting/setting'));
        }
        /**
         * 实例化机构等级模型
         */
        // 从基类中读取机构等级信息
        $store_grade = $this->store_grade;

        /**
         * 输出机构信息
         */
        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('seller_setting');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('store_setting');
        View::assign('store_info', $store_info);
        View::assign('store_grade', $store_grade);
        /**
         * 页面输出
         */
        return View::fetch($this->template_dir . 'setting');
    }

    public function getMiniProCode($force=0){
        if($force || !file_exists(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR . session('store_id').'/miniprogram_code.png')){
            model('wechat')->getOneWxconfig();
            $a=model('wechat')->getMiniProCode(session('store_id'));
            if(@imagecreatefromstring($a)==false){
                $a= json_decode($a);
                //View::assign('errmsg',$a->errmsg);
            }else{
                if (is_dir(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR . session('store_id')) || (!is_dir(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR . session('store_id')) && mkdir(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR . session('store_id'), 0755, true))) {
                    file_put_contents(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR . session('store_id').'/miniprogram_code.png', $a);
                    return true;
                } else {
                    //View::assign('errmsg','没有权限生成目录');
                }
                
            }
            
        }else{
            return true;
        }
        return false;
    }
    public function store_image_upload() {
        $store_id = session('store_id');
        $upload_file = BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR . $store_id;
        $file_name = session('store_id') . '_' . date('YmdHis') . rand(10000, 99999).'.png';
        $store_image_name = input('param.id');

        if (!in_array($store_image_name, array('store_logo', 'store_banner', 'store_avatar'))) {
            exit;
        }

        if (!empty($_FILES[$store_image_name]['name'])) {
            $res = ds_upload_pic(ATTACH_STORE . DIRECTORY_SEPARATOR . $store_id, $store_image_name, $file_name);
            if ($res['code']) {
                $file_name = $res['data']['file_name'];
                if(file_exists($upload_file . DIRECTORY_SEPARATOR . $file_name)){
                /* 处理图片 */
                $image = Image::open($upload_file . DIRECTORY_SEPARATOR . $file_name);
                switch ($store_image_name) {
                    case 'store_logo':
                        $image->thumb(200, 60, \think\Image::THUMB_CENTER)->save($upload_file . DIRECTORY_SEPARATOR . $file_name);
                        break;
                    case 'store_banner':
                        $image->thumb(1920, 150, \think\Image::THUMB_CENTER)->save($upload_file . DIRECTORY_SEPARATOR . $file_name);
                        break;
                    case 'store_avatar':
                        $image->thumb(100, 100, \think\Image::THUMB_CENTER)->save($upload_file . DIRECTORY_SEPARATOR . $file_name);
                        break;
                    default:
                        break;
                }
                }
            } else {
                json_encode(array('error' => $res['msg']));
                exit;
            }
        }
        $store_model = model('store');
        //删除原图
        $store_info = $store_model->getStoreInfoByID($store_id);
        @unlink($upload_file . DIRECTORY_SEPARATOR . $store_info[$store_image_name]);
        $result = $store_model->editStore(array($store_image_name => $file_name), array('store_id' => $store_id));
        if ($result) {
            $data = array();
            $data['file_name'] = $file_name;
            $data['file_path'] = ds_get_pic( ATTACH_STORE . '/' . $store_id , $file_name);
            /**
             * 整理为json格式
             */
            $output = json_encode($data);
            echo $output;
            exit;
        }
    }

    /**
     * 机构幻灯片
     */
    public function store_slide() {
        /**
         * 模型实例化
         */
        $store_model = model('store');
        $upload_model = model('upload');
        /**
         * 保存机构信息
         */
        if (request()->isPost()) {
            // 更新机构信息
            $update = array();
            $update['store_slide'] = implode(',', input('post.image_path/a'));
            $update['store_slide_url'] = implode(',', input('post.image_url/a'));
            $store_model->editStore($update, array('store_id' => session('store_id')));

            // 删除upload表中数据
            $upload_model->delUpload(array('upload_type' => 3, 'item_id' => session('store_id')));
            ds_json_encode(10000,lang('ds_common_save_succ'));
        } else {
            // 删除upload中的无用数据
            $upload_info = $upload_model->getUploadList(array('upload_type' => 3, 'item_id' => session('store_id')), 'file_name');
            if (is_array($upload_info) && !empty($upload_info)) {
                foreach ($upload_info as $val) {
                    @unlink(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_SLIDE . DIRECTORY_SEPARATOR . $val['file_name']);
                }
            }
            $upload_model->delUpload(array('upload_type' => 3, 'item_id' => session('store_id')));

            $store_info = $store_model->getStoreInfoByID(session('store_id'));
            if ($store_info['store_slide'] != '' && $store_info['store_slide'] != ',,,,') {
                View::assign('store_slide', explode(',', $store_info['store_slide']));
                View::assign('store_slide_url', explode(',', $store_info['store_slide_url']));
            }
            $this->setSellerCurMenu('seller_setting');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('store_slide');
            return View::fetch($this->template_dir . 'slide');
        }
    }

    /**
     * 机构幻灯片ajax上传
     */
    public function silde_image_upload() {
        $file_id = intval(input('param.file_id'));
        $id = input('param.id');
        if($file_id<0 || empty($id)){
            return;
        }
        
        $file_name = session('store_id') . '_' . $file_id . '.png';
        $res = ds_upload_pic(ATTACH_SLIDE, $id, $file_name);
        if ($res['code']) {
            $file_name = $res['data']['file_name'];
            $img_path = $file_name;
            $output['file_id'] = $file_id;
            $output['id'] = $id;
            $output['file_name'] = $img_path;
            $output['file_url'] = ds_get_pic(ATTACH_SLIDE, $img_path);
            echo json_encode($output);
            exit;
        } else {
            json_encode(array('error' => $res['msg']));
            exit;
        }
    }

    /**
     * ajax删除幻灯片图片
     */
    public function dorp_img() {
        $file_id = intval(input('param.file_id'));
        $img_src = input('param.img_src');
        if($file_id<0 || empty($img_src)){
            return;
        }
        $ext =  strrchr($img_src, '.');
        $file_name = session('store_id') . '_' . $file_id .$ext;
        @unlink(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_SLIDE . DIRECTORY_SEPARATOR . $file_name);
        echo json_encode(array('succeed' => lang('ds_common_save_succ')));
        die;
    }

    /**
     * 卖家机构主题设置
     *
     * @param string
     * @param string
     * @return
     */
    public function theme() {
        /**
         * 机构信息
         */
        $store_class = model('store');
        $store_info = $store_class->getStoreInfoByID(session('store_id'));
        /**
         * 主题配置信息
         */
        $style_data = array();
        $style_configurl = PUBLIC_PATH . '/static/home/default/store/styles/' . "styleconfig.php";

        if (file_exists($style_configurl)) {
            include_once($style_configurl);
        }
        /**
         * 当前机构主题
         */
        $curr_store_theme = !empty($store_info['store_theme']) ? $store_info['store_theme'] : 'default';
        /**
         * 当前机构预览图片
         */
        $curr_image = BASE_SITE_ROOT . '/static/home/default/store/styles/' . $curr_store_theme . '/images/preview.jpg';

        $curr_theme = array(
            'curr_name' => $curr_store_theme,
            'curr_truename' => $style_data[$curr_store_theme]['truename'],
            'curr_image' => $curr_image
        );

        // 自营店全部可用
        if (check_platform_store()) {
            $themes = array_keys($style_data);
        } else {
            /**
             * 机构等级
             */
            $grade_class = model('storegrade');
            $grade = $grade_class->getOneStoregrade($store_info['grade_id']);

            /**
             * 可用主题
             */
            $themes = explode('|', $grade['storegrade_template']);
        }
        $theme_list = array();
        /**
         * 可用主题预览图片
         */
        foreach ($style_data as $key => $val) {
            if (in_array($key, $themes)) {
                $theme_list[$key] = array(
                    'name' => $key, 'truename' => $val['truename'],
                    'image' => BASE_SITE_ROOT . '/static/home/default/store/styles/' . $key . '/images/preview.jpg'
                );
            }
        }
        /**
         * 页面输出
         */
        $this->setSellerCurMenu('seller_setting');
        $this->setSellerCurItem('store_theme');

        View::assign('store_info', $store_info);
        View::assign('curr_theme', $curr_theme);
        View::assign('theme_list', $theme_list);
        return View::fetch($this->template_dir . 'theme');
    }

    /**
     * 卖家机构主题设置
     *
     * @param string
     * @param string
     * @return
     */
    public function set_theme() {
        //读取语言包
        $style = input('param.style_name');
        $style = isset($style) ? trim($style) : null;
        if (!empty($style) && file_exists(PUBLIC_PATH . '/static/home/default/store/styles/theme/' . $style . '/images/preview.jpg')) {
            $store_class = model('store');
            $rs = $store_class->editStore(array('store_theme' => $style), array('store_id' => session('store_id')));
            ds_json_encode(10000,lang('store_theme_congfig_success'));
        } else {
            ds_json_encode(10001,lang('store_theme_congfig_fail'));
        }
    }

    protected function getStoreMbSliders() {
        $store_info = model('store')->getStoreInfoByID(session('store_id'));

        $mbSliders = @unserialize($store_info['mb_sliders']);
        if (!$mbSliders) {
            $mbSliders = array_fill(1, self::MAX_MB_SLIDERS, array(
                'img' => '', 'type' => 1, 'link' => '',
            ));
        }

        return $mbSliders;
    }

    protected function setStoreMbSliders(array $mbSliders) {
        return model('store')->editStore(array(
                    'mb_sliders' => serialize($mbSliders),
                        ), array(
                    'store_id' => session('store_id'),
        ));
    }

    public function store_mb_sliders() {
        //上传文件名称
        $fileName = input('param.id');
        //文件ID
        $file_id = intval(input('param.file_id'));
        if (!preg_match('/^file_(\d+)$/', $fileName, $fileIndex) || empty($_FILES[$fileName]['name'])) {
            echo json_encode(array('error' => lang('param_error')));
            exit;
        }

        $fileIndex = (int) $fileIndex[1];
        if ($fileIndex < 1 || $fileIndex > self::MAX_MB_SLIDERS) {
            echo json_encode(array('error' => lang('param_error')));
            exit;
        }

        $mbSliders = $this->getStoreMbSliders();
        $file_name = session('store_id') . '_' . $file_id . '.png';
        $res = ds_upload_pic(ATTACH_STORE . DIRECTORY_SEPARATOR . 'mobileslide', $fileName, $file_name);
        if ($res['code']) {
            $file_name = $res['data']['file_name'];
            $newImg = $file_name;


            $oldImg = $mbSliders[$fileIndex]['img'];
            $mbSliders[$fileIndex]['img'] = $newImg;
            //即时更新
            $this->setStoreMbSliders($mbSliders);
            if ($oldImg && file_exists($oldImg)) {
                unlink($oldImg);
            }
            echo json_encode(array(
                'uploadedUrl' => ds_get_pic( ATTACH_STORE . DIRECTORY_SEPARATOR . 'mobileslide' , $newImg),
            ));
            exit;
        } else {
            echo json_encode(array('error' => $res['msg']));
            exit;
        }
    }

    public function store_mb_sliders_drop() {
        try {
            $id = (int) $_REQUEST['id'];
            if ($id < 1 || $id > self::MAX_MB_SLIDERS) {
                throw new \think\Exception(lang('param_error'), 10006);
            }
            $mbSliders = $this->getStoreMbSliders();
            $mbSliders[$id]['img'] = '';
            if (!$this->setStoreMbSliders($mbSliders)) {
                throw new \think\Exception(lang('update_failed'), 10006);
            }
            echo json_encode(array(
                'success' => true,
            ));
        } catch (\Exception $ex) {
            echo json_encode(array(
                'success' => false, 'error' => $ex->getMessage(),
            ));
        }
    }

    public function store_mobile() {
        View::assign('max_mb_sliders', self::MAX_MB_SLIDERS);

        $store_info = model('store')->getStoreInfoByID(session('store_id'));

        // 页头背景图
        $mb_title_img = $store_info['mb_title_img'] ? ds_get_pic( ATTACH_STORE , $store_info['mb_title_img']) : '';

        // 轮播
        $mbSliders = $this->getStoreMbSliders();

        if (request()->isPost()) {
            $update_array = array();

            if ($mb_title_img_del = !empty(input('post.mb_title_img_del'))) {
                $update_array['mb_title_img'] = '';
            }
            if (!empty($_FILES['mb_title_img']['name'])) {
                $file_name = session('store_id') . '_' . date('YmdHis') . rand(10000, 99999) . '.png';
                $res=ds_upload_pic(ATTACH_STORE,'mb_title_img');
                if($res['code']){
                    $file_name=$res['data']['file_name'];
                    $mb_title_img_del = true;
                    $update_array['mb_title_img'] = $file_name;
                }else{
                    $this->error($res['msg']);
                }
            }
            if ($mb_title_img_del && $mb_title_img && file_exists($mb_title_img)) {
                unlink($mb_title_img);
            }

            // mb_sliders
            $skuToValid = array();
            $mb_sliders_links_array = input('post.mb_sliders_links/a');#获取数组
            $mb_sliders_type_array = input('post.mb_sliders_type/a');#获取数组
            $mb_sliders_sort_array = input('post.mb_sliders_sort/a');#获取数组
            
            foreach ($mb_sliders_links_array as $k => $v) {
                if ($k < 1 || $k > self::MAX_MB_SLIDERS) {
                    $this->error(lang('param_error'));
                }

                $type = intval($mb_sliders_type_array[$k]);
                switch ($type) {
                    case 1:
                        // 链接URL
                        $v = (string) $v;
                        if (!preg_match('#^https?://#', $v)) {
                            $v = '';
                        }
                        break;

                    case 2:
                        // 商品ID
                        $v = (int) $v;
                        if ($v < 1) {
                            $v = '';
                        } else {
                            $skuToValid[$k] = $v;
                        }
                        break;

                    default:
                        $type = 1;
                        $v = '';
                        break;
                }

                $mbSliders[$k]['type'] = $type;
                $mbSliders[$k]['link'] = $v;
            }

            if ($skuToValid) {
                $condition = array();
                $condition[] = array('goods_id','in',$skuToValid);
                $condition[] = array('store_id','=',session('store_id'));
                $validSkus = Db::name('goods')->field('goods_id')->where($condition)->select()->toArray();
                if (!empty($validSkus)) {
                    $validSkus = ds_change_arraykey($validSkus, 'goods_id');
                }
                foreach ($skuToValid as $k => $v) {
                    if (!isset($validSkus[$v])) {
                        $mbSliders[$k]['link'] = '';
                    }
                }
            }

            // sort
            for ($i = 0; $i < self::MAX_MB_SLIDERS; $i++) {
                $sortedMbSliders[$i + 1] = @$mbSliders[$mb_sliders_sort_array[$i]];
            }

            $update_array['mb_sliders'] = serialize($sortedMbSliders);

            model('store')->editStore($update_array, array(
                'store_id' => session('store_id'),
            ));
            $this->success(lang('save_success'), url('Sellersetting/store_mobile'));
        }

        $mbSliderUrls = array();
        foreach ($mbSliders as $v) {
            if ($v['img']) {
                $mbSliderUrls[] = ds_get_pic( ATTACH_STORE . DIRECTORY_SEPARATOR . 'mobileslide' , $v['img']);
            }
        }

        View::assign('mb_title_img', $mb_title_img);
        View::assign('mbSliders', $mbSliders);
        View::assign('mbSliderUrls', $mbSliderUrls);
        $this->setSellerCurMenu('seller_setting');
        $this->setSellerCurItem('store_mobile');
        return View::fetch($this->template_dir . 'store_mobile');
    }

    public function map() {
        $this->setSellerCurMenu('seller_setting');
        $this->setSellerCurItem('store_map');
        /**
         * 实例化模型
         */
        $store_model = model('store');

        $store_id = session('store_id'); //当前机构ID
        /**
         * 获取机构信息
         */
        $store_info = $store_model->getStoreInfoByID($store_id);

        /**
         * 保存机构设置
         */
        if (request()->isPost()) {
            model('store')->editStore(array(
                'store_address' => input('post.company_address_detail'),
                'region_id' => input('post.district_id') ? input('post.district_id') : (input('post.city_id') ? input('post.city_id') : (input('post.province_id') ? input('post.province_id') : 0)),
                'area_info' => input('post.company_address'),
                'store_longitude' => input('post.longitude'),
                'store_latitude' => input('post.latitude')
                    ), array(
                'store_id' => session('store_id'),
            ));
            ds_json_encode(10000,lang('save_success'));
        }
        View::assign('store_info', $store_info);
        View::assign('baidu_ak', config('ds_config.baidu_ak'));
        return View::fetch($this->template_dir . 'map');
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_type 导航类型
     * @param string $name 当前导航的name
     * @return
     */
    protected function getSellerItemList() {
        $menu_array = array(
            1 => array(
                'name' => 'store_setting', 'text' => lang('ds_member_path_store_config'),
                'url' => url('Sellersetting/setting')
            ),
            2 => array(
                'name' => 'store_map', 'text' => lang('ds_member_path_store_map'),
                'url' => url('Sellersetting/map')
            ),
            4 => array(
                'name' => 'store_slide', 'text' => lang('ds_member_path_store_slide'),
                'url' => url('Sellersetting/store_slide')
            ), 5 => array(
                'name' => 'store_theme', 'text' => lang('store_theme'), 'url' => url('Sellersetting/theme')
            ),
            7 => array(
                'name' => 'store_mobile', 'text' => lang('mobile_phone_store_settings'), 'url' => url('Sellersetting/store_mobile'),
            ),
        );
        return $menu_array;
    }

}

?>
