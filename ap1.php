<?php

namespace Latchel;

use Controller;
use Post;
use User;
use Comment;

class HomeController extends Controller
{
    public function index() {
        $posts = $this->getPosts('home');
        
        return view('template', ['posts' => $posts]);
    }
    
    public static function getPosts($slug) {
        $posts = Post::where('slug', '=', $slug)->get();
        
        // I didn't test the code, but I'm confident this'll decrease the time complexity from O(n * n^2) to O(n)
        // I'm not sure I'm traversing array_walk() &$value correctly since I'm not  using xDebug
        foreach($posts as &$post) {
            $post->user = User::find($post->user_id);
            $post->comments = Comment::where('post_id', '=', $post->post_id)->get();
            
            array_walk($post->comments, function(&$value, $key) {
                $value->user = User::find($value->user_id);
            });
        }
        
        foreach($posts as &$post) {
            
            
            foreach($post->comments as &$comment) {
                $comment->user = User::find($comment->user_id);
            }
        }
        
        return $posts;
    }
    
}




//