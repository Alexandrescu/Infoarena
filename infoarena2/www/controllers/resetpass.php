<?php

require_once(IA_ROOT."common/db/smf.php");
require_once(IA_ROOT."common/db/user.php");

// hash user info in password reset code
// Note: we keep the code short enough so it does not wrap on several lines
// in bad e-mail clients.
function resetpass_code($user) {
    return substr(sha1($user['password'].IA_SECRET), 0, 24);
}

// displays form to identify user. On submit it sends e-mail with confirmation
// link.
function controller_resetpass() {
    // `data` dictionary is a dictionary with data to be displayed by form view
    $data = array();

    // here we store validation errors.
    // It is a dictionary, indexed by field names
    $errors = array();

    // submit?
    $submit = request_is_post();

    if ($submit) {
        // 1. validate
        // check username
        $data['username'] = getattr($_POST, 'username');
        $data['email'] = getattr($_POST, 'email');
        if ($data['username']) {
            $user = user_get_by_username($data['username']);
            if (!$user) {
                $errors['username'] = 'Nu exista vreun utilizator cu acest '
                                      .'nume de cont';
            }
        }
        elseif ($data['email']) {
            $user = user_get_by_email($data['email']);
            if (!$user) {
                $errors['email'] = 'Nu exista utilizator cu aceasta adresa '
                                   .'de e-mail.';
            }
        }

        if (isset($user) && $user) {
            // user was found

            // confirmation code
            $cpass = $user['username'].'-'.resetpass_code($user);

            // confirmation link
            $clink = url('confirm', array('c' => $cpass), true);

            // email user
            $to = $user['email'];
            $subject = 'Recupereaza utilizator si parola';
            $message = "
Ai solicitat ca parola contului tau de pe infoarena fie resetata.

Nume de cont: {$user['username']}
Adresa ta de e-mail: {$user['email']}
Numele tau: {$user['full_name']}

Pentru a confirma aceasta actiune trebuie sa vizitezi acest link:

----
$clink
----

Daca nu ai facut o astfel de solicitare, ignora acest mesaj iar parola nu va fi resetata.

Echipa infoarena
".IA_URL."\n";

            // send email
            send_email($to, $subject, $message);

            // notify user
            flash('Am trimis instructiuni pe e-mail.');
            redirect(url('login'));
        }
        else {
            flash_error('Trebuie sa completezi cel putin unul din campuri!');
        }
    }
    else {
        // initial display of form
    }

    // page title
    $view = array();
    $view['title'] = 'Recuperare parola';
    $view['form_errors'] = $errors;
    $view['form_values'] = $data;
    $view['no_sidebar_login'] = true;
    execute_view('views/resetpass.php', $view);
}

// checks confirmation code and resets password
function controller_resetpass_confirm() {
    $code = getattr($_GET, 'c');
    $pair = split('-', $code);

    $username = getattr($pair, 0);
    $cpass = getattr($pair, 1);

    // validate username
    if ($username) {
        $user = user_get_by_username($username);
    }
    if (!$user) {
        flash_error('Numele de utilizator este invalid.');
        redirect(url(''));
    }

    // validate confirmation code
    if ($cpass != resetpass_code($user)) {
        flash_error('Codul de confirmare nu este corect!');
        redirect(url(''));
    }

    // reset password
    $new_password = sha1(mt_rand(1000000, 9999999).IA_SECRET);
    $new_password = substr($new_password, 0, 6);
    $fields = array(
        'password' => $new_password,
        'username' => $user['username']
    );
    user_update($fields, $user['id']);

    // also update SMF user entry
    smf_update_user(user_get_by_id($user['id']));

    // send email with new password
    $to = $user['email'];
    $subject = 'Parola noua';
    $message = "
Ai solicitat si ai confirmat ca parola ta sa fie resetata.

Parola noua: {$new_password}
Numele contului: {$user['username']}

Te poti autentifica aici:
".url('login', array(), true)."

Echipa infoarena
".IA_URL."\n";

    // send e-mail
    send_email($to, $subject, $message);

    // notify yser
    flash('Parola a fost resetata si trimisa pe e-mail. Verifica-ti '
          .'e-mail-ul ca sa afli noua parola.');
    redirect(url('login'));
}

?>
