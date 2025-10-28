
<?php if ($times) { foreach ($times as $t) { ?>
<h3>运行时间：<?php echo $t['tpl'];?>ms</h3>
<?php } } ?>


<br>
<h2>模板文件</h2>
<table>
    <thead>
    <tr>
        <th width=200>模板</th>
        <th>路径</th>
    </tr>
    </thead>
    <tbody>
    <?php if ($files) { foreach ($files as $t) { ?>
        <tr>
            <td><?php echo $t['name'];?></td>
            <td><?php echo $t['path'];?></td>
        </tr>
    <?php } } ?>
    </tbody>
</table>

<br>
<h2>引用提示</h2>
<table>
    <thead>
    <tr>
        <th width=200>模板</th>
        <th>提示</th>
    </tr>
    </thead>
    <tbody>
    <?php if ($tips) { foreach ($tips as $t) { ?>
    <tr>
            <td><?php echo $t['name'];?></td>
            <td><?php echo $t['tips'];?></td>
    </tr>
    <?php } } ?>
    </tbody>
</table>

<br>

<h2>模板变量</h2>
<table>
    <tbody>
    <?php if ($vars) { foreach ($vars as $t) { ?>
        <tr>
            <td width=200><?php echo $t['name'];?></td>
            <td><pre><?php echo $t['value'];?></pre></td>
        </tr>
    <?php } } ?>
    </tbody>
</table>

