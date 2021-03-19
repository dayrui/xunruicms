<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-cn">
<head>
	<meta charset="utf-8">
	<title>系统错误 - <?= htmlspecialchars($title, ENT_SUBSTITUTE, 'UTF-8') ?></title>
	<style type="text/css">
		<?= preg_replace('#[\r\n\t ]+#', ' ', file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'debug.css')) ?>
	</style>
	<style>
	div.logo {
		height: 200px;
		width: 155px;
		display: inline-block;
		opacity: 0.08;
		position: absolute;
		top: 2rem;
		left: 50%;
		margin-left: -73px;
	}
	body {
		height: 100%;
		background: #fafafa;
		font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
		color: #777;
		font-weight: 300;
	}
	h1 {
		font-weight: lighter;
		letter-spacing: 0.8;
		font-size: 3rem;
		margin-top: 0;
		margin-bottom: 0;
		color: #222;
	}
	.wrap {
		max-width: 1024px;
		margin: 5rem auto;
		padding: 2rem;
		background: #fff;
		text-align: center;
		border: 1px solid #efefef;
		border-radius: 0.5rem;
		position: relative;
	}
	pre {
		white-space: normal;
		margin-top: 1.5rem;
	}
	code {
		background: #fafafa;
		border: 1px solid #efefef;
		padding: 0.5rem 1rem;
		border-radius: 5px;
		display: block;
	}
	p {
		margin-top: 1.5rem;
	}
	.footer {
		margin-top: 2rem;
		border-top: 1px solid #efefef;
		padding: 1em 2em 0 2em;
		font-size: 85%;
		color: #999;
	}
	a:active,
	a:link,
	a:visited {
		color: #dd4814;
	}
</style>
</head>
<body>
	<div class="wrap">
		<h3>系统错误</h3>

		<p>
			<?php if (! empty($message) && $message != '(null)') : ?>
				<?= $message ?>
			<?php else : ?>
				Sorry! Cannot seem to find the page you were looking for.
			<?php endif ?>
		</p>
		<p>
			<?= FC_NOW_URL ?>
		</p>
		<div class="footer" style="border-top:0">
			<div class="container">

				<p>
					Displayed at <?= date('Y-m-d H:i:s') ?> &mdash;
					PHP: <?= phpversion() ?>  &mdash;
					CodeIgniter
				</p>

			</div>
		</div>

	</div>

</body>
</html>
