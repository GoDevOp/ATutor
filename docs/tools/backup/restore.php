<?php
/****************************************************************************/
/* ATutor																	*/
/****************************************************************************/
/* Copyright (c) 2002-2005 by Greg Gay, Joel Kronenberg & Heidi Hazelton	*/
/* Adaptive Technology Resource Centre / University of Toronto				*/
/* http://atutor.ca															*/
/*																			*/
/* This program is free software. You can redistribute it and/or			*/
/* modify it under the terms of the GNU General Public License				*/
/* as published by the Free Software Foundation.							*/
/****************************************************************************/
// $Id$

define('AT_INCLUDE_PATH', '../../include/');
require(AT_INCLUDE_PATH.'vitals.inc.php');

authenticate(AT_PRIV_ADMIN); 
require(AT_INCLUDE_PATH.'classes/Backup/Backup.class.php');
require(AT_INCLUDE_PATH.'lib/filemanager.inc.php');

$Backup =& new Backup($db, $_SESSION['course_id']);

if (!isset($_REQUEST['backup_id'])) {
	header('Location: index.php');
	exit;
}

if (isset($_POST['cancel'])) {
	$msg->addFeedback('CANCELLED');
	header('Location: index.php');
	exit;
} else if (isset($_POST['submit'])) {
	if (!$_POST['material']) {
		$msg->addError('RESTORE_MATERIAL');
	} else {
		$Backup->restore($_POST['material'], $_POST['action'], $_POST['backup_id']);

		$msg->addFeedback('IMPORT_SUCCESS');
		header('Location: index.php');
		exit;
	}
}

require(AT_INCLUDE_PATH.'header.inc.php');

$row = $Backup->getRow($_REQUEST['backup_id']);

?>

<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" name="form">
<input type="hidden" name="backup_id" value="<?php echo $_REQUEST['backup_id']; ?>" />

<div class="input-form">
	<div class="row">
		<div class="required" title="<?php echo _AT('required_field'); ?>">*</div><?php echo _AT('material'); ?><br />

		<input type="checkbox" value="1" name="all" id="all" onclick="javascript:selectAll();" /><label for="all"><?php echo _AT('material_select_all'); ?></label><br /><br />

		<?php
		$modules = $moduleFactory->getModules(AT_MODULE_STATUS_ENABLED | AT_MODULE_STATUS_DISABLED, 0, TRUE);
		$keys = array_keys($modules);
		?>
		<?php foreach($keys as $module_name): ?>
			<?php $module =& $modules[$module_name]; ?>
			<?php if ($module->isBackupable()): ?>
				<input type="checkbox" value="1" name="material[<?php echo $module_name; ?>]" id="m<?php echo $module_name; ?>" /><label for="m<?php echo $module_name; ?>"><?php echo $module->getName($_SESSION['lang']); ?></label><br />
			<?php endif; ?>
		<?php endforeach; ?>


	</div>

	<div class="row">
		<?php echo _AT('action'); ?><br />
		<input type="radio" checked="checked" name="action" value="append" id="append" /><label for="append"><?php echo _AT('append_content'); ?></label><br />
		
		<input type="radio" name="action" value="overwrite" id="overwrite" /><label for="overwrite"><?php echo _AT('overwite_content'); ?></label><br />
	</div>

	<div class="row buttons">
		<input type="submit" name="submit" value="<?php echo _AT('restore'); ?>" /> 
		<input type="submit" name="cancel" value="<?php echo _AT('cancel'); ?>" />
	</div>
</div>
</form>

<script language="javascript" type="text/javascript">
	
	function selectAll() {
		if (document.form.all.checked == true) {
			<?php foreach($keys as $module_name): $module =& $modules[$module_name]; if ($module->isBackupable()): ?>
				document.form.m<?php echo $module_name; ?>.checked = true;
			<?php endif; endforeach; ?>
		} else {
			<?php foreach($keys as $module_name): $module =& $modules[$module_name]; if ($module->isBackupable()): ?>
				document.form.m<?php echo $module_name; ?>.checked = false;
			<?php endif; endforeach; ?>

		}
	}
</script>

<?php require (AT_INCLUDE_PATH.'footer.inc.php');  ?>