<?php
add_action('user_register', 'my_function');

function my_function($user_id) {
// Pripojenie k inej databáze
    include( 'pripojenie.php'); 

    $user = get_user_by('ID', $user_id);
    if (!$user) return;

    $id_uzivatela = $user->ID;
    $email = $user->user_email;
    $heslo = $user->user_pass;
    $meno = $user->user_login;
    $nickname = $user->user_nicename;
    $datum_registracie = $user->user_registered;
    $displayname = $user->display_name;

    // Pripojenie k inej databáze
    $remote_conn = new mysqli(REMOTE_DB_HOST, REMOTE_DB_USER, REMOTE_DB_PASSWORD, REMOTE_DB_NAME);
    if ($remote_conn->connect_error) {
        die("Connection to remote database failed: " . $remote_conn->connect_error);
    }
    $remote_conn->set_charset("utf8");

    $prefix_user = $remote_conn->prefix . 'users';
    $prefix_usermeta = $remote_conn->prefix . 'usermeta';

    $rola = serialize(['customer' => true]);

    $remote_conn->query("INSERT INTO `$prefix_user` (`ID`, `user_login`, `user_pass`, `user_nicename`, `user_email`, `user_registered`, `display_name`) 
    VALUES ('$id_uzivatela', '$meno', '$heslo', '$nickname', '$email', '$datum_registracie', '$displayname')");
    $remote_conn->query("INSERT INTO `$prefix_usermeta` (`user_id`, `meta_key`, `meta_value`) 
    VALUES ('$id_uzivatela', 'wp_capabilities', '$rola')");

    $remote_conn->close();
}

add_action('profile_update', 'my_profile_update', 10, 2);
add_action('woocommerce_customer_save_address', 'my_profile_update', 10, 2);

function my_profile_update($user_id, $old_user_data) {
    // Pripojenie k inej databáze
    include( 'pripojenie.php'); 

    $remote_conn = new mysqli(REMOTE_DB_HOST, REMOTE_DB_USER, REMOTE_DB_PASSWORD, REMOTE_DB_NAME);
    if ($remote_conn->connect_error) {
        die("Connection to remote database failed: " . $remote_conn->connect_error);
    }
    $remote_conn->set_charset("utf8");

    $prefix_usermeta = $remote_conn->prefix . 'usermeta';

    $fields = [
        'billing_first_name', 'billing_last_name', 'billing_company',
        'billing_address_1', 'billing_address_2', 'billing_city',
        'billing_postcode', 'billing_country', 'billing_email',
        'billing_phone', 'billing_ico', 'billing_dic', 'billing_ic_dph'
    ];

    foreach ($fields as $field) {
        $value = get_user_meta($user_id, $field, true);
        $remote_conn->query("UPDATE `$prefix_usermeta` SET `meta_value` = '$value' WHERE `user_id` = '$user_id' AND `meta_key` = '$field'");
    }

    $remote_conn->close();
}