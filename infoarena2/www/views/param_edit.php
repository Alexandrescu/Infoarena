<?php
// Display a parameter table.
// FIXME: format_table?
?>
<?php
log_assert(is_array($entity_types), "entity_types missing from view");
log_assert(is_array($param_infos), "param_infos missing from view");
foreach ($entity_types as $etype) {
?>
    <table class="parameters" id="<?= "params_$etype" ?>">
        <thead>
            <tr>
                <th>Parametru</th>
                <th>Valoare</th>
                <th>Descriere</th>
            </tr>
        </thead>
        <tbody>
    <?php
    foreach ($param_infos[$etype] as $name => $info) {
        $form_name = "param_{$etype}_{$name}";
        $label_tag = format_tag('label', $info['name'], array(
                'for' => "form_$form_name",
        ));

        // Handle booleans.
        if ($info['type'] == 'boolean') {
            $input_tag = format_open_tag('select', array(
                    'name' => $form_name,
                    'id' => "form_$form_name",
            ));
            if (fval($form_name)) {
                $input_tag .= '<option value="1" selected="selected">'.
                    'Da</option>';
                $input_tag .= '<option value="0">Nu</option>';
            } else {
                $input_tag .= '<option value="1">Da</option>';
                $input_tag .= '<option value="0" selected="selected">'.
                    'Nu</option>';
            }
            $input_tag .= "</select>";
        } else {
            $input_tag = format_tag('input', null, array(
                    'type' => 'text',
                    'class' => 'parameter',
                    'value' => fval($form_name),
                    'id' => "form_$form_name",
                    'name' => $form_name,
            ));
        }
    ?>
        <tr>
            <td><?= $label_tag ?></td>
            <td><?= $input_tag ?> <?= ferr_span($form_name) ?> </td>
            <td><?= $info['description'] ?></td>
        </tr>
    <?php } ?>
        </tbody>
    </table>
<?php } ?>

