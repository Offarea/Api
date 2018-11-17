<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\v1\Comments;
use App\Models\v1\UsersMeta;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CommentController extends Controller
{
    public function get_comments(Request $request)
    {
        $comments = Comments::all()->where('user_id', '<>', '0')
        ->where('comment_post_ID', $request->product_id);
        $result = array();
        $counter = 0;

        foreach ($comments as $comment)
        {
            $result[$counter]['id'] = $comment->comment_ID;
            $userFullName = $this->getUserFullName($comment->user_id);
            if(!$userFullName)
                $userFullName = $comment->comment_author;
            $result[$counter]['user'] = $userFullName;
            $result[$counter]['content'] = $comment->comment_content;
            $result[$counter]['comment_date'] = $comment->comment_date;
            $counter++;
        }

        return json_encode(
            $result
        );
    }

    public function getUserFullName($user_id)
    {
        $first_name = UsersMeta::where('user_id', $user_id)
            ->where('meta_key', 'first_name')->first();

        $last_name = UsersMeta::where('user_id', $user_id)
            ->where('meta_key', 'last_name')->first();

        if($first_name and $last_name)
        {
            return $first_name->meta_value. ' ' .$last_name->meta_value;
        }

    }
}
