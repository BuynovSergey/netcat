<?php if ( ! defined('NC')) exit ?>
<?/*------------------------------------------------------------------------*/?>

<? example('nc-h[1-5] | Заголовки') ?>
<h1 class="nc-h1">Header H1</h1>
<h2 class="nc-h2">Header H2</h2>
<h3 class="nc-h3">Header H3</h3>
<h4 class="nc-h4">Header H4</h4>
<h5 class="nc-h5">Header H5</h5>

<? example('nc-text-* | Цветной текст') ?>

<? foreach ($accent_colors as $color): ?>
	<? $text_color = "text_{$color}" ?>
	<?=$nc_core->ui->html('p')->text("Цветной текст (nc-{$text_color})")->$text_color() ?>
<? endforeach ?>

<?=$nc_core->ui->html('p')->text('Цветной текст (nc-text-grey)')->text_grey() ?>

<? example('nc-box | Блоки') ?>
<div class="nc-box">Lorem ipsum dolor sit amet, consectetur adipiscing elit. <code class="nc--right">.nc-box</code></div>
<div class="nc-box nc--light">Lorem ipsum dolor sit amet, consectetur adipiscing elit. <code class="nc--right">.nc-box.nc--light</code></div>
<div class="nc-box nc--grey">Lorem ipsum dolor sit amet, consectetur adipiscing elit. <code class="nc--right">.nc-box.nc--grey</code></div>
<div class="nc-box nc--dark nc-text-light">Lorem ipsum dolor sit amet, consectetur adipiscing elit. <code class="nc--right">.nc-box.nc--dark.nc-text-light</code></div>
<div class="nc-box nc--darken nc-text-light">Lorem ipsum dolor sit amet, consectetur adipiscing elit. <code class="nc--right">.nc-box.nc--darken.nc-text-light</code></div>
<div class="nc-box nc--black nc-text-light">Lorem ipsum dolor sit amet, consectetur adipiscing elit. <code class="nc--right">.nc-box.nc--black.nc-text-light</code></div>
<div class="nc-box nc--red">Lorem ipsum dolor sit amet, consectetur adipiscing elit. <code class="nc--right">.nc-box.nc--red</code></div>
<div class="nc-box nc--green">Lorem ipsum dolor sit amet, consectetur adipiscing elit. <code class="nc--right">.nc-box.nc--green</code></div>
<div class="nc-box nc--blue">Lorem ipsum dolor sit amet, consectetur adipiscing elit. <code class="nc--right">.nc-box.nc--blue</code></div>
<div class="nc-box nc--yellow">Lorem ipsum dolor sit amet, consectetur adipiscing elit. <code class="nc--right">.nc-box.nc--yellow</code></div>



<? example('Разное') ?>
<code class="nc-code">code.nc-code</code>
<pre class="nc-code"># pre.nc-code
user = dict(id=1, group_id=1, login='admin')
for k,v in user.list():
    print k, ':', v
</pre>

