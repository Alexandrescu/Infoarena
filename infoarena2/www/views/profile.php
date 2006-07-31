<?php include('header.php'); ?>

<h1><?= htmlentities($view['title']) ?></h1>

<?php if ($register) { ?>
<form enctype="multipart/form-data" action="<?= url('register/save') ?>" method="post">
<?php } else {?>
<form enctype="multipart/form-data" action="<?= url('profile/save') ?>" method="post">
<?php } ?>
<div class="tabber userProfile">
    <?php if (!$register) { ?>
    <div class="tabbertab<?= 'generalData' == $active_tab ? ' tabbertabdefault' : '' ?> generalData">
        <h3>Date generale</h3>
    <?php } ?>
        <ul class="form">
            <?php if ($register) { ?>
            <li>
                <label for='form_username'>Nume utilizator</label>
                <input type="text" name="username" value="<?= fval('username') ?>" id="form_username" />
                <?= ferr_span('username') ?> 
            </li>
            <?php } ?>
        
            <?php if (!$register) { ?>
            <li>
                <label for='form_password_old'>Parola veche</label>
                <input type="password" name='password_old' id="form_password_old" />
                <?= ferr_span('password_old') ?>
                <span class="fieldHelp">Necesara pentru modificarea parolei sau adresei de email</span>
            </li>
            <?php } ?>
            
            <li>
                <label for='form_password'>Parola<?php if (!$register) echo ' noua';?></label>
                <input type="password" name='password' id="form_password" />
                <?= ferr_span('password') ?>
            </li>
        
            <li>
                <label for='form_password2'>Confirmare parola</label>
                <input type="password" name='password2' id="form_password2" />
                <?= ferr_span('password2') ?>
            </li>
            
            <li>
                <label for="form_email">Adresa e-mail</label>
                <input type="text" name="email" value="<?= fval('email') ?>" id="form_email" />
                <?= ferr_span('email') ?>
            </li>
            
            <li>
                <label for="form_name">Nume complet</label>
                <input type="text" name="full_name" value="<?= fval('full_name') ?>" id="form_name" />
                <?= ferr_span('full_name') ?>
            </li>
        
            <li>
                <label for="form_country">Tara</label>
                <input type="text" name="country" value="<?= fval('country') ?>" id="form_country" />
                <?= ferr_span('country') ?>
            </li>
            
            <li>
                <label for="form_county">Judetul</label>
                <select name="county" id="form_county">
                    <option selected="selected" value="TODO">TODO</option>
                    <option value="Bucuresti">Bucuresti</option>
                </select>
                <?= ferr_span('county') ?>
            </li>
            
            <li>
                <label for="form_newsletter">Abonat la newsletter</label>
                <input type="checkbox" <?php if (fval('newsletter'))
                    echo 'checked="checked"'; ?> name="newsletter" id="form_newsletter"/>
            </li>
        </ul>
    <?php if (!$register) { ?>
    </div>
    <div class="tabbertab<?= 'profileData' == $active_tab ? ' tabbertabdefault' : '' ?> profileData">
        <h3>Profil</h3>
        <ul class="form">
            <?php if (!$register) { ?>
            <li>
                <?php // display avatar
                    $dic['action'] = 'download';
                    $dic['file'] = $avatar;
                    echo '<img src="' . url('user/'.$username, $dic) . '"/>';
                ?>
                
                <label for="form_avatar">Avatar</label>
                <input type="file" name="avatar" value="" id="form_avatar" />
                <?= ferr_span('avatar') ?>
            </li>
            <?php } ?>
            
            <li>
                <label for="form_quote">Citat</label>
                <textarea name="quote" id="form_quote"><?= fval('quote') ?></textarea>
                <?= ferr_span('quote') ?>
            </li>
            
            <li>
                <label for="form_birthday">Data nasterii</label>
                <input type="text" name="birthday" value="<?= fval('birthday') ?>" id="form_birthday" />
                <?= ferr_span('birthday') ?>
                <span class="fieldHelp">Trebuie sa fie de forma AAAA-LL-ZZ</span>
            </li>
            <li>
                <label for="form_lines_per_page">Numarul de linii pe pagina</label>
                <input type="text" size='2' maxlength='2' name="lines_per_page" value="<?= fval('lines_per_page') ?>" id="form_lines_per_page" />
                <?= ferr_span('lines_per_page') ?>
                <span class="fieldHelp">Folosit in tablele ca monitorul de evaluare</span>
            </li>
        </ul>
    </div>
    
    <div class="tabbertab<?= 'personalData' == $active_tab ? ' tabbertabdefault' : '' ?> personalData">
        <h3>Date personale</h3>
        <ul class="form">
            <li>
                <label for="form_city">Oras</label>
                <input type="text" name="city" value="<?= fval('city') ?>" id="form_city" />
                <?= ferr_span('city') ?> 
            </li>
            
            <li>
                <label for="form_workplace">Institutie de invatamant</label>
                <input type="text" name="workplace" value="<?= fval('workplace') ?>" id="form_workplace" />
                <?= ferr_span('workplace') ?>
                <span class="fieldHelp">(merge un assisted-input aici)</span>
            </li>
            
            <li>
                <label for="form_study_level">Nivel scolar</label>
                <select name="study_level" id="form_study_level">
                    <option <?php if (fval('study_level') == 'nespecificat')
                    echo 'selected="selected"'; ?> value="nespecificat">nespecificat</option>
                    <option <?php if (fval('study_level') == 'gimnaziu')
                    echo 'selected="selected"'; ?> value="gimnaziu">gimnaziu</option>
                    <option <?php if (fval('study_level') == 'liceu')
                    echo 'selected="selected"'; ?> value="liceu">liceu</option>
                    <option <?php if (fval('study_level') == 'facultate')
                    echo 'selected="selected"'; ?> value="facultate">facultate</option>
                    <option <?php if (fval('study_level') == 'absolvent')
                    echo 'selected="selected"'; ?> value="absolvent">absolvent</option>
                </select>
            </li>
        
            <li>
                <label for="form_abs_year">Anul de absolvire</label>
                <input type="text" size='4' maxlength='4' name="abs_year" value="<?= fval('abs_year') ?>" id="form_abs_year" />
                <?= ferr_span('abs_year') ?>
            </li>
            
            <li>
                <label for="form_postal_address">Adresa postala</label>
                <textarea name="postal_address" id="form_postal_address"><?= fval('postal_address') ?></textarea>
                <?= ferr_span('postal_address') ?>
            </li>
            
            <li>
                <label for="form_phone">Numar telefon</label>
                <input type="text" name="phone" value="<?= fval('phone') ?>" id="form_phone" />
                <?= ferr_span('phone') ?>
            </li>
        </ul>
    </div>
    <?php } ?>
</div>

<div class="submit">
    <ul class="form">
        <li>
            <?php if ($register) { ?>
            <input type="submit" value="Inregistreaza-ma" id="form_submit" />
            <?php } else { ?>
            <input type="submit" value="Salveaza modificarile" id="form_submit" />
            <? } ?>
        </li>
    </ul>
</div>



</form>

<?php include('footer.php'); ?>
