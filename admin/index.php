<?php
/**
*   Administrative entry point for the Subscription plugin.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2010 Lee Garner
*   @package    subscription
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

/** Import core glFusion functions */
require_once('../../../lib-common.php');

if (!in_array('subscription', $_PLUGINS)) {
    COM_404();
}

USES_subscription_functions();
USES_lib_admin();
USES_subscription_class_product();


/**
*   Basic admin menu for Subscription administration
*
*   @param  string  $instr  Specific text instructions to show
*   @return string          HTML for admin menu
*/
function SUBSCR_adminMenu($view = '')
{
    global $_CONF, $LANG_ADMIN, $LANG_SUBSCR;

    $menu_arr = array (
        array('url' => $_CONF['site_admin_url'],
                'text' => $LANG_ADMIN['admin_home']),
    );
    if ($view == 'products') {
        $menu_arr[] = array('url' => SUBSCR_ADMIN_URL . '/index.php?editproduct=x',
                'text' => '<span class="subNewAdminItem">' . $LANG_SUBSCR['new_product'] . '</span>');
    } else {
        $menu_arr[] = array('url' => SUBSCR_ADMIN_URL . '/index.php',
                'text' => $LANG_SUBSCR['products']);
    }
    if ($view == 'subscriptions') {
        $menu_arr[] = array('url' => SUBSCR_ADMIN_URL . '/index.php?editsubscrip=x',
                'text' => '<span class="subNewAdminItem">' . $LANG_SUBSCR['new_subscription'] . '</span>');
    } else {
        $menu_arr[] = array('url' => SUBSCR_ADMIN_URL . '/index.php?subscriptions=x',
                'text' => $LANG_SUBSCR['subscriptions']);
    }

    if (isset($LANG_SUBSCR['admin_txt_' . $view])) {
        $hdr_txt = $LANG_SUBSCR['admin_txt_' . $view];
    } else {
        $hdr_txt = $LANG_SUBSCR['admin_txt'];
    }

    $retval = ADMIN_createMenu($menu_arr, $hdr_txt, 
            plugin_geticon_subscription());

    return $retval;

}


/**
*   Create an admin list of subscriptions for a product
*
*   @return string  HTML for list
*/
function SUBSCR_subscriptionList($item_id)
{
    global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_ACCESS;
    global $_CONF_SUBSCR, $LANG_SUBSCR, $_IMAGE_TYPE, $LANG01;

    $retval = '';

    $header_arr = array(      # display 'text' and use table field 'field'
        array('field' => 'edit', 'text' => $LANG_ADMIN['edit'], 
            'sort' => false, 'align' => 'center'),
        //array('field' => 'uid', 
        //    'text' => $LANG_SUBSCR['uid'], 'sort' => true),
        array('field' => 'subscriber',
            'text' => $LANG_SUBSCR['subscriber'], 'sort' => false),
    );
    if (empty($item_id)) {
        $header_arr[] = array('field' => 'plan', 
            'text' => $LANG_SUBSCR['plan'], 'sort' => true);
    }
    $header_arr[] = array('field' => 'expiration', 
            'text' => $LANG_SUBSCR['expires'], 'sort' => true);

    $defsort_arr = array('field' => 'expiration', 'direction' => 'desc');

    $title = $LANG_SUBSCR['admin_hdr'];
    if (!empty($item_id)) {
        list($item_name, $duration) = DB_fetchArray(DB_query(
            "SELECT name, duration FROM {$_TABLES['subscr_products']}
            WHERE item_id='$item_id'", 1), false);
        $title  .= " :: $item_name";
        $item_query = " s.item_id = '".DB_escapeString($item_id)."' ";
    } else {
        $item_query = ' 1=1 ';
    }

    $retval .= COM_startBlock($title, '', 
            COM_getBlockTemplate('_admin_block', 'header'));

    $retval .= SUBSCR_adminMenu('subscriptions');

    $text_arr = array(
        'has_extras' => true,
        'form_url' => SUBSCR_ADMIN_URL . 
                '/index.php?subscriptions=x&amp;item_id='.$item_id,
    );

    $options = array('chkdelete' => 'true', 'chkfield' => 'id',
        'chkactions' => '<input name="cancelbutton" type="image" src="'
            . $_CONF['layout_url'] . '/images/admin/delete.' . $_IMAGE_TYPE
            . '" style="vertical-align:text-bottom;" title="' . $LANG01[124]
            . '" class="gl_mootip"'
            . ' data-uk-tooltip="{pos:\'top-left\'}"'
            . ' onclick="return confirm(\'' . $LANG01[125] . '\');"'
            . '/>&nbsp;' . $LANG_ADMIN['delete'] . '&nbsp;&nbsp;' .

            '<input name="renewbutton" type="image" src="'
            . SUBSCR_URL . '/images/renew.png'
            . '" style="vertical-align:text-bottom;" title="' . $LANG_SUBSCR['renew_all'] 
            . '" class="gl_mootip"' 
            . ' data-uk-tooltip="{pos:\'top-left\'}"'
            . ' onclick="return confirm(\'' . $LANG_SUBSCR['confirm_renew']
            . '\');"'
            . '/>&nbsp;' . $LANG_SUBSCR['renew'],
    );
    
    if (isset($_POST['showexp'])) {
        $frmchk = 'checked="checked"';
        $exp_query = '';
    } else {
        $frmchk = '';
        $exp_query = ' AND s.status = ' . SUBSCR_STATUS_ENABLED;
    }

    $query_arr = array('table' => 'subscr_subscriptions',
        'sql' => "SELECT s.*, p.name as plan, u.username, u.fullname
                FROM {$_TABLES['subscr_subscriptions']} s
                LEFT JOIN {$_TABLES['subscr_products']} p
                    ON s.item_id = p.item_id
                LEFT JOIN {$_TABLES['users']} u
                    ON s.uid = u.uid
                WHERE $item_query
                $exp_query",
        'query_fields' => array('username', 'fullname',),
        'default_filter' => '',
    );
    //echo $query_arr['sql'];die;

    $filter = '<input type="checkbox" name="showexp" ' . $frmchk . 
            ' onclick="javascript:submit();"> Show expired?';
    /*$form_arr = array(
        'top' => '<input type="checkbox" name="showexp"> Show expired?'
    );*/
    $retval .= ADMIN_list('subscription', 'SUBSCR_subscription_getListField', 
                $header_arr,
                $text_arr, $query_arr, $defsort_arr, $filter, '', 
                $options, $form_arr);
    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));

    return $retval;
}


/**
*   Get a single field for the Subscription admin list.
*
*   @param  string  $fieldname  Name of field
*   @param  mixed   $fieldvalud Value of field
*   @param  array   $A          Array of all fields
*   @param  array   $icon_arr   Array of system icons
*   @return string              HTML content for field display
*/
function SUBSCR_subscription_getListField($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $LANG_ACCESS, $LANG_SUBSCR, $_CONF_SUBSCR;

    $retval = '';

    switch($fieldname) {
    case 'edit':
        $retval .= COM_createLink(
            $icon_arr['edit'],
            SUBSCR_ADMIN_URL . 
            '/index.php?editsubscrip=x&amp;sub_id=' . $A['id']
        );
        break;

    case 'uid':
        $retval = COM_createLink($fieldvalue, 
            $_CONF['site_url'] . '/users.php?mode=profile&uid=' . $fieldvalue);
        break;

    case 'subscriber':
        $retval = COM_createLink(COM_getDisplayName($A['uid']), 
            $_CONF['site_url'] . '/users.php?mode=profile&uid=' .$A['uid']);
        break;

    case 'expiration':
        if ($A['status'] > SUBSCR_STATUS_ENABLED) {
            $retval = '<span class="expired">' . $fieldvalue . '</span>';
        } elseif ($fieldvalue < date('Y-m-d')) {
            $retval .= '<span class="ingrace">' . $fieldvalue . '</span>';
        } else {
            $retval = $fieldvalue;
        }
        break;
    default:
        $retval = $fieldvalue;
        break;
    }

    return $retval;
}


/**
*   Create an admin list of products.
*
*   @return string  HTML for list
*/
function SUBSCR_productAdminList()
{
    global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_ACCESS;
    global $_CONF_SUBSCR, $LANG_SUBSCR, $LANG28;

    $retval = '';

    $header_arr = array(      # display 'text' and use table field 'field'
        array('field' => 'edit', 
            'text' => $LANG_ADMIN['edit'], 'sort' => false),
        array('field' => 'enabled', 
            'text' => $LANG_SUBSCR['enabled'], 'sort' => false,
            'align' => 'center'),
        array('field' => 'name', 
            'text' => $LANG_SUBSCR['name'], 'sort' => true),
        array('field' => 'duration', 
            'text' => $LANG_SUBSCR['duration'], 'sort' => false),
        array('field' => 'grp_name',
            'text' => $LANG28[101], 'sort' => false),
        array('field' => 'price', 
            'text' => $LANG_SUBSCR['price'], 'sort' => true),
        array('field' => 'subscriptions',
            'text' => $LANG_SUBSCR['subscriptions'], 'sort' => true),
        array('field' => 'delete',
            'text' => $LANG_ADMIN['delete'], 'sort' => false),
    );

    $defsort_arr = array('field' => 'name', 'direction' => 'asc');

    $retval .= COM_startBlock($LANG_SUBSCR['admin_hdr'], '', COM_getBlockTemplate('_admin_block', 'header'));

    $retval .= SUBSCR_adminMenu('products');

    $text_arr = array(
        'has_extras' => true,
        'form_url' => SUBSCR_ADMIN_URL . '/index.php?type=products',
    );

    //$options = array('chkdelete' => 'true', 'chkfield' => 'camp_id');

    $query_arr = array('table' => 'subscr_products',
        'sql' => "SELECT p.*, g.grp_name
                FROM {$_TABLES['subscr_products']} p 
                LEFT JOIN {$_TABLES['groups']} g
                    ON g.grp_id=p.addgroup", 
        'query_fields' => array('name', 'description'),
        'default_filter' => ' WHERE 1=1 ',
    );

    $retval .= ADMIN_list('subscription', 'SUBSCR_product_getListField', 
                    $header_arr,
                    $text_arr, $query_arr, $defsort_arr, $filter, '', 
                    $options, $form_arr);
    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));

    return $retval;
}


/**
*   Get a single field for the Subscription Product admin list.
*
*   @param  string  $fieldname  Name of field
*   @param  mixed   $fieldvalud Value of field
*   @param  array   $A          Array of all fields
*   @param  array   $icon_arr   Array of system icons
*   @return string              HTML content for field display
*/
function SUBSCR_product_getListField($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $LANG_ACCESS, $LANG_SUBSCR, $_CONF_SUBSCR, $_TABLES;

    $retval = '';

    switch($fieldname) {
    case 'edit':
        $retval .= COM_createLink(
            $icon_arr['edit'],
            SUBSCR_ADMIN_URL . 
                '/index.php?editproduct=x&amp;item_id=' . $A['item_id']
        );
        break;

    case 'delete':
        if (!SubscriptionProduct::isUsed($A['item_id'])) {
            $retval .= COM_createLink($icon_arr['delete'],
                SUBSCR_ADMIN_URL . 
                "/index.php?deleteproduct=x&amp;item_id={$A['item_id']}"
            );
        }
        break;

    case 'enabled':
        $enabled = $fieldvalue == 1 ? 1 : 0;
        $chk = $enabled ? 'checked="checked"' : '';
        $retval = "<input type=\"checkbox\" name=\"togena{$A['item_id']}\"
            id=\"togena{$A['item_id']}\" $chk
            onchange='SUBSCR_toggleEnabled(\"{$enabled}\", \"{$A['item_id']}\",
                \"subscription\", \"{$_CONF['site_url']}\");' />";
        break;

    case 'name':
        $retval = COM_createLink($fieldvalue, 
                SUBSCR_ADMIN_URL . 
                '/index.php?subscriptions=' . $A['item_id']);
        break;

    case 'duration':
        if ($A['expiration'] !== NULL) {
            $retval = $LANG_SUBSCR['expires'] . ' ' . $A['expiration'];
        } else {
            $retval = $fieldvalue . ' ' . $LANG_SUBSCR[$A['duration_type']];
        }
        break;

    case 'subscriptions':
        $retval = (int)DB_count($_TABLES['subscr_subscriptions'], 
                'item_id', $A['item_id']);
        break;

    default:
        $retval = $fieldvalue;
        break;
    }

    return $retval;
}



/**
*   MAIN
*/
// Only let admin users access this page
if (!SEC_inGroup($_CONF_SUBSCR['pi_name'] . ' Admin')) {
    COM_errorLog("Attempted unauthorized access the Subscription Admin page." . 
        " User id: {$_USER['uid']}, Username: {$_USER['username']}, " .
        " IP: $REMOTE_ADDR", 1);
    $display = COM_siteHeader();
    $display .= COM_startBlock($LANG_SUBSCR['access_denied']);
    $display .= $LANG_SUBSCR['access_denied_msg'];
    $display .= COM_endBlock();
    $display .= COM_siteFooter(true);
    echo $display;
    exit;
}

$action = '';
$actionval = '';
$expected = array(
    'edit', 'cancel', 
    'saveproduct', 'deleteproduct',
    'savesubscription', 'deletesubscription',
    'renewbutton_x', 'cancelbutton_x',
    // Views
    'editproduct', 'products', 'subscriptions',
    'editsubscrip',
    'mode'
);
foreach($expected as $provided) {
    if (isset($_POST[$provided])) {
        $action = $provided;
        $actionval = $_POST[$provided];
        break;
    } elseif (isset($_GET[$provided])) {
    	$action = $provided;
        $actionval = $_GET[$provided];
        break;
    }
}
if ($action == 'mode') {
    // Catch the old "mode=xxx" format
    $action = $actionval;
}


// Get the mode and page name, if any
//$view = isset($_REQUEST['view']) ? 
//                COM_applyFilter($_REQUEST['view']) : $action;

// Get the product and subscription IDs, if any
$item_id = isset($_REQUEST['item_id']) ? 
        COM_sanitizeId($_REQUEST['item_id']) : '';
$sub_id = isset($_REQUEST['sub_id']) ? (int)$_REQUEST['sub_id'] : 0;

$content = '';      // initialize variable for page content

switch ($action) {
case 'saveproduct':
    USES_subscription_class_product();
    $S = new SubscriptionProduct($item_id);
    $status = $S->Save($_POST);
    if ($status) {
        $view = 'products';
    } else {
        $content .= SUBSCR_errorMessage($S->PrintErrors());
        $view = 'editproduct';
    }
    break;

case 'deleteproduct':
    USES_subscription_class_product();
    $S = new SubscriptionProduct($item_id);
    $S->Delete();
    $view = 'products';
    break;

case 'savesubscription':
    USES_subscription_class_subscription();
    $item_id = isset($_POST['item_id']) ? $_POST['item_id'] : '';
    $S = new Subscription($item_id);
    if ($S->Save($_POST)) {
        $item_id = $S->item_id;
        $view = 'subscriptions';
    } else {
        $content .= SUBSCR_errorMessage($S->PrintErrors());
        $view = 'editsubscrip';
    }
    break;

case 'deletesubscription':
    USES_subscription_class_subscription();
    $S = new Subscription($_POST['id']);
    $S->Delete();
    $view = 'subscriptions';
    break;

case 'cancelbutton_x':
//case 'delMultiSub':
    USES_subscription_class_subscription();
    if (isset($_POST['delitem']) && is_array($_POST['delitem'])) {
        foreach ($_POST['delitem'] as $item) {
            Subscription::Cancel($item);
        }
    }
    echo COM_refresh(SUBSCR_ADMIN_URL.'/index.php?products=x');
    break;

case 'renewbutton_x':
    if (isset($_POST['delitem']) && is_array($_POST['delitem']) && 
            !empty($_POST['delitem'])) {
        USES_subscription_class_subscription();
        $S = new Subscription();
        foreach ($_POST['delitem'] as $item) {
            $S->Read($item);
            $S->Add($S->uid, $S->item_id);
        }
    }
    echo COM_refresh(SUBSCR_ADMIN_URL .
            '/index.php?subscriptions=' . $S->item_id);
    break;

default:
    $view = $action;
    break;
}

// Display the correct page content
switch ($view) {
case 'editproduct':
    USES_subscription_class_product();
    $P = new SubscriptionProduct($item_id);
    if (isset($_POST['name'])) {
        // Pick a field.  If it exists, then this is probably a rejected save
        $P->SetVars($_POST);
    }
    $content .= SUBSCR_adminMenu($view);
    $content .= $P->Edit();
    break;

case 'subscriptions':
    //if ($actionval != 'x') $item_id = $actionval;
    $item_id = isset($_REQUEST['item_id']) ? $_REQUEST['item_id'] : '';
    $content .= SUBSCR_subscriptionList($item_id);
    break;

case 'editsubscrip':
    USES_subscription_class_subscription();
    $sub_id = isset($_GET['sub_id']) ? $_GET['sub_id'] : '';
    $S = new Subscription($sub_id);
    $content .= SUBSCR_adminMenu($view);
    if ($actionval == 0 && isset($_POST['uid'])) {
        // Pick a field.  If it exists, then this is probably a rejected save
        $S->SetVars($_POST);
    }
    $content .= $S->Edit();
    break;
    
case 'products':
default:
    $content .= SUBSCR_productAdminList();
    break;
}

$display = COM_siteHeader();
$display .= $content;
$display .= COM_siteFooter();

echo $display;

?>
