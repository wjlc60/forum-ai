<?PHP // $Id$

//  Edit and save a new post to a discussion


    require("../../config.php");
    require("lib.php");

    if (isguest()) {
        error(get_string("noguestpost", "forum"), $HTTP_REFERER);
    }

    if (match_referer() && isset($HTTP_POST_VARS)) {    // form submitted
        $post = (object)$HTTP_POST_VARS;

        $post->subject = strip_tags($post->subject);  // Strip all tags
        $post->message = clean_text($post->message, $post->format);   // Clean up any bad tags

        $post->attachment = $HTTP_POST_FILES["attachment"];

        if (!$post->subject and !$post->message) {
            error(get_string("emptymessage", "forum"));
        }

        require_login();

        if ($post->edit) {           // Updating a post
            $post->id = $post->edit;
            if (forum_update_post($post)) {
                add_to_log($post->course, "forum", "update post", "discuss.php?d=$post->discussion&parent=$post->id", "$post->id");
                redirect(forum_go_back_to("discuss.php?d=$post->discussion"), get_string("postupdated", "forum"), 1);
            } else {
                error(get_string("couldnotupdate", "forum")); 
            }

        } else if ($post->discussion) { // Adding a new post to an existing discussion
            if ($post->id = forum_add_new_post($post)) {
                if ( ! forum_is_subscribed($USER->id, $post->forum) ) {
                    forum_subscribe($USER->id, $post->forum);
                }

                add_to_log($post->course, "forum", "add post", "discuss.php?d=$post->discussion&parent=$post->id", "$post->id");
                redirect(forum_go_back_to("discuss.php?d=$post->discussion"), 
                         get_string("postadded", "forum", format_time($CFG->maxeditingtime)), 2);
            } else {
                error(get_string("couldnotadd", "forum")); 
            }
        } else {                     // Adding a new discussion
            $discussion = $post;
            $discussion->name  = $post->subject;
            $discussion->intro = $post->message;
            if ($discussion->id = forum_add_discussion($discussion)) {
                if ( ! forum_is_subscribed($USER->id, $post->forum) ) {
                    forum_subscribe($USER->id, $post->forum);
                }
                add_to_log($post->course, "forum", "add discussion", "discuss.php?d=$discussion->id", "$discussion->id");
                redirect(forum_go_back_to("view.php?f=$post->forum"), 
                         get_string("postadded", "forum", format_time($CFG->maxeditingtime)), 3);
            } else {
                error(get_string("couldnotadd", "forum")); 
            }
        }
        die;
    }

    if ($usehtmleditor = can_use_richtext_editor()) {
        $defaultformat = FORMAT_HTML;
        $onsubmit = "onsubmit=\"copyrichtext(theform.message);\"";
    } else {
        $defaultformat = FORMAT_MOODLE;
        $onsubmit = "";
    }


    if (isset($forum)) {      // User is starting a new discussion in a forum

        $SESSION->fromurl = $HTTP_REFERER;
        save_session("SESSION");

        if (! $forum = get_record("forum", "id", $forum)) {
            error("The forum number was incorrect ($forum)");
        }
        if (! $course = get_record("course", "id", $forum->course)) {
            error("The course number was incorrect ($forum)");
        }

        if (! forum_user_can_post_discussion($forum)) {
            error("Sorry, but you can not post a new discussion in this forum.");
        }

        // Load up the $post variable.

        $post->course = $course->id;
        $post->forum  = $forum->id;
        $post->discussion = 0;           // ie discussion # not defined yet
        $post->parent = 0;
        $post->subject = "";
        $post->userid = $USER->id;
        $post->message = "";
        $post->format = $defaultformat;

        forum_set_return();

    } else if (isset($reply)) {      // User is writing a new reply

        if (! $parent = forum_get_post_full($reply)) {
            error("Parent post ID was incorrect ($reply)");
        }
        if (! $discussion = get_record("forum_discussions", "id", $parent->discussion)) {
            error("This post is not part of a discussion! ($reply)");
        }
        if (! $forum = get_record("forum", "id", $discussion->forum)) {
            error("The forum number was incorrect ($discussion->forum)");
        }
        if (! $course = get_record("course", "id", $discussion->course)) {
            error("The course number was incorrect ($discussion->course)");
        }

        if (! forum_user_can_post($forum)) {
            error("Sorry, but you can not post in this forum.");
        }
        // Load up the $post variable.

        $post->course  = $course->id;
        $post->forum  = $forum->id;
        $post->discussion  = $parent->discussion;
        $post->parent = $parent->id;
        $post->subject = $parent->subject;
        $post->userid = $USER->id;
        $post->message = "";
        $post->format = $defaultformat;

        $strre = get_string("re", "forum");
        if (!(substr($post->subject, 0, 3) == $strre)) {
            $post->subject = "$strre $post->subject";
        }

        forum_set_return();

    } else if (isset($edit)) {  // User is editing their own post

        if (! $post = forum_get_post_full($edit)) {
            error("Post ID was incorrect");
        }
        if ($post->userid <> $USER->id) {
            error("You can't edit other people's posts!");
        }
        if ((time() - $post->created) > $CFG->maxeditingtime) {
            error( get_string("maxtimehaspassed", "forum", format_time($CFG->maxeditingtime)) );
        }
        if ($post->parent) {
            if (! $parent = forum_get_post_full($post->parent)) {
                error("Parent post ID was incorrect ($post->parent)");
            }
        }
        if (! $discussion = get_record("forum_discussions", "id", $post->discussion)) {
            error("This post is not part of a discussion! ($reply)");
        }
        if (! $forum = get_record("forum", "id", $discussion->forum)) {
            error("The forum number was incorrect ($discussion->forum)");
        }
        if (! $course = get_record("course", "id", $discussion->course)) {
            error("The course number was incorrect ($discussion->course)");
        }

        // Load up the $post variable.

        $post->edit = $edit;

        $post->course  = $course->id;
        $post->forum  = $forum->id;

        forum_set_return();


    } else if (isset($delete)) {  // User is deleting a post

        if (! $post = forum_get_post_full($delete)) {
            error("Post ID was incorrect");
        }
        if (! $discussion = get_record("forum_discussions", "id", $post->discussion)) {
            error("This post is not part of a discussion!");
        }
        if (! $forum = get_record("forum", "id", $discussion->forum)) {
            error("The forum number was incorrect ($discussion->forum)");
        }
        if (($post->userid <> $USER->id) and !isteacher($forum->course)) {
            error("You can't delete other people's posts!");
        }

        if (isset($confirm)) {    // User has confirmed the delete

            if ($post->totalscore) {
                notice(get_string("couldnotdeleteratings", "forum"), 
                        forum_go_back_to("discuss.php?d=$post->discussion"));

            } else if (record_exists("forum_posts", "parent", $delete)) {
                error(get_string("couldnotdeletereplies", "forum"),
                        forum_go_back_to("discuss.php?id=$post->discussion"));

            } else {
                if (! $post->parent) {  // post is a discussion topic as well, so delete discussion
                    if ($forum->type == "single") {
                        notice("Sorry, but you are not allowed to delete that discussion!", 
                                forum_go_back_to("discuss.php?d=$post->discussion"));
                    }
                    forum_delete_discussion($discussion);

                    add_to_log($discussion->course, "forum", "delete discussion", "view.php?id=$discussion->forum", "$post->id");
                    redirect("view.php?f=$discussion->forum", 
                             get_string("deleteddiscussion", "forum"), 1);

                } else if (forum_delete_post($post)) {

                    add_to_log($discussion->course, "forum", "delete post", "discuss.php?d=$post->discussion", "$post->id");
                    redirect(forum_go_back_to("discuss.php?d=$post->discussion"), 
                             get_string("deletedpost", "forum"), 1);
                } else {
                    error("An error occurred while deleting record $post->id");
                }
            }


        } else { // User just asked to delete something

            forum_set_return();

            print_header();
            notice_yesno(get_string("deletesure", "forum"), 
                         "post.php?delete=$delete&confirm=$delete",
                         $HTTP_REFERER);
                         
            echo "<CENTER><HR>";
            forum_print_post($post, $forum->course, $ownpost=false, $reply=false, $link=false);
        }

        die;


    } else {
        error("No operation specified");

    }


    // To get here they need to edit a post, and the $post 
    // variable will be loaded with all the particulars,
    // so bring up the form.

    // $course, $forum are defined.  $discussion is for edit and reply only.

    require_login($course->id);


    if ($post->discussion) {
        if (! $toppost = get_record("forum_posts", "discussion", $post->discussion, "parent", 0)) {
            error("Could not find top parent of post $post->id");
        }
    } else {
        $toppost->subject = get_string("yournewtopic", "forum");
    }

    if ($post->subject) {
        $formstart = "form.message";
    } else {
        $formstart = "form.subject";
    }

    if ($post->parent) {
        $navtail = "<A HREF=\"discuss.php?d=$discussion->id\">$toppost->subject</A> -> ".get_string("editing", "forum");
    } else {
        $navtail = "$toppost->subject";
    }

    if (empty($post->edit)) {
        $post->edit = "";
    }

    $strforums = get_string("modulenameplural", "forum");


    $navmiddle = "<A HREF=\"../forum/index.php?id=$course->id\">$strforums</A> -> <A HREF=\"view.php?f=$forum->id\">$forum->name</A>";

    $cm = get_coursemodule_from_instance("forum", $forum->id, $course->id);

    if (empty($discussion->name)) {
        $discussion->name = $forum->name;
    }

    if ($course->category) {
        print_header("$course->shortname: $discussion->name: $toppost->subject", "$course->fullname",
                 "<A HREF=../../course/view.php?id=$course->id>$course->shortname</A> ->
                  $navmiddle -> $navtail", "$formstart", "", true, "", navmenu($course, $cm));
    } else {
        print_header("$course->shortname: $discussion->name: $toppost->subject", "$course->fullname",
                 "$navmiddle -> $navtail", "$formstart", "", true, "", navmenu($course, $cm));

    }

    echo "<CENTER>";
    if (isset($parent)) {
        forum_print_post($parent, $course->id, $ownpost=false, $reply=false, $link=false);
        echo "<H2>".get_string("yourreply", "forum").":</H2>";
    } else {
        echo "<H2>".get_string("yournewtopic", "forum")."</H2>";
    }
    echo "</CENTER>";

    print_simple_box_start("center", "", "$THEME->cellheading");
    require("post.html");
    print_simple_box_end();

    print_footer($course);


?>
