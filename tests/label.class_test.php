<?php

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/local/mail/tests/testcase.class.php');
require_once($CFG->dirroot.'/local/mail/label.class.php');

class local_mail_label_test extends local_mail_testcase {

    /* 1xx -> users
       4xx -> labels
       5xx -> maessages */

    function test_create() {
        $label = local_mail_label::create(201, 'name', 'red');

        $this->assertNotEquals(false, $label->id());
        $this->assertEquals(201, $label->userid());
        $this->assertEquals('name', $label->name());
        $this->assertEquals('red', $label->color());
        $this->assertEquals($label, local_mail_label::fetch($label->id()));
    }

    function test_delete() {
        $label = local_mail_label::create(201, 'label', 'red');
        $other = local_mail_label::create(201, 'other', 'green');
        $this->loadRecords('local_mail_message_labels', array(
            array('messageid', 'labelid'),
            array( 501,       $label->id()),
            array( 502,       $label->id()),
            array( 501,       $other->id()),
        ));
        
        $label->delete();

        $this->assertNotRecords('labels', array('id' => $label->id()));
        $this->assertNotRecords('message_labels', array('labelid' => $label->id()));
        $this->assertRecords('labels');
        $this->assertRecords('message_labels');
    }

    function test_fetch() {
        $this->loadRecords('local_mail_labels', array(
            array('id', 'userid', 'name',   'color'),
            array( 401,  201,     'label1', 'red'),
            array( 402,  201,     'label2', ''),
        ));

        $result = local_mail_label::fetch(401);

        $this->assertInstanceOf('local_mail_label', $result);
        $this->assertEquals(401, $result->id());
        $this->assertEquals(201, $result->userid());
        $this->assertEquals('label1', $result->name());
        $this->assertEquals('red', $result->color());
    }

    function test_fetch_user() {
        $label1 = local_mail_label::create(201, 'label1', 'red');
        $label2 = local_mail_label::create(201, 'label2', 'green');
        $label3 = local_mail_label::create(202, 'label3', 'blue');

        $result = local_mail_label::fetch_user(201);

        $this->assertCount(2, $result);
        $this->assertContains($label1, $result);
        $this->assertContains($label2, $result);
    }

    function test_fetch_many() {
        $label1 = local_mail_label::create(201, 'label1', 'red');
        $label2 = local_mail_label::create(202, 'label2', 'green');
        $label3 = local_mail_label::create(203, 'label3', 'blue');

        $result = local_mail_label::fetch_many(array($label1->id(), $label2->id()));

        $this->assertCount(2, $result);
        $this->assertContains($label1, $result);
        $this->assertContains($label2, $result);
    }

    function test_save() {
        $label = local_mail_label::create(201, 'name', 'red');

        $label->save('changed', 'green');

        $this->assertEquals('changed', $label->name());
        $this->assertEquals('green', $label->color());
        $this->assertEquals($label, local_mail_label::fetch($label->id()));
    }
}