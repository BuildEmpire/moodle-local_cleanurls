<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests for cleaner and uncleaner
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_cleanurls\clean_moodle_url;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/cleanurls_testcase.php');

/**
 * Tests for cleaner and uncleaner
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_cleaner_uncleaner_test extends local_cleanurls_testcase {
    public function test_it_always_cleans_the_test_url() {
        // Test with cleaning on.
        set_config('cleaningon', true, 'local_cleanurls');
        $this->assert_clean_unclean('/local/cleanurls/tests/foo.php',
                                    'http://www.example.com/moodle/local/cleanurls/tests/bar');

        // Test with cleaning off.
        set_config('cleaningon', false, 'local_cleanurls');
        $this->assert_clean_unclean('/local/cleanurls/tests/foo.php',
                                    'http://www.example.com/moodle/local/cleanurls/tests/bar');
    }

    public function test_it_cannot_clean_if_destination_is_a_directory() {
        global $CFG;
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(['fullname'  => 'How to use ajax',
                                                            'shortname' => 'ajax',
                                                            'visible'   => 1,
                                                            'category'  => $category->id]);

        self::assertTrue(is_dir($CFG->dirroot.'/course/ajax'), 'Directory required for the test.');

        $url = 'http://www.example.com/moodle/course/view.php?id='.urlencode($course->id);
        $this->assert_clean_unclean($url, $url);
    }

    public function test_it_cannot_clean_if_destination_is_a_php_file() {
        global $CFG;
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(['fullname'  => 'How to enrol to a course',
                                                            'shortname' => 'enrol',
                                                            'visible'   => 1,
                                                            'category'  => $category->id]);

        self::assertTrue(is_file($CFG->dirroot.'/course/enrol.php'), 'File required for the test.');
        $url = 'http://www.example.com/moodle/course/view.php?id='.urlencode($course->id);
        $this->assert_clean_unclean($url, $url);
    }

    public function test_it_cleans_category_urls() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);

        $this->assert_clean_unclean('http://www.example.com/moodle/course/index.php?categoryid='.$category->id,
                                    'http://www.example.com/moodle/category/category-'.$category->id);
    }

    public function test_it_cleans_course_module_view_urls() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(['fullname'  => 'course long name',
                                                            'shortname' => 'shortname',
                                                            'visible'   => 1,
                                                            'category'  => $category->id]);
        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id,
                                                                    'name'   => 'A Test Forum']);

        $url = 'http://www.example.com/moodle/mod/forum/view.php?id='.$forum->cmid;
        $expected = 'http://www.example.com/moodle/course/shortname/forum/'.$forum->cmid.'-a-test-forum';
        $this->assert_clean_unclean($url, $expected);
    }

    public function test_it_cleans_course_modules_urls() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(['fullname'  => 'course long name',
                                                            'shortname' => 'shortname',
                                                            'visible'   => 1,
                                                            'category'  => $category->id]);

        $this->assert_clean_unclean('http://www.example.com/moodle/mod/forum/index.php?id='.$course->id,
                                    'http://www.example.com/moodle/course/shortname/forum');
    }

    public function test_it_cleans_course_urls_by_id() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(['fullname'  => 'full name of the course',
                                                            'shortname' => 'shortname',
                                                            'visible'   => 1,
                                                            'category'  => $category->id]);

        $this->assert_clean_unclean('http://www.example.com/moodle/course/view.php?id='.$course->id,
                                    'http://www.example.com/moodle/course/shortname',
                                    'http://www.example.com/moodle/course/view.php?name=shortname');
    }

    public function test_it_cleans_course_with_hash_in_shortname() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(['fullname'  => 'full name of the course #3',
                                                            'shortname' => 'short#name',
                                                            'visible'   => 1,
                                                            'category'  => $category->id]);

        $this->assert_clean_unclean('http://www.example.com/moodle/course/view.php?id='.$course->id,
                                    'http://www.example.com/moodle/course/short%23name',
                                    'http://www.example.com/moodle/course/view.php?name=short%2523name');
    }

    public function test_it_cleans_course_urls_by_name() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $this->getDataGenerator()->create_course(['fullname'  => 'full name',
                                                  'shortname' => 'theshortname',
                                                  'visible'   => 1,
                                                  'category'  => $category->id]);

        $this->assert_clean_unclean('http://www.example.com/moodle/course/view.php?name=theshortname',
                                    'http://www.example.com/moodle/course/theshortname');
    }

    public function test_it_cleans_course_users_urls() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(['fullname'  => 'a course name',
                                                            'shortname' => 'shortcoursename',
                                                            'visible'   => 1,
                                                            'category'  => $category->id]);

        $this->assert_clean_unclean('http://www.example.com/moodle/user/index.php?id='.$course->id,
                                    'http://www.example.com/moodle/course/shortcoursename/user');
    }

    public function test_it_cleans_subcategory_urls() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $subcategory = $this->getDataGenerator()->create_category(['name'   => 'subcategory',
                                                                   'parent' => $category->id]);

        $url = 'http://www.example.com/moodle/course/index.php?categoryid='.$subcategory->id;
        $expected = 'http://www.example.com/moodle/category/category-'.$category->id.'/subcategory-'.$subcategory->id;
        $this->assert_clean_unclean($url, $expected);
    }

    public function test_it_cleans_username_in_course() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(['fullname'  => 'a course',
                                                            'shortname' => 'mycourse',
                                                            'visible'   => 1,
                                                            'category'  => $category->id]);
        $user = $this->getDataGenerator()->create_user(['email'    => 'someone@example.com',
                                                        'username' => 'theusername']);

        $this->assert_clean_unclean(
            "http://www.example.com/moodle/user/view.php?course=1&id={$user->id}&course={$course->id}",
            'http://www.example.com/moodle/course/mycourse/user/theusername'
        );
    }

    public function test_it_cleans_username_in_forum_discussion() {
        $user = $this->getDataGenerator()->create_user(['email'    => 'someone@example.com',
                                                        'username' => 'theusername']);

        $this->assert_clean_unclean('http://www.example.com/moodle/mod/forum/user.php?mode=discussions&id='.$user->id,
                                    'http://www.example.com/moodle/user/theusername/discussions');
    }

    public function test_it_cleans_username_in_site_course() {
        $user = $this->getDataGenerator()->create_user(['email'    => 'someone@example.com',
                                                        'username' => 'theusername']);

        $this->assert_clean_unclean('http://www.example.com/moodle/user/view.php?course=1&id='.$user->id,
                                    'http://www.example.com/moodle/user/theusername?course=1');
    }

    public function test_it_cleans_username_urls() {
        $user = $this->getDataGenerator()->create_user(['email'    => 'someone@example.com',
                                                        'username' => 'theusername']);

        $this->assert_clean_unclean('http://www.example.com/moodle/user/profile.php?id='.$user->id,
                                    'http://www.example.com/moodle/user/theusername');
    }

    public function test_it_does_not_clean_draftfile_urls() {
        $url = 'http://moodle.test/moodle/draftfile.php/5/user/draft/949704188/daniel-roperto.jpg';
        $this->assert_clean_unclean($url, $url);
    }

    public function test_it_does_not_clean_help_urls() {
        $url = 'http://www.example.com/moodle/help.php?blah=foo';
        $this->assert_clean_unclean($url, $url);
    }

    public function test_it_does_not_clean_lib_urls() {
        $url = 'http://www.example.com/moodle/lib/whatever.php';
        $this->assert_clean_unclean($url, $url);
    }

    public function test_it_does_not_clean_plugin_urls() {
        $url = 'http://www.example.com/moodle/pluginfile.php/12345/foo/bar';
        $this->assert_clean_unclean($url, $url);
    }

    public function test_it_does_not_clean_pluginfile_urls() {
        $url = 'http://www.example.com/moodle/pluginfile.php/12345/foo/bar';
        $this->assert_clean_unclean($url, $url);
    }

    public function test_it_does_not_clean_theme_urls() {
        $url = 'http://www.example.com/moodle/theme/whatever.php';
        $this->assert_clean_unclean($url, $url);
    }

    public function test_it_does_not_clean_username_in_forum_discussion_if_not_discussions_mode() {
        $user = $this->getDataGenerator()->create_user(['email'    => 'someone@example.com',
                                                        'username' => 'theusername']);

        $url = 'http://www.example.com/moodle/mod/forum/user.php?mode=somethingelse&id='.$user->id;
        $this->assert_clean_unclean($url, $url);
    }

    public function test_it_does_not_clean_usernames_if_config_disabled() {
        $user = $this->getDataGenerator()->create_user(['email'    => 'someone@example.com',
                                                        'username' => 'theusername']);

        set_config('cleanusernames', false, 'local_cleanurls');
        $url = 'http://www.example.com/moodle/user/profile.php?id='.$user->id;
        $this->assert_clean_unclean($url, $url);
    }

    public function test_it_returns_the_same_url_if_cleaning_is_off() {
        set_config('cleaningon', false, 'local_cleanurls');

        $url = 'http://www.example.com/moodle/cache/disabled-test.php';

        cache::make('local_cleanurls', 'outgoing')->set($url, 'http://www.example.com/moodle/disabledcachedurl');
        $this->assert_clean_unclean($url, $url); // Cleaning disabled, should not get cached version.
    }

    public function test_it_should_use_a_cache() {
        $url = 'http://www.example.com/moodle/cache/test.php';
        $cached = 'http://www.example.com/moodle/cachedurl.php';

        cache::make('local_cleanurls', 'outgoing')->set($url, $cached);
        $this->assert_clean_unclean($url, $cached, $cached);
    }
}