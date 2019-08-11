
<div class="form-group">
    <label class="col-md-2 control-label">描述模板</label>
    <div class="col-md-9">
        <p class="form-control-static"> ./config/pay/payremit.html </p>
    </div>
</div>
<div class="form-group">
    <label class="col-md-2 control-label">转账账号</label>
    <div class="col-md-9">
        <textarea class="form-control" name="data[<?php echo $dir;?>][value]" rows="5"><?php echo $data[$dir]['value']?></textarea>

    </div>
</div>
<div class="form-group">
    <label class="col-md-2 control-label">付款流程</label>
    <div class="col-md-9">
        <p class="form-control-static"> 用户提交付款请求 -> 去银行转账汇款 -> 提交汇款确认 -> 平台审核</p>
    </div>
</div>