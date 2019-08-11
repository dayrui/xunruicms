<?php

switch ($data['status']) {

 case 1:
  $return = ['code' => 1, 'msg' => dr_lang('已付款')];
   break;

 case 0:
  $return = ['code' => 0, 'msg' => dr_lang('未付款')];
   break;

 case 2:
  $return = ['code' => 0, 'msg' => dr_lang('汇款中')];
   break;

 case 3:
  $return = ['code' => 0, 'msg' => dr_lang('汇款失败')];
   break;

}
