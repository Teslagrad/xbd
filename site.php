<?php
/**
 * 微活动
 *
 * @author Chuangyi
 * @url 
 */
defined('IN_IA') or exit('Access Denied');
include "../addons/xbd_activity/PHPExcel.php";

class xbd_activityModuleSite extends WeModuleSite {

    //模块标识
    public $modulename = 'xbd_activity';

    public $msg_status_success = 1;
    public $msg_status_bad = 0;
    public $_debug = 1;

    public $_appid = '';
    public $_appsecret = '';
    public $_accountlevel = '';

    public $_weid = '';
    public $_hid = '';
    public $_fromuser = '';
    public $_nickname = '';
    public $_headimgurl = '';

    public $_auth2_openid = '';
    public $_auth2_nickname = '';
    public $_auth2_headimgurl = '';

    function __construct()
    {
        global $_W, $_GPC;
        $this->_fromuser = $_W['fans']['from_user']; //debug

        if ($_SERVER['HTTP_HOST'] == 'we7.com') {
            $this->_debug = 1;
            $this->_fromuser = 'fromUser';
        }

        $this->_weid = $_W['uniacid'];
        $account = account_fetch($this->_weid);

        $this->_auth2_openid = 'auth2_openid_' . $_W['uniacid'];
        $this->_auth2_nickname = 'auth2_nickname_' . $_W['uniacid'];
        $this->_auth2_headimgurl = 'auth2_headimgurl_' . $_W['uniacid'];

        $this->_appid = '';
        $this->_appsecret = '';
        $this->_accountlevel = $account['level']; //是否为高级号

        if ($this->_accountlevel == 4) {
            $this->_appid = $account['key'];
            $this->_appsecret = $account['secret'];
        }
    
    }


    //活动管理
    public function doWebHuodong() {
        global $_W, $_GPC;
        checklogin();
        $action = 'huodong';
        $title = '活动管理';
        $url = $this->createWebUrl($action, array('op' => 'display'));
        $operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
        if ($operation == 'display') {
            if (checksubmit('submit')) { //排序
                if (is_array($_GPC['displayorder'])) {
                    foreach ($_GPC['displayorder'] as $id => $val) {
                        $data = array('displayorder' => intval($_GPC['displayorder'][$id]));
                        pdo_update($this->modulename . '_stores', $data, array('id' => $id));
                    }
                }
                message('操作成功!', $url);
            }
            $pindex = max(1, intval($_GPC['page']));
            $psize = 15;
            $where = "WHERE weid = '{$_W['uniacid']}'";
            $huodonglist = pdo_fetchall("SELECT * FROM " . tablename($this->modulename . '_huodong') . " {$where} order by displayorder desc,id desc LIMIT " . ($pindex - 1) * $psize . ",{$psize}");
            include $this->template('huodong');
        } elseif ($operation == 'post') {
            load()->func('tpl');
            $id = intval($_GPC['id']); //活动ID
            $reply = pdo_fetch("select * from " . tablename($this->modulename . '_huodong') . " where id=:id and weid =:weid", array(':id' => $id, ':weid' => $_W['uniacid']));
            if (!empty($id)) {
                if (empty($reply)) {
                    message('抱歉，数据不存在或是已经删除！', '', 'error');
                }
            }
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']);
            $starttime = $starttime == '' ? TIMESTAMP : $starttime;
            $endtime = $endtime == '' ? TIMESTAMP+(60*60*24*7) : $endtime;
            
            if (checksubmit('submit')) {
                $data = array();
                $data['weid'] = intval($_W['uniacid']);
                $data['displayorder'] = intval($_GPC['displayorder']);
                $data['title'] = trim($_GPC['title']);
                $data['starttime'] = $starttime;
                $data['endtime'] = $endtime;
                $data['enabled'] = intval($_GPC['enabled']);
                $data['sharetitle'] = trim($_GPC['sharetitle']);
                $data['sharecontent'] = trim($_GPC['sharecontent']);
                $data['sharefriendtitle'] = trim($_GPC['sharefriendtitle']);
                $data['shareimage'] = trim($_GPC['shareimage']);

                if (istrlen($data['title']) == 0) {
                    message('没有输入活动名称.', '', 'error');
                }
                if (istrlen($data['title']) > 30) {
                    message('标题不能多于30个字。', '', 'error');
                }

                if (!empty($reply)) {
                    pdo_update($this->modulename . '_huodong', $data, array('id' => $id, 'weid' => $_W['uniacid']));
                } else {
                    pdo_insert($this->modulename . '_huodong', $data);
                }
                message('操作成功!', $url);
            }
            include $this->template('huodong');
        } elseif ($operation == 'delete') {
            $id = intval($_GPC['id']);
            $store = pdo_fetch("SELECT id FROM " . tablename($this->modulename . '_huodong') . " WHERE id = '$id'");
            if (empty($store)) {
                message('抱歉，不存在或是已经被删除！', $this->createWebUrl('_huodong', array('op' => 'display')), 'error');
            }
            pdo_delete($this->modulename . '_huodong', array('id' => $id, 'weid' => $_W['uniacid']));
            message('删除成功！', $this->createWebUrl('huodong', array('op' => 'display')), 'success');
        }
    }

    public function exportexcel($data=array(),$filename='report',$title=array()) {
        $letter_Array = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $objPHPExcel = new PHPExcel();
        $m = 0;
        foreach ($title as $k => $v) {
            $objPHPExcel->getActiveSheet()->setCellValue($letter_Array[$m].'1', $v); 
            $m ++;
        }
       
        $i = 2; 
        foreach($data as $item => $value){ 
            $m = 0;
            foreach ($value as $ck => $cv) {
                $objPHPExcel->getActiveSheet()->setCellValue($letter_Array[$m] . $i, $cv);
                $objPHPExcel->getActiveSheet()->getColumnDimension($letter_Array[$m])->setWidth(20);
                $m ++;
            }
            $i ++; 
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$filename.'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    //活动记录
    public function doWebAttender() {
        global $_W, $_GPC;
        checklogin();
        $action = 'attender';
        $title = '活动记录';
        $hid = intval($_GPC['hid']); //活动ID
        if (empty($hid)) {
            message('请先选择活动!');
        }
        $operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
        load()->func('tpl');

        if($operation=='display') {
            $pindex = max(1, intval($_GPC['page']));
            $psize = 10;

            $nickname = $_GPC['nickname'];
            $condition = " WHERE weid = '{$_W['uniacid']}' AND hid=$hid ";
            $time = $_GPC['time'];
            if (!empty($_GPC['time'])) {
                $starttime = strtotime($_GPC['time']['start']);
                $endtime = strtotime($_GPC['time']['end']);
            }else {
                $starttime = 1446307200;
                $endtime = TIMESTAMP;
            }
            $condition.=" AND createtime BETWEEN $starttime AND $endtime ";
            if(!empty($nickname)) {
                $condition .= " AND nickname LIKE '%$nickname%' ";
            }
            $status = intval($_GPC['status']);
            $condition1 = "";
            if($status == 1) { //未参与
                $condition1.=" AND isjoin=0 ";
            }elseif($status == 2) { //已参与
                $condition1.=" AND isjoin=1";
            }elseif($status == 3) { //未加油
                $condition1.=" AND isjiayou=0 ";
            }elseif($status == 4) { //已加油
                $condition1.=" AND isjiayou=1 ";
            }elseif($status == 5) { //发起人
                $condition1.=" AND ispost=1 ";
            }elseif($status == 6) { //参与者
                $condition1.=" AND isshare=1 ";
            }elseif($status == 7) { //既是发起人又是参与者
                $condition1.=" AND isshare=1 AND ispost=1 ";
            }elseif($status == 8) { //既是发起人又是参与者
                $condition1.=" AND code <> '' ";
            }

            
            $condition.=" AND openid NOT IN (SELECT from_user FROM ".tablename($this->modulename."_blacklist")." WHERE status = 0 )";
            $isexport = intval($_GPC['isexport']);
            if($isexport==1) {
                $list = pdo_fetchall("SELECT * FROM " . tablename($this->modulename . '_attender') . " $condition $condition1 ORDER BY id desc, createtime DESC ");        
                foreach ($list as $key => $value) {
                    $list[$key]['helptimes']=pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename($this->modulename.'_record')." WHERE weid = '{$_W['uniacid']}' AND hid=$hid AND target_from_user=:from_user ",array(':from_user'=>$value['openid']));
                }

                $filename = '参与用户_'.date('YmdHis');
                // 设置excel标题行
                $excel_title = array('编号','头像地址', '昵称', '性别', '地区', '是否加油', '是否参与', '创建时间', '当前温度', '免单兑换码', '被加油次数', '阅读次数', '分享朋友次数', '转发朋友圈次数', '朋友圈阅读次数');
               $i=0;
               foreach ($list as $key => $value) {
                    $arr[$i]['id'] = $value['id'];
                    $arr[$i]['headimgurl'] = $value['headimgurl'];
                    $arr[$i]['nickname'] = $value['nickname'];
                    if($value['sex'] == 1) {
                        $arr[$i]['sex'] = '男';
                    }elseif($value['sex'] == 2) {
                        $arr[$i]['sex'] = '女';
                    }else{
                        $arr[$i]['sex'] = '未知';
                    }
                    $arr[$i]['area'] = $value['country'].$value['province'].$value['city'];
                    if($value['isjiayou'] == 1) {
                        $arr[$i]['isjiayou'] = '是';
                    }else{
                        $arr[$i]['isjiayou'] = '否';
                    }
                    if($value['isjoin'] == 1) {
                        $arr[$i]['isjoin'] = '是';
                    }else{
                        $arr[$i]['isjoin'] = '否';
                    }
                    $arr[$i]['createtime'] = date('Y-m-d H:i:s',$value['createtime']);
                    $arr[$i]['degree'] = $value['degree'];
                    $arr[$i]['code'] = $value['code'];
                    $arr[$i]['helptimes'] = $value['helptimes'];
                    $arr[$i]['readtimes'] = $value['readtimes'];
                    $arr[$i]['sharetimes'] = $value['sharetimes'];
                    $arr[$i]['transfertimes'] = $value['transfertimes'];
                    $arr[$i]['transferreadtimes'] = $value['transferreadtimes'];
                    $i++;
                }

                $this->exportexcel($arr, $filename, $excel_title);
            }else {
                $list = pdo_fetchall("SELECT * FROM " . tablename($this->modulename . '_attender') . " $condition $condition1 ORDER BY id desc, createtime DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize);        
                $total = pdo_fetchcolumn("SELECT COUNT(1) FROM " . tablename($this->modulename . '_attender') . " $condition ");        
                $total1 = pdo_fetchcolumn("SELECT COUNT(1) FROM " . tablename($this->modulename . '_attender') . " $condition AND isjoin=0 ");        
                $total2 = pdo_fetchcolumn("SELECT COUNT(1) FROM " . tablename($this->modulename . '_attender') . " $condition AND isjoin=1 "); 
                $total3 = pdo_fetchcolumn("SELECT COUNT(1) FROM " . tablename($this->modulename . '_attender') . " $condition AND isjiayou=0 "); 
                $total4 = pdo_fetchcolumn("SELECT COUNT(1) FROM " . tablename($this->modulename . '_attender') . " $condition AND isjiayou=1 "); 
                $total5 = pdo_fetchcolumn("SELECT COUNT(1) FROM " . tablename($this->modulename . '_attender') . " $condition AND ispost=1 "); 
                $total6 = pdo_fetchcolumn("SELECT COUNT(1) FROM " . tablename($this->modulename . '_attender') . " $condition AND isshare=1 "); 
                $total7 = pdo_fetchcolumn("SELECT COUNT(1) FROM " . tablename($this->modulename . '_attender') . " $condition AND isshare=1 AND ispost=1 "); 
                $total8 = pdo_fetchcolumn("SELECT COUNT(1) FROM " . tablename($this->modulename . '_attender') . " $condition AND code <> '' "); 

                foreach ($list as $key => $value) {
                    $list[$key]['helptimes']=pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename($this->modulename.'_record')." WHERE weid = '{$_W['uniacid']}' AND hid=$hid and target_from_user=:from_user ",array(':from_user'=>$value['openid']));
                }
                if($status == 1) { //未参与
                    $pager = pagination($total1, $pindex, $psize);
                }elseif($status == 2) { //已参与
                    $pager = pagination($total2, $pindex, $psize);
                }elseif($status == 3) { //未加油
                    $pager = pagination($total3, $pindex, $psize);
                }elseif($status == 4) { //已加油
                    $pager = pagination($total4, $pindex, $psize);
                }elseif($status == 5) { //发起人
                    $pager = pagination($total5, $pindex, $psize);
                }elseif($status == 6) { //参与者
                    $pager = pagination($total6, $pindex, $psize);
                }elseif($status == 7) { //既是发起人又是参与者
                    $pager = pagination($total7, $pindex, $psize);
                }elseif($status == 8) { //既是发起人又是参与者
                    $pager = pagination($total8, $pindex, $psize);
                }else {
                    $pager = pagination($total, $pindex, $psize);
                }
                
            }
            
        }elseif($operation=='black') {
            $id = $_GPC['id'];//用户id
            $attender = pdo_fetch("SELECT * FROM " . tablename($this->modulename . '_attender') . " WHERE hid =:hid AND id=:id AND weid=:weid  LIMIT 1", array(':hid' => $hid, ':id' => $id, ':weid' => $_W['uniacid']));

            if (empty($attender)) {
                message('数据不存在!');
            }

            $blacker = pdo_fetch("SELECT * FROM " . tablename($this->modulename . '_blacklist') . " WHERE  hid =:hid  and from_user=:from_user AND weid=:weid  LIMIT 1", array(':hid' => $hid, ':from_user' => $attender['openid'], ':weid' => $_W['uniacid']));
            if (!empty($blacker)) {
                if($blacker['status'] == 0) {
                    message('该用户已经在黑名单中!', $this->createWebUrl('attender', array('op' => 'display', 'hid' => $hid)));
                }else {
                    pdo_update($this->modulename.'_blacklist',array('dateline'=>TIMESTAMP,'status'=>0),array('id'=>$blacker['id']));
                }
                
            }else {
                $data = array(
                    'weid' => $_W['uniacid'],
                    'hid' => $hid,
                    'from_user' => $attender['openid'],
                    'status' => 0,
                    'dateline' => TIMESTAMP
                );
                pdo_insert($this->modulename.'_blacklist', $data);
            }            
            message('操作成功！', $this->createWebUrl('attender', array('op' => 'display', 'hid' => $hid)), 'success');
        }elseif($operation=='delete') {
            $id = $_GPC['id'];//用户id
            $attender = pdo_fetch("SELECT * FROM " . tablename($this->modulename . '_attender') . " WHERE id=:id AND weid=:weid  LIMIT 1", array(':id' => $id, ':weid' => $_W['uniacid']));
            if (empty($attender)) {
                message('数据不存在!');
            }
            $insertdata = array(
                'weid'=>$attender['weid'],
                'hid'=>$attender['hid'],
                'unionid'=>$attender['unionid'],
                'openid'=>$attender['openid'],
                'name'=>$attender['name'],
                'isjoin'=>$attender['isjoin'],
                'iszihai'=>$attender['iszihai'],
                'isjiayou'=>$attender['isjiayou'],
                'ispost'=>$attender['ispost'],
                'isshare'=>$attender['isshare'],
                'degree'=>$attender['degree'],
                'code'=>$attender['code'],
                'nickname'=>$attender['nickname'],
                'sex'=>$attender['sex'],
                'province'=>$attender['province'],
                'country'=>$attender['country'],
                'city'=>$attender['city'],
                'createtime'=>$attender['createtime'],
                'headimgurl'=>$attender['headimgurl'],
                'readtimes'=>$attender['readtimes'],
                'sharetimes'=>$attender['sharetimes'],
                'transfertimes'=>$attender['transfertimes'],
                'transferreadtimes'=>$attender['transferreadtimes'],
                );
            pdo_insert($this->modulename.'_attender_his',$insertdata);
            pdo_delete($this->modulename . '_attender', array('id' => $id));

            pdo_delete($this->modulename.'_record',array('from_user'=>$attender['openid']));
            pdo_delete($this->modulename.'_transfer',array('from_user'=>$attender['openid']));
            message('操作成功！', $this->createWebUrl('attender', array('op' => 'display', 'hid' => $hid)), 'success');
        }elseif($operation=='detail') {
            $id = intval($_GPC['id']);
            $pindex = max(1, intval($_GPC['page']));
            $psize = 10;
            $attender = pdo_fetch("SELECT * FROM " . tablename($this->modulename . '_attender') . " WHERE id=:id AND weid=:weid AND hid=:hid",array(':id'=>$id,':weid'=>$_W['uniacid'],':hid'=>$hid));
            $attender['supporters'] = pdo_fetchall("SELECT a.*,r.degree as helpdegree, r.createtime as helptime,r.id as rid FROM ".tablename($this->modulename.'_attender')." a, ".tablename($this->modulename."_record")." r WHERE a.weid = r.weid and a.hid=r.hid and a.openid = r.from_user  AND r.target_from_user =:target_from_user AND r.hid=:hid AND r.weid=:weid ORDER BY r.id,r.createtime desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize,array(':target_from_user'=>$attender['openid'],':hid'=>$hid,':weid'=>$_W['uniacid']));
            $total = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename($this->modulename.'_attender')." a, ".tablename($this->modulename."_record")." r WHERE a.openid = r.from_user  AND r.target_from_user =:target_from_user AND r.hid=:hid AND r.weid=:weid ",array(':target_from_user'=>$attender['openid'],':hid'=>$hid,':weid'=>$_W['uniacid']));
            foreach ($attender['supporters'] as $key => $value) {
                $attender['supporters'][$key]['helptimes']=pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename($this->modulename.'_record')." WHERE hid=:hid AND weid=:weid and target_from_user=:from_user ",array(':hid'=>$hid,':weid'=>$_W['uniacid'], ':from_user'=>$value['openid']));
            }
            if (checksubmit('confirm')) {
                message('操作成功！', referer(), 'success');
            }
            $pager = pagination($total, $pindex, $psize);
            
        }
        include $this->template('attender');

    }

    //加油记录
    public function doWebSupporter() {
        global $_W, $_GPC;
        checklogin();
        $action = 'supporter';
        $title = '加油记录';
        $hid = intval($_GPC['hid']); //活动ID
        if (empty($hid)) {
            message('请先选择活动!');
        }
        $operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
        load()->func('tpl');
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;

        $nickname = $_GPC['nickname'];
        $condition = " WHERE r.weid = '{$_W['uniacid']}' AND r.hid=$hid ";
        $time = $_GPC['time'];
        if (!empty($_GPC['time'])) {
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']);
        }else {
            $starttime = 1446307200;
            $endtime = TIMESTAMP;
        }
        $condition.=" AND r.createtime BETWEEN $starttime AND $endtime ";
        if(!empty($nickname)) {
            $condition .= " AND a.nickname LIKE '%$nickname%' ";
        }
        $condition.=" AND a.openid NOT IN (SELECT from_user FROM ".tablename($this->modulename."_blacklist")." WHERE status = 0 )";
        $isexport = intval($_GPC['isexport']);
        if($isexport==1) {
            $supporters = pdo_fetchall("SELECT a.*,r.degree as helpdegree, r.createtime as helptime,r.id as rid FROM ".tablename($this->modulename.'_attender')." a, ".tablename($this->modulename."_record")." r $condition AND r.hid = a.hid  AND r.weid = a.weid AND  a.openid = r.from_user   ORDER BY r.id,r.createtime ");
            foreach ($supporters as $key => $value) {
                $supporters[$key]['helptimes']=pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename($this->modulename.'_record')." WHERE weid = '{$_W['uniacid']}' AND hid=$hid and target_from_user=:from_user ",array(':from_user'=>$value['openid']));
            }

            $filename = '加油记录_'.date('YmdHis');
            // 设置excel标题行
            $excel_title = array('编号','头像地址', '昵称', '性别', '地区', '是否参与', '当前温度', '加温度数', '加温时间','免单兑换码', '被加油次数', '阅读次数', '分享朋友次数', '转发朋友圈次数', '朋友圈阅读次数');
           $i=0;
           foreach ($supporters as $key => $value) {
                $arr[$i]['id'] = $value['id'];
                $arr[$i]['headimgurl'] = $value['headimgurl'];
                $arr[$i]['nickname'] = $value['nickname'];
                if($value['sex'] == 1) {
                    $arr[$i]['sex'] = '男';
                }elseif($value['sex'] == 2) {
                    $arr[$i]['sex'] = '女';
                }else{
                    $arr[$i]['sex'] = '未知';
                }
                $arr[$i]['area'] = $value['country'].$value['province'].$value['city'];
                if($value['isjoin'] == 1) {
                    $arr[$i]['isjoin'] = '是';
                }else{
                    $arr[$i]['isjoin'] = '否';
                }
                $arr[$i]['degree'] = $value['degree'];
                $arr[$i]['helpdegree'] = $value['helpdegree'];
                $arr[$i]['helptime'] = date('Y-m-d H:i:s',$value['helptime']);
                $arr[$i]['code'] = $value['code'];
                $arr[$i]['helptimes'] = $value['helptimes'];
                $arr[$i]['readtimes'] = $value['readtimes'];
                $arr[$i]['sharetimes'] = $value['sharetimes'];
                $arr[$i]['transfertimes'] = $value['transfertimes'];
                $arr[$i]['transferreadtimes'] = $value['transferreadtimes'];
                $i++;
            }

            $this->exportexcel($arr, $filename, $excel_title);
        }else {
            $supporters = pdo_fetchall("SELECT a.*,r.degree as helpdegree, r.createtime as helptime,r.id as rid FROM ".tablename($this->modulename.'_attender')." a, ".tablename($this->modulename."_record")." r $condition AND r.weid = a.weid AND r.hid = a.hid AND a.openid = r.from_user   ORDER BY r.id,r.createtime LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
            $total = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename($this->modulename.'_attender')." a, ".tablename($this->modulename."_record")." r $condition AND r.weid = a.weid AND r.hid = a.hid  AND a.openid = r.from_user ");
            foreach ($supporters as $key => $value) {
                $supporters[$key]['helptimes']=pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename($this->modulename.'_record')." WHERE weid = '{$_W['uniacid']}' AND hid=$hid and target_from_user=:from_user ",array(':from_user'=>$value['openid']));
            }
            $pager = pagination($total, $pindex, $psize);
            
        }
        include $this->template('supporter');
    }

    //转发记录
    public function doWebTransfer() {
        global $_W, $_GPC;
        checklogin();
        $action = 'transfer';
        $title = '转发记录';
        $hid = intval($_GPC['hid']); //活动ID
        if (empty($hid)) {
            message('请先选择活动!');
        }
        $operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
        load()->func('tpl');
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;

        $nickname = $_GPC['nickname'];
        $condition = " WHERE r.weid = '{$_W['uniacid']}' AND r.hid=$hid ";
        $time = $_GPC['time'];
        if (!empty($_GPC['time'])) {
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']);
        }else {
            $starttime = 1446307200;
            $endtime = TIMESTAMP;
        }
        $condition.=" AND r.createtime BETWEEN $starttime AND $endtime ";
        if(!empty($nickname)) {
            $condition .= " AND a.nickname LIKE '%$nickname%' ";
        }
        $condition.=" AND a.openid NOT IN (SELECT from_user FROM ".tablename($this->modulename."_blacklist")." WHERE status = 0 )";
        $isexport = intval($_GPC['isexport']);
        if($isexport==1) {
            $transfers = pdo_fetchall("SELECT a.*, r.createtime as transfertime,r.id as rid, r.sharetime FROM ".tablename($this->modulename.'_attender')." a, ".tablename($this->modulename."_transfer")." r $condition AND r.hid = a.hid  AND r.weid = a.weid AND a.openid = r.from_user AND r.from_user<>r.friend_openid AND r.friend_openid = ''  ORDER BY r.id,r.createtime LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
            foreach ($transfers as $key => $value) {
                $transfers[$key]['helptimes']=pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename($this->modulename.'_record')." WHERE target_from_user=:from_user ",array(':from_user'=>$value['openid']));
                $transfers[$key]['friends']=pdo_fetchall("SELECT a.*, r.createtime as transfertime,r.id as rid FROM ".tablename($this->modulename.'_attender')." a, ".tablename($this->modulename."_transfer")." r  WHERE a.openid = r.friend_openid AND r.from_user<>r.friend_openid AND r.friend_openid <> '' AND r.sharetime =:sharetime AND r.from_user=:from_user  GROUP BY r.sharetime,r.friend_openid ORDER BY r.id,r.createtime",array(':sharetime'=>$value['sharetime'],':from_user'=>$value['openid']));
                foreach ($transfers[$key]['friends'] as $k => $v) {
                    $transfers[$key]['friends'][$k]['helptimes'] = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename($this->modulename.'_record')." WHERE target_from_user=:from_user ",array(':from_user'=>$v['openid']));
                }
            }

            $filename = '加油记录_'.date('YmdHis');
            // 设置excel标题行
            $excel_title = array('身份','编号','头像地址', '昵称', '性别', '地区', '是否参与', '是否加油','当前温度', '免单兑换码', '被加油次数', '阅读次数', '分享朋友次数', '转发朋友圈次数', '朋友圈阅读次数','转发时间/阅读时间');
           $i=0;
           foreach ($transfers as $key => $value) {
                $arr[$i]['istransfer'] = '转发者';
                $arr[$i]['id'] = $value['id'];
                $arr[$i]['headimgurl'] = $value['headimgurl'];
                $arr[$i]['nickname'] = $value['nickname'];
                if($value['sex'] == 1) {
                    $arr[$i]['sex'] = '男';
                }elseif($value['sex'] == 2) {
                    $arr[$i]['sex'] = '女';
                }else{
                    $arr[$i]['sex'] = '未知';
                }
                $arr[$i]['area'] = $value['country'].$value['province'].$value['city'];
                if($value['isjoin'] == 1) {
                    $arr[$i]['isjoin'] = '是';
                }else{
                    $arr[$i]['isjoin'] = '否';
                }
                if($value['isjiayou'] == 1) {
                    $arr[$i]['isjiayou'] = '是';
                }else{
                    $arr[$i]['isjiayou'] = '否';
                }
                $arr[$i]['degree'] = $value['degree'];
                $arr[$i]['code'] = $value['code'];
                $arr[$i]['helptimes'] = $value['helptimes'];
                $arr[$i]['readtimes'] = $value['readtimes'];
                $arr[$i]['sharetimes'] = $value['sharetimes'];
                $arr[$i]['transfertimes'] = $value['transfertimes'];
                $arr[$i]['transferreadtimes'] = $value['transferreadtimes'];
                $arr[$i]['transfertime'] = date('Y-m-d H:i:s',$value['transfertime']);
                $i++;
                if(!empty($transfers[$key]['friends'])) {
                    foreach ($transfers[$key]['friends'] as $k => $v) {
                        $arr[$i]['istransfer'] = '-------------|';
                        $arr[$i]['id'] = $v['id'];
                        $arr[$i]['headimgurl'] = $v['headimgurl'];
                        $arr[$i]['nickname'] = $v['nickname'];
                        if($v['sex'] == 1) {
                            $arr[$i]['sex'] = '男';
                        }elseif($v['sex'] == 2) {
                            $arr[$i]['sex'] = '女';
                        }else{
                            $arr[$i]['sex'] = '未知';
                        }
                        $arr[$i]['area'] = $v['country'].$v['province'].$v['city'];
                        if($v['isjoin'] == 1) {
                            $arr[$i]['isjoin'] = '是';
                        }else{
                            $arr[$i]['isjoin'] = '否';
                        }
                        if($v['isjiayou'] == 1) {
                            $arr[$i]['isjiayou'] = '是';
                        }else{
                            $arr[$i]['isjiayou'] = '否';
                        }
                        $arr[$i]['degree'] = $v['degree'];
                        $arr[$i]['code'] = $v['code'];
                        $arr[$i]['helptimes'] = $v['helptimes'];
                        $arr[$i]['readtimes'] = $v['readtimes'];
                        $arr[$i]['sharetimes'] = $v['sharetimes'];
                        $arr[$i]['transfertimes'] = $v['transfertimes'];
                        $arr[$i]['transferreadtimes'] = $v['transferreadtimes'];
                        $arr[$i]['transfertime'] = date('Y-m-d H:i:s',$v['transfertime']);
                        $i++;
                    }
                }
                
                
            }

            $this->exportexcel($arr, $filename, $excel_title);
        }else {
            $transfers = pdo_fetchall("SELECT a.*, r.createtime as transfertime,r.id as rid, r.sharetime FROM ".tablename($this->modulename.'_attender')." a, ".tablename($this->modulename."_transfer")." r $condition AND r.hid = a.hid  AND r.weid = a.weid AND a.openid = r.from_user AND r.from_user<>r.friend_openid AND r.friend_openid = ''  ORDER BY r.id,r.createtime LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
            $total = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename($this->modulename.'_attender')." a, ".tablename($this->modulename."_transfer")." r $condition AND a.openid = r.from_user AND r.from_user<>r.friend_openid AND r.friend_openid = '' ");
            foreach ($transfers as $key => $value) {
                $transfers[$key]['helptimes']=pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename($this->modulename.'_record')." WHERE weid='{$_W['uniacid']}' AND hid=$hid AND  target_from_user=:from_user ",array(':from_user'=>$value['openid']));
                $transfers[$key]['friends']=pdo_fetchall("SELECT a.*, r.createtime as transfertime,r.id as rid FROM ".tablename($this->modulename.'_attender')." a, ".tablename($this->modulename."_transfer")." r  WHERE r.hid = a.hid  AND r.weid = a.weid AND  a.weid='{$_W['uniacid']}' AND a.hid=$hid AND a.openid = r.friend_openid AND r.from_user<>r.friend_openid AND r.friend_openid <> '' AND r.sharetime =:sharetime AND r.from_user=:from_user  GROUP BY r.sharetime,r.friend_openid ORDER BY r.id,r.createtime",array(':sharetime'=>$value['sharetime'],':from_user'=>$value['openid']));
                foreach ($transfers[$key]['friends'] as $k => $v) {
                    $transfers[$key]['friends'][$k]['helptimes'] = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename($this->modulename.'_record')." WHERE weid='{$_W['uniacid']}' AND hid=$hid and target_from_user=:from_user ",array(':from_user'=>$v['openid']));
                }
            }

            $pager = pagination($total, $pindex, $psize);
            
        }
        include $this->template('transfer');
        
    }

    //跳失率统计
    public function doWebPagerate() {
        global $_W, $_GPC;
        checklogin();
        $weid = $_W['uniacid'];
        $action = 'pagerate';
        $title = '跳失率统计';
        $hid = intval($_GPC['hid']); //活动ID
        if (empty($hid)) {
            message('请先选择活动!');
        }
        $operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
        load()->func('tpl');
        $pagerate = pdo_fetchall("SELECT * FROM ".tablename($this->modulename."_pagerate")." WHERE hid = $hid AND weid = $weid ORDER BY pageid");
        include $this->template('pagerate');

    }

    public function doWebBlacklist()
    {
        global $_W, $_GPC;
        checklogin();
        load()->model('mc');
        $weid = $_W['uniacid'];
        $hid = intval($_GPC['hid']);

        $operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
        if ($operation == 'display') {
            $pindex = max(1, intval($_GPC['page']));
            $psize = 10;

            $list = pdo_fetchall("SELECT *,b.id as bid FROM ".tablename($this->modulename."_blacklist")." b, ".tablename($this->modulename."_attender")." a WHERE a.weid='{$_W['uniacid']}' AND a.hid=$hid AND a.hid=b.hid and a.weid = b.weid and a.openid = b.from_user AND b.status = 0");

            if (!empty($list)) {
                $total = pdo_fetchcolumn('SELECT COUNT(1) FROM ' . tablename($this->modulename . '_blacklist') . "  WHERE hid=$hid AND weid=:weid", array(':weid' => $_W['uniacid']));
            }
            $pager = pagination($total, $pindex, $psize);
        } else if ($operation == 'black') {
            $id = $_GPC['id'];
            pdo_update($this->modulename . '_blacklist', array('status' => 1),array('id'=>$id,'weid'=>$weid));
            message('操作成功！', $this->createWebUrl('blacklist', array('op' => 'display')), 'success');
        }

        include $this->template('blacklist');
    }

    public function addClicktimes($pageid) {
        global $_W, $_GPC;
        $hid=$_GPC['hid'];
        $weid=$_W['uniacid'];
        $clicktimes=pdo_fetchcolumn("SELECT clicktimes FROM ".tablename($this->modulename.'_pagerate')." WHERE hid = $hid AND weid = $weid AND pageid=$pageid");
        if(empty($clicktimes)) {
            $data=array(
                'weid'=>$weid,
                'hid'=>$hid,
                'pageid'=>$pageid,
                'clicktimes'=>1                );
            pdo_insert($this->modulename.'_pagerate',$data);
        }else{
            pdo_update($this->modulename.'_pagerate',array('clicktimes'=>$clicktimes+1),array('pageid'=>$pageid));
        }
    }

    //首页
    public function doMobileIndex() {
        global $_W, $_GPC;
        $hid=$_GPC['hid'];
        $weid=$_W['uniacid'];
        $method = "index";
        
        $this->checkHuodongTime($hid, $method);
        $from_user = $this->_fromuser;
        $shareurl = $_W['siteroot'].'app/'.$this->createMobileUrl("index",array ('hid' => $hid, 'weid' => $weid,'pageid'=>1));//分享URL

        $huodong=pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_huodong')." WHERE id=$hid AND weid=$weid");
        $attender = pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_attender')." WHERE hid=:hid AND weid=:weid AND openid=:openid",array(':hid' => $hid, ':weid' => $weid, ':openid'=>$from_user));
        pdo_update($this->modulename.'_attender',array('readtimes'=>$attender['readtimes']+1),array('id'=>$attender['id']));
        //检查是否第一次
        if($attender['isjoin'] == 1) {
            $url = $this->createMobileUrl('bangdan2', array('hid' => $hid, "weid"=>$weid,'pageid'=>6), true);
            die('<script>location.href = "' . $url . '";</script>');
            exit();
        }else {
            $this->addClicktimes(1);
            include $this->template('index');
        }
    }

    //自嗨
    public function doMobileZihai() {
        global $_W, $_GPC;

        $hid=$_GPC['hid'];
        $weid=$_W['uniacid'];
        $method = "zihai";

        $this->checkHuodongTime($hid, $method);
        $from_user = $this->_fromuser;
        $huodong=pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_huodong')." WHERE id=$hid AND weid=$weid");
    //    $sharetime = TIMESTAMP;
        $shareurl = $_W['siteroot'].'app/'.$this->createMobileUrl("friendindex",array ('hid' => $hid, 'weid' => $weid,'transfer_openid'=>base64_encode($from_user),'pageid'=>4));//分享URL
        $this->addClicktimes(2);
        include $this->template('zihai');
    }

    public function doMobileCheckZihai() {
        global $_W, $_GPC;

        $hid=$_GPC['hid'];
        $weid=$_W['uniacid'];
        $method = "checkzihai";

        $this->checkHuodongTime($hid, $method);
        $from_user = $this->_fromuser;
        $attender = pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_attender')." WHERE hid=:hid AND weid=:weid AND openid=:openid",array(':hid' => $hid, ':weid' => $weid, ':openid'=>$from_user));
        
        if($attender['iszihai'] == 0) {
            pdo_update($this->modulename.'_attender',array('iszihai'=>1,'isjoin'=>1,'degree'=>$attender['degree']+5),array('id'=>$attender['id']));
            $insertdata = array(
                'weid'=>$weid,
                'hid'=>$hid,
                'from_user'=>$from_user,
                'target_from_user'=>$from_user,
                'degree'=>5,
                'createtime'=>TIMESTAMP
                );
            pdo_insert($this->modulename.'_record',$insertdata);
            $this->showMsg("自嗨",1);
        }else {
            $this->showMsg("已自嗨",2);
        }
    }

    public function showMsg($msg, $status = 1) {
        $result['msg'] = $msg;
        $result['code'] = $status;
        message($result, '', 'ajax');
    }

    //免单
    public function doMobileMiandan() {
        global $_W, $_GPC;
        $hid=$_GPC['hid'];
        $weid=$_W['uniacid'];
        $method = "miandan";

        $this->checkHuodongTime($hid, $method);
        $from_user = $this->_fromuser;
        $shareurl = $_W['siteroot'].'app/'.$this->createMobileUrl("index",array ('hid' => $hid, 'weid' => $weid,'pageid'=>1));//分享URL
        $huodong=pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_huodong')." WHERE id=$hid AND weid=$weid");
        //生成8位兑换码
        $flag = true;
        while($flag) {
            $duihuancode = $this->getRandomString(8);
            $attender = pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_attender')." WHERE hid=:hid AND weid=:weid AND openid=:openid",array(':hid' => $hid, ':weid' => $weid, ':openid'=>$from_user));
            if(empty($attender['code'])) {
                $isGenerated = pdo_fetchcolumn("SELECT count(1) FROM ".tablename($this->modulename.'_attender')." WHERE hid=:hid AND weid=:weid AND code=:code",array(':hid' => $hid, ':weid' => $weid, ':code'=>$duihuancode));
                if(empty($isGenerated)) {
                    pdo_update($this->modulename.'_attender',array('code'=>$duihuancode),array('id'=>$attender['id']));
                    $flag = false;
                }
            }else {
                $duihuancode = $attender['code'];
                $flag = false;
            }
        }
        $this->addClicktimes(8);
        include $this->template('miandan');
    }

    //生成8位兑换码
    function getRandomString($len, $chars=null) {
        if (is_null($chars)){
            $chars = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        }  
        mt_srand(10000000*(double)microtime());
        for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++){
            $str .= $chars[mt_rand(0, $lc)];  
        }
        return $str;
    }

    //发起者排行榜
    public function doMobileBangdan() {
        global $_W, $_GPC;
        $hid=$_GPC['hid'];
        $weid=$_W['uniacid'];
        $method = "bangdan";
        $this->checkHuodongTime($hid, $method);
        $from_user = $this->_fromuser;
        $huodong=pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_huodong')." WHERE id=$hid AND weid=$weid");
        $shareurl = $_W['siteroot'].'app/'.$this->createMobileUrl("friendindex",array ('hid' => $hid, 'weid' => $weid,'transfer_openid'=>base64_encode($from_user),'pageid'=>4));//分享URL

        $attender = pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_attender')." WHERE hid=:hid AND weid=:weid AND openid=:openid",array(':hid' => $hid, ':weid' => $weid, ':openid'=>$from_user));
        //查询帮忙加油名单
        $records = pdo_fetchall("SELECT * FROM ".tablename($this->modulename.'_record')." WHERE hid=:hid AND weid=:weid  AND target_from_user =:openid order by createtime ",array(':hid' => $hid, ':weid' => $weid, ':openid'=>$from_user));
        foreach ($records as $key => $value) {
            $records[$key]['friend'] = pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_attender')." WHERE hid=:hid AND weid=:weid AND openid=:openid",array(':hid' => $hid, ':weid' => $weid, ':openid'=>$value['from_user']));
        }
        $this->addClicktimes(3);
       include $this->template('bangdan');
    }

    //参与者首页
    public function doMobileFriendIndex() {
        global $_W, $_GPC;
        $hid=$_GPC['hid'];
        $weid=$_W['uniacid'];
        $transfer_openid = base64_decode($_GPC['transfer_openid']);
        $type = intval($_GPC['type']);
        $sharetime = intval($_GPC['sharetime']);
        $method = "friendindex";
        $this->checkHuodongTime($hid, $method);
        $from_user = $this->_fromuser;
        //TODO - need to delete
        //$transfer_openid = $from_user;
        $shareurl = $_W['siteroot'].'app/'.$this->createMobileUrl("index",array ('hid' => $hid, 'weid' => $weid,'pageid'=>1));//分享URL
        
        $count = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename($this->modulename.'_record')." WHERE from_user=:from_user AND from_user<>target_from_user ",array(":from_user"=>$from_user));
        //分享人就是自己
        if(!empty($transfer_openid) && $transfer_openid == $from_user) {
            $url = $this->createMobileUrl('index', array('hid' => $hid, "weid"=>$weid,'pageid'=>1), true);
            die('<script>location.href = "' . $url . '";</script>');
            exit();
        }else{
            $isjiayou = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename($this->modulename.'_record')." WHERE hid=$hid AND weid = $weid AND from_user=:from_user AND target_from_user=:target_from_user AND from_user<>target_from_user ",array(":from_user"=>$from_user,":target_from_user"=>$transfer_openid));
            $attender = pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_attender')." WHERE hid=:hid AND weid=:weid AND openid=:openid",array(':hid' => $hid, ':weid' => $weid, ':openid'=>$from_user));
            pdo_update($this->modulename.'_attender', array('isshare'=>1),array('id'=>$attender['id']));
            //添加转发记录
            $data=array(
                'weid'=>$weid,
                'hid'=>$hid,
                'from_user'=>$transfer_openid,
                'friend_openid'=>$from_user,
                'createtime'=>TIMESTAMP,
                'sharetime'=>$sharetime,
                'type'=>$type
                );
            pdo_insert($this->modulename.'_transfer',$data);

            $huodong=pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_huodong')." WHERE id=$hid AND weid=$weid");
            $transfer_attender = pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_attender')." WHERE hid=:hid AND weid=:weid AND openid=:openid",array(':hid' => $hid, ':weid' => $weid, ':openid'=>$transfer_openid));
            pdo_update($this->modulename.'_attender', array('transferreadtimes'=>$transfer_attender['transferreadtimes']+1),array('id'=>$transfer_attender['id']));

            if($isjiayou>=1) {
                    $remaindegree = 100-$transfer_attender['degree'];
                    //查询帮忙加油名单
                    $records = pdo_fetchall("SELECT * FROM ".tablename($this->modulename.'_record')." WHERE hid=:hid AND weid=:weid  AND target_from_user =:openid order by createtime ",array(':hid' => $hid, ':weid' => $weid, ':openid'=>$transfer_openid));
                    foreach ($records as $key => $value) {
                        $records[$key]['friend'] = pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_attender')." WHERE hid=:hid AND weid=:weid AND openid=:openid",array(':hid' => $hid, ':weid' => $weid, ':openid'=>$value['from_user']));
                    }

                  $degree = pdo_fetchcolumn("SELECT degree FROM ".tablename($this->modulename.'_record')." WHERE hid=:hid AND weid=:weid AND from_user=:from_user AND target_from_user=:target_from_user LIMIT 0,1",array(':hid' => $hid, ':weid' => $weid, ':from_user'=>$from_user,":target_from_user"=>$transfer_openid));
                  $valNum=intval($degree);
                  $valNum1 = abs(intval($degree));
                  $this->addClicktimes(5);
                  include $this->template('jiayou');
            }else{
                $this->addClicktimes(4);
                include $this->template('friendindex');
            }
        }
    }

    //帮好友加油
    public function doMobileJiayou() {
        global $_W, $_GPC;
        $hid=$_GPC['hid'];
        $weid=$_W['uniacid'];
        $transfer_openid = base64_decode($_GPC['transfer_openid']);
        $valNum=intval($_GPC['valNum']);
        $valNum1 = abs($valNum);
        $isover=intval($_GPC['isover']);

        $method = "jiayou";
        $this->checkHuodongTime($hid, $method);
        $from_user = $this->_fromuser;
        //TODO - need to delete
        //$transfer_openid = $from_user;

        $operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
        $shareurl = $_W['siteroot'].'app/'.$this->createMobileUrl("index",array ('hid' => $hid, 'weid' => $weid,'pageid'=>1));//分享URL
        $huodong=pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_huodong')." WHERE id=$hid AND weid=$weid");
        $attender = pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_attender')." WHERE hid=:hid AND weid=:weid AND openid=:openid",array(':hid' => $hid, ':weid' => $weid, ':openid'=>$from_user));
        $transfer_attender = pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_attender')." WHERE hid=:hid AND weid=:weid AND openid=:openid",array(':hid' => $hid, ':weid' => $weid, ':openid'=>$transfer_openid));
        if($isover==1) {
            if($attender['isjoin']==1) {
                $url = $this->createMobileUrl('index', array('hid' => $hid, "weid"=>$weid,'pageid'=>1), true);
                die('<script>location.href = "' . $url . '";</script>');
                exit();
            }else {
                $url = $this->createMobileUrl('bangdan2', array('hid' => $hid, "weid"=>$weid,'pageid'=>6), true);
                die('<script>location.href = "' . $url . '";</script>');
                exit();
            }
        }
        if($operation=='display') {
            $this->addClicktimes(5);
            $remaindegree = 100-$transfer_attender['degree'];
            //查询帮忙加油名单
            $records = pdo_fetchall("SELECT * FROM ".tablename($this->modulename.'_record')." WHERE hid=:hid AND weid=:weid  AND target_from_user =:openid order by createtime ",array(':hid' => $hid, ':weid' => $weid, ':openid'=>$transfer_openid));
            foreach ($records as $key => $value) {
                $records[$key]['friend'] = pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_attender')." WHERE hid=:hid AND weid=:weid AND openid=:openid",array(':hid' => $hid, ':weid' => $weid, ':openid'=>$value['from_user']));
            }
            
            include $this->template('jiayou');
        }elseif($operation=='post') {
            $isjiayou = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename($this->modulename.'_record')." WHERE hid=$hid AND weid = $weid AND  from_user=:from_user AND target_from_user=:target_from_user AND from_user<>target_from_user ",array(":from_user"=>$from_user,":target_from_user"=>$transfer_openid));
            if($isjiayou<1) {
                  if($valNum>0) {
                        $degree = $transfer_attender['degree']+$valNum;
                        if($degree>100) {
                            $degree = 100;
                        }
                        pdo_update($this->modulename.'_attender',array('degree'=>$degree),array('id'=>$transfer_attender['id']));
                        $transfer_attender['degree'] = $transfer_attender['degree'] + $valNum;
                    }
                    if($valNum<0) {
                        $degree = $transfer_attender['degree'] - abs($valNum);
                        if($degree<0) {
                            $degree = 0;
                        }elseif($degree>100) {
                            $degree = 100;
                        }
                        pdo_update($this->modulename.'_attender',array('degree'=>$degree),array('id'=>$transfer_attender['id']));
                        $transfer_attender['degree'] = $transfer_attender['degree'] - abs($valNum);
                    }
                    pdo_update($this->modulename.'_attender',array('isjiayou'=>1),array('id'=>$attender['id']));
                    $insertdata = array(
                        'weid'=>$weid,
                        'hid'=>$hid,
                        'from_user'=>$from_user,
                        'target_from_user'=>$transfer_openid,
                        'degree'=>$valNum,
                        'createtime'=>TIMESTAMP
                        );
                    pdo_insert($this->modulename.'_record',$insertdata);
                    $this->showMsg("加油成功",1);
            }else{
                $this->showMsg("不能加两次",1);
            }
        }        
    }

    //3次提示
    public function doMobileOver() {
        global $_W, $_GPC;
        $hid=$_GPC['hid'];
        $weid=$_W['uniacid'];
        $method = "over";
        $this->checkHuodongTime($hid, $method);
        $from_user = $this->_fromuser;
        $shareurl = $_W['siteroot'].'app/'.$this->createMobileUrl("index",array ('hid' => $hid, 'weid' => $weid,'pageid'=>1));//分享URL
        $huodong=pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_huodong')." WHERE id=$hid AND weid=$weid");

        $transfer_openid = $_GPC['transfer_openid'];
        $this->addClicktimes(7);
        include $this->template('over');
    }

    //帮好友加油
    public function doMobileBangdan2() {
        global $_W, $_GPC;
        $hid=$_GPC['hid'];
        $weid=$_W['uniacid'];
        $method = "bangdan2";
        $this->checkHuodongTime($hid, $method);
        $from_user = $this->_fromuser;
        $shareurl = $_W['siteroot'].'app/'.$this->createMobileUrl("friendindex",array ('hid' => $hid, 'weid' => $weid,'transfer_openid'=>base64_encode($from_user),'pageid'=>4));//分享URL
        $huodong=pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_huodong')." WHERE id=$hid AND weid=$weid");

        $attender = pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_attender')." WHERE hid=:hid AND weid=:weid AND openid=:openid",array(':hid' => $hid, ':weid' => $weid, ':openid'=>$from_user));
        if($attender['isjoin'] == 0) {
            $url = $this->createMobileUrl('index', array('hid' => $hid, "weid"=>$weid,'pageid'=>1), true);
            die('<script>location.href = "' . $url . '";</script>');
            exit();
        }else {
            //查询帮忙加油名单
            $records = pdo_fetchall("SELECT * FROM ".tablename($this->modulename.'_record')." WHERE hid=:hid AND weid=:weid  AND target_from_user =:openid order by createtime ",array(':hid' => $hid, ':weid' => $weid, ':openid'=>$from_user));
            foreach ($records as $key => $value) {
                $records[$key]['friend'] = pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_attender')." WHERE hid=:hid AND weid=:weid AND openid=:openid",array(':hid' => $hid, ':weid' => $weid, ':openid'=>$value['from_user']));
            }
            if($attender['degree'] == 100) {
                $this->addClicktimes(9);
                include $this->template('success');
            }else {
                if($attender['degree']<0) {
                    $degree = abs($attender['degree']) + 100;
                }else {
                    $degree = 100 - $attender['degree'];
                }
                $this->addClicktimes(6);
                include $this->template('bangdan2');
            }
        }
    }

     //转发记录
    public function doMobileTransfer() {
        global $_GPC,$_W;
        $hid=$_GPC['hid'];
        $weid=$_W['uniacid'];
        $method = "transfer";
        $this->checkHuodongTime($hid, $method);
        $from_user = $this->_fromuser;
        $sharetime = $_GPC['sharetime'];

        $type = intval($_GPC['type']);
        $insertdata = array(
            'weid' => $weid,
            'hid' => $hid,
            'from_user' => $from_user,
            'createtime' => TIMESTAMP,
            'sharetime' => $sharetime,
            'type' => $type
            );
        pdo_insert($this->modulename.'_transfer', $insertdata);
        //更新转发次数
        $attender = pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_attender')." WHERE openid =:from_user AND weid=:weid AND hid=:hid",array('from_user'=>$from_user,'weid'=>$weid,'hid'=>$hid));
        if($type == 1) {//朋友
            pdo_update($this->modulename.'_attender', array('isjoin'=>1,'ispost'=>1,'sharetimes'=>$attender['sharetimes']+1),array('id'=>$attender['id']));
        }elseif($type == 2) {//朋友圈
            pdo_update($this->modulename.'_attender', array('isjoin'=>1,'ispost'=>1,'transfertimes'=>$attender['transfertimes']+1),array('id'=>$attender['id']));
        }else{//其他
            pdo_update($this->modulename.'_attender', array('isjoin'=>1,'ispost'=>1),array('id'=>$attender['id']));
        }
    }

    public function checkHuodongTime($hid, $method = "index") {
        global $_GPC,$_W;
        $weid=$_W['uniacid'];

        if(!empty($_GPC['transfer_openid'])) {
            if(!empty($_GPC['sharetime'])) {
                $authurl = $_W['siteroot'] ."app/". $this->createMobileUrl($method, array('authkey' => 1,'hid'=>$hid,'transfer_openid'=>$_GPC['transfer_openid'],'sharetime'=>$_GPC['sharetime'],'type'=>$_GPC['type'],'pageid'=>$_GPC['pageid']), true);
                $url = $_W['siteroot'] ."app/". $this->createMobileUrl($method,array('hid'=>$hid,'transfer_openid'=>$_GPC['transfer_openid'],'sharetime'=>$_GPC['sharetime'],'type'=>$_GPC['type'],'pageid'=>$_GPC['pageid']));
            }else {
                $authurl = $_W['siteroot'] ."app/". $this->createMobileUrl($method, array('authkey' => 1,'hid'=>$hid,'transfer_openid'=>$_GPC['transfer_openid'],'pageid'=>$_GPC['pageid']), true);
                $url = $_W['siteroot'] ."app/". $this->createMobileUrl($method,array('hid'=>$hid,'transfer_openid'=>$_GPC['transfer_openid'],'pageid'=>$_GPC['pageid']));
            } 
        }else {
            $authurl = $_W['siteroot'] ."app/". $this->createMobileUrl($method, array('authkey' => 1,'hid'=>$hid,'pageid'=>$_GPC['pageid']), true);
            $url = $_W['siteroot'] ."app/". $this->createMobileUrl($method,array('hid'=>$hid,'pageid'=>$_GPC['pageid']));
        }

        if(isset($_COOKIE[$this->_auth2_openid])) {
            $this->_fromuser = $_COOKIE[$this->_auth2_openid];
            $user = pdo_fetch(" SELECT * FROM ".tablename($this->modulename.'_attender')." WHERE weid = $weid AND hid = $hid AND openid = '".$this->_fromuser."' LIMIT 0,1");
        }


        if($this->_debug==1) {
            $this->_fromuser = $this->getRandomString(30,"abcdefghijklmnopqrstuvwxyz012345678");
            // $this->_fromuser = "o9WkpxKuaA68n7Bhn2PL7YCzEu-0";
            $user = pdo_fetch(" SELECT * FROM ".tablename($this->modulename.'_attender')." WHERE openid = '".$this->_fromuser."' AND hid=$hid AND weid=$weid LIMIT 0,1");
            if (empty($user)) {
                $user = array(
                    // 'id' => $this->_fromuser,
                    'hid' => $hid,
                    'weid' => $weid, 
                    'openid' => $this->_fromuser, 
                    'nickname' => "Leo", 
                    'sex' => 1, 
                    'province' => "广东", 
                    'city' => "广州", 
                    'country' => "中国", 
                    'headimgurl' => "", 
                    'createtime' => TIMESTAMP,
                );
            }
            setcookie($this->modulename . $this->_auth2_openid, $this->_fromuser, time() + 3600 * 24);
        }elseif (isset($_COOKIE[$this->_auth2_openid]) && !empty($user)) {
            $this->_fromuser = $_COOKIE[$this->_auth2_openid];
            $this->_nickname = $_COOKIE[$this->_auth2_nickname];
            $this->_headimgurl = $_COOKIE[$this->_auth2_headimgurl];
            $blacklist = pdo_fetch("SELECT * FROM ".tablename($this->modulename."_blacklist")." WHERE from_user =:from_user AND status = 0 LIMIT 0,1 ",array(':from_user'=>$user['openid']));
            if(!empty($blacklist)) {
                message('抱歉，您的账号已被拉入黑名单！');
            }
        } else {
            WeUtility::logging("================ code = ".$_GPC['code']);
            if (isset($_GPC['code'])) {
                $userinfo = $this->oauth2($authurl);
                if (!empty($userinfo)) {
                    $this->_fromuser = $userinfo["openid"];
                    $this->_nickname = $userinfo["nickname"];
                    $this->_headimgurl = $userinfo["headimgurl"];
                    
                    if(!empty($this->_fromuser)) {
                        $user = pdo_fetch(" SELECT * FROM ".tablename($this->modulename.'_attender')." WHERE openid = '".$this->_fromuser."' AND hid=$hid AND weid=$weid LIMIT 0,1");
                        if(empty($user)) {
                            $insertdata = array(
                                'weid' => $weid,
                                'hid' => $hid,
                                'openid' => $this->_fromuser,
                                'nickname' => $userinfo['nickname'],
                                'sex' => $userinfo['sex'],
                                'province' => $userinfo['province'],
                                'city' => $userinfo['city'],
                                'country' => $userinfo['country'],
                                'headimgurl' => $userinfo['headimgurl'],
                                'createtime' => TIMESTAMP,
                                'readtimes' => 1);
                            pdo_insert($this->modulename.'_attender', $insertdata);
                        }
                    }
                } else {
                    message('授权失败!');
                }
            } else {
                if (!empty($this->_appsecret)) {
                    $this->toAuthUrl($url);
                }
            }
        }

        if (empty($this->_fromuser)) {
            message('会话已经过时，请从微信端重新打开链接登录！');
        }

        $huodong=pdo_fetch("SELECT * FROM ".tablename($this->modulename.'_huodong')." WHERE id=$hid AND weid = $weid");
        if(empty($huodong))
        {
            message("抱歉，活动不存在或是已经被删除！");
        }
        $now=time();
        
        if($huodong['enabled'] == 1) {
            if($now < $huodong['starttime']) {
                message("抱歉，活动还没有开始，请耐心等待！");
            }
            if($now > $huodong['endtime']) {
                message("抱歉，活动已结束！");
            }
        }else {
            message("抱歉，活动暂停！");
        }
        
    }

    //auth2
    public function toAuthUrl($url)
    {
        global $_W;
        $oauth2_code = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $this->_appid . "&redirect_uri=" . urlencode($url) . "&response_type=code&scope=snsapi_base&state=0#wechat_redirect";
        header("location:$oauth2_code");
    }

    public function oauth1($authurl)
    {
        global $_GPC, $_W;
        load()->func('communication');
        $state = $_GPC['state']; //1为关注用户, 0为未关注用户
        $code = $_GPC['code'];
        $oauth2_code = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $this->_appid . "&secret=" . $this->_appsecret . "&code=" . $code . "&grant_type=authorization_code";
        $content = ihttp_get($oauth2_code);
        $token = @json_decode($content['content'], true);
        if (empty($token) || !is_array($token) || empty($token['access_token']) || empty($token['openid'])) {
            echo '<h1>获取微信公众号授权' . $code . '失败[无法取得token以及openid], 请稍后重试！ 公众平台返回原始数据为: <br />' . $content['meta'] . '<h1>';
            exit;
        }
        $from_user = $token['openid'];
        $this->_fromuser = $from_user;
        return $from_user;
    }

    public function oauth2($authurl)
    {
        global $_GPC, $_W;
        load()->func('communication');
        $state = $_GPC['state']; //1为关注用户, 0为未关注用户
        $code = $_GPC['code'];
        $oauth2_code = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $this->_appid . "&secret=" . $this->_appsecret . "&code=" . $code . "&grant_type=authorization_code";
        $content = ihttp_get($oauth2_code);
        $token = @json_decode($content['content'], true);
        if (empty($token) || !is_array($token) || empty($token['access_token']) || empty($token['openid'])) {
            echo '<h1>获取微信公众号授权' . $code . '失败[无法取得token以及openid], 请稍后重试！ 公众平台返回原始数据为: <br />' . $content['meta'] . '<h1>';
            exit;
        }
        $from_user = $token['openid'];
        $this->_fromuser = $from_user;
        if ($this->_accountlevel != 4) { //普通号
            $authkey = intval($_GPC['authkey']);
            if ($authkey == 0) {
                $url = $authurl;
                $oauth2_code = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $this->_appid . "&redirect_uri=" . urlencode($url) . "&response_type=code&scope=snsapi_userinfo&state=0#wechat_redirect";
                header("location:$oauth2_code");
            }
        } else {
            //再次查询是否为关注用户
            $profile = fans_search($from_user);
            if ($profile['follow'] == 1) { //关注用户直接获取信息
                $state = 1;
            } else { //未关注用户跳转到授权页
                $url = $authurl;
                $oauth2_code = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $this->_appid . "&redirect_uri=" . urlencode($url) . "&response_type=code&scope=snsapi_userinfo&state=0#wechat_redirect";
                header("location:$oauth2_code");
            }
        }
        //未关注用户和关注用户取全局access_token值的方式不一样
        if ($state == 1) {
            $oauth2_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $this->_appid . "&secret=" . $this->_appsecret . "";
            $content = ihttp_get($oauth2_url);
            $token_all = @json_decode($content['content'], true);
            if (empty($token_all) || !is_array($token_all) || empty($token_all['access_token'])) {
                echo '<h1>获取微信公众号授权失败[无法取得access_token], 请稍后重试！ 公众平台返回原始数据为: <br />' . $content['meta'] . '<h1>';
                exit;
            }
            $access_token = $token_all['access_token'];
            $oauth2_url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=" . $access_token . "&openid=" . $from_user . "&lang=zh_CN";
        } else {
            $access_token = $token['access_token'];
            $oauth2_url = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $access_token . "&openid=" . $from_user . "&lang=zh_CN";
        }
        WeUtility::logging(" ==== access_token = ".$access_token);
        //使用全局ACCESS_TOKEN获取OpenID的详细信息
        $content = ihttp_get($oauth2_url);
        $info = @json_decode($content['content'], true);
        if (empty($info) || !is_array($info) || empty($info['openid']) || empty($info['nickname'])) {
            echo '<h1>获取微信公众号授权失败[无法取得info], 请稍后重试！ 公众平台返回原始数据为: <br />' . $content['meta'] . '<h1>' . 'state:' . $state . 'nickname' . $profile['nickname'] . 'weid:' . $profile['weid'];
            exit;
        }
        $headimgurl = $info['headimgurl'];
        $nickname = $info['nickname'];
        //设置cookie信息
        setcookie($this->_auth2_headimgurl, $headimgurl, time() + 3600 * 24);
        setcookie($this->_auth2_nickname, $nickname, time() + 3600 * 24);
        setcookie($this->_auth2_openid, $from_user, time() + 3600 * 24);
        return $info;
    }


    /*
    ** 设置切换导航
    */
    public function set_tabbar($action, $hid)
    {
        $actions_titles = $this->actions_titles;
        $html = '<ul class="nav nav-tabs">';
        foreach ($actions_titles as $key => $value) {
            $url = $this->createWebUrl($key, array('op' => 'display', 'hid' => $hid));
            $html .= '<li class="' . ($key == $action ? 'active' : '') . '"><a href="' . $url . '">' . $value . '</a></li>';
        }
        $html .= '</ul>';
        return $html;
    }

    public $actions_titles = array(
        'huodong' => '返回活动管理',
        'attender' => '参与记录',
        'supporter' => '加油记录',
        'transfer' => '转发记录',
        'pagerate' => '跳失率统计'
    );



}