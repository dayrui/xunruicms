
{times}
<h3>运行时间：{tpl}ms</h3>
{/times}

<br>
<h2>模板文件</h2>
<table>
    <thead>
    <tr>
        <th>模板</th>
        <th>路径</th>
    </tr>
    </thead>
    <tbody>
    {files}
        <tr>
            <td>{name}</td>
            <td>{path}</td>
        </tr>
    {/files}
    </tbody>
</table>

<br>
<h2>引用提示</h2>
<table>
    <thead>
    <tr>
        <th>模板</th>
        <th>提示</th>
    </tr>
    </thead>
    <tbody>
    {tips}
    <tr>
        <td>{name}</td>
        <td>{tips}</td>
    </tr>
    {/tips}
    </tbody>
</table>

<br>

<h2>模板变量</h2>
<table>
    <tbody>
    {vars}
        <tr>
            <td>{name}</td>
            <td><pre>{value}</pre></td>
        </tr>
    {/vars}
    </tbody>
</table>

