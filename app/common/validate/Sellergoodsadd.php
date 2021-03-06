<?php

namespace app\common\validate;


use think\Validate;
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
 * 验证器
 */
class  Sellergoodsadd extends Validate
{
    protected $rule = [
        'goods_name'=>'require',
        'goods_price'=>'require',
        'goods_type'=>'in:0,1',
    ];
    protected $message = [
        'goods_name.require'=>'课程名称不能为空',
        'goods_price.require'=>'课程价格不能为空',
        'goods_type.in'=>'课程类型错误',
    ];
    protected $scene = [
        'save_goods' => ['goods_name', 'goods_price','goods_type'],
    ];
}