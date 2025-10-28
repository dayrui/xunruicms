<h3>Matched Route</h3>

<table>
    <tbody>
    <?php if ($matchedRoute) { foreach ($matchedRoute as $key => $tt) { ?>
           <tr>
            <td width=200><?php echo $key;?></td>
            <td><?php echo $tt;?></td>
        </tr>
    <?php } } ?>
    </tbody>
</table>


<h3>GET</h3>

<table>
    <thead>
        <tr>
            <th>key</th>
            <th>value</th>
        </tr>
    </thead>
    <tbody>
     <?php if ($get) { foreach ($get as $key => $tt) { ?>
        <tr>
            <td width=200><?php echo $key;?></td>
            <td><?php echo $tt;?></td>
        </tr>
    <?php } } ?>
    </tbody>
</table>
