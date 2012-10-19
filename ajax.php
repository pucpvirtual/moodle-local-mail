<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/local/mail/lib.php');

$action   = optional_param('action', false, PARAM_ALPHA);
$type     = optional_param('type', false, PARAM_ALPHA);
$msgs     = optional_param('msgs', '', PARAM_SEQUENCE);
$itemid   = optional_param('itemid', 0, PARAM_INT);
$courseid = optional_param('courseid', $SITE->id, PARAM_INT);
$offset   = optional_param('offset', 0, PARAM_INT);
$labelid  = optional_param('labelid', 0, PARAM_INT);
$perpage  = optional_param('perpage', 0, PARAM_INT);


require_login($courseid);

$valid_actions = array(
	'starred',
	'nostarred',
	'delete',
	'markasread',
	'markasunread',
	'prevpage',
	'nextpage',
	'perpage'
);

if ($action and in_array($action, $valid_actions) and !empty($USER->id)) {

	require_sesskey();
	$params = array();

	if (empty($msgs) and ($action != 'prevpage' and $action != 'nextpage'and $action != 'perpage')){
		echo json_encode('error');
		die;
	}
	if ($action != 'prevpage' and $action != 'nextpage' and $action != 'perpage') {
		$msgsids = explode(',', $msgs);
		$messages = local_mail_message::fetch_many($msgsids);
	}
	$mailpagesize = get_user_preferences('local_mail_mailsperpage', MAIL_PAGESIZE, $USER->id);
	if ($action === 'starred') {
		$func = 'setstarred';
		array_push($params, $messages);
		array_push($params, true);
	} elseif ($action === 'nostarred') {
		$func = 'setstarred';
		array_push($params, $messages);
		array_push($params, false);
	} elseif ($action === 'markasread') {
		$func = 'setread';
		array_push($params, $messages);
		array_push($params, true);
	}elseif ($action === 'markasunread') {
		$func = 'setread';
		array_push($params, $messages);
		array_push($params, false);
	}elseif ($action === 'delete') {
		$func = 'setdelete';
		array_push($params, $messages);
		array_push($params, ($type != 'trash'));
		array_push($params, $itemid);
		array_push($params, $courseid);
		array_push($params, $labelid);
		array_push($params, $type);
		array_push($params, $offset);
		array_push($params, $mailpagesize);
	} elseif ($action === 'prevpage') {
		$func = 'setprevpage';
		array_push($params, $itemid);
		array_push($params, $courseid);
		array_push($params, $labelid);
		array_push($params, $type);
		array_push($params, $offset);
		array_push($params, $mailpagesize);
	} elseif ($action === 'nextpage') {
		$func = 'setnextpage';
		array_push($params, $itemid);
		array_push($params, $courseid);
		array_push($params, $labelid);
		array_push($params, $type);
		array_push($params, $offset);
		array_push($params, $mailpagesize);
	}  elseif ($action === 'perpage') {
		$func = 'setperpage';
		array_push($params, $itemid);
		array_push($params, $courseid);
		array_push($params, $labelid);
		array_push($params, $type);
		array_push($params, $offset);
		array_push($params, $perpage);
	} else {
		echo json_encode('error');
		die;
	}
	echo json_encode(call_user_func_array($func, $params));
} else {
	echo json_encode("error");
}

function setstarred ($messages, $bool) {
	global $USER;

	foreach ($messages as $message) {
        if ($message->viewable($USER->id)) {
            $message->set_starred($USER->id, $bool);
        }
    }
}

function setread ($messages, $bool) {
	global $USER;

	foreach ($messages as $message) {
        if ($message->viewable($USER->id)) {
        	$message->set_unread($USER->id, !$bool);
        }
    }
}

function setdelete ($messages, $bool, $itemid, $courseid, $labelid, $type, $offset, $mailpagesize) {
	global $PAGE, $USER;

	$totalcount = local_mail_message::count_index($USER->id, $type, $itemid);
    foreach ($messages as $message) {
        if ($message->editable($USER->id)) {
            $message->discard();
        } elseif ($message->viewable($USER->id)) {
            $message->set_deleted($USER->id, $bool);
        }
        $totalcount -= 1;
    }
    if ($offset > $totalcount-1) {
        $offset = min(0, $offset-$mailpagesize);
    }
    return print_messages($itemid, $courseid, $labelid, $type, $offset, $mailpagesize, $totalcount);
}

function setprevpage($itemid, $courseid, $labelid, $type, $offset, $mailpagesize){
	global $USER;

	$totalcount = local_mail_message::count_index($USER->id, $type, $itemid);
    $offset = max(0, $offset - $mailpagesize);
    return print_messages($itemid, $courseid, $labelid, $type, $offset, $mailpagesize, $totalcount);
}

function setnextpage($itemid, $courseid, $labelid, $type, $offset, $mailpagesize){
	global $USER;

	$totalcount = local_mail_message::count_index($USER->id, $type, $itemid);
    $offset = $offset + $mailpagesize;
    return print_messages($itemid, $courseid, $labelid, $type, $offset, $mailpagesize, $totalcount);
}

function setperpage($itemid, $courseid, $labelid, $type, $offset, $mailpagesize){
	global $USER;

	$totalcount = local_mail_message::count_index($USER->id, $type, $itemid);
	if (in_array($mailpagesize, array (5, 10, 20, 50, 100))) {
        set_user_preference('local_mail_mailsperpage', $mailpagesize);
        return print_messages($itemid, $courseid, $labelid, $type, $offset, $mailpagesize, $totalcount);
    } else {
    	return 'error';
    }
    
}

function print_messages($itemid, $courseid, $labelid, $type, $offset, $mailpagesize, $totalcount) {
	global $PAGE, $USER;
	$url = new moodle_url('/local/mail/view.php', array('t' => $type));
    $PAGE->set_url($url);
    $mailoutput = $PAGE->get_renderer('local_mail');
    $messages = local_mail_message::fetch_index($USER->id, $type, $itemid, $offset, $mailpagesize);
    $content = $mailoutput->view(array(
        'type' => $type,
        'labelid' => $labelid,
        'itemid' => $itemid,
        'courseid' => $courseid,
        'userid' => $USER->id,
        'messages' => $messages,
        'totalcount' => $totalcount,
        'offset' => $offset,
        'ajax' => true
    ));
    return preg_replace('/^\<div\>|\<\/div\>$/', '', $content);
}