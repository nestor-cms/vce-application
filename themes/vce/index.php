<?php
/*
Template Name: Index
*/
?>
<!DOCTYPE html>
<html lang="en">
<meta charset="utf-8">
<title><?php $site->site_title(); ?> : <?php $page->title(); ?></title>
<?php $content->javascript(); ?>
<?php $content->stylesheet(); ?>
</head>
<body>

<?php $content->menu('main'); ?>

<?php $content->output(array('admin', 'premain', 'main', 'postmain')); ?>

</div>
</body>
</html>