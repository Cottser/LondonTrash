<?php echo $this->Html->script('validate/jquery.validate.pack', array('inline' => false)); ?>
<?php echo $this->Html->script('jquery.form', array('inline' => false)); ?>
<?php echo $this->Html->script('notifications', array('inline' => false)); ?>
<?php
echo $this->Html->scriptBlock(
<<<JAVASCRIPT
$(document).ready(function(){
	notify_prepForm();
});
JAVASCRIPT
, array('inline' => false));
?>

<?php echo $this->Form->create('Subscriber', array('id' => 'subscriber')); ?>
	<h3>Enter your email address and/or cell phone details. We&apos;ll send you a reminder the night before pickup so you don&apos;t forget to take out the garbage!</h3>
	<div class="error">
		<div class="label-container">
			<?php if (!empty($validationErrorList)): ?>
				<ul>
				<?php foreach($validationErrorList as $field => $error): ?>
				<li><?php echo $error; ?></li>
				<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
	<?php echo $this->Form->input('email', array(
		'class' => 'required_group email',
		'div' => array('id' => 'email'),
		'error' => false
	)); ?>
	<div id="andor">and/or</div>
	<?php echo $this->Form->input('phone', array(
		'class' => 'required_group phone',
		'div' => array('id' => 'phone'),
		'label' => 'Cell Phone',
		'error' => false
	)); ?>
	<?php echo $this->Form->input('provider_id', array('empty' => 'Choose Provider', 'label' => false, 'error' => false)); ?>
	<?php echo $this->Form->hidden('zone_id', array('value' => $this->Session->read('zone_id'))); ?>
<div class="clear"></div>
<?php echo $this->Form->end('Let me know!'); ?>