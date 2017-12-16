<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\models;

use Yii;

/**
 * Description of Message
 *
 * @author Administrator
 * @datetime 2017-2-23 17:49:44
 */
class Message extends DmActiveRecord {

    const TYPE_NO_READ = 0;
    const TYPE_READED = 2;

    //发送一条
    public function sendOne($data) {
        $_table = self::getTableName($data['user_id']);
        return Yii::$app->db->createCommand()->insert($_table, $data)->execute();
    }
    /**
     * 根据用户id和状态类型，获取用户信息总数
     * @param type $user_id
     * @param type $m_type
     * @return int
     */
    public static function countUserMsgAll($user_id, $m_type = null) {
        $_table = self::getTableName($user_id);
        $where = 'WHERE user_id = :user_id';
        $param[':user_id'] = $user_id;
        if ($m_type !== null) {
            $where .= ' AND m_type = :m_type';
            $param[':m_type'] = $m_type;
        }
        $sql = "SELECT COUNT(mid) as count_msg FROM $_table $where ";
        $result = Yii::$app->db->createCommand($sql, $param)->queryOne();
        return empty($result['count_msg']) ? 0 : $result['count_msg'];
    }
    
    public static function getUserMsgList($user_id, $page, $pageSize, $starttime = null, $endtime = null, $m_type = null) {
        $_table = self::getTableName($user_id);
        $total = self::countUserMsgAll($user_id, $m_type);
        $list = array();
        if ($total > 0) {
            $where = 'WHERE user_id = :user_id';
            $param[':user_id'] = $user_id;
            if ($m_type !== null) {
                $where .= ' AND m_type = :m_type';
                $param[':m_type'] = $m_type;
            }
            if (!is_null($starttime)) {
                $where .= ' AND addtime >= :starttime';
                $param[':starttime'] = $starttime;
            }
            if (!is_null($endtime)) {
                $where .= ' AND addtime < :endtime';
                $param[':endtime'] = $endtime;
            }
            $orderBy = 'addtime desc';
            $limit = self::pageToLimit($page, $pageSize);
            $sql = "SELECT * FROM $_table $where ORDER BY $orderBy limit {$limit},$pageSize";
            $list = Yii::$app->db->createCommand($sql, $param)->queryAll();
            foreach ($list as &$val)
            {
                preg_match('/^(.*)<a href=\\\\\'\/home\/borrow/',$val['m_content'],$match);
                if($match)
                {
                    $val['m_content']=$match[1];
                }
            }
        }
        return ['total' => $total, 'list' => $list];
    }
    
    /**
     * 信息设置为已读
     * @param type $user_id
     * @param mix $mids 可以是一个mid也可以是数组[1,2]
     * @return type
     */
    public static function read($user_id, $mids) {
        $_table = self::getTableName($user_id);
        if ($mids) {
            if (is_array($mids)) {
                $where = ['in', 'mid', $mids];
            } else {
                $where = 'mid = ' . $mids;
            }
        }else{
            $where = ['m_type' => self::TYPE_NO_READ];
        }
        return Yii::$app->db->createCommand()->update($_table, ['m_type' => self::TYPE_READED], $where)->execute();
    }
    /**
     * 删除信息
     * @param type $user_id
     * @param mix $mids 可以是一个mid也可以是数组[1,2]
     * @return type
     */
    public static function del($user_id, $mids) {
        $_table = self::getTableName($user_id);
        if (is_array($mids)) {
            $where = ['in', 'mid', $mids];
        } else {
            $where = 'mid = ' . $mids;
        }
        return Yii::$app->db->createCommand()->delete($_table, $where)->execute();
    }

    /**
     * 根据划分规则返回对应的用户信息表
     * @param type $user_id
     * @return string
     */
    public static function getTableName($user_id) {
        $_userid = $user_id % 100;
        return 'xx_message_' . $_userid;
    }
}
