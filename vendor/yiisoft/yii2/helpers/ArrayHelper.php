<?php

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\helpers;

/**
 * ArrayHelper provides additional array functionality that you can use in your
 * application.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ArrayHelper extends BaseArrayHelper {

    /**
     * 将二维数组内层对应key的value值转换为二维数组外层key值
     * @param array $array
     * @param string $key
     * @return array
     */
    public static function switchKey($array, $key) {
        foreach ($array as $val) {
            $new_arr[$val[$key]] = $val;
        }
        unset($val);
        return $new_arr;
    }

    /**
     * 将一维或者二维数组的键值按顺序返回
     * ex.
     * $array = [
     *     ['id' => '123', 'data' => 'abc', 'device' => 'laptop'],
     *     ['id' => '345', 'data' => 'def', 'device' => 'tablet'],
     * ];
     * $array = [
     *      colimns => ['id', 'data', 'device'],
     *      rows => [['123', 'abc', 'laptop'],['345', 'def', 'tablet']]
     * ]
     * @param type $array
     * @return type
     */
    public static function explodeArr($array) {
        $rows = array();
        $columns = array();
        if (count($array) !== count($array, 1)) {
            foreach ($array as $val) {
                $columns = array_keys($val);
                $rows[] = array_values($val);
            }
        } else {
            $columns = array_keys($array);
            $rows[] = array_values($array);
        }
        return ['columns' => $columns, 'rows' => $rows];
    }
    
    /**
     * 先降序，再连接
     * @param type $array
     * @param type $glue
     * @return type
     */
    public static function myImplode($array, $glue = '') {
        ksort($array);
        return implode($glue, $array);
    }
}
