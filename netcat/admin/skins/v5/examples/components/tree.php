<?php if ( ! defined('NC')) exit ?>
<?/*------------------------------------------------------------------------*/?>

<? example('nc-tree | list') ?>

<ul class="nc-list nc--hovered">
	<li>
		<a href="#"><i class="nc-icon nc--folder-dark"></i> Item 1</a>
		<div class="nc-actions nc--on-hover">
			<a href="#" class='nc-btn nc--blue'><i class="nc-icon nc--field-bool nc--white"></i> Выбрать</a>
			<a href="#"><i class="nc-icon nc--edit"></i></a>
			<a href="#"><i class="nc-icon nc--settings"></i></a>
			<a href="#"><i class="nc-icon nc--remove"></i></a>
		</div>
	</li>
	<li><a href="#"><i class="nc-icon nc--folder-dark"></i> Item 2</a>
		<div class="nc-actions nc--on-hover">
			<a href="#" class='nc-btn nc--blue'><i class="nc-icon nc--field-bool nc--white"></i> Выбрать</a>
			<a href="#"><i class="nc-icon nc--edit"></i></a>
			<a href="#"><i class="nc-icon nc--settings"></i></a>
			<a href="#"><i class="nc-icon nc--remove"></i></a>
		</div>
	</li>
	<li><a href="#"><i class="nc-icon nc--folder-dark"></i> Item 3</a></li>
	<li><a href="#"><i class="nc-icon nc--folder-dark"></i> Item 4</a></li>
	<li><a href="#"><i class="nc-icon nc--folder-dark"></i> Item 5</a></li>
	<li><a href="#"><i class="nc-icon nc--folder-dark"></i> Item 6</a></li>
	<li><a href="#"><i class="nc-icon nc--folder-dark"></i> Item 7</a></li>
	<li><a href="#"><i class="nc-icon nc--folder-dark"></i> Item 8</a></li>
	<li><a href="#"><i class="nc-icon nc--folder-dark"></i> Item 9</a></li>
	<li><a href="#"><i class="nc-icon nc--folder-dark"></i> Item 10</a></li>
</ul>
<!-- /.nc-list -->

<? example('nc-tree') ?>
<div class="nc-tree">
	<ul>
		<li>
			<a href="#"><i class="nc-icon nc--site"></i> Сайт 1</a>
			<ul>
				<li><a href="#"><i class="nc-icon nc--folder"></i> Item 1</a></li>
				<li><a href="#"><i class="nc-icon nc--folder"></i> Item 2</a></li>
				<li><a href="#"><i class="nc-icon nc--folder"></i> Item 3</a></li>
				<li>
					<a href="#"><i class="nc-icon nc--folder"></i> Item 4</a>
					<ul>
						<li><a href="#"><i class="nc-icon nc--folder"></i> Item 1</a></li>
						<li><a href="#"><i class="nc-icon nc--folder"></i> Item 2</a></li>
						<li><a href="#"><i class="nc-icon nc--folder"></i> Item 3</a></li>
						<li><a href="#"><i class="nc-icon nc--folder"></i> Item 4</a></li>
						<li><a href="#"><i class="nc-icon nc--folder"></i> Item 5</a></li>
						<li><a href="#"><i class="nc-icon nc--folder"></i> Item 6</a></li>
						<li><a href="#"><i class="nc-icon nc--folder"></i> Item 7</a></li>
						<li><a href="#"><i class="nc-icon nc--folder"></i> Item 8</a></li>
						<li><a href="#"><i class="nc-icon nc--folder"></i> Item 9</a></li>
						<li><a href="#"><i class="nc-icon nc--folder"></i> Item 10</a></li>
					</ul>
				</li>
				<li><a href="#"><i class="nc-icon nc--folder"></i> Item 5</a></li>
				<li><a href="#"><i class="nc-icon nc--folder"></i> Item 6</a></li>
				<li><a href="#"><i class="nc-icon nc--folder"></i> Item 7</a></li>
				<li><a href="#"><i class="nc-icon nc--folder"></i> Item 8</a></li>
				<li><a href="#"><i class="nc-icon nc--folder"></i> Item 9</a></li>
				<li><a href="#"><i class="nc-icon nc--folder"></i> Item 10</a></li>
			</ul>
		</li>
		<li><a href="#"><i class="nc-icon nc--site"></i> Сайт 2</a></li>
		<li><a href="#"><i class="nc-icon nc--site"></i> Сайт 3</a></li>
		<li><a href="#"><i class="nc-icon nc--site"></i> Сайт 4</a></li>
		<li><a href="#"><i class="nc-icon nc--site"></i> Сайт 5</a></li>
	</ul>
</div>
<!-- /.nc-tree -->