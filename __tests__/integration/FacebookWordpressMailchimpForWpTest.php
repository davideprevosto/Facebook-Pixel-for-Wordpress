<?php
/*
 * Copyright (C) 2017-present, Facebook, Inc.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the License.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

namespace FacebookPixelPlugin\Tests\Integration;

use FacebookPixelPlugin\Integration\FacebookWordpressMailchimpForWp;
use FacebookPixelPlugin\Tests\FacebookWordpressTestBase;
use FacebookPixelPlugin\Core\FacebookServerSideEvent;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * All tests in this test class should be run in seperate PHP process to
 * make sure tests are isolated.
 * Stop preserving global state from the parent process.
 */
final class FacebookWordpressMailchimpForWpTest extends FacebookWordpressTestBase {
  public function testInjectPixelCode() {
    $mocked_base = \Mockery::mock(
      'alias:FacebookPixelPlugin\Integration\FacebookWordpressIntegrationBase');
    $mocked_base->shouldReceive('addPixelFireForHook')
      ->with(array(
        'hook_name' => 'mc4wp_form_subscribed',
        'classname' => FacebookWordpressMailchimpForWp::class,
        'inject_function' => 'injectLeadEvent'))
      ->once();
    FacebookWordpressMailchimpForWp::injectPixelCode();
  }

  public function testInjectLeadEventWithoutAdmin() {
    self::mockIsAdmin(false);
    self::mockUseS2S(true);

    $_POST['EMAIL'] = 'pika.chu@s2s.com';
    $_POST['FNAME'] = 'Pika';
    $_POST['LNAME'] = 'Chu';

    FacebookWordpressMailchimpForWp::injectLeadEvent();
    $this->expectOutputRegex(
      '/mailchimp-for-wp[\s\S]+End Facebook Pixel Event Code/');

    $tracked_events =
      FacebookServerSideEvent::getInstance()->getTrackedEvents();

    $this->assertCount(1, $tracked_events);

    $event = $tracked_events[0];
    $this->assertEquals('Lead', $event->getEventName());
    $this->assertNotNull($event->getEventTime());
    $this->assertEquals('pika.chu@s2s.com', $event->getUserData()->getEmail());
    $this->assertEquals('Pika', $event->getUserData()->getFirstName());
    $this->assertEquals('Chu', $event->getUserData()->getLastName());
  }

  public function testInjectLeadEventWithAdmin() {
    self::mockIsAdmin(true);
    FacebookWordpressMailchimpForWp::injectLeadEvent();
    $this->expectOutputString("");
  }
}
