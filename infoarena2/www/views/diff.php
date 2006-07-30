<?php include('header.php'); ?>

<?php if ($view['page_name']) { ?>
    <h1>Diferente pentru pagina <a href="<?= url($view['page_name']) ?>"><?= $view['page_name'] ?></a></h1>
<?php } else { ?>
    <h1>Diferente pentru pagina <?= $view['page_name'] ?></h1>
<?php } ?>
    
<?php
if (count($view['diff_title']) <= 1) {
    echo "<h3>Nu exista diferente intre titluri.</h3>";
}
else {
    echo "<h3>Diferente intre titluri:</h3>";
    echo "<pre class=\"diff\">";
    for ($i = 0; $i+1 < count($view['diff_title']); $i++) {
        $s = $view['diff_title'][$i];
        echo htmlentities($s)."\n";
}
    echo "</pre>";
}
?>

<?php
if (count($view['diff_content']) <= 1) {
    echo "<h3>Nu exista diferente intre continut.</h3>";
}
else {
    echo "<h3>Diferente intre continut:</h3>";
    echo "<pre class=\"diff\">";
    for ($i = 0; $i+1 < count($view['diff_content']); $i++) {
        $s = $view['diff_content'][$i];
        echo htmlentities($s)."\n";
    }
    echo "</pre>";
}
?>

<?php include('footer.php'); ?>