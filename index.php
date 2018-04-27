<?php

/*
Plugin Name: Phone Number Changer
Plugin URI:
Description: (Beta Version) Display organization/company real number to whitelisted countries only
Author: Ken Carino
Author URI: http://kencarino.com
Version: 2.0
*/

function add_change_number_shortcode( $atts ){

    global $wpdb;
    $table_name = $wpdb->prefix . 'number_changer';
    $real = $wpdb->get_results('SELECT * FROM ' . $table_name . ' WHERE real_status=1 LIMIT 1');
    $fake = $wpdb->get_results('SELECT * FROM ' . $table_name . ' WHERE fake_status=1 LIMIT 1');

    $ip = $_SERVER['REMOTE_ADDR'];
    $get_user_info = json_decode(file_get_contents("https://ipinfo.io/" . $ip));

    if($get_user_info->country == "SE") {
        ?>
            <script type="text/javascript">
            jQuery(document).ready(function () {
                var phone = "<?php echo $real[0]->phone; ?>";
                jQuery(".icon_pin").parent().html('<i class="icon icon_pin"></i> ' + phone);
                jQuery(".top-bar .alt-font").show();
            });
            </script>
        <?php
        return '<a class="phone text-white phone-call" href="tel:'. str_replace(' ', '', str_replace('-', '', $real[0]->phone)) .'">' . $real[0]->phone . '</a>';
    } else {
        ?>
            <script type="text/javascript">
            jQuery(document).ready(function () {
                var phone = "<?php echo $fake[0]->phone; ?>";
                jQuery(".icon_pin").parent().html('<i class="icon icon_pin"></i> ' + phone);
                jQuery(".top-bar .alt-font").show();
            });
            </script>
        <?php
        return '<a class="phone text-white phone-call" href="tel:'. str_replace(' ', '', str_replace('-', '', $fake[0]->phone)) .'">' . $fake[0]->phone . '</a>';
    }

}
add_shortcode( 'change_number', 'add_change_number_shortcode' );

if(isset($_REQUEST['submit'])) {
    insert_number($_POST);  // see below function.php
}

if(isset($_REQUEST['update_selected_real_number'])) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'number_changer';
    $wpdb->query('UPDATE ' . $table_name . ' SET real_status=0 WHERE real_status=1');
    $wpdb->query('UPDATE ' . $table_name . ' SET real_status=1 WHERE phone="'. $_POST['phone'] .'"');
}

if(isset($_REQUEST['update_selected_fake_number'])) { 
    global $wpdb;
    $table_name = $wpdb->prefix . 'number_changer';
    $wpdb->query('UPDATE ' . $table_name . ' SET fake_status=0 WHERE fake_status=1');
    $wpdb->query('UPDATE ' . $table_name . ' SET fake_status=1 WHERE phone="'. $_POST['fake_phone'] .'"');
}

if(isset($_REQUEST['delete_number'])) {

    $phone = (isset($_POST['phone'])?$_POST['phone']:$_POST['fake_phone']);

    global $wpdb;
    $table_name = $wpdb->prefix . 'number_changer';
    $wpdb->get_results('DELETE FROM ' . $table_name . ' WHERE phone="'. $phone .'" AND fake_status=0 AND real_status=0');

}

function insert_number( $userData ) {

    if(trim($userData['phone']) == '' || trim($userData['phone']) == null) {
        return false;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'number_changer';

    $qry = $wpdb->get_results('SELECT * FROM ' . $table_name . ' WHERE phone="' . trim($userData['phone']) . '"');

    if(count($qry) == 0) {
        $wpdb->insert($table_name , array(
            'phone'          => $userData['phone'],
            'real_status'    => 0,
            'fake_status'    => 0
        ));
    }

}

function get_number() {

    global $wpdb;
    $table_name = $wpdb->prefix . 'number_changer';
    return $wpdb->get_results('SELECT * FROM ' . $table_name . ' ORDER BY id DESC');

}

function get_phone_status( $status, $type ) {

    if($type == "Real") {
        $array_type = [
            1 => ' (Real) '
        ];
    } else {
        $array_type = [
            1 => ' (Fake) '
        ];
    }

    return $array_type[$status];

}

# Database Table Creation
register_activation_hook( __FILE__, 'create_db_table' );

function create_db_table() {
    
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'number_changer';

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        phone varchar(25) NOT NULL,
        real_status smallint(2) NOT NULL,
        fake_status smallint(2) NOT NULL,
        UNIQUE KEY id (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

}

# Functionalities
add_action('admin_menu', 'addMenu');

function addMenu() {
	add_menu_page('Change Primary', 'Phone Changer', 4, '/phone-changer/change-primary', 'changePrimaryMenu');
	// add_submenu_page('/phone-changer/change-primary', 'Settings', 'Settings', 4, '/phone-changer/settings', 'settingsMenu');
}

function settingsMenu() { 
    ob_start();
    ?>
    
    <style type="text/css">
        .phone-changer-container {
            width: 50%;
            padding: 10px 15px;
            background: #fff;
            /*border-radius: 8px;*/
        }  

        .phone-changer-container input {
            width: 50%;
            padding: 7px;
            border: 1px solid #aaa;
            color: #333;
			vertical-align: middle;
        }    

        .phone-changer-container select {
            width: 50%;
            padding: 0 7px;
            border: 1px solid #aaa;
            color: #333;
			height: 34px;
        }   
		
		.phone-changer-container select:focus {
			box-shadow: none;
		}
		
        button {
            background: #23282d;
            color: #fefefe;
            border: none;
            padding: 8px 18px;
			vertical-align: middle;
            cursor: pointer;
            border-radius: 1px;
            -webkit-transition: all ease 0.2s;
            -moz-transition: all ease 0.2s;
            -o-transition: all ease 0.2s;
            transition: all ease 0.2s;
        }  

        button:hover {
            background: #00a896;
        }

        .phone-changer-container .note {
            font-size: 12px;
            font-style: italic;
            margin-top: 10px;
        }   

        table {
            width: calc(50% + 30px);
        }
        
        table, th, td {
           border: 1px solid black;
           border-collapse: collapse;
        }

        table td {
            padding: 4px 7px;
            background: #FFF;
        }

        table tr:first-child {
            font-weight: bold;
        }

    </style>

    <br/><h1>Add Phone Number</h1>

    <div class="phone-changer-container">
        <form name="form" method="POST">
            <div class="form-group">
                <label for="phone">Phone Number:</label><br/>
                <input class="form-control" type="text" name="phone" id="phone"/>
                <button type="submit" name="submit" class="btn btn-default">Add Number</button>
            </div>
        </form>        
    </div>

<?php
}

function changePrimaryMenu() { ?>
    <br/><h1>Phone Changer</h1>

    <div class="phone-changer-container">
        <form name="form" method="POST" onsubmit="return confirm('Are you sure?');">
            <div class="form-group">
                <h3><label for="phone">Select real number:</label></h3>
                <select class="form-control" name="phone">
                <?php
                foreach(get_number() as $value) { 
                        $status = ''; 
                        $status_msg = ''; 
                        if($value->real_status == 1) {
                            $status = 'selected';
                            $status_msg = ' (selected)';
                        }
                        ?>
                        <option value="<?php echo $value->phone; ?>" <?php echo $status; ?>> <?php echo $value->phone . $status_msg; ?></option>
                <?php } ?>
                </select>
                <button type="submit" name="update_selected_real_number" class="btn btn-default">Select</button>
                <button type="submit" name="delete_number" class="btn btn-default">Delete</button>
            </div>
        </form>        

        <form name="form" method="POST" onsubmit="return confirm('Are you sure?');">
            <div class="form-group">
                <h3><label for="fake_phone">Select fake number:</label></h3>
                <select class="form-control" name="fake_phone">
                <?php
                foreach(get_number() as $value) { 
                        $status = ''; 
                        $status_msg = ''; 
                        if($value->fake_status == 1) {
                            $status = 'selected';
                            $status_msg = ' (selected)';
                        }
                        ?>
                        <option value="<?php echo $value->phone; ?>" <?php echo $status; ?>> <?php echo $value->phone . $status_msg; ?></option>
                <?php } ?>
                </select>
                <button type="submit" name="update_selected_fake_number" class="btn btn-default">Select</button>
                <button type="submit" name="delete_number" class="btn btn-default">Delete</button>
            </div>
        </form>       
        <div class="note">* Selected numbers cannot be deleted.</div> 
    </div>
<?php
    settingsMenu();
?>
    <br/>
    <h1>List of Numbers</h1>
    <table border="1">
        <tr>
            <td>Phone</td>
            <td>Status</td>
            <td>Action</td>
        </tr>
        <?php
        foreach(get_number() as $value) { ?>
                <tr>
                    <td><?php echo $value->phone; ?></td>
                    <td><?php echo get_phone_status($value->real_status, "Real"); echo get_phone_status($value->fake_status, "Fake"); ?></td>
                    <td>
                        <form method="POST">
                            <input name="phone" type="hidden" value="<?php echo $value->phone; ?>" />
                            <button type="submit" name="delete_number" class="btn btn-default">Delete</button>
                        </form>
                    </td>
                </tr>
        <?php } ?>
    </table>
<?php }