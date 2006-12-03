<?php include('header.php'); ?>

<h1><?= $title ?></h1>

<form action="<?= url($page_name) ?>" method="post" class="hollyfix">
<fieldset>
    <input type="hidden" name="action" value="move" />

    <ul class="form">
        <li>
            <label for="form_new_name">Noul nume</label>
            <?= ferr_span('new_name') ?>
            <input type="text" name="new_name" value="<?= fval('new_name') ?>" id="form_new_name" />
        </li>
    </ul>
</fieldset>
<ul class="form">
    <li>
        <input type="submit" class="button important" value="Muta pagina" id="form_submit" />
    </li>
</ul>
</form>

<?php include('footer.php'); ?>
