<?php if ( ! defined('NC')) exit ?>
<?/*------------------------------------------------------------------------*/?>

<? example('Стили виджетов') ?>

<? foreach (array('lighten', 'light', 'grey', 'dark', 'cyan', 'green', 'blue', 'purple', 'yellow', 'orange', 'red') as $color): ?>
<div style="width:500px;">
	<div class="nc-widget nc--<?=$color?>">
		<table class="nc-widget-grid">
			<tr>
				<td colspan="5">nc-widget nc--<?=$color?></td>
			</tr>
			<tr>
				<td class="nc-bg-lighten">nc-bg-lighten</td>
				<td class="nc-bg-light">nc-bg-light</td>
				<td>[no class]</td>
				<td class="nc-bg-dark">nc-bg-dark</td>
				<td class="nc-bg-darken">nc-bg-darken</td>
			</tr>
		</table>
	</div>
</div>
<br>
<? endforeach ?>


<? example('Типография виджетов') ?>

<div style="width:150px; height:150px; float:left; margin:10px">
	<div class="nc-widget nc--green">
		<table class="nc-widget-grid nc-text-center">
			<tr>
				<td class="nc-bg-light" colspan="2">минимагазин</td>
			</tr>
			<tr>
				<td class="nc--gradient">
					<dl class="nc-info nc--large"><dt>44</dt></dl>
				</td>
				<td>
					<dl class="nc-info nc--mini nc--vertical">
						<dt><i class="nc-icon nc--mod-netshop nc--white"></i></dt>
						<dd>сегодня</dd>
					</dl>
				</td>
			</tr>
			<tr>
				<td class="nc-bg-dark">
					<dl class="nc-info nc--mini"><dt>55</dt> <dd>вчера</dd></dl>
				</td>
				<td class="nc-bg-darken">
					<dl class="nc-info nc--mini"><dt>12</dt> <dd>ждут</dd></dl>
				</td>
			</tr>
		</table>
	</div>
</div>



<div style="width:310px; height:150px; float:left; margin:10px">
	<div class="nc-widget nc--text-dark nc--lighten">
		<table class="nc-widget-grid nc-text-center">
			<tr>
				<td class="nc--gradient nc-bg-dark" colspan="2">минимагазин</td>
			</tr>
			<tr>
				<td class="">
					<dl class="nc-info nc--large"><dt>44</dt></dl>
				</td>
				<td>
					<dl class="nc-info nc--mini nc--vertical">
						<dt><i class="nc-icon nc--mod-netshop nc--dark"></i></dt>
						<dd>сегодня</dd>
					</dl>
				</td>
			</tr>
			<tr>
				<td class="nc-bg-dark nc--gradient">
					<dl class="nc-info nc--mini"><dt>55</dt> <dd>вчера</dd></dl>
				</td>
				<td class="nc-bg-darken nc--gradient">
					<dl class="nc-info nc--mini"><dt>12</dt> <dd>ждут</dd></dl>
				</td>
			</tr>
		</table>
	</div>
</div>


<div style="width:150px; height:150px; float:left; margin:10px">
	<div class="nc-widget nc--red">
		<table class="nc-widget-grid">
			<tr>
				<td style="height:1%">
					<i class="nc-icon nc--user nc--white"></i> admin
				</td>
			</tr>
			<tr>
				<td class="nc-bg-dark">
					<big>директор</big>
				</td>
			</tr>
			<tr>
				<td style="height:1%">
					<a href="#" class="nc-btn nc--blocked">сменить пароль</a>
				</td>
			</tr>
		</table>
	</div>
</div>
<div class="nc--clearfix"></div>

<? example('Типография виджетов 2') ?>


<div style="width:150px; height:150px; float:left; margin:10px">
	<div class="nc-widget nc--light">
		<div>
			<a class="nc-position-tl nc-bg-light" href="#"><i class="nc-icon nc--info nc--dark"></i></a>
			<a class="nc-position-tr nc-bg-light" href="#"><i class="nc-icon nc--close nc--dark"></i></a>

			<a class="nc-position-bl nc-bg-dark" href="#"><i class="nc-icon nc--download nc--dark"></i></a>
			<a class="nc-position-br nc-bg-dark" href="#"><i class="nc-icon nc--edit nc--dark"></i></a>
		</div>
	</div>
</div>

<div style="width:150px; height:150px; float:left; margin:10px">
	<div class="nc-widget nc--grey">
		<div>
			<a class="nc-position-t nc-bg-light" href="#"><i class="nc-icon nc--info nc--white"></i> Info</a>
			<a class="nc-position-tr nc-bg-lighten" href="#"><i class="nc-icon nc--close nc--white"></i></a>

			<a class="nc-position-b nc-bg-darken" href="#"><i class="nc-icon nc--download nc--white"></i> Download</a>
			<a class="nc-position-br nc-bg-dark" href="#"><i class="nc-icon nc--edit nc--white"></i></a>
		</div>
	</div>
</div>



<div style="width:150px; height:150px; float:left; margin:10px">
	<div class="nc-widget nc--dark">
		<div>
			<a class="nc-position-t nc-text-center nc-bg-light" href="#"><i class="nc-icon nc--info nc--white"></i> Information</a>
			<a class="nc-position-b nc-text-center nc-bg-light" href="#"><i class="nc-icon nc--download nc--white"></i> Download</a>
		</div>
	</div>
</div>

<div style="width:150px; height:150px; float:left; margin:10px">
	<div class="nc-widget" style="background: #39A">
		<div>
			<a class="nc-position-t nc-bg-light" href="#"><i class="nc-icon nc--info nc--white"></i> Info</a>
			<a class="nc-position-tr nc-bg-lighten" href="#"><i class="nc-icon nc--close nc--white"></i></a>

			<a class="nc-position-b nc-bg-darken" href="#"><i class="nc-icon nc--download nc--white"></i> Download</a>
			<a class="nc-position-br nc-bg-dark" href="#"><i class="nc-icon nc--edit nc--white"></i></a>
		</div>
	</div>
</div>

<div style="width:150px; height:150px; float:left; margin:10px">
	<div class="nc-widget" style="background-image: url('http://farm8.static.flickr.com/7171/6430443481_11c69b8a5a_b.jpg')">
		<div>
			<a class="nc-position-t nc-bg-light" href="#"><i class="nc-icon nc--info nc--white"></i> Info</a>
			<a class="nc-position-tr nc-bg-lighten" href="#"><i class="nc-icon nc--close nc--white"></i></a>

			<a class="nc-position-b nc-bg-darken" href="#"><i class="nc-icon nc--download nc--white"></i> Download</a>
			<a class="nc-position-br nc-bg-dark" href="#"><i class="nc-icon nc--edit nc--white"></i></a>
		</div>
	</div>
</div>