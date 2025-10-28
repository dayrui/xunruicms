<table>
    <tbody>
    <?php foreach ($userFiles as $t) { ?>
        <tr>
            <td> <?php echo $t['name'];?></td>
            <td> <?php echo $t['path'];?></td>
        </tr>
    <?php } ?>
    <?php foreach ($coreFiles as $t) { ?>
   
        <tr class="muted">
            <td class="debug-bar-width20e"> <?php echo $t['name'];?> </td>
            <td> <?php echo $t['path'];?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
