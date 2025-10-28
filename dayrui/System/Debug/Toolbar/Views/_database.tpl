<table>
    <thead>
        <tr>
            <th class="debug-bar-width6r">Time</th>
            <th>Query String</th>
        </tr>
    </thead>
    <tbody>
   <?php if ($queries) { foreach ($queries as $t) { ?>
        <tr class="<?php echo $t['class'];?>" title="<?php echo $t['hover'];?>" data-toggle="<?php echo $t['qid'];?>-trace">
            <td class="narrow"><?php echo $t['duration'];?></td>
            <td><?php echo $t['sql'];?></td>
            <td class="debug-bar-alignRight"><strong><?php echo $t['trace-file'];?></strong></td>
        </tr>
        <tr class="muted debug-bar-ndisplay" id="<?php echo $t['qid'];?>-trace">
            <td></td>
            <td colspan="2">
            <?php foreach ($t['trace'] as $tt) { ?>
                <?php echo $tt['index'];?>
                <strong><?php echo $tt['file'];?></strong><br/>
                <?php echo $tt['function'];?><br/><br/>
            <?php } ?>
            </td>
        </tr>
     <?php } } ?>
    </tbody>
</table>
