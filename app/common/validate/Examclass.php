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
class  Examclass extends Validate
{
    protected $rule = [
        'examclass_name'=>'require',
        'examclass_sort'=>'number'
    ];
    protected $message = [
        'examclass_name.require'=>'分类名称不能为空',
        'examclass_sort.number'=>'分类排序仅能为数字'
    ];
    protected $scene = [
        'add' => ['examclass_name', 'examclass_sort'],
        'edit' => ['examclass_name', 'examclass_sort'],
    ];
}