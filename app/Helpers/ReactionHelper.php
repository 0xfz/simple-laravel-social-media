<?php
namespace App\Helpers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Reaction;
use App\Models\Follower;
use App\Models\User;

class ReactionHelper{
    public $id;
    public function __construct($id){
        $this->id = $id;
    }
    public function getTotalPostReactions(){
        $count = Reaction::where("post_id", $this->id)->get()->count();
        return $count;
    }        
    public function getTotalCommentReactions(){
        $count = Reaction::where("comment_id", $this->id)->get()->count();
        return $count;
    }    
    public function getTopCommentReactions(){
        $isExist = Reaction::where("post_id", $this->id)->first();
        if($isExist){
            $topReactions = Reaction::where("post_id", $this->id)
                            ->select('reaction_type', DB::raw('count(*) as total'))
                            ->groupBy("reaction_type")
                            ->orderBy('total', 'DESC')
                            ->get();
            $topReactions = $topReactions->reaction_type;
            if(count($topReactions) > 3){
                $topReactions = array_slice($topReactions, 0, 2);
            }
            return $topReactions;
        }
        return [];
    }    
    public function getTopPostReactions(){
        $isExist = Reaction::where("post_id", $this->id)->first();
        if($isExist){
            $topReactions = Reaction::where("post_id", $this->id)
                            ->select('reaction_type', DB::raw('count(*) as total'))
                            ->groupBy("reaction_type")
                            ->orderBy('total', 'DESC')
                            ->get()->toArray();
            $arrayReactionsType = array();
            foreach($topReactions as $reaction){
                $arrayReactionsType[] = $reaction["reaction_type"];
            }
            $topReactions = array_values($arrayReactionsType);
            if(count($topReactions) > 3){
                $topReactions = array_slice($topReactions, 0, 2);
            }
            return $topReactions;
        }
        return [];

    }
    public function isReactedByViewer(){
        $id = (Auth::check()) ? Auth::id() : 0;
        $result = Reaction::where("post_id", $this->id)->where("user_id", $id)->first();
        if($result) return [
            "status" => true, 
            "reaction_type" => $result->reaction_type
        ];
        return [
            "status" => false
        ];
    }
    public function isReactedByMutual(){
        $current_id  = (Auth::check()) ? Auth::id() : 0;
        $follow = new Follower;
        $users_raw_data = $follow::where("user_id", $this->id)->get(["following_id"]);
        if($users_raw_data){
            $user_ids = [];
            foreach($users_raw_data as $user){
                array_push($user_ids, $user->following_id);
            }
            $isMutualReacted = Reaction::where("post_id", $this->id)->whereIn("user_id", $user_ids)->with("reactor")->get(["user_id"]);
            if($isMutualReacted){
                $reacted_mutual_users = [];
                foreach($isMutualReacted as $reaction){
                    array_push($reacted_mutual_users, $reaction->reactor->username);
                }
                return $reacted_mutual_users;
            }
        }
    }
}