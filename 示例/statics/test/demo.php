<?php
	$htmlData = '';
	if (!empty($_POST['content1'])) {
		if (get_magic_quotes_gpc()) {
			$htmlData = stripslashes($_POST['content1']);
		} else {
			$htmlData = $_POST['content1'];
		}
	}
    
    $URL='';
    
?>

	<meta charset=gbk />
	<title>KindEditor PHP</title>
	<link rel="stylesheet" href="{$URL}themes/default/default.css" />
	<link rel="stylesheet" href="{$URL}plugins/code/prettify.css" />
	<script charset=gbk src="{$URL}kindeditor.js"></script>
	<script charset=gbk src="{$URL}lang/zh_CN.js"></script>
	<script charset=gbk src="{$URL}plugins/code/prettify.js"></script>
	<script>
		KindEditor.ready(function(K) {
			var editor1 = K.create('textarea[name="content1"]', {
				cssPath : '{$URL}/plugins/code/prettify.css',
				uploadJson : '{$URL}/upload_json.php',
				fileManagerJson : '{$URL}/file_manager_json.php',
				allowFileManager : true,
				afterCreate : function() {
					var self = this;
					K.ctrl(document, 13, function() {
						self.sync();
						K('form[name=example]')[0].submit();
					});
					K.ctrl(self.edit.doc, 13, function() {
						self.sync();
						K('form[name=example]')[0].submit();
					});
				}
			});
			prettyPrint();
		});
	</script>

	

		<textarea name="content1" style="width:700px;height:200px;visibility:hidden;"><?php echo htmlspecialchars($htmlData); ?></textarea>




