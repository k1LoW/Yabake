<div class="<?php echo $pluralVar;?> form">
<h2><?php echo "<?php  echo __('{$singularHumanName}');?>";?></h2>

	<dl>
<?php
foreach ($fields as $field) {
if ($field == $primaryKey || in_array($field, array('created', 'modified', 'updated'))) {
continue;
}

	$isKey = false;
	if (!empty($associations['belongsTo'])) {
		foreach ($associations['belongsTo'] as $alias => $details) {
			if ($field === $details['foreignKey']) {
				$isKey = true;
				echo "\t\t<dt><?php echo __('" . Inflector::humanize(Inflector::underscore($alias)) . "'); ?></dt>\n";
				echo "\t\t<dd>\n\t\t\t<?php echo \$this->Html->link(\$mergedData['{$alias}']['{$details['displayField']}'], array('controller' => '{$details['controller']}', 'action' => 'view', \$mergedData['{$alias}']['{$details['primaryKey']}'])); ?>\n\t\t\t&nbsp;\n\t\t</dd>\n";
				break;
			}
		}
	}
	if ($isKey !== true) {
		echo "\t\t<dt><?php echo __('" . Inflector::humanize($field) . "'); ?></dt>\n";
		echo "\t\t<dd>\n\t\t\t<?php echo h(\$mergedData['{$modelClass}']['{$field}']); ?>\n\t\t\t&nbsp;\n\t\t</dd>\n";
	}
}
?>
</dl>

<?php echo "<?php echo \$this->Form->create('{$modelClass}', array('action' => 'form_confirm'));?>\n";?>
<?php
	echo "<?php echo \$this->Form->end(__('Submit'));?>\n";
?>
</div>