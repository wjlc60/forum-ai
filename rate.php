<?PHP // $Id$

//  Collect ratings, store them, then return to where we came from


    require_once("../../config.php");
    require_once("lib.php");

    if (isguest()) {
        error("Guests are not allowed to rate posts.", $_SERVER["HTTP_REFERER"]);
    }

    require_variable($id);  // The course these ratings are part of

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID was incorrect");
    }

    require_login($course->id);

    if ($data = data_submitted("$CFG->wwwroot/mod/forum/discuss.php")) {    // form submitted

        foreach ($data as $post => $rating) {
            if ($post == "id") {
                continue;
            }
            if ($oldrating = get_record("forum_ratings", "userid", $USER->id, "post", $post)) {
                if ($rating != $oldrating->rating) {
                    $oldrating->rating = $rating;
                    $oldrating->time = time();
                    if (! update_record("forum_ratings", $oldrating)) {
                        error("Could not update an old rating ($post = $rating)");
                    }
                }
            } else if ($rating) {
                unset($newrating);
                $newrating->userid = $USER->id;
                $newrating->time = time();
                $newrating->post = $post;
                $newrating->rating = $rating;

                if (! insert_record("forum_ratings", $newrating)) {
                    error("Could not insert a new rating ($post = $rating)");
                }
            }
        }
        redirect($_SERVER["HTTP_REFERER"], get_string("ratingssaved", "forum"));

    } else {
        error("This page was not accessed correctly");
    }

?>
